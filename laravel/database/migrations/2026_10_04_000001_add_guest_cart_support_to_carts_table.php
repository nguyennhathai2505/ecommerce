<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->timestamp('expires_at')->nullable()->after('total')->index();
            $table->unique('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('carts')->whereNull('user_id')->delete();

        Schema::table('carts', function (Blueprint $table) {
            $table->dropUnique(['session_id']);
            $table->dropColumn('expires_at');
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
