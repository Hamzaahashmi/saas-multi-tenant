<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $fillable = [
        'email',
        'name',
        'role',
        'token',
        'invited_by',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    public static function generate(string $email, string $role, int $invitedBy, ?string $name = null): self
    {
        // Revoke any existing pending invite for this email
        static::where('email', $email)->whereNull('accepted_at')->delete();

        return static::create([
            'email'      => $email,
            'name'       => $name,
            'role'       => $role,
            'token'      => Str::random(64),
            'invited_by' => $invitedBy,
            'expires_at' => now()->addHours(48),
        ]);
    }

    public function isPending(): bool
    {
        return is_null($this->accepted_at) && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
