<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Reviews extends API_Controller
{
  public function __construct()
  {
    parent::__construct();
    $this->load->database();
    $this->load->model('Review_model');
  }

  public function add()
  {
    // 1. Amankan Token Login
    $auth = $this->verify_token();
    if ($auth !== true) return $auth;
    $user_id = $this->user_data->id;

    // 2. Tangkap Input dari FormData (Bukan JSON lagi)
    $product_id = $this->input->post('product_id');
    $rating     = $this->input->post('rating');
    $comment    = $this->input->post('comment');

    if (!$product_id || !$rating) {
      return json_response(false, 'Produk ID dan Rating wajib diisi bos!', null, 400);
    }

    // 3. Validasi Keaslian Transaksi
    $is_sah = $this->Review_model->check_pembelian_sah($user_id, $product_id);
    if (!$is_sah) {
      return json_response(false, 'Ilegal! Anda tidak bisa mengulas produk yang belum dibeli.', null, 403);
    }

    // 4. 🔥 PROSES UPLOAD GAMBAR (JIKA ADA)
    $image_name = null;
    if (!empty($_FILES['image']['name'])) {
      $config['upload_path']   = './uploads/reviews/';
      $config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
      $config['max_size']      = 3048; // Maks 3MB
      $config['file_name']     = 'rev_' . $user_id . '_' . time(); // Nama unik

      $this->load->library('upload', $config);

      if ($this->upload->do_upload('image')) {
        $upload_data = $this->upload->data();
        $image_name  = $upload_data['file_name'];
      } else {
        return json_response(false, 'Gagal upload gambar: ' . strip_tags($this->upload->display_errors()), null, 400);
      }
    }

    // 5. Susun Data dan Simpan
    $review_data = [
      'product_id' => $product_id,
      'user_id'    => $user_id,
      'rating'     => $rating,
      'comment'    => $comment
    ];

    // Jika ada gambar yang diupload, masukkan ke array database
    if ($image_name) {
      $review_data['image'] = $image_name;
    }

    $proses = $this->Review_model->simpan_ulasan($review_data);

    if ($proses) {
      return json_response(true, 'Ulasan dan foto berhasil disimpan bos!');
    } else {
      return json_response(false, 'Gagal menyimpan ulasan ke database server.', null, 500);
    }
  }
}
