<?php

namespace App\Services\Integration;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Arr;

class FakeStoreSyncService
{
    public function __construct(
        private ?string $baseUrl = null,
    ) {
        $this->baseUrl = $this->baseUrl ?: rtrim(config('services.fakestore.base_url', env('FAKESTORE_BASE_URL', 'https://fakestoreapi.com')), '/');
    }

    public function syncAll(): array
    {
        $imported = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        $cats = $this->fetchJson($this->baseUrl.'/products/categories');
        if (!is_array($cats)) {
            return ['imported'=>0,'updated'=>0,'skipped'=>0,'errors'=>['categories fetch failed']];
        }

        $catMap = [];
        foreach ($cats as $name) {
            if (!is_string($name) || $name === '') {
                $skipped++;
                continue;
            }
            $category = Category::firstOrCreate(['name' => $name]);
            $catMap[$name] = $category->id;
        }

        $items = $this->fetchJson($this->baseUrl.'/products');
        if (!is_array($items)) {
            return ['imported'=>$imported,'updated'=>$updated,'skipped'=>$skipped,'errors'=>array_merge($errors, ['products fetch failed'])];
        }

        foreach ($items as $row) {
            $extId = Arr::get($row, 'id');
            $title = trim((string) Arr::get($row, 'title', ''));
            $price = Arr::get($row, 'price');
            $desc  = (string) Arr::get($row, 'description', '');
            $img   = (string) Arr::get($row, 'image', '');
            $catName = (string) Arr::get($row, 'category', '');

            if (!is_numeric($extId) || $title === '' || !is_numeric($price) || $catName === '' || !isset($catMap[$catName])) {
                $skipped++;
                continue;
            }

            $payload = [
                'category_id' => (int) $catMap[$catName],
                'title'       => $title,
                'description' => $desc,
                'price'       => (float) $price,
                'image_url'   => $img,
            ];

            $product = Product::where('external_id', (int)$extId)->first();

            if ($product) {
                $dirty = false;
                foreach ($payload as $k => $v) {
                    if ($product->{$k} !== $v) {
                        $dirty = true;
                        break;
                    }
                }
                if ($dirty) {
                    $product->update($payload);
                    $updated++;
                } else {
                    $skipped++;
                }
            } else {
                Product::create(array_merge(['external_id' => (int)$extId], $payload));
                $imported++;
            }
        }

        return compact('imported','updated','skipped','errors');
    }

    private function fetchJson(string $url): mixed
    {
        $resp = Http::acceptJson()->get($url);
        if ($resp->failed()) {
            return null;
        }
        return $resp->json();
    }
}
