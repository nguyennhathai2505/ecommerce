<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    public function withWishlistStatus(Builder|Relation $query, User $user): Builder|Relation
    {
        return $query->withExists([
            'wishlists as is_wishlisted' => fn ($query) => $query->where('user_id', $user->id),
        ]);
    }

    public function getWishlist(User $user): LengthAwarePaginator
    {
        return Wishlist::query()
            ->where('user_id', $user->id)
            ->whereHas('product', fn ($query) => $query->where('status', 'active'))
            ->with([
                'product' => fn ($query) => $this->withWishlistStatus($query, $user)
                    ->with(['shoe', 'cloth', 'shoesVariants', 'clothesVariants']),
            ])
            ->latest()
            ->paginate(12);
    }

    public function add(User $user, Product $product): bool
    {
        if ($product->status !== 'active') {
            throw ValidationException::withMessages([
                'product' => 'Sản phẩm hiện không khả dụng.',
            ]);
        }

        $wishlist = Wishlist::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return $wishlist->wasRecentlyCreated;
    }

    public function remove(User $user, Product $product): bool
    {
        return Wishlist::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->delete() > 0;
    }

    public function count(User $user): int
    {
        return Wishlist::query()
            ->where('user_id', $user->id)
            ->whereHas('product', fn ($query) => $query->where('status', 'active'))
            ->count();
    }
}
