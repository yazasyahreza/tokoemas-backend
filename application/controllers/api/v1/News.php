<?php
defined('BASEPATH') or exit('No direct script access allowed');

class News extends CI_Controller
{

  public function __construct()
  {
    parent::__construct();
    $this->load->model('News_model');

    // CORS Headers
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Authorization");
    header('Content-Type: application/json');

    if ($this->input->server('REQUEST_METHOD') === 'OPTIONS') {
      exit();
    }
  }

  public function index()
  {
    $news = $this->News_model->get_published_news();

    if (!empty($news)) {
      echo json_encode([
        'status' => true,
        'message' => 'Berhasil mengambil berita',
        'data' => $news
      ]);
    } else {
      echo json_encode([
        'status' => false,
        'message' => 'Belum ada berita',
        'data' => []
      ]);
    }
  }

  public function detail($slug = null)
  {
    // Jaga-jaga jika slug kosong
    if (empty($slug)) {
      echo json_encode([
        'status' => false,
        'message' => 'Slug berita tidak boleh kosong',
        'data' => null
      ]);
      return;
    }

    // Panggil fungsi model yang baru kita buat
    $news = $this->News_model->get_news_by_slug($slug);

    if (!empty($news)) {
      echo json_encode([
        'status' => true,
        'message' => 'Berhasil mengambil detail berita',
        'data' => $news
      ]);
    } else {
      echo json_encode([
        'status' => false,
        'message' => 'Berita tidak ditemukan',
        'data' => null
      ]);
    }
  }
}
