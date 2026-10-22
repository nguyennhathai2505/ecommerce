<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CartService;
use App\Services\ProductService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    //
    protected CartService $cartService;
    protected ProductService $productService;

    public function __construct(CartService $cartService, ProductService $productService){
        $this->cartService =  $cartService;
        $this->productService = $productService;
    }

    // hien thi trang gio hang
    public function index(){
        $cart = $this->cartService->getCart();
        $cartTotal = $this->cartService->getCartTotal();
        $itemImages = $this->productService->getItemImages($cart->items);
        $itemIssues = $this->cartService->getItemIssues($cart);

        return view('customer.cart.index', compact('cart', 'cartTotal', 'itemImages', 'itemIssues'));
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
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'required|integer',
            'variant_type' => 'required|in:shoe,cloth',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            $product = Product::where('status', 'active')->findOrFail($data['product_id']);
            $cart = $this->cartService->addItem($product, $data);
            $cartCount = $cart->items->sum('quantity');
            $cartTotal = $cart->items->sum(fn ($item) => $item->price * $item->quantity);

            return response()->json([
                'success' => true,
                'message' => 'Đã thêm vào giỏ hàng',
                'cart_count' => $cartCount,
                'cart_total' => $cartTotal,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm hiện không khả dụng.',
            ], 404);
        } catch (Exception $e) {
            \Log::error('Cart add error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Không thể thêm sản phẩm vào giỏ hàng. Vui lòng thử lại.',
            ], 500);
        }
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'item_id'=>'required|string',
            'quantity' => 'required|integer|min:1',
        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'errors'=>$validator->errors(),
            ],422);
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

        }catch(ValidationException $e){
            return response()->json([
                'success'=>false,
                'message'=>collect($e->errors())->flatten()->first(),
                'errors'=>$e->errors(),
            ],422);
        }catch(Exception $e){
            return response()->json([
                'success'=>false,
                'message'=>$e->getMessage()
            ],500);
        }
    }

    public function remove(Request $request){
        $validator = Validator::make($request->all(), [
            'item_id'=>'required|string'
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
