<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function __construct(protected WishlistService $wishlistService)
    {
    }

    public function index(Request $request): View
    {
        $wishlists = $this->wishlistService->getWishlist($request->user());

        return view('customer.wishlist.index', compact('wishlists'));
    }

    public function count(Request $request): JsonResponse
    {
        return response()->json([
            'wishlist_count' => $this->wishlistService->count($request->user()),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $created = $this->wishlistService->add($request->user(), $product);

        return redirect()->back()->with(
            'success',
            $created
                ? 'Đã thêm sản phẩm vào danh sách yêu thích.'
                : 'Sản phẩm đã có trong danh sách yêu thích.'
        );
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $removed = $this->wishlistService->remove($request->user(), $product);

        return redirect()->back()->with(
            'success',
            $removed
                ? 'Đã xóa sản phẩm khỏi danh sách yêu thích.'
                : 'Sản phẩm không còn trong danh sách yêu thích.'
        );
    }
}
