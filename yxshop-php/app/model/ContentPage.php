<?php

namespace app\model;

class ContentPage extends BaseModel
{
    protected $table = 'yxshop_content_pages';

    protected $fillable = [
        'title', 'slug', 'content', 'page_type', 'sort', 'status', 'app_id'
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
        'deleted_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    const TYPE_ABOUT = 'about';
    const TYPE_TERMS = 'terms';
    const TYPE_PRIVACY = 'privacy';
    const TYPE_FAQ = 'faq';
    const TYPE_CONTACT = 'contact';
    const TYPE_CUSTOM = 'custom';
}