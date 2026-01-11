<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StaffEventRegistration extends Model
{
    use HasFactory;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'staff_id',
        'event_id',
        'comment',
        'help_before_event',
        'team_affiliation',
        'is_first_participation',
        'is_validated',
        'assigned_role_id',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'help_before_event' => 'boolean',
            'is_first_participation' => 'boolean',
            'is_validated' => 'boolean',
        ];
    }

    /**
     * Bootstrap the model and auto-generate UUID.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });

        // Auto-create availability for all event days when registration is created
        static::created(function ($registration) {
            $registration->createDefaultAvailability();
        });
    }

    /**
     * Get the staff member for this registration.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the event for this registration.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the assigned role for this registration.
     */
    public function assignedRole(): BelongsTo
    {
        return $this->belongsTo(EventRole::class, 'assigned_role_id');
    }

    /**
     * Get the role preferences for this registration.
     */
    public function rolePreferences(): HasMany
    {
        return $this->hasMany(StaffRolePreference::class, 'registration_id')
            ->orderBy('preference_order');
    }

    /**
     * Get the availability records for this registration.
     */
    public function availability(): HasMany
    {
        return $this->hasMany(StaffAvailability::class, 'registration_id');
    }

    /**
     * Create default availability (all available) for all event days.
     */
    public function createDefaultAvailability(): void
    {
        foreach ($this->event->days as $day) {
            StaffAvailability::firstOrCreate([
                'registration_id' => $this->id,
                'event_day_id' => $day->id,
            ], [
                'is_available_morning' => true,
                'is_available_afternoon' => true,
            ]);
        }
    }

    /**
     * Check if the registration is complete.
     */
    public function isComplete(): bool
    {
        // Must have at least one role preference
        if ($this->rolePreferences()->count() === 0) {
            return false;
        }

        // Must have at least one available half-day
        $hasAvailability = $this->availability()
            ->where(function ($query) {
                $query->where('is_available_morning', true)
                    ->orWhere('is_available_afternoon', true);
            })
            ->exists();

        if (!$hasAvailability) {
            return false;
        }

        // Staff profile must be complete
        return $this->staff->isProfileComplete();
    }

    /**
     * Set role preferences (up to 3).
     */
    public function setRolePreferences(array $roleIds): void
    {
        // Clear existing preferences
        $this->rolePreferences()->delete();

        // Add new preferences (max 3)
        $roleIds = array_slice($roleIds, 0, 3);
        
        foreach ($roleIds as $order => $roleId) {
            StaffRolePreference::create([
                'registration_id' => $this->id,
                'role_id' => $roleId,
                'preference_order' => $order + 1,
            ]);
        }
    }

    /**
     * Get the first preferred role.
     */
    public function getFirstPreferredRoleAttribute(): ?EventRole
    {
        $preference = $this->rolePreferences()->first();
        return $preference?->role;
    }
}
