<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\CatalogVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(title="FTeam Catalog API", version="1.0.0")
 * @OA\Server(url="/", description="Base")
 * @OA\Tag(name="Catalog")
 */

class CatalogController extends Controller
{
    public function __construct(private CatalogVersion $version) {}

    /**
     * @OA\Get(
     *   path="/api/categories",
     *   operationId="getCategories",
     *   summary="Lista categorias com contagem de produtos",
     *   tags={"Catalog"},
     *   security={{"ClientId":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="version", type="integer"),
     *       @OA\Property(
     *         property="categories",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer"),
     *           @OA\Property(property="name", type="string"),
     *           @OA\Property(property="products_count", type="integer")
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function categories(Request $request){
        $version = $this->version->get();

        $payload = Cache::remember("categories:{$version}", 600, function () {
            $cats = \App\Models\Category::query()
                ->withCount('products')
                ->orderBy('name')
                ->get(['id', 'name']);

            return $cats->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'products_count' => $c->products_count,
            ])->all();
        });

        return response()->json([
            'version' => $this->version->getCatalog(),
            'categories' => $payload,
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/products",
     *   operationId="listProducts",
     *   summary="Lista produtos com filtros, ordenação e paginação",
     *   tags={"Catalog"},
     *   security={{"ClientId":{}}},
     *   @OA\Parameter(name="category_id", in="query", required=false, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="q", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="min_price", in="query", required=false, @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(name="max_price", in="query", required=false, @OA\Schema(type="number", format="float")),
     *   @OA\Parameter(
     *     name="sort", in="query", required=false,
     *     description="price_asc|price_desc|title_asc|title_desc|created_asc|created_desc",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *   @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer", default=1)),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="version", type="integer"),
     *       @OA\Property(
     *         property="pagination",
     *         type="object",
     *         @OA\Property(property="total", type="integer"),
     *         @OA\Property(property="per_page", type="integer"),
     *         @OA\Property(property="current_page", type="integer"),
     *         @OA\Property(property="last_page", type="integer")
     *       ),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="external_id", type="integer"),
     *           @OA\Property(property="category_id", type="integer"),
     *           @OA\Property(property="title", type="string"),
     *           @OA\Property(property="description", type="string"),
     *           @OA\Property(property="price", type="number", format="float"),
     *           @OA\Property(property="image_url", type="string", format="uri")
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function products(Request $request) {
        $q = Product::query()->with('category');

        if ($request->filled('category_id')) {
            $q->where('category_id', (int) $request->input('category_id'));
        }
        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $q->where(function ($w) use ($term) {
                $w->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            });
        }
        if ($request->filled('min_price')) {
            $q->where('price', '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $q->where('price', '<=', (float) $request->input('max_price'));
        }

        $sort = (string) $request->input('sort', 'created_desc');
        $sorts = [
            'price_asc'  => ['price', 'asc'],
            'price_desc' => ['price', 'desc'],
            'title_asc'  => ['title', 'asc'],
            'title_desc' => ['title', 'desc'],
            'created_asc'  => ['id', 'asc'],
            'created_desc' => ['id', 'desc'],
        ];
        [$col, $dir] = $sorts[$sort] ?? ['id', 'desc'];
        $q->orderBy($col, $dir);

        $perPage = (int) $request->input('per_page', 15);
        $page    = (int) $request->input('page', 1);
        $pag = $q->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'version' => $this->version->getCatalog(),
            'pagination' => [
                'total'        => $pag->total(),
                'per_page'     => $pag->perPage(),
                'current_page' => $pag->currentPage(),
                'last_page'    => $pag->lastPage(),
            ],
            'data' => $pag->getCollection()->map(fn (Product $p) => [
                'external_id' => (int) $p->external_id,
                'category_id' => (int) $p->category_id,
                'title'       => $p->title,
                'description' => $p->description,
                'price'       => (float) $p->price,
                'image_url'   => $p->image_url,
            ]),
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/stats",
     *   operationId="getStats",
     *   summary="Retorna métricas básicas e versões",
     *   tags={"Catalog"},
     *   security={{"ClientId":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="version", type="integer"),
     *       @OA\Property(property="total_products", type="integer"),
     *       @OA\Property(property="total_categories", type="integer"),
     *       @OA\Property(property="min_price", type="number", format="float", nullable=true),
     *       @OA\Property(property="max_price", type="number", format="float", nullable=true),
     *       @OA\Property(property="avg_price", type="number", format="float", nullable=true)
     *     )
     *   )
     * )
     */
    public function stats(Request $request) {
        $version = $this->version->get();

        $data = Cache::remember("stats:{$version}", 600, function () {
            $agg = DB::selectOne('select count(*) as total_products, min(price) as min_price, max(price) as max_price, round(avg(price),2) as avg_price from products');
            $totalCategories = DB::selectOne('select count(*) as total_categories from categories');

            return [
                'total_products' => (int) ($agg->total_products ?? 0),
                'total_categories' => (int) ($totalCategories->total_categories ?? 0),
                'min_price' => isset($agg->min_price) ? (float) $agg->min_price : null,
                'max_price' => isset($agg->max_price) ? (float) $agg->max_price : null,
                'avg_price' => isset($agg->avg_price) ? (float) $agg->avg_price : null,
            ];
        });

        return response()->json([
            'version' => $this->version->getStats(),
            'total_products' => $data['total_products'],
            'total_categories' => $data['total_categories'],
            'min_price' => $data['min_price'],
            'max_price' => $data['max_price'],
            'avg_price' => $data['avg_price'],
        ]);
    }

}
