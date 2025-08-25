<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Integration\FakeStoreSyncService;
use Illuminate\Http\JsonResponse;

class SyncController extends Controller
{
    public function __construct(private FakeStoreSyncService $service) {}

    /**
     * @OA\Post(
     *   path="/api/integrations/fakestore/sync",
     *   operationId="syncFakeStore",
     *   summary="Executa sync com a FakeStore",
     *   tags={"Sync"},
     *   security={{"ClientId":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="message", type="string"),
     *       @OA\Property(property="imported", type="integer"),
     *       @OA\Property(property="updated", type="integer"),
     *       @OA\Property(property="skipped", type="integer"),
     *       @OA\Property(
     *         property="errors",
     *         type="array",
     *         @OA\Items(type="string")
     *       )
     *     )
     *   )
     * )
     */
    public function sync(): JsonResponse{
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
