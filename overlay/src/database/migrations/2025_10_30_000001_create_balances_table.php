<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->bigInteger('balance_cents')->default(0);
        });
        // CHECK constraint for non-negative balance (Postgres)
        DB::statement("ALTER TABLE balances ADD CONSTRAINT balances_non_negative CHECK (balance_cents >= 0)");
    }
    public function down(): void {
        Schema::dropIfExists('balances');
    }
};
