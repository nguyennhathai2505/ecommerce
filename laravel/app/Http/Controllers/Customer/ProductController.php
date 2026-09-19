<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected WishlistService $wishlistService;

    public function __construct(WishlistService $wishlistService)
    {
        $this->wishlistService = $wishlistService;
    }

    public function index(Request $request)
    {
        $query = $this->wishlistService->withWishlistStatus(
            Product::where('status', 'active'),
            $request->user()
        );

        if ($request->has('category')) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        if ($request->has('price')) {
            $range = explode('-', $request->price);
            if (count($range) == 2) {
                $query->whereBetween('base_price', [$range[0], $range[1]]);
            } elseif (str_ends_with($request->price, '+')) {
                $min = (int) rtrim($request->price, '+');
                $query->where('base_price', '>=', $min);
            }
        }

        if ($request->has('sizes')) {
            $sizes = explode(',', $request->sizes);
            $query->whereHas('variants', function ($q) use ($sizes) {
                $q->whereIn('size', $sizes);
            });
        }

        if ($request->has('stud_types')) {
            $studTypes = explode(',', $request->stud_types);
            $query->whereHas('shoesVariants', function ($q) use ($studTypes) {
                $q->whereIn('stud_type', $studTypes);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_asc': $query->orderBy('base_price', 'asc'); break;
            case 'price_desc': $query->orderBy('base_price', 'desc'); break;
            default: $query->orderBy('created_at', 'desc');
        }

        $products = $query
            ->with(['shoe', 'cloth', 'shoesVariants', 'clothesVariants'])
            ->paginate(12);
        $categories = Category::where('status', 'active')->get();
        $selectedCategory = $request->category;

        return view('customer.products.index', compact('products', 'categories', 'selectedCategory'));
    }

    public function show($slug)
    {
        $user = auth()->user();
        $product = $this->wishlistService->withWishlistStatus(
            Product::where('slug', $slug)->where('status', 'active'),
            $user
        )
            ->with(['category', 'shoesVariants', 'clothesVariants'])
            ->firstOrFail();

        $relatedProducts = $this->wishlistService->withWishlistStatus(
            Product::where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->where('status', 'active'),
            $user
        )
            ->with(['shoe', 'cloth', 'shoesVariants', 'clothesVariants'])
            ->limit(4)
            ->get();

        return view('customer.products.show', compact('product', 'relatedProducts'));
    }
}