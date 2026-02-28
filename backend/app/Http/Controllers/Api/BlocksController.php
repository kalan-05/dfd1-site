<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageBlock;
use Illuminate\Http\JsonResponse;

class BlocksController extends Controller
{
    public function index(): JsonResponse
    {
        $blocks = PageBlock::query()
            ->where('is_enabled', true)
            ->orderBy('sort_order')
            ->get()
            ->map(static fn (PageBlock $block): array => [
                'key' => $block->key,
                'is_enabled' => $block->is_enabled,
                'sort_order' => $block->sort_order,
                'title' => $block->title,
                'content' => $block->content,
                'meta' => $block->meta ?? new \stdClass(),
            ])
            ->values();

        return response()->json($blocks);
    }
}
