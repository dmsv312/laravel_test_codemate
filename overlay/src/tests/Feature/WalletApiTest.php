<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function apiHeaders(): array {
    return [
        'X-Api-Key' => env('APP_API_KEY', 'test_key'),
        'Idempotency-Key' => (string) Str::uuid(),
        'Accept' => 'application/json',
    ];
}

beforeEach(function () {
    \App\Models\User::factory()->create(['id' => 1]);
    \App\Models\User::factory()->create(['id' => 2]);
});

// Deposit creates balance if missing
It('deposits and creates balance if missing', function () {
    $res = $this->postJson('/api/deposit', ['user_id' => 1, 'amount' => '10.00'], apiHeaders());
    $res->assertStatus(200)->assertJsonPath('data.balance', '10.00');
});

// Withdraw cannot go below zero
It('withdraw fails when insufficient', function () {
    $res = $this->postJson('/api/withdraw', ['user_id' => 1, 'amount' => '5.00'], apiHeaders());
    $res->assertStatus(409)->assertJsonPath('error.code', 'insufficient_funds');
});

// Transfer happy-path
It('transfer works and links transactions', function () {
    $this->postJson('/api/deposit', ['user_id' => 1, 'amount' => '10.00'], apiHeaders())->assertStatus(200);
    $res = $this->postJson('/api/transfer', ['from_user_id' => 1, 'to_user_id' => 2, 'amount' => '3.50'], apiHeaders());
    $res->assertStatus(200)->assertJsonPath('data.from.balance', '6.50')->assertJsonPath('data.to.balance', '3.50');
});

// Idempotency returns same response
It('idempotency repeats same response for same key', function () {
    $key = (string) Str::uuid();
    $h = apiHeaders();
    $h['Idempotency-Key'] = $key;
    $res1 = $this->postJson('/api/deposit', ['user_id' => 1, 'amount' => '1.00'], $h);
    $res2 = $this->postJson('/api/deposit', ['user_id' => 1, 'amount' => '1.00'], $h);
    $res1->assertStatus(200);
    $res2->assertStatus(200)->assertExactJson($res1->json());
});
