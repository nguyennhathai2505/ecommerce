@extends('layouts.customer')

@section('title', 'Danh sách yêu thích')

@section('breadcrumb')
    @include('customer.partials.breadcrumb', ['items' => [
        ['label' => 'Tài khoản', 'url' => route('customer.dashboard')],
        ['label' => 'Danh sách yêu thích'],
    ]])
@endsection

@section('content')
    <div class="mb-6 flex items-center gap-3">
        <svg class="h-7 w-7 text-red-500" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25A5.25 5.25 0 017.5 3c1.566 0 2.953.76 3.817 1.93A5.25 5.25 0 0115.135 3a5.25 5.25 0 015.25 5.25c0 3.925-2.438 7.11-4.74 9.257a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.218l-.022.012-.007.004-.003.001a.752.752 0 01-.684 0l-.003-.001z" />
        </svg>
        <h1 class="text-2xl font-bold text-gray-900">Sản phẩm yêu thích</h1>
        <span class="text-gray-400">({{ $wishlists->total() }} sản phẩm)</span>
    </div>

    @if($wishlists->isNotEmpty())
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4">
            @foreach($wishlists as $wishlist)
                @include('customer.partials.product-card', [
                    'product' => $wishlist->product,
                ])
            @endforeach
        </div>

        @if($wishlists->hasPages())
            <div class="mt-6">
                {{ $wishlists->links('customer.partials.pagination') }}
            </div>
        @endif
    @else
        <div class="flex min-h-[380px] items-center justify-center rounded-2xl border border-gray-200 px-4">
            <div class="text-center">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
                    <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-xl font-bold text-gray-900">Chưa có sản phẩm yêu thích</h2>
                <p class="mt-2 text-gray-500">Nhấn vào biểu tượng trái tim trên sản phẩm để lưu vào đây.</p>
                <a href="{{ route('products.index') }}" class="mt-7 inline-flex rounded-lg bg-gray-900 px-6 py-3 font-medium text-white transition hover:bg-black">
                    Khám phá sản phẩm
                </a>
            </div>
        </div>
    @endif
@endsection
