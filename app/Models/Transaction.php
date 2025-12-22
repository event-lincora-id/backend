<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'participant_id',
        'withdrawal_request_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function organizer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function participant()
    {
        return $this->belongsTo(EventParticipant::class, 'participant_id');
    }

    public function withdrawalRequest()
    {
        return $this->belongsTo(WithdrawalRequest::class, 'withdrawal_request_id');
    }
}
