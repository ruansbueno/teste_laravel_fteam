<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CatalogVersion
{
    private const KEY = 'catalog_version';

    public function get(): int {
        return (int) Cache::rememberForever(self::KEY, fn () => 1);
    }

    public function bump(): int {
        $v = $this->get() + 1;
        Cache::forever(self::KEY, $v);
        return $v;
    }

    public function set(int $v): void {
        Cache::forever(self::KEY, $v);
    }
}
