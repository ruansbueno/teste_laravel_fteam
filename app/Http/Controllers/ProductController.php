<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category'); // Evita N+1

        // Filtro por categoria
        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        // Filtro por preço mínimo
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        // Filtro por preço máximo
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Busca por título
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if (in_array($sortBy, ['price', 'title', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Paginação configurável
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::with('category')->find($id);
        
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product);
    }
}