<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrganizerBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_earned',
        'withdrawn',
        'pending_withdrawal',
        'available_balance',
        'platform_fee_total',
    ];

    protected function casts(): array
    {
        return [
            'total_earned' => 'decimal:2',
            'withdrawn' => 'decimal:2',
            'pending_withdrawal' => 'decimal:2',
            'available_balance' => 'decimal:2',
            'platform_fee_total' => 'decimal:2',
        ];
    }

    // Relationships
    public function organizer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Methods
    public function updateAvailableBalance(): void
    {
        $this->available_balance = $this->total_earned - $this->withdrawn - $this->pending_withdrawal;
        $this->save();
    }

    public function addEarnings(float $amount, float $platformFee): void
    {
        $this->total_earned += $amount;
        $this->platform_fee_total += $platformFee;
        $this->updateAvailableBalance();
    }

    public function addPendingWithdrawal(float $amount): void
    {
        $this->pending_withdrawal += $amount;
        $this->updateAvailableBalance();
    }

    public function approvePendingWithdrawal(float $amount): void
    {
        $this->pending_withdrawal -= $amount;
        $this->withdrawn += $amount;
        $this->updateAvailableBalance();
    }

    public function rejectPendingWithdrawal(float $amount): void
    {
        $this->pending_withdrawal -= $amount;
        $this->updateAvailableBalance();
    }
}
