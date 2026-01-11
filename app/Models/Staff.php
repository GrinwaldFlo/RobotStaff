<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Staff extends Model
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
     * The table associated with the model.
     */
    protected $table = 'staff';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'username',
        'email',
        'first_name',
        'last_name',
        'phone_number',
        'city',
        'languages',
        'comment',
        'photo_path',
        'auth_token',
        'token_expires_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'auth_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'token_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
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
            if (empty($model->auth_token)) {
                $model->auth_token = Str::random(64);
            }
        });
    }

    /**
     * Get the event registrations for the staff member.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(StaffEventRegistration::class, 'staff_id');
    }

    /**
     * Check if the staff profile is complete.
     */
    public function isProfileComplete(): bool
    {
        return !empty($this->first_name) 
            && !empty($this->last_name) 
            && !empty($this->phone_number);
    }

    /**
     * Generate a new authentication token.
     */
    public function regenerateToken(): string
    {
        $this->auth_token = Str::random(64);
        $this->token_expires_at = now()->addDays(60);
        $this->save();

        return $this->auth_token;
    }

    /**
     * Refresh the token expiration.
     */
    public function refreshTokenExpiration(): void
    {
        $this->token_expires_at = now()->addDays(60);
        $this->last_login_at = now();
        $this->save();
    }

    /**
     * Anonymize staff data for GDPR compliance.
     */
    public function anonymize(): void
    {
        $this->username = 'anonymized_' . Str::random(8);
        $this->email = 'anonymized_' . Str::random(8) . '@deleted.local';
        $this->first_name = null;
        $this->last_name = null;
        $this->phone_number = null;
        $this->city = null;
        $this->languages = null;
        $this->comment = null;
        $this->photo_path = null;
        $this->auth_token = Str::random(64);
        $this->token_expires_at = null;
        $this->save();
    }
}
