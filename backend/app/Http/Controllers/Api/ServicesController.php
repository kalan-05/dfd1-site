<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ServicesController extends Controller
{
    public function index(): JsonResponse
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(static fn (Service $service): array => [
                'id' => $service->id,
                'title' => $service->title,
                'group' => $service->group,
                'sort_order' => $service->sort_order,
            ])
            ->values();

        return response()->json($services);
    }
}
