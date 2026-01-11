<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
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
        'name',
        'tagname',
        'short_description',
        'long_description',
        'start_date',
        'end_date',
        'location',
        'contact_email',
        'logo_path',
        'whatsapp_link',
        'general_documents_links',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'general_documents_links' => 'array',
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

        // Auto-create event days when event is created
        static::created(function ($event) {
            $event->createEventDays();
        });
    }

    /**
     * Get the days for the event.
     */
    public function days(): HasMany
    {
        return $this->hasMany(EventDay::class)->orderBy('date');
    }

    /**
     * Get the roles for the event.
     */
    public function roles(): HasMany
    {
        return $this->hasMany(EventRole::class);
    }

    /**
     * Get the registrations for the event.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(StaffEventRegistration::class);
    }

    /**
     * Check if the event is in the future.
     */
    public function isFuture(): bool
    {
        return $this->end_date->isFuture();
    }

    /**
     * Check if the event has passed.
     */
    public function isPast(): bool
    {
        return $this->end_date->isPast();
    }

    /**
     * Get the number of days for this event.
     */
    public function getDurationInDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Create event days based on start and end dates.
     */
    public function createEventDays(): void
    {
        $currentDate = $this->start_date->copy();
        
        while ($currentDate->lte($this->end_date)) {
            EventDay::firstOrCreate([
                'event_id' => $this->id,
                'date' => $currentDate->toDateString(),
            ]);
            $currentDate->addDay();
        }
    }

    /**
     * Update event days when dates change.
     */
    public function syncEventDays(): void
    {
        // Remove days outside the new date range
        $this->days()
            ->where('date', '<', $this->start_date)
            ->orWhere('date', '>', $this->end_date)
            ->delete();

        // Create any missing days
        $this->createEventDays();
    }

    /**
     * Copy event to a new event with a new tagname.
     */
    public function copyTo(string $newTagname): Event
    {
        $newEvent = $this->replicate(['id']);
        $newEvent->tagname = $newTagname;
        $newEvent->save();

        // Copy roles
        foreach ($this->roles as $role) {
            $newRole = $role->replicate(['id', 'event_id']);
            $newRole->event_id = $newEvent->id;
            $newRole->save();
        }

        return $newEvent;
    }

    /**
     * Scope for future events.
     */
    public function scopeFuture($query)
    {
        return $query->where('end_date', '>=', now()->toDateString());
    }

    /**
     * Scope for past events.
     */
    public function scopePast($query)
    {
        return $query->where('end_date', '<', now()->toDateString());
    }
}
