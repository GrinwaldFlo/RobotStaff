<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailNotification extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'event_id',
        'notification_type',
        'sent_at',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Notification type constants.
     */
    public const TYPE_PARTICIPATION_VALIDATED = 'staff_participation_validated';
    public const TYPE_ROLE_ASSIGNED = 'staff_role_assigned';
    public const TYPE_EVENT_REMINDER = 'event_reminder';
    public const TYPE_NEW_REGISTRATION = 'new_staff_registration';
    public const TYPE_PREFERENCES_CHANGED = 'staff_preferences_changed';
    public const TYPE_CONNECTION_LINK = 'connection_link';

    /**
     * Get the event for this notification.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Check if a notification can be sent (cooldown check).
     */
    public static function canSendNotification(
        string $recipientType,
        string $recipientId,
        string $notificationType,
        int $cooldownMinutes = 5
    ): bool {
        // No cooldown for certain notification types
        $noCooldownTypes = [
            self::TYPE_NEW_REGISTRATION,
            self::TYPE_CONNECTION_LINK,
            self::TYPE_PARTICIPATION_VALIDATED,
            self::TYPE_ROLE_ASSIGNED,
            self::TYPE_EVENT_REMINDER,
        ];

        if (in_array($notificationType, $noCooldownTypes)) {
            return true;
        }

        return !self::where('recipient_type', $recipientType)
            ->where('recipient_id', $recipientId)
            ->where('notification_type', $notificationType)
            ->where('sent_at', '>', now()->subMinutes($cooldownMinutes))
            ->exists();
    }

    /**
     * Record a sent notification.
     */
    public static function recordNotification(
        string $recipientType,
        string $recipientId,
        string $notificationType,
        ?string $eventId = null
    ): self {
        return self::create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'event_id' => $eventId,
            'notification_type' => $notificationType,
            'sent_at' => now(),
            'created_at' => now(),
        ]);
    }
}
