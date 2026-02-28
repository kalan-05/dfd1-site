<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUrl
{
    public static function toUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $cleanPath = ltrim($path, '/');

        if (Str::startsWith($cleanPath, 'storage/')) {
            return url('/' . $cleanPath);
        }

        return Storage::disk('public')->url($cleanPath);
    }
}
