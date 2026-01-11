<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAvailability extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'registration_id',
        'event_day_id',
        'is_available_morning',
        'is_available_afternoon',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'is_available_morning' => 'boolean',
            'is_available_afternoon' => 'boolean',
        ];
    }

    /**
     * The table associated with the model.
     */
    protected $table = 'staff_availability';

    /**
     * Get the registration for this availability.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(StaffEventRegistration::class, 'registration_id');
    }

    /**
     * Get the event day for this availability.
     */
    public function eventDay(): BelongsTo
    {
        return $this->belongsTo(EventDay::class, 'event_day_id');
    }
}
