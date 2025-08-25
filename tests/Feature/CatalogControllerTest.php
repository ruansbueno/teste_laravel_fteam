<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_lists_names_and_counts(): void
    {
        // given
        $men   = Category::create(['name' => "men's clothing", 'external_id' => 1]);
        $jewel = Category::create(['name' => 'jewelery',        'external_id' => 2]);

        Product::create([
            'external_id' => 101, 'category_id' => $men->id, 'title' => 'Backpack',
            'description' => 'desc', 'price' => 100.00, 'image_url' => null, 'raw_payload' => [],
        ]);
        Product::create([
            'external_id' => 102, 'category_id' => $men->id, 'title' => 'Shirt',
            'description' => null, 'price' => 50.00, 'image_url' => null, 'raw_payload' => [],
        ]);
        Product::create([
            'external_id' => 201, 'category_id' => $jewel->id, 'title' => 'Gold Ring',
            'description' => null, 'price' => 999.00, 'image_url' => null, 'raw_payload' => [],
        ]);

        Cache::put('catalog_version', 3);

        // when
        $res = $this->getJson('/api/categories', [
            'X-Client-Id' => 'test',
        ]);

        // then
        $res->assertStatus(200)
            ->assertJsonStructure(['version', 'categories'])
            ->assertJsonFragment(['version' => 3])
            ->assertJsonFragment(['name' => "men's clothing", 'products_count' => 2])
            ->assertJsonFragment(['name' => 'jewelery', 'products_count' => 1]);
    }

    public function test_products_supports_filters_sort_and_pagination(): void
    {
        // given
        $men   = Category::create(['name' => "men's clothing", 'external_id' => 1]);
        $jewel = Category::create(['name' => 'jewelery',        'external_id' => 2]);

        $p1 = Product::create([
            'external_id' => 1, 'category_id' => $men->id, 'title' => 'Backpack',
            'description' => 'travel bag', 'price' => 109.95, 'image_url' => null, 'raw_payload' => [],
        ]);
        $p2 = Product::create([
            'external_id' => 2, 'category_id' => $jewel->id, 'title' => 'Gold Ring',
            'description' => '18k', 'price' => 999.00, 'image_url' => null, 'raw_payload' => [],
        ]);
        $p3 = Product::create([
            'external_id' => 3, 'category_id' => $men->id, 'title' => 'Shirt Blue',
            'description' => 'cotton', 'price' => 49.90, 'image_url' => null, 'raw_payload' => [],
        ]);
        $p4 = Product::create([
            'external_id' => 4, 'category_id' => $jewel->id, 'title' => 'Silver Necklace',
            'description' => '925', 'price' => 150.00, 'image_url' => null, 'raw_payload' => [],
        ]);

        Cache::put('catalog_version', 7);

        // 1) filtro por categoria + ordenação por preço desc
        $res1 = $this->getJson('/api/products?category_id='.$jewel->id.'&sort=price_desc', [
            'X-Client-Id' => 'test',
        ]);
        $res1->assertStatus(200)
             ->assertJsonFragment(['version' => 7])
             ->assertJsonPath('pagination.total', 2)
             ->assertJsonPath('data.0.external_id', $p2->external_id) // 999.00 primeiro
             ->assertJsonPath('data.1.external_id', $p4->external_id); // 150.00

        // 2) busca textual (q) encontra 'Backpack'
        $res2 = $this->getJson('/api/products?q=back', [
            'X-Client-Id' => 'test',
        ]);
        $res2->assertStatus(200)
             ->assertJsonPath('pagination.total', 1)
             ->assertJsonFragment(['external_id' => $p1->external_id, 'title' => 'Backpack']);

        // 3) faixa de preço (>=50 e <=200) deve trazer p1 (109.95) e p4 (150.00) – ordenado asc
        $res3 = $this->getJson('/api/products?min_price=50&max_price=200&sort=price_asc', [
            'X-Client-Id' => 'test',
        ]);
        $res3->assertStatus(200)
             ->assertJsonPath('pagination.total', 2)
             ->assertJsonPath('data.0.external_id', $p1->external_id)
             ->assertJsonPath('data.1.external_id', $p4->external_id);

        // 4) paginação (per_page=2): página 2 deve conter 2 itens
        $res4 = $this->getJson('/api/products?per_page=2&page=2', [
            'X-Client-Id' => 'test',
        ]);
        $res4->assertStatus(200)
             ->assertJsonPath('pagination.per_page', 2)
             ->assertJsonPath('pagination.current_page', 2)
             ->assertJsonCount(2, 'data');
    }

    public function test_stats_returns_basic_metrics_and_versions(): void
    {
        // given
        $cat = Category::create(['name' => 'misc', 'external_id' => 999]);
        Product::create([
            'external_id' => 10, 'category_id' => $cat->id, 'title' => 'A', 'price' => 100, 'raw_payload' => []
        ]);
        Product::create([
            'external_id' => 11, 'category_id' => $cat->id, 'title' => 'B', 'price' => 50, 'raw_payload' => []
        ]);
        Product::create([
            'external_id' => 12, 'category_id' => $cat->id, 'title' => 'C', 'price' => 150, 'raw_payload' => []
        ]);

        Cache::put('stats_version', 5);

        // when
        $res = $this->getJson('/api/stats', [
            'X-Client-Id' => 'test',
        ]);

        // then
        $res->assertStatus(200)
            ->assertJsonFragment(['version' => 5])
            ->assertJsonFragment(['total_products' => 3])
            ->assertJsonFragment(['total_categories' => 1])
            ->assertJsonFragment(['min_price' => 50.00])
            ->assertJsonFragment(['max_price' => 150.00])
            ->assertJsonFragment(['avg_price' => 100.00]); // (100 + 50 + 150) / 3
    }
}
