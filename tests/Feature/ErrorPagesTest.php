<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_custom_404_page_renders_correctly(): void
    {
        $response = $this->get('/bu-sayfa-kesinlikle-yok-404-test');
        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee('Aradığınız Sayfa Mevcut Değil');
        $response->assertSee('Ana Sayfaya Dön');
    }
}
