<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * 未ログインでトップにアクセスするとログイン画面へ誘導される。
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    /**
     * ログイン画面自体は表示できる。
     */
    public function test_login_page_is_accessible(): void
    {
        $this->get('/login')->assertOk()->assertSee('Bloom');
    }
}
