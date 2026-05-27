<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Voucher_model extends CI_Model
{
  protected $table = 'vouchers';

  // 🔥 Ambil semua voucher yang aktif, belum kedaluwarsa, dan kuotanya masih ada (untuk modal list)
  public function get_available_vouchers()
  {
    $now = date('Y-m-d H:i:s');
    $this->db->where('is_active', 1);
    $this->db->where('start_date <=', $now);
    $this->db->where('end_date >=', $now);
    $this->db->group_start()
      ->where('usage_limit IS NULL')
      ->or_where('used_count < usage_limit')
      ->group_end();
    return $this->db->get($this->table)->result();
  }

  // 🔥 Cari voucher spesifik berdasarkan kodenya
  public function get_voucher_by_code($code)
  {
    return $this->db->get_where($this->table, [
      'code' => strtoupper(trim($code)),
      'is_active' => 1
    ])->row();
  }

  public function increment_usage($voucher_id)
  {
    $this->db->where('id', $voucher_id);
    // Menggunakan FALSE agar CodeIgniter tidak menambahkan backtick otomatis pada string rumus
    $this->db->set('used_count', 'used_count + 1', FALSE);
    return $this->db->update('vouchers');
  }
}
