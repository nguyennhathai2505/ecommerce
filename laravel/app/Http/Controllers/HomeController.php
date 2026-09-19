<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\WishlistService;

class HomeController extends Controller
{
    public function __construct(protected WishlistService $wishlistService)
    {
    }

    public function index()
    {
        $user = auth()->user();
        $newProducts = $this->wishlistService->withWishlistStatus(
            Product::where('status', 'active'),
            $user
        )
            ->with(['shoe', 'cloth', 'shoesVariants', 'clothesVariants'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $popularProducts = $this->wishlistService->withWishlistStatus(
            Product::where('status', 'active'),
            $user
        )
            ->with(['shoe', 'cloth', 'shoesVariants', 'clothesVariants'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $categories = Category::where('status', 'active')->get();

        return view('customer.home', compact('newProducts', 'popularProducts', 'categories'));
    }
}