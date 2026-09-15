<?php

namespace app\model;

class Discount extends BaseModel
{
    protected $table = 'yxshop_discounts';

    protected $fillable = [
        'name', 'type', 'value', 'min_amount', 'max_discount',
        'start_time', 'end_time', 'is_active', 'app_id', 'sort',
    ];

    protected $casts = [
        'type' => 'integer',
        'value' => 'float',
        'min_amount' => 'float',
        'max_discount' => 'float',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'is_active' => 'integer',
        'app_id' => 'integer',
        'sort' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    const TYPE_PERCENT = 1;
    const TYPE_FIXED = 2;

    public function isActive()
    {
        $now = time();
        return $this->is_active && $this->start_time <= $now && $this->end_time >= $now;
    }

    public function calculateDiscount($amount)
    {
        if ($amount < $this->min_amount) {
            return 0;
        }

        if ($this->type === self::TYPE_PERCENT) {
            $discount = $amount * ($this->value / 100);
            if ($this->max_discount > 0) {
                $discount = min($discount, $this->max_discount);
            }
            return round($discount, 2);
        }

        return min($this->value, $amount);
    }
}
