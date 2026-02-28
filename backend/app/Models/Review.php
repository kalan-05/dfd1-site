<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_name',
        'rating',
        'text',
        'source',
        'status',
        'published_at',
        'doctor_id',
        'author_contacts',
        'meta',
        'updated_by',
    ];

    protected $casts = [
        'rating' => 'integer',
        'published_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
