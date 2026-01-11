<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventRole extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'event_id',
        'designation',
        'number_required',
        'document_links',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'document_links' => 'array',
            'number_required' => 'integer',
        ];
    }

    /**
     * Get the event that owns the role.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the preferences for this role.
     */
    public function preferences(): HasMany
    {
        return $this->hasMany(StaffRolePreference::class, 'role_id');
    }

    /**
     * Get the registrations assigned to this role.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(StaffEventRegistration::class, 'assigned_role_id');
    }

    /**
     * Get the count of staff assigned to this role.
     */
    public function getAssignedCountAttribute(): int
    {
        return $this->assignments()->count();
    }

    /**
     * Check if the role is fully staffed.
     */
    public function isFullyStaffed(): bool
    {
        return $this->assigned_count >= $this->number_required;
    }
}
