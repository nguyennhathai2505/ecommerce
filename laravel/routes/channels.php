<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.orders', function (User $user) {
    return $user->isAdmin();
});

Broadcast::channel('customer.orders.{userId}', function (User $user, string $userId) {
    return $user->isCustomer() && (string) $user->id === $userId;
});
