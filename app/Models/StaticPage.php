<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class StaticPage extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['title', 'content', 'meta_keywords', 'meta_description'];

    protected $fillable = [
        'title',
        'slug',
        'content',
        'is_published',
        'meta_keywords',
        'meta_description',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
