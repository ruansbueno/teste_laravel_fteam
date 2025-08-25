<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="FTeam Catalog API",
 *     version="1.0.0",
 *     description="Catálogo, sincronização e estatísticas."
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Servidor da aplicação"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="client_id",
 *     type="apiKey",
 *     in="header",
 *     name="X-Client-Id",
 *     description="Informe o identificador do cliente"
 * )
 */
class OpenApi {}
