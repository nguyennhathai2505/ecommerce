<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class AuthController extends Controller
{
    //

    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function showLogin(){
        return view('auth.login', [
            'checkoutData'=>session('checkout.form', []),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        $user = Auth::user();

        // Kiểm tra request muốn JSON response (từ API/Postman)
        if ($request->wantsJson()) {
            // API Response - Tạo Sanctum token
            $user->tokens()->delete();
            $token = $user->createToken('api-token')->plainTextToken;
            if ($token) {
                Log::info('Token được tạo thành công cho User ID ' . $user->id . ': ' . $token);
            } else {
                Log::warning('Token không được tạo cho User ID ' . $user->id);
            }
            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ], 200);
        }

        // Web Response - Session-based login with remember me
        $remember = $request->has('remember');
        
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        if ($user->isCustomer()) {
            $this->cartService->mergeGuestCartIntoUser($user);
        }
        
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($request->session()->pull('checkout.auth_pending', false)) {
            return redirect()->route('checkout');
        }
        return redirect()->route('home');
    }

    public function showRegister(){
        return view('auth.register', [
            'checkoutData'=>session('checkout.form', []),
        ]);
    }


    public function register(Request $request){
        $request->validate([
            'name'=>'required|string|max:255',
            'email'=>'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password'=>'required|string|min:8|confirmed',
            'terms' => 'accepted',
        ]);

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'phone' => $request->phone,
            'password'=>Hash::make($request->password),
            'role'=>'customer',
        ]);

        Auth::login($user);

        $request->session()->regenerate();
        $this->cartService->mergeGuestCartIntoUser($user);

        if ($request->session()->pull('checkout.auth_pending', false)) {
            return redirect()->route('checkout')->with('success', 'Đăng ký thành công.');
        }

        return redirect()->route('customer.dashboard')->with('success', 'Đăng ký thành công');
    }

    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('success', 'Đăng xuất thành công.');
    }

    /**
     * API Logout - Huỷ Sanctum Token
     */
    public function apiLogout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logout successful'
        ], 200);
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public function getCurrentUser(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
            ]
        ], 200);
    }
}
