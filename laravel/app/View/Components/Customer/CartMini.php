<?php

namespace App\View\Components\Customer;

use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CartMini extends Component
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function render(): View
    {
        $cart = $this->cartService->getCart();
        $cartItems = $cart->items;
        $cartCount = $cartItems->sum('quantity');
        $cartTotal = $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        return view('components.customer.cart-mini', compact('cartItems', 'cartCount', 'cartTotal'));
    }
}
