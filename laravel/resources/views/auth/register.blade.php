@extends('layouts.auth')

@section('title', 'Đăng ký')

@section('content')
<div>
    <h1 class="text-3xl font-bold text-slate-900">Tạo tài khoản mới</h1>
    <p class="text-slate-600 mt-1">Đăng ký để nhận ưu đãi và trải nghiệm mua sắm cá nhân hóa.</p>

    <!-- Hiển thị lỗi chung -->
    @if ($errors->any())
        <div class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.post') }}" class="mt-6 space-y-4" x-data="{
        password: '',
        confirmPassword: '',
        showPassword: false,
        get passwordStrength() {
            let score = 0;
            if (this.password.length >= 8) score++;
            if (/[a-z]/.test(this.password) && /[A-Z]/.test(this.password)) score++;
            if (/\d/.test(this.password)) score++;
            if (/[^a-zA-Z0-9]/.test(this.password)) score++;
            return score;
        },
        get strengthText() {
            const levels = ['Yếu', 'Trung bình', 'Khá', 'Mạnh'];
            return levels[this.passwordStrength] || '';
        },
        get strengthColor() {
            const colors = ['bg-red-500', 'bg-yellow-500', 'bg-blue-500', 'bg-green-500'];
            return colors[this.passwordStrength] || '';
        },
        get passwordsMatch() {
            return this.confirmPassword && this.password === this.confirmPassword;
        }
    }">
        @csrf

        <!-- Họ và Tên -->
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Họ và Tên</label>
            <div class="relative mt-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fas fa-user"></i>
                </span>
                <input type="text" name="name" id="name" value="{{ old('name', $checkoutData['customer_name'] ?? '') }}" placeholder="Nguyễn Văn A"
                    class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent @error('name') border-red-500 @enderror">
            </div>
            @error('name')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

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

        <!-- Số điện thoại (tùy chọn) -->
        <div>
            <label for="phone" class="block text-sm font-medium text-slate-700">Số điện thoại (tùy chọn)</label>
            <div class="relative mt-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fas fa-phone"></i>
                </span>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $checkoutData['customer_phone'] ?? '') }}" placeholder="0912 345 678"
                    class="w-full pl-10 pr-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent">
            </div>
        </div>

        <!-- Mật khẩu -->
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700">Mật khẩu</label>
            <div class="relative mt-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fas fa-lock"></i>
                </span>
                <input :type="showPassword ? 'text' : 'password'" name="password" id="password" x-model="password" placeholder="••••••••"
                    class="w-full pl-10 pr-10 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent @error('password') border-red-500 @enderror">
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>
            <!-- Thanh đo độ mạnh -->
            <div class="mt-2 h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
                <div class="h-full transition-all duration-300" :style="'width: ' + (passwordStrength / 4 * 100) + '%'" :class="strengthColor"></div>
            </div>
            <p class="mt-1 text-xs text-slate-500" x-text="password ? 'Độ mạnh: ' + strengthText : ''"></p>
            @error('password')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- Xác nhận mật khẩu -->
        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Xác nhận mật khẩu</label>
            <div class="relative mt-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fas fa-lock"></i>
                </span>
                <input :type="showPassword ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" x-model="confirmPassword" placeholder="••••••••"
                    class="w-full pl-10 pr-10 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-black focus:border-transparent">
                <span class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <i class="fas" :class="confirmPassword ? (passwordsMatch ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500') : ''"></i>
                </span>
            </div>
            <p x-show="confirmPassword && !passwordsMatch" class="mt-1 text-sm text-red-500">Mật khẩu không khớp</p>
        </div>

        <!-- Điều khoản -->
        <div>
            <label class="inline-flex items-start">
                <input type="checkbox" name="terms" value="1" class="mt-1 rounded border-slate-300 text-black focus:ring-black">
                <span class="ml-2 text-sm text-slate-600">
                    Tôi đồng ý với <a href="#" class="text-black hover:underline">Điều khoản dịch vụ</a> và <a href="#" class="text-black hover:underline">Chính sách bảo mật</a>.
                </span>
            </label>
            @error('terms')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <!-- Nút đăng ký -->
        <button type="submit" class="w-full py-3 px-4 bg-black hover:bg-slate-800 text-white font-medium rounded-lg transition duration-200">
            Tạo Tài Khoản
        </button>

        <!-- Chuyển hướng sang login -->
        <p class="text-center text-sm text-slate-600 mt-4">
            Đã có tài khoản? <a href="{{ route('login') }}" class="font-semibold text-black hover:underline">Đăng nhập ngay</a>
        </p>
    </form>
</div>
@endsection