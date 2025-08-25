<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;         

class IntegrationMiddlewareTest extends TestCase
{
    protected function setUp(): void{
        parent::setUp();

        Route::middleware('integration')->get('/api/ping', function () {
            return response()->json(['ok' => true]);
        });
    }

     public function test_missing_client_id_returns_400(): void {
        $res = $this->getJson('/api/ping');
        $res->assertStatus(400)
            ->assertJson([
                'error' => 'missing_header',
            ])
            ->assertHeader('X-Request-Id');
    }

    public function test_with_client_id_passes_and_sets_headers(): void {
        $res = $this->getJson('/api/ping', [
            'X-Client-Id' => 'test-client',
            'X-Request-Id'=> 'req-123',
        ]);

        $res->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertHeader('X-Request-Id', 'req-123')
            ->assertHeader('X-Client-Id', 'test-client');
    }

    public function test_rate_limit_blocks_after_threshold(): void {
        config()->set('integrations.rate_limit_per_minute', 2);
        config()->set('integrations.rate_limit_decay', 60);

        ## 1 e 2 ok
        $this->getJson('/api/ping', ['X-Client-Id' => 'c1'])->assertStatus(200);
        $this->getJson('/api/ping', ['X-Client-Id' => 'c1'])->assertStatus(200);

        // 3 bloqueia
        $res = $this->getJson('/api/ping', ['X-Client-Id' => 'c1']);
        $res->assertStatus(429)
            ->assertJsonStructure(['error','message','retry_after'])
            ->assertHeader('Retry-After');
    }

        public function test_rate_limit_is_per_client(): void {
        config()->set('integrations.rate_limit_per_minute', 1);
        config()->set('integrations.rate_limit_decay', 60);

        ## c1 usa a cota
        $this->getJson('/api/ping', ['X-Client-Id' => 'c1'])->assertStatus(200);

        ## c2 é outro cliente → não deve ser bloqueado
        $this->getJson('/api/ping', ['X-Client-Id' => 'c2'])->assertStatus(200);
    }
}
