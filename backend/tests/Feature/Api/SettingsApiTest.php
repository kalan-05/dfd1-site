<?php

namespace Tests\Feature\Api;

use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_endpoint_returns_200_and_contract_fields(): void
    {
        SiteSetting::query()->create([
            'site_name' => '???????? ????',
            'phone_1' => '+79990000000',
            'email' => 'test@example.com',
            'social' => ['tg' => 'https://t.me/test'],
            'map_zoom' => 14,
        ]);

        $response = $this->getJson('/api/settings');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'site_name',
                'phones',
                'email',
                'address_main',
                'worktime_main',
                'social',
                'seo' => ['title', 'description', 'keywords'],
                'map' => ['lat', 'lng', 'zoom'],
                'og_image_url',
            ]);
    }
}
