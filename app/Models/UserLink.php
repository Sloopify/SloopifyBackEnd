<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLink extends Model
{
    protected $table = 'user_links';
    
    protected $fillable = [
        'user_id',
        'link_type',
        'link_url',
        'title',
        'description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Link type constants
    const TYPE_WEBSITE = 'website';
    const TYPE_PORTFOLIO = 'portfolio';
    const TYPE_BLOG = 'blog';
    const TYPE_LINKEDIN = 'linkedin';
    const TYPE_TWITTER = 'twitter';
    const TYPE_FACEBOOK = 'facebook';
    const TYPE_INSTAGRAM = 'instagram';
    const TYPE_YOUTUBE = 'youtube';
    const TYPE_TIKTOK = 'tiktok';
    const TYPE_GITHUB = 'github';
    const TYPE_BEHANCE = 'behance';
    const TYPE_DRIBBBLE = 'dribbble';
    const TYPE_PINTEREST = 'pinterest';
    const TYPE_SNAPCHAT = 'snapchat';
    const TYPE_TELEGRAM = 'telegram';
    const TYPE_WHATSAPP = 'whatsapp';
    const TYPE_OTHER = 'other';

    /**
     * Get the user that owns the link.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active links
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get links by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('link_type', $type);
    }

    /**
     * Get link type display name
     */
    public function getLinkTypeDisplayAttribute()
    {
        $types = [
            self::TYPE_WEBSITE => 'Website',
            self::TYPE_PORTFOLIO => 'Portfolio',
            self::TYPE_BLOG => 'Blog',
            self::TYPE_LINKEDIN => 'LinkedIn',
            self::TYPE_TWITTER => 'Twitter',
            self::TYPE_FACEBOOK => 'Facebook',
            self::TYPE_INSTAGRAM => 'Instagram',
            self::TYPE_YOUTUBE => 'YouTube',
            self::TYPE_TIKTOK => 'TikTok',
            self::TYPE_GITHUB => 'GitHub',
            self::TYPE_BEHANCE => 'Behance',
            self::TYPE_DRIBBBLE => 'Dribbble',
            self::TYPE_PINTEREST => 'Pinterest',
            self::TYPE_SNAPCHAT => 'Snapchat',
            self::TYPE_TELEGRAM => 'Telegram',
            self::TYPE_WHATSAPP => 'WhatsApp',
            self::TYPE_OTHER => 'Other'
        ];

        return $types[$this->link_type] ?? $this->link_type;
    }

    /**
     * Get link icon (for UI purposes)
     */
    public function getLinkIconAttribute()
    {
        $icons = [
            self::TYPE_WEBSITE => 'globe',
            self::TYPE_PORTFOLIO => 'briefcase',
            self::TYPE_BLOG => 'rss',
            self::TYPE_LINKEDIN => 'linkedin',
            self::TYPE_TWITTER => 'twitter',
            self::TYPE_FACEBOOK => 'facebook',
            self::TYPE_INSTAGRAM => 'instagram',
            self::TYPE_YOUTUBE => 'youtube',
            self::TYPE_TIKTOK => 'music',
            self::TYPE_GITHUB => 'github',
            self::TYPE_BEHANCE => 'palette',
            self::TYPE_DRIBBBLE => 'basketball-ball',
            self::TYPE_PINTEREST => 'pinterest',
            self::TYPE_SNAPCHAT => 'ghost',
            self::TYPE_TELEGRAM => 'telegram',
            self::TYPE_WHATSAPP => 'whatsapp',
            self::TYPE_OTHER => 'link'
        ];

        return $icons[$this->link_type] ?? 'link';
    }

    /**
     * Get domain from URL
     */
    public function getDomainAttribute()
    {
        $url = parse_url($this->link_url, PHP_URL_HOST);
        return $url ? str_replace('www.', '', $url) : null;
    }

    /**
     * Validate URL format
     */
    public function getIsValidUrlAttribute()
    {
        return filter_var($this->link_url, FILTER_VALIDATE_URL) !== false;
    }
}
