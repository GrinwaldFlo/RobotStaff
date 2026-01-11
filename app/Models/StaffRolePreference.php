<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffRolePreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'registration_id',
        'role_id',
        'preference_order',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'preference_order' => 'integer',
        ];
    }

    /**
     * Get the registration for this preference.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(StaffEventRegistration::class, 'registration_id');
    }

    /**
     * Get the role for this preference.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(EventRole::class, 'role_id');
    }
}
