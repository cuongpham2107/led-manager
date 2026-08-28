<?php

test('mobile web route returns 200 and loads expo html', function () {
    $response = $this->get('/mobile');

    $response->assertOk()
        ->assertSee('id="root"', false)
        ->assertSee('_expo/static/js/web', false);
});

test('scanner route redirects to mobile', function () {
    $response = $this->get('/scanner');

    $response->assertRedirect('/mobile');
});
