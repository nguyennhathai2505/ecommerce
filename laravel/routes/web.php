<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RevenueController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Admin\ImageUploadController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ShoeVariantController;
use App\Http\Controllers\Admin\ClothVariantController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\WishlistController;
use App\View\Components\Customer\CartMini;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('customer')->group(function () {
    Route::get('/products', [CustomerProductController::class, 'index'])->name('products.index');
    Route::get('/products/{slug}', [CustomerProductController::class, 'show'])->name('products.show');

    Route::get('/cart', [CartController::class, 'index'])->middleware('shopping')->name('cart.index');

    Route::get('/checkout', [OrderController::class, 'checkout'])->middleware('shopping')->name('checkout');
    Route::post('/checkout', [OrderController::class, 'placeOrder'])->middleware('shopping')->name('checkout.place');
});

Route::middleware('shopping')->prefix('cart')->group(function () {
    Route::post('/add', [CartController::class, 'add'])->name('api.cart.add');
    Route::put('/update', [CartController::class, 'update'])->name('api.cart.update');
    Route::delete('/remove', [CartController::class, 'remove'])->name('api.cart.remove');
    Route::get('/count', [CartController::class, 'count'])->name('api.cart.count');
    Route::get('/mini', function () {
        return app(CartMini::class)->render();
    })->name('cart.mini');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/revenue', [RevenueController::class, 'index'])->name('admin.revenue.index');
    Route::get('/revenue/orders', [RevenueController::class, 'orders'])->name('admin.revenue.orders');

    // Nhập quần áo - gọi controller để truyền categories
    Route::get('/nhap-quan-ao', [ProductController::class, 'createCloth'])->name('admin.nhap-quan-ao');
    
    // Nhập giày - gọi controller để truyền categories
    Route::get('/nhap-giay', [ProductController::class, 'createShoe'])->name('admin.nhap-giay');

    Route::get('/ton-kho/quan-ao', [ProductController::class, 'inventoryClothes'])->name('admin.inventory.clothes');
    Route::get('/ton-kho/giay', [ProductController::class, 'inventoryShoes'])->name('admin.inventory.shoes');
    Route::patch('/ton-kho/san-pham/{id}/trang-thai', [ProductController::class, 'updateInventoryProductStatus'])->name('admin.inventory.products.update-status');
    Route::patch('/ton-kho/quan-ao/bien-the/{id}', [ClothVariantController::class, 'updateInventory'])->name('admin.inventory.clothes.update');
    Route::patch('/ton-kho/giay/bien-the/{id}', [ShoeVariantController::class, 'updateInventory'])->name('admin.inventory.shoes.update');
    Route::patch('/ton-kho/quan-ao/{productId}/bien-the/hang-loat', [ClothVariantController::class, 'bulkUpdateInventory'])->name('admin.inventory.clothes.bulk-update');
    Route::patch('/ton-kho/giay/{productId}/bien-the/hang-loat', [ShoeVariantController::class, 'bulkUpdateInventory'])->name('admin.inventory.shoes.bulk-update');

    Route::post('/shoes', [ProductController::class, 'storeShoe'])->name('admin.shoes.store');
    Route::post('/clothes', [ProductController::class, 'storeCloth'])->name('admin.clothes.store');

    Route::post('/upload-image', [ImageUploadController::class, 'upload'])->name('admin.upload.image');

    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/search', [AdminOrderController::class, 'search'])->name('admin.orders.search');
    Route::get('/orders/statuses', [AdminOrderController::class, 'statuses'])->middleware('throttle:120,1')->name('admin.orders.statuses');
    Route::get('/orders/{id}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::post('/orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->whereNumber('id')->name('admin.orders.update-status');


});

Route::middleware(['auth', 'role:customer'])->prefix('customer')->group(function () {
    Route::get('/dashboard', function () {
        return view('customer.dashboard');
    })->name('customer.dashboard');

    Route::get('/wishlist', [WishlistController::class, 'index'])->name('customer.wishlist.index');
    Route::get('/wishlist/count', [WishlistController::class, 'count'])->name('customer.wishlist.count');
    Route::post('/wishlist/{product}', [WishlistController::class, 'store'])->name('customer.wishlist.store');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('customer.wishlist.destroy');

    // Order history
    Route::get('/orders', [OrderController::class, 'index'])->name('customer.orders');
    Route::get('/orders/statuses', [OrderController::class, 'statuses'])->middleware('throttle:120,1')->name('customer.orders.statuses');
    Route::get('/orders/{id}', [OrderController::class, 'show'])->name('customer.orders.show');
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel'])->middleware('throttle:30,1')->whereNumber('id')->name('customer.orders.cancel');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth');
