<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function index()
    {
        $totalProducts = DB::selectOne("
            SELECT COUNT(*) as total FROM products
        ")->total;

        $productsByCategory = DB::select("
            SELECT c.name as category_name, COUNT(p.id) as count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            GROUP BY c.id, c.name
            ORDER BY count DESC
        ");

        $averagePrice = DB::selectOne("
            SELECT AVG(price) as average FROM products
        ")->average;

        $mostExpensiveProducts = DB::select("
            SELECT title, price 
            FROM products 
            ORDER BY price DESC 
            LIMIT 5
        ");

        return response()->json([
            'total_products' => (int)$totalProducts,
            'products_by_category' => $productsByCategory,
            'average_price' => (float)$averagePrice,
            'most_expensive_products' => $mostExpensiveProducts
        ]);
    }
}