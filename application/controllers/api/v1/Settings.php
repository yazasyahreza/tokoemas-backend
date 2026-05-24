<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Settings extends CI_Controller
{

  public function __construct()
  {
    parent::__construct();
    $this->load->model('Settings_model'); // Panggil model yang baru kita buat

    // Atur header agar bisa dibaca Vue (CORS & JSON)
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Authorization");
    header('Content-Type: application/json');

    // Handle preflight request dari Axios
    if ($this->input->server('REQUEST_METHOD') === 'OPTIONS') {
      exit();
    }
  }

  public function index()
  {
    // Ambil data dari model
    $settings = $this->Settings_model->get_all_settings();

    // Kembalikan dalam format JSON
    if (!empty($settings)) {
      echo json_encode([
        'status' => true,
        'message' => 'Berhasil mengambil pengaturan',
        'data' => $settings
      ]);
    } else {
      echo json_encode([
        'status' => false,
        'message' => 'Data pengaturan kosong',
        'data' => []
      ]);
    }
  }
}
