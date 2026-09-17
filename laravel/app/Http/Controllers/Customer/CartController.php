<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;

class CartController extends Controller
{
    //
    protected CartService $cartService;

    public function __construct(CartService $cartService){
        $this->cartService =  $cartService;
    }

    // hien thi trang gio hang
    public function index(){
        $cart = $this->cartService->getCart();
        $cartTotal = $this->cartService->getCartTotal();

        return view('customer.cart.index', compact('cart', 'cartTotal'));
    }


     /**
     * Thêm sản phẩm vào giỏ hàng (AJAX)
     */
    // public function add(Request $request){
    //     $validator =Validator::make($request->all(), [
    //         'product_id'=> 'required|exists:products,id',
    //         'size' => 'required|string|max:20',
    //         'color' => 'required|string|max:50',
    //         'stud_type' => 'nullable|string|max:20',
    //         'quantity' => 'required|integer|min:1',
    //     ]);

    //     if($validator->fails()){
    //         return response()->json([
    //             'success'=>false,
    //             'errors'=>$validator->errors()
    //         ], 422);
    //     }

    //     try{

    //     $product = Product::findOrFail($request->product_id);
    //     if($product->product_type ==='SHOE' && empty($request->stud_type)){
    //         return response()->json([
    //             'success'=>false,
    //             'message'=>'Vui lòng chọn loại đinh'
    //         ], 400);
    //     }

    //     $cart = $this->cartService->addItem($product, [
    //             'size' => $request->size,
    //             'color' => $request->color,
    //             'stud_type' => $request->stud_type,
    //             'quantity' => $request->quantity,
    //         ]);

    //     $cartCount = $this->cartService->getCartCount();

    //     return response()->json([
    //             'success' => true,
    //             'message' => 'Đã thêm vào giỏ hàng',
    //             'cart_count' => $cartCount,
    //             'cart_total' => $this->cartService->getCartTotal(),
    //         ]);

    //     }catch(Exception $e){
    //         return response()->json([
    //             'success' => false,
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }

    // }

public function add(Request $request)
{
    $validator = Validator::make($request->all(), [
        'product_id' => 'required|exists:products,id',
        'variant_id' => 'nullable|integer',
        'variant_type' => 'nullable|in:shoe,cloth',
        'size' => 'required|string|max:20',
        'color' => 'required|string|max:50',
        'stud_type' => 'nullable|string|max:20',
        'quantity' => 'required|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $product = Product::findOrFail($request->product_id);
        
        $cart = $this->cartService->addItem($product, [
            'variant_id' => $request->variant_id,
            'variant_type' => $request->variant_type,
            'size' => $request->size,
            'color' => $request->color,
            'stud_type' => $request->stud_type,
            'quantity' => $request->quantity,
        ]);

        // Tính số lượng và tổng tiền từ items
        $cartCount = 0;
        $cartTotal = 0;
        foreach ($cart->items as $item) {
            $cartCount += $item->quantity;
            $cartTotal += $item->price * $item->quantity;
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'cart_count' => $cartCount,
            'cart_total' => $cartTotal,
        ]);

    } catch (\Exception $e) {
        \Log::error('Cart add error: ' . $e->getMessage(), [
            'request' => $request->all(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'item_id'=>'required|exists:cart_items,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'errors'=>$validator->errors(),
            ]);
        }

        try{

        $this->cartService->updateQuantity($request->item_id, $request->quantity);
        $cartCount = $this->cartService->getCartCount();
        $cartTotal = $this->cartService->getCartTotal();

        return response()->json([
            'success'=>true,
            'cart_count'=>$cartCount,
            'cart_total'=>$cartTotal
        ]);

        }catch(Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>$e->getMessage()
            ],500);
        }
    }

    public function remove(Request $request){
        $validator = Validator::make($request->all(), [
            'item_id'=>'required|exists:cart_items,id'
        ]);

        if($validator->fails()){
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ],422);
        }

        try{

            $this->cartService->removeItem($request->item_id);
            $cartCount = $this->cartService->getCartCount();
            $cartTotal = $this->cartService->getCartTotal();

            return response()->json([
                'success' => true,
                'cart_count' => $cartCount,
                'cart_total' => $cartTotal,
            ]);

        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }


    }

    public function count(){
        return response()->json([
            'cart_count'=>$this->cartService->getCartCount()
        ]);
    }
}
