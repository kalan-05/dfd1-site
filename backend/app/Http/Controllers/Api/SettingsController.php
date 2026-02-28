<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = SiteSetting::query()->first();

        if (! $settings) {
            return response()->json([
                'site_name' => null,
                'phones' => [],
                'email' => null,
                'address_main' => null,
                'worktime_main' => null,
                'social' => [],
                'seo' => [
                    'title' => null,
                    'description' => null,
                    'keywords' => null,
                ],
                'map' => [
                    'lat' => null,
                    'lng' => null,
                    'zoom' => 14,
                ],
                'og_image_url' => null,
            ]);
        }

        $phones = array_values(array_filter([
            $settings->phone_1,
            $settings->phone_2,
        ], static fn (?string $phone): bool => filled($phone)));

        return response()->json([
            'site_name' => $settings->site_name,
            'phones' => $phones,
            'email' => $settings->email,
            'address_main' => $settings->address_main,
            'worktime_main' => $settings->worktime_main,
            'social' => $settings->social ?? [],
            'seo' => [
                'title' => $settings->seo_title,
                'description' => $settings->seo_description,
                'keywords' => $settings->seo_keywords,
            ],
            'map' => [
                'lat' => $settings->map_lat,
                'lng' => $settings->map_lng,
                'zoom' => $settings->map_zoom,
            ],
            'og_image_url' => MediaUrl::toUrl($settings->og_image),
        ]);
    }
}
