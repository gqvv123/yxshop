<?php

use app\model\Setting;
use app\repository\SettingRepository;
use app\service\cache\TieredCache;
use app\service\SettingService;
use Illuminate\Support\Arr;

/**
 * 全局辅助函数：i18n 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('translate')) {
    /**
     * 翻译静态文本
     */
    function translate($keyWord, $lang_code = null): string
    {
        try {
            $lang_code = $lang_code ?: app()->getLocale();
            $lang_key = preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($keyWord)));
            $translate_data = TieredCache::remember('translations-' . $lang_code, 60 * 60, function () use ($lang_code) {
                return \app\model\Translation::where('code', $lang_code)->pluck('value', 'key')->toArray();
            });
            if (!array_key_exists($lang_key, $translate_data)) {
                $translate_val = str_replace(["\r", "\n", "\r\n"], '', $keyWord);
                \app\model\Translation::create([
                    'code'  => $lang_code,
                    'key'   => $lang_key,
                    'value' => $translate_val,
                ]);
                $keyWord = $translate_val;
                TieredCache::delete('translations-' . $lang_code);
            } else {
                $keyWord = $translate_data[$lang_key];
            }
        } catch (\Throwable $th) {
            // 翻译数据异常时回退到原始文案
        }
        return ucfirst($keyWord);
    }
}

if (!function_exists('site_settings')) {
    /**
     * 获取站点设置
     */
    function site_settings(?string $key, mixed $default = null): int|string|array|object|null
    {
        return Arr::get(TieredCache::remember('site_settings', 24 * 60 * 60, function () {
            $settingService = new SettingService(new SettingRepository(new Setting()));
            return $settingService->getAllSettings(0);
        }), $key, $default);
    }
}

if (!function_exists('default_currency')) {
    /**
     * 获取默认货币
     */
    function default_currency()
    {
        return \app\utility\MoneyHelper::defaultCurrency();
    }
}

if (!function_exists('frontend_section')) {
    /**
     * 获取前端区块数据
     */
    function frontend_section($slug = null)
    {
        $frontends = TieredCache::remember('frontend', 24 * 60 * 60, fn () => \app\model\Frontend::get());
        if ($slug) {
            return $frontends->where('slug', $slug)->first();
        }
        return $frontends;
    }
}

if (!function_exists('get_translation')) {
    /**
     * 获取多语言翻译值
     */
    function get_translation($data, $lang = null)
    {
        $lang = $lang ?: session()->get('locale');
        $lang_data = \app\common\SafeHelper::decodeJson((string) $data);
        $default = Arr::get($lang_data, 'en', 'default');
        if (array_key_exists($lang, $lang_data ?? [])) {
            $transate = $lang_data[$lang];
        }
        return $transate ?? $default;
    }
}

if (!function_exists('translateable_locale')) {
    /**
     * 获取可翻译的语言代码列表（当前语言排在前面）
     */
    function translateable_locale(object $languages): array
    {
        $localeArray = $languages->pluck('code')->toArray();
        usort($localeArray, function ($a, $b) {
            $systemLocale = session()->get('locale');
            $systemLocaleIndex = array_search($systemLocale, [$a, $b]);
            return $systemLocaleIndex === false ? 0 : ($systemLocaleIndex === 0 ? -1 : 1);
        });
        return $localeArray;
    }
}

if (!function_exists('getLanguagesArr')) {
    /**
     * 获取语言名称映射数组
     */
    function getLanguagesArr(object $languages): array
    {
        return $languages->pluck('name', 'code')->toArray();
    }
}

if (!function_exists('get_ai_option')) {
    /**
     * 获取AI写作选项
     */
    function get_ai_option(): array
    {
        return [
            'improve_it' => ['prompt' => 'Improve the above message writing'],
            'Grammer Correction' => ['prompt' => 'Correct any grammatical mistake in the message'],
            'make_it_more_detailed' => ['prompt' => 'Make this message More Detailed'],
            'simplyfy_it' => ['prompt' => 'Simplyfy this message'],
            'make_it_informative' => ['prompt' => 'Make the message more informative'],
            'fix_any_mistake' => ['prompt' => 'Fix if there is any mistake in the message'],
            'sound_fluent' => ['prompt' => 'Make this message as it sound more fluent'],
            'make_it_objective' => ['prompt' => 'Make  this message more objective'],
        ];
    }
}

if (!function_exists('get_ai_tone')) {
    /**
     * 获取AI语气选项
     */
    function get_ai_tone(): array
    {
        return [
            'engaging' => ['display_name' => 'Make It Engaging', 'prompt' => 'Make the message content tone more engaging'],
            'sound_formal' => ['display_name' => 'Sound Formal', 'prompt' => 'Make the message content tone more formal'],
            'sound_casual' => ['display_name' => 'Sound Casual', 'prompt' => 'Make the message  content tone  sound more casual'],
            'friendly' => ['display_name' => 'Make It Friendly', 'prompt' => 'Make the message content tone more user friendly'],
            'exciting' => ['display_name' => 'Make It Exciting', 'prompt' => 'Make the message content tone more exciting'],
            'confident' => ['display_name' => 'Make It Confident', 'prompt' => 'Make the message content tone more Confident'],
            'assertive' => ['display_name' => 'Make It Assertive', 'prompt' => 'Make the message content tone more assertive'],
        ];
    }
}
