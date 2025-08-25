<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Integration\FakeStoreSyncService;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    public function __construct(private FakeStoreSyncService $service) {}

    public function sync(): JsonResponse
    {
        $result = $this->service->syncAll();

        return response()->json([
            'message'  => 'sync finished',
            'imported' => $result['imported'],
            'updated'  => $result['updated'],
            'skipped'  => $result['skipped'],
            'errors'   => $result['errors']
        ]);
    }
}
