<form action="{{ route($product->is_wishlisted ? 'customer.wishlist.destroy' : 'customer.wishlist.store', $product) }}"
      method="POST"
      class="{{ $variant === 'detail' ? 'flex' : 'w-full sm:w-auto' }}">
    @csrf
    @if($product->is_wishlisted)
        @method('DELETE')
    @endif
    <button type="submit"
            class="wishlist-toggle flex w-full items-center justify-center rounded-lg border transition {{ $variant === 'detail' ? 'px-4 py-2.5 sm:px-6 sm:py-3' : 'p-1.5 sm:p-2' }} {{ $product->is_wishlisted ? 'border-red-300 bg-red-50 text-red-500' : 'border-gray-300 text-gray-500 hover:bg-gray-50' }}"
            aria-pressed="{{ $product->is_wishlisted ? 'true' : 'false' }}"
            aria-label="{{ $product->is_wishlisted ? 'Xóa khỏi danh sách yêu thích' : 'Thêm vào danh sách yêu thích' }}">
        <svg class="{{ $variant === 'detail' ? 'h-5 w-5' : 'h-4 w-4' }}"
             fill="{{ $product->is_wishlisted ? 'currentColor' : 'none' }}"
             stroke="currentColor"
             viewBox="0 0 24 24"
             aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
        </svg>
    </button>
</form>
