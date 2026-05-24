<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Settings_model extends CI_Model
{

  public function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  // Fungsi untuk mengambil semua data pengaturan
  public function get_all_settings()
  {
    return $this->db->get('settings')->result_array();
  }
}
