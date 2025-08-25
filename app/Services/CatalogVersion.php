<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CatalogVersion
{
    public function getCatalog(): int
    {
        return (int) Cache::get('catalog_version', 1);
    }

    public function getStats(): int
    {
        return (int) Cache::get('stats_version', 1);
    }
}
