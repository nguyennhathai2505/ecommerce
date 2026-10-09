<?php

use App\Models\Cart;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('carts:prune-guests', function () {
    $deleted = Cart::whereNull('user_id')
                   ->whereNotNull('expires_at')
                   ->where('expires_at', '<=', now())
                   ->delete();

    $this->info("Deleted {$deleted} expired guest carts.");
})->purpose('Delete expired guest carts');

Schedule::command('carts:prune-guests')->dailyAt('02:00')->withoutOverlapping();
