<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_reviews_creates_draft_record(): void
    {
        $response = $this->postJson('/api/reviews', [
            'author_name' => '????',
            'rating' => 5,
            'text' => '????? ? ?????? ????????? ? ???????????? ???????????.',
            'website' => '',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'message' => '????? ?????? ?? ?????????',
            ]);

        $this->assertDatabaseHas('reviews', [
            'author_name' => '????',
            'source' => 'form',
            'status' => 'draft',
        ]);
    }
}
