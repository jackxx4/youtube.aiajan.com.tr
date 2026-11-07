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
 public function test_legal_and_contact_pages_are_available(): void {
  $this->get('/cerez-politikasi')->assertOk()->assertSee('Çerez');
  $this->get('/kvkk')->assertOk()->assertSee('KVKK');
  $this->get('/iletisim')->assertOk()->assertSee('İletişim');
 }
 public function test_deploy_artisan_endpoint_is_forbidden_without_token(): void {
  $this->post('/_deploy/artisan')->assertForbidden();
 }
}
