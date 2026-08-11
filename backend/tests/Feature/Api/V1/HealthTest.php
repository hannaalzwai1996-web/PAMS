<?php

test('the versioned health check responds with the standard envelope', function () {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonStructure(['data' => ['status', 'timestamp']])
        ->assertJsonPath('data.status', 'ok');
});
