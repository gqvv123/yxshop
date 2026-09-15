<?php

namespace app\utility;

use Illuminate\Support\Carbon;

class DateHelper
{
    public static function diffForHumans($date): string
    {
        return Carbon::parse($date)->diffForHumans();
    }

    public static function getDateTime($date, string $format = 'Y-m-d h:i A'): string
    {
        return Carbon::parse($date)->translatedFormat($format);
    }

    public static function daysInYear(?int $year = null): int
    {
        $year = $year ?? date('Y');
        return (int)date('L', strtotime("{$year}-01-01")) ? 366 : 365;
    }

    public static function daysInMonth(?int $month = null, ?int $year = null): int
    {
        $month = $month ?? date('m');
        $year = $year ?? date('Y');
        return (int)cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }

    public static function sortByMonth(array $data): array
    {
        uksort($data, function ($a, $b) {
            $monthA = date_parse($a);
            $monthB = date_parse($b);
            return ($monthA['month'] ?? 0) <=> ($monthB['month'] ?? 0);
        });
        return $data;
    }

    public static function distanceInWords(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . '秒';
        }
        if ($seconds < 3600) {
            return floor($seconds / 60) . '分钟';
        }
        if ($seconds < 86400) {
            return floor($seconds / 3600) . '小时';
        }
        if ($seconds < 2592000) {
            return floor($seconds / 86400) . '天';
        }
        if ($seconds < 31536000) {
            return floor($seconds / 2592000) . '个月';
        }
        return floor($seconds / 31536000) . '年';
    }

    public static function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2, string $unit = 'km'): float
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtolower($unit);
        if ($unit === 'km') {
            return round($miles * 1.609344, 2);
        }
        if ($unit === 'm') {
            return round($miles * 1609.344, 0);
        }
        return round($miles, 2);
    }
}
