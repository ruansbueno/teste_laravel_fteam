<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Category;

class FakeStoreService
{
    private $baseUrl = 'https://fakestoreapi.com';

    public function syncProducts()
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->get("{$this->baseUrl}/products");

            if ($response->failed()) {
                Log::error('Failed to fetch products from FakeStore API', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

            $products = $response->json();
            $syncedCount = 0;
            $errorCount = 0;

            foreach ($products as $productData) {
                try {
                    // Primeiro sincroniza a categoria
                    $category = $this->syncCategory($productData['category']);
                    
                    // Sincroniza o produto
                    Product::updateOrCreate(
                        ['external_id' => $productData['id']],
                        [
                            'title' => $productData['title'],
                            'price' => $productData['price'],
                            'description' => $productData['description'],
                            'category_id' => $category->id,
                            'image' => $productData['image'],
                            'rating_rate' => $productData['rating']['rate'] ?? null,
                            'rating_count' => $productData['rating']['count'] ?? null,
                        ]
                    );
                    
                    $syncedCount++;
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error('Error syncing product', [
                        'external_id' => $productData['id'],
                        'error' => $e->getMessage()
                    ]);
                    continue; // Continua com próximo produto
                }
            }

            Log::info('Products synchronization completed', [
                'synced' => $syncedCount,
                'errors' => $errorCount
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('FakeStore API synchronization failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function syncCategory($categoryName)
    {
        return Category::firstOrCreate(
            ['name' => $categoryName],
            ['name' => $categoryName]
        );
    }

    public function syncCategories()
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 100)
                ->get("{$this->baseUrl}/products/categories");

            if ($response->failed()) {
                throw new \Exception("Failed to fetch categories: " . $response->status());
            }

            $categories = $response->json();
            $syncedCount = 0;

            foreach ($categories as $categoryName) {
                Category::firstOrCreate(
                    ['name' => $categoryName],
                    ['name' => $categoryName]
                );
                $syncedCount++;
            }

            Log::info('Categories synchronization completed', [
                'synced' => $syncedCount
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Categories synchronization failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}