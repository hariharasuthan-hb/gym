<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends BaseModel
{
    use HasFactory, SoftDeletes;

    /**
     * Lead status constants
     */
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_LOST = 'lost';

    /**
     * Lead source constants
     */
    public const SOURCE_WEBSITE = 'website';
    public const SOURCE_REFERRAL = 'referral';
    public const SOURCE_WALK_IN = 'walk_in';
    public const SOURCE_PHONE = 'phone';
    public const SOURCE_OTHER = 'other';

    /**
     * Get all available status options
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_NEW,
            self::STATUS_CONTACTED,
            self::STATUS_QUALIFIED,
            self::STATUS_CONVERTED,
            self::STATUS_LOST,
        ];
    }

    /**
     * Get all available source options
     */
    public static function getSourceOptions(): array
    {
        return [
            self::SOURCE_WEBSITE,
            self::SOURCE_REFERRAL,
            self::SOURCE_WALK_IN,
            self::SOURCE_PHONE,
            self::SOURCE_OTHER,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'message',
        'status',
        'source',
        'assigned_to',
        'notes',
        'follow_up_date',
        'converted_at',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'follow_up_date' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    /**
     * Get the user assigned to this lead.
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this lead.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include new leads.
     */
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    /**
     * Scope a query to only include contacted leads.
     */
    public function scopeContacted($query)
    {
        return $query->where('status', self::STATUS_CONTACTED);
    }

    /**
     * Scope a query to only include qualified leads.
     */
    public function scopeQualified($query)
    {
        return $query->where('status', self::STATUS_QUALIFIED);
    }

    /**
     * Scope a query to only include converted leads.
     */
    public function scopeConverted($query)
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    /**
     * Scope a query to only include lost leads.
     */
    public function scopeLost($query)
    {
        return $query->where('status', self::STATUS_LOST);
    }

    /**
     * Get the readable status name.
     * Converts "new" to "New"
     */
    public function getReadableStatusAttribute(): string
    {
        if (!$this->status) {
            return '—';
        }

        return ucfirst($this->status);
    }

    /**
     * Get the readable source name.
     * Converts "walk_in" to "Walk In"
     */
    public function getReadableSourceAttribute(): string
    {
        if (!$this->source) {
            return '—';
        }

        return ucwords(str_replace('_', ' ', $this->source));
    }
}
