<?php

namespace App\Services\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Category;
use App\Models\Product;

class FakeStoreSyncService
{
    public function syncAll(): array
    {
        $imported = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        try {
            $categories = $this->fetchCategories();
        } catch (\Throwable $e) {
            return [
                'imported' => 0,
                'updated'  => 0,
                'skipped'  => 0,
                'errors'   => ['categories' => $e->getMessage()],
            ];
        }

        foreach ($categories as $catName) {
            Category::firstOrCreate(['name' => $catName]);
        }

        try {
            $products = $this->fetchProducts();
        } catch (\Throwable $e) {
            return [
                'imported' => 0,
                'updated'  => 0,
                'skipped'  => 0,
                'errors'   => ['products' => $e->getMessage()],
            ];
        }

        foreach ($products as $p) {
            if (!isset($p['id'], $p['title'], $p['price'], $p['description'], $p['category'], $p['image'])) {
                $skipped++;
                $errors[] = 'invalid product payload';
                continue;
            }

            $category = Category::firstOrCreate(['name' => (string) $p['category']]);

            $payload = [
                'title'       => (string) $p['title'],
                'price'       => (float) $p['price'],
                'description' => (string) $p['description'],
                'category_id' => $category->id,
                'image_url'   => (string) $p['image'],
            ];

            $existing = Product::where('external_id', (int) $p['id'])->first();

            if ($existing) {
                $changed = false;
                foreach ($payload as $k => $v) {
                    if ($existing->{$k} !== $v) {
                        $changed = true;
                        break;
                    }
                }
                if ($changed) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                Product::create(array_merge(
                    ['external_id' => (int) $p['id']],
                    $payload
                ));
                $imported++;
            }
        }

        return [
            'imported' => $imported,
            'updated'  => $updated,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ];
    }

    protected function fetchCategories(): array
    {
        $base = rtrim(config('integrations.fakestore.base_url', config('services.fakestore.base_url', 'https://fakestoreapi.com')), '/');
        $timeout = (int) env('FAKESTORE_TIMEOUT', 5);
        $retries = (int) env('FAKESTORE_RETRIES', 3);
        $backoff = (int) env('FAKESTORE_RETRY_BACKOFF', 200);

        $resp = Http::retry($retries, $backoff)->timeout($timeout)->get($base.'/products/categories');

        if ($resp->failed()) {
            throw new \RuntimeException('categories request failed');
        }

        $data = $resp->json();

        if (!is_array($data)) {
            throw new \RuntimeException('invalid categories response');
        }

        return array_values(array_filter(array_map('strval', $data)));
        }

    protected function fetchProducts(): array
    {
        $base = rtrim(config('integrations.fakestore.base_url', config('services.fakestore.base_url', 'https://fakestoreapi.com')), '/');
        $timeout = (int) env('FAKESTORE_TIMEOUT', 5);
        $retries = (int) env('FAKESTORE_RETRIES', 3);
        $backoff = (int) env('FAKESTORE_RETRY_BACKOFF', 200);

        $resp = Http::retry($retries, $backoff)->timeout($timeout)->get($base.'/products');

        if ($resp->failed()) {
            throw new \RuntimeException('products request failed');
        }

        $data = $resp->json();

        if (!is_array($data)) {
            throw new \RuntimeException('invalid products response');
        }

        return $data;
    }
}
