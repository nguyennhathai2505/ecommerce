<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Admin\ImageUploadController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\WishlistController;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    // Nhập quần áo - gọi controller để truyền categories
    Route::get('/nhap-quan-ao', [ProductController::class, 'createCloth'])->name('admin.nhap-quan-ao');
    
    // Nhập giày - gọi controller để truyền categories
    Route::get('/nhap-giay', [ProductController::class, 'createShoe'])->name('admin.nhap-giay');

    Route::get('/ton-kho/quan-ao', [ProductController::class, 'inventoryClothes'])->name('admin.inventory.clothes');
    Route::get('/ton-kho/giay', [ProductController::class, 'inventoryShoes'])->name('admin.inventory.shoes');

    Route::post('/shoes', [ProductController::class, 'storeShoe'])->name('admin.shoes.store');
    Route::post('/clothes', [ProductController::class, 'storeCloth'])->name('admin.clothes.store');

    Route::post('/upload-image', [ImageUploadController::class, 'upload'])->name('admin.upload.image');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/search', [AdminOrderController::class, 'search'])->name('admin.orders.search');
    Route::get('/orders/{id}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::post('/orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.update-status');


});

Route::middleware(['auth', 'role:customer'])->prefix('customer')->group(function () {
    Route::get('/dashboard', function () {
        return view('customer.dashboard');
    })->name('customer.dashboard');

    // Customer Routes
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/products', [CustomerProductController::class, 'index'])->name('products.index');
    Route::get('/products/{slug}', [CustomerProductController::class, 'show'])->name('products.show');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('customer.wishlist.index');
    Route::get('/wishlist/count', [WishlistController::class, 'count'])->name('customer.wishlist.count');
    Route::post('/wishlist/{product}', [WishlistController::class, 'store'])->name('customer.wishlist.store');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('customer.wishlist.destroy');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    
    // Checkout routes
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [OrderController::class, 'placeOrder'])->name('checkout.place');

    // Order history
    Route::get('/orders', [OrderController::class, 'index'])->name('customer.orders');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('customer.orders.show');
});

Route::middleware(['auth', 'role:customer'])->prefix('cart')->group(function (){
    Route::post('/add', [CartController::class, 'add'])->name('api.cart.add');
    Route::put('/update', [CartController::class, 'update'])->name('api.cart.update');
    Route::delete('/remove', [CartController::class, 'remove'])->name('api.cart.remove');
    Route::get('/count', [CartController::class, 'count'])->name('api.cart.count');
    // Cart mini - partial view
    Route::get('/mini', function () {
        return view('customer.partials.cart-mini');
    })->name('cart.mini');
}) ;



Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth');
