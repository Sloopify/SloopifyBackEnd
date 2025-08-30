<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    protected $fillable = [
        'category',
        'name',
        'status',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];

    // Category constants
    const CATEGORY_TECHNOLOGY = 'Technology & Digital';
    const CATEGORY_CREATIVE = 'Creative & Arts';
    const CATEGORY_BUSINESS = 'Business & Finance';
    const CATEGORY_LIFESTYLE = 'Lifestyle & Personal Growth';
    const CATEGORY_SCIENCE = 'Science & Education';
    const CATEGORY_SOCIAL = 'Social & Community';
    const CATEGORY_GAMING = 'Gaming & Entertainment';

    /**
     * Get users who have this skill
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_skills')
                    ->withPivot('proficiency_level', 'description', 'is_public')
                    ->withTimestamps();
    }

    /**
     * Scope to get active skills
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get skills by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get all available categories
     */
    public static function getCategories()
    {
        return [
            self::CATEGORY_TECHNOLOGY,
            self::CATEGORY_CREATIVE,
            self::CATEGORY_BUSINESS,
            self::CATEGORY_LIFESTYLE,
            self::CATEGORY_SCIENCE,
            self::CATEGORY_SOCIAL,
            self::CATEGORY_GAMING
        ];
    }
}
