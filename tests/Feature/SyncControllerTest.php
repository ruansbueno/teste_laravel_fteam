<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SyncControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_imports_and_updates_products_and_categories(): void
    {
        Http::fake([
            'https://fakestoreapi.com/products/categories' => Http::response([
                "men's clothing", 'jewelery'
            ], 200),

            'https://fakestoreapi.com/products' => Http::response([
                [
                    'id' => 1,
                    'title' => 'Backpack',
                    'price' => 109.95,
                    'description' => 'desc',
                    'category' => "men's clothing",
                    'image' => 'https://fakestoreapi.com/img/1.png',
                ],
                [
                    'id' => 2,
                    'title' => 'Gold Ring',
                    'price' => 999.00,
                    'description' => null,
                    'category' => 'jewelery',
                    'image' => 'https://fakestoreapi.com/img/2.png',
                ],
            ], 200),

            'https://fakestoreapi.com/products/1' => Http::response([
                'id' => 1,
                'title' => 'Backpack',
                'price' => 109.95,
                'description' => 'desc',
                'category' => "men's clothing",
                'image' => 'https://fakestoreapi.com/img/1.png',
            ], 200),
            'https://fakestoreapi.com/products/2' => Http::response([
                'id' => 2,
                'title' => 'Gold Ring',
                'price' => 999.00,
                'description' => null,
                'category' => 'jewelery',
                'image' => 'https://fakestoreapi.com/img/2.png',
            ], 200),
        ]);

        Cache::put('catalog_version', 1);
        Cache::put('stats_version', 1);

        $res = $this->postJson('/api/integrations/fakestore/sync', [], [
            'X-Client-Id' => 'test',
        ]);

        $res->assertStatus(200)
            ->assertJsonStructure(['message','imported','updated','skipped','errors']);

        $this->assertDatabaseHas('categories', ['name' => "men's clothing"]);
        $this->assertDatabaseHas('categories', ['name' => 'jewelery']);

        $this->assertDatabaseHas('products', ['external_id' => 1, 'title' => 'Backpack']);
        $this->assertDatabaseHas('products', ['external_id' => 2, 'title' => 'Gold Ring']);

        Http::fake([
            'https://fakestoreapi.com/products/categories' => Http::response([
                "men's clothing", 'jewelery'
            ], 200),

            'https://fakestoreapi.com/products' => Http::response([
                [
                    'id' => 1,
                    'title' => 'Teste',
                    'price' => 109.95,
                    'description' => 'desc',
                    'category' => "men's clothing",
                    'image' => 'https://fakestoreapi.com/img/1.png',
                ],
                [
                    'id' => 2,
                    'title' => 'Gold Ring',
                    'price' => 999.00,
                    'description' => null,
                    'category' => 'jewelery',
                    'image' => 'https://fakestoreapi.com/img/2.png',
                ],
            ], 200),

            'https://fakestoreapi.com/products/1' => Http::response([
                'id' => 1,
                'title' => 'Backpack V2',
                'price' => 109.95,
                'description' => 'desc',
                'category' => "men's clothing",
                'image' => 'https://fakestoreapi.com/img/1.png',
            ], 200),
            'https://fakestoreapi.com/products/2' => Http::response([
                'id' => 2,
                'title' => 'Gold Ring',
                'price' => 999.00,
                'description' => null,
                'category' => 'jewelery',
                'image' => 'https://fakestoreapi.com/img/2.png',
            ], 200),
        ]);

        $res2 = $this->postJson('/api/integrations/fakestore/sync', [], [
            'X-Client-Id' => 'test',
        ]);

        $res2->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'external_id' => 1,
            'title' => 'Backpack V2',
        ]);

        $this->assertGreaterThan(1, Cache::get('catalog_version', 1));
        $this->assertGreaterThan(1, Cache::get('stats_version', 1));
    }

    public function test_sync_skips_invalid_product_but_continues(): void
    {
        Http::fake([
            'https://fakestoreapi.com/products/categories' => Http::response(['toys'], 200),

            'https://fakestoreapi.com/products' => Http::response([
                ['id' => 10, 'title' => 'Toy 1', 'price' => 10, 'category' => 'toys'],
                ['id' => 11, 'title' => null,     'price' => 20, 'category' => 'toys'],
                ['id' => 12, 'title' => 'Toy 3', 'price' => 30, 'category' => 'toys'],
            ], 200),

            'https://fakestoreapi.com/products/10' => Http::response([
                'id' => 10, 'title' => 'Toy 1', 'price' => 10, 'category' => 'toys',
            ], 200),
            'https://fakestoreapi.com/products/11' => Http::response([
                'id' => 11, 'title' => null, 'price' => 20, 'category' => 'toys',
            ], 200),
            'https://fakestoreapi.com/products/12' => Http::response([
                'id' => 12, 'title' => 'Toy 3', 'price' => 30, 'category' => 'toys',
            ], 200),
        ]);

        $res = $this->postJson('/api/integrations/fakestore/sync', [], [
            'X-Client-Id' => 'c1',
        ]);

        $res->assertStatus(200);

        $this->assertDatabaseHas('products', ['external_id' => 10]);
        $this->assertDatabaseHas('products', ['external_id' => 12]);
        $this->assertDatabaseMissing('products', ['external_id' => 11]);
    }
}
