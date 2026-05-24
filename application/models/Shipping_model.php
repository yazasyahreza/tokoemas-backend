<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Shipping_model extends MY_Model
{
  protected $table = 'shipping_methods';

  public function __construct()
  {
    parent::__construct();
  }

  public function get_active_methods()
  {
    // Hanya mengambil kurir yang statusnya aktif (1)
    return $this->db->where('status', 1)->get($this->table)->result();
  }
}
