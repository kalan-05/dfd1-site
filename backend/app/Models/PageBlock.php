<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class PageBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'title',
        'content',
        'is_enabled',
        'sort_order',
        'meta',
        'updated_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
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

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
