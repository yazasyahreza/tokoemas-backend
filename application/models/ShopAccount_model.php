<?php
defined('BASEPATH') or exit('No direct script access allowed');

class ShopAccount_model extends MY_Model
{
  protected $table = 'shop_accounts';

  public function __construct()
  {
    parent::__construct();
  }

  public function get_active_accounts()
  {
    // Mengambil semua data rekening toko
    // Jika punya kolom 'status', gunakan: return $this->db->where('status', 1)->get($this->table)->result();
    return $this->db->get($this->table)->result();
  }
}
