<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\CatalogVersion;


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
    public function categories(Request $request)
    {
        $cats = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'version'    => $this->version->get(),
            'categories' => $cats->map(fn ($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'products_count' => $c->products_count,
            ]),
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
    public function products(Request $request)
    {
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
            'version'    => $this->version->get(),
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
    public function stats(Request $request)
    {
        $totalProducts   = Product::count();
        $totalCategories = Category::count();

        $min = Product::min('price');
        $max = Product::max('price');
        $avg = Product::avg('price');

        return response()->json([
            'version'          => $this->version->get(),
            'total_products'   => (int) $totalProducts,
            'total_categories' => (int) $totalCategories,
            'min_price'        => $min !== null ? (float) $min : null,
            'max_price'        => $max !== null ? (float) $max : null,
            'avg_price'        => $avg !== null ? (float) round($avg, 2) : null,
        ]);
    }
}
