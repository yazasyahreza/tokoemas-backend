<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Payment_model extends MY_Model
{
  protected $table = 'payment_methods';

  public function __construct()
  {
    parent::__construct();
  }

  public function get_active_methods()
  {
    // Hanya mengambil metode pembayaran yang statusnya aktif (1)
    return $this->db->where('status', 1)->get($this->table)->result();
  }
}
