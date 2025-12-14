<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'status',
        'is_paid',
        'amount_paid',
        'payment_reference',
        'payment_status',
        'payment_url',
        'paid_at',
        'attended_at',
        'qr_code',
        'qr_code_string',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'amount_paid' => 'decimal:2',
            'attended_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    // Prevent UTC conversion when serializing to JSON
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // Scopes
    public function scopeRegistered($query)
    {
        return $query->where('status', 'registered');
    }

    public function scopeAttended($query)
    {
        return $query->where('status', 'attended');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
