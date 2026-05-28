<?php

use function Pest\Laravel\getJson;

test('health endpoint returns ok', function () {
    getJson('/api/v1/health')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'service' => 'skontro-api',
        ])
        ->assertJsonStructure([
            'status',
            'service',
            'version',
            'timestamp',
        ]);
});
