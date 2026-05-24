<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ShopAccounts extends API_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->database();
    $this->load->model('ShopAccount_model');
  }

  public function index()
  {
    // Proteksi token, hanya member yang bisa melihat daftar rekening
    $auth = $this->verify_token();
    if ($auth !== true) return $auth;

    $accounts = $this->ShopAccount_model->get_active_accounts();

    if (!empty($accounts)) {
      return json_response(true, 'Data rekening toko berhasil diambil!', $accounts);
    }

    return json_response(false, 'Data rekening toko belum disetting di database.', [], 404);
  }
}
