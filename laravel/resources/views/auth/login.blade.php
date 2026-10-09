@extends('layouts.auth')

@section('title', 'Đăng nhập')

@section('content')
<div>
    <h1 class="text-3xl font-bold text-slate-900">Đăng nhập</h1>
    <p class="text-slate-600 mt-1">Chào mừng bạn quay trở lại!</p>

    <!-- Hiển thị thông báo lỗi chung -->
    @if ($errors->any())
        <div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form -->
    <form method="POST" action="{{ route('login.post') }}" class="mt-6 space-y-5" x-data="{ showPassword: false }">
        @csrf

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
            <div class="relative mt-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fas fa-envelope"></i>
                </span>
                <input type="email" name="email" id="email" value="{{ old('email', $checkoutData['customer_email'] ?? '') }}" placeholder="example@domain.com"
                    class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent @error('email') border-red-500 @enderror">
            </div>
            @error('email')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- Mật khẩu -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Mật khẩu</label>
            <div class="relative mt-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fas fa-lock"></i>
                </span>
                <input :type="showPassword ? 'text' : 'password'" name="password" id="password" placeholder="••••••••"
                    class="w-full pl-10 pr-10 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent @error('password') border-red-500 @enderror">
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            @error('password')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- Ghi nhớ + Quên mật khẩu -->
        <div class="flex items-center justify-between">
            <label class="inline-flex items-center">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-black focus:ring-black">
                <span class="ml-2 text-sm text-slate-600">Ghi nhớ đăng nhập</span>
            </label>
            <a href="#" class="text-sm text-slate-700 hover:underline">Quên mật khẩu?</a>
        </div>

        <!-- Nút đăng nhập -->
        <button type="submit" class="w-full py-3 px-4 bg-black hover:bg-slate-800 text-white font-medium rounded-lg transition duration-200 flex items-center justify-center">
            Đăng Nhập
        </button>

        <!-- Social Login -->
        <div class="relative flex items-center my-4">
            <div class="flex-1 border-t border-slate-200"></div>
            <span class="px-4 text-sm text-slate-400">Hoặc đăng nhập bằng</span>
            <div class="flex-1 border-t border-slate-200"></div>
        </div>
        <div class="flex gap-3">
            <button type="button" class="flex-1 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition flex items-center justify-center gap-2">
                <i class="fab fa-google text-red-500"></i> Google
            </button>
            <button type="button" class="flex-1 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition flex items-center justify-center gap-2">
                <i class="fab fa-facebook text-blue-600"></i> Facebook
            </button>
        </div>

        <!-- Chuyển hướng sang đăng ký -->
        <p class="text-center text-sm text-slate-600 mt-4">
            Chưa có tài khoản? <a href="{{ route('register') }}" class="font-semibold text-black hover:underline">Đăng ký ngay</a>
        </p>
    </form>
</div>
@endsection