<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'location',
        'event_type',
        'contact_info',
        'requirements',
        'start_date',
        'end_date',
        'is_paid',
        'price',
        'quota',
        'registered_count',
        'image',
        'qr_code',
        'qr_code_string',
        'status',
        'is_active',
        'feedback_summary',
        'feedback_summary_generated_at',
        'feedback_count_at_summary'
    ];

    protected $appends = ['image_url', 'qr_code_url'];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_paid' => 'boolean',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'feedback_summary_generated_at' => 'datetime'
        ];
    }

    // Prevent UTC conversion when serializing to JSON
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    // Accessors for Storage URLs
    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return Storage::disk('public')->url($this->image);
        }
        return null;
    }

    public function getQrCodeUrlAttribute()
    {
        if ($this->qr_code) {
            return Storage::disk('public')->url($this->qr_code);
        }
        return null;
    }

    // Relationships
    public function organizer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function participants()
    {
        return $this->hasMany(EventParticipant::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeOpen($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('quota')
              ->orWhereRaw('registered_count < quota');
        });
    }

    // Tambahkan juga helper method untuk cek apakah user sudah memberikan feedback
public function hasFeedbackFrom($userId)
{
    return $this->feedbacks()->where('user_id', $userId)->exists();
}

// Method untuk mendapatkan feedback dari user tertentu
public function getFeedbackFrom($userId)
{
    return $this->feedbacks()->where('user_id', $userId)->first();
}

    // Accessors
    public function getIsFullAttribute()
    {
        return $this->quota && $this->registered_count >= $this->quota;
    }

    public function getIsUpcomingAttribute()
    {
        return $this->start_date > now();
    }

    public function getIsPastAttribute()
    {
        return $this->end_date < now();
    }

    
}
