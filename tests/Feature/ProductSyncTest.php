<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_products_without_header_returns_error()
    {
        $response = $this->postJson('/integrations/fakestore/sync');
        
        $response->assertStatus(400)
                ->assertJson(['error' => 'X-Client-Id header is required']);
    }

    public function test_sync_products_with_header()
    {
        $response = $this->withHeaders(['X-Client-Id' => 'test-client'])
                        ->postJson('/integrations/fakestore/sync');
        
        // Verifica se a sincronização foi iniciada
        $response->assertStatus(200);
    }
}