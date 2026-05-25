<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Review_model extends CI_Model
{
  protected $table = 'reviews';

  public function __construct()
  {
    parent::__construct();
  }

  // 🔥 Validasi apakah user ini benar-benar pernah membeli produk ini dan statusnya 'completed'
  public function check_pembelian_sah($user_id, $product_id)
  {
    $this->db->from('orders');
    $this->db->join('order_items', 'order_items.order_id = orders.id');
    $this->db->where('orders.user_id', $user_id);
    $this->db->where('order_items.product_id', $product_id);
    $this->db->where('orders.order_status', 'completed'); // Hanya boleh review jika sudah selesai
    return $this->db->get()->num_rows() > 0;
  }

  // 🔥 Simpan ulasan atau update jika sebelumnya sudah pernah mengulas
  public function simpan_ulasan($data)
  {
    // Cek apakah user sudah pernah mengulas produk ini sebelumnya
    $existing = $this->db->get_where($this->table, [
      'product_id' => $data['product_id'],
      'user_id'    => $data['user_id']
    ])->row();

    if ($existing) {
      // Jika sudah ada, kita update ulasan lamanya
      $this->db->where('id', $existing->id);
      return $this->db->update($this->table, [
        'rating'  => $data['rating'],
        'comment' => $data['comment']
      ]);
    } else {
      // Jika belum ada, kita insert data baru
      return $this->db->insert($this->table, $data);
    }
  }
}
