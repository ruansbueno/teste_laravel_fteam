<?php

namespace App\Swagger;

/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     title="FTeam Catalog API",
 *     version="1.0.0"
 *   ),
 *   @OA\Server(
 *     url="/",
 *     description="Base"
 *   ),
 *   security={{"ClientId":{}}}
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="ClientId",
 *   type="apiKey",
 *   in="header",
 *   name="X-Client-Id"
 * )
 */
class OpenApi {}
