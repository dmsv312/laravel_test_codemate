<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 128);
            $table->string('request_fingerprint', 64);
            $table->unsignedSmallInteger('status_code');
            $table->json('response_json');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['key', 'request_fingerprint']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('idempotency_keys');
    }
};
