<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->bigInteger('amount_cents');
            $table->bigInteger('balance_before_cents');
            $table->bigInteger('balance_after_cents');
            $table->uuid('transfer_group')->nullable()->index();
            $table->string('comment', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_amount_positive CHECK (amount_cents > 0)");
        DB::statement("CREATE INDEX transactions_user_created_at_idx ON transactions (user_id, created_at DESC)");
    }
    public function down(): void {
        Schema::dropIfExists('transactions');
    }
};
