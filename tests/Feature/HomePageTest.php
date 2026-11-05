<?php
namespace Tests\Feature;
use Tests\TestCase;
class HomePageTest extends TestCase {
 public function test_home_page_is_available(): void {
  $this->get('/')->assertOk()->assertSee('Keşfet. Tanış. Bağ kur.');
 }
 public function test_login_page_is_available(): void {
  $this->get('/giris')->assertOk()->assertSee('Google ile devam et');
 }
}
