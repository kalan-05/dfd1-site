<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;

class GalleryController extends Controller
{
    public function index(): JsonResponse
    {
        $items = GalleryItem::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(static fn (GalleryItem $item): array => [
                'id' => $item->id,
                'image_url' => MediaUrl::toUrl($item->image),
                'caption' => $item->caption,
                'alt' => $item->alt,
                'sort_order' => $item->sort_order,
            ])
            ->values();

        return response()->json($items);
    }
}
