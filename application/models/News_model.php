<?php
defined('BASEPATH') or exit('No direct script access allowed');

class News_model extends CI_Model
{

  public function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  public function get_published_news()
  {
    $this->db->where('status', 'published');
    $this->db->order_by('created_at', 'DESC');
    return $this->db->get('news')->result_array();
  }

  public function get_news_by_slug($slug)
  {
    $this->db->where('slug', $slug);
    $this->db->where('status', 'published'); // Pastikan hanya berita yang sudah rilis
    return $this->db->get('news')->row_array(); // Menggunakan row_array karena hanya mengambil 1 data saja
  }
}
