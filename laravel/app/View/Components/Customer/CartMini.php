<?php

namespace App\View\Components\Customer;

use App\Services\CartService;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CartMini extends Component
{
    protected CartService $cartService;
    protected ProductService $productService;

    public function __construct(CartService $cartService, ProductService $productService)
    {
        $this->cartService = $cartService;
        $this->productService = $productService;
    }

    public function render(): View
    {
        $cart = $this->cartService->getCart();
        $cartItems = $cart->items;
        $itemImages = $this->productService->getItemImages($cartItems);
        $itemIssues = $this->cartService->getItemIssues($cart);
        $cartCount = $cartItems->sum('quantity');
        $cartTotal = $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        return view('components.customer.cart-mini', compact('cartItems', 'cartCount', 'cartTotal', 'itemImages', 'itemIssues'));
    }
}
