<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Product_model extends MY_Model
{

    protected $table = 'products';

    public function __construct()
    {
        $this->load->database();
        parent::__construct();
    }

    public function get_list($filters = [], $limit = 10, $offset = 0)
    {
        $this->db->select('products.*, categories.name as category_name');
        $this->db->from($this->table);
        $this->db->join('categories', 'categories.id = products.category_id');
        $this->db->where('products.status', 'active');

        if (!empty($filters['category_id'])) {
            $this->db->where('products.category_id', $filters['category_id']);
        }
        if (!empty($filters['search'])) {
            $this->db->like('products.name', $filters['search']);
        }
        if (!empty($filters['min_price'])) {
            $this->db->where('products.price >=', $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $this->db->where('products.price <=', $filters['max_price']);
        }

        $this->db->limit($limit, $offset);
        $this->db->order_by('products.created_at', 'DESC');

        return $this->db->get()->result();
    }

    public function get_detail($slug)
    {
        $product = $this->db->select('products.*, categories.name as category_name')
            ->from($this->table)
            ->join('categories', 'categories.id = products.category_id')
            ->where('products.slug', $slug)
            ->get()
            ->row();

        if ($product) {
            // 1. Ambil daftar ulasan beserta nama user yang mereview
            $this->db->select('reviews.*, users.name as user_name');
            $this->db->from('reviews');
            $this->db->join('users', 'users.id = reviews.user_id', 'left');
            $this->db->where('reviews.product_id', $product->id); // Sesuaikan parameter ID produk
            $this->db->order_by('reviews.created_at', 'DESC');
            $product->reviews = $this->db->get()->result();

            // 2. Hitung rata-rata rating (Bintang 1-5)
            $this->db->select_avg('rating', 'average_rating');
            $this->db->where('product_id', $product->id); // Sesuaikan parameter ID produk
            $avg_query = $this->db->get('reviews')->row();

            // Format agar tampil 1 angka di belakang koma (misal: 4.5)
            $product->average_rating = $avg_query->average_rating ? number_format($avg_query->average_rating, 1) : 0;

            // 3. Hitung total jumlah ulasan
            $product->total_reviews = count($product->reviews);
        }

        return $product;
    }
}
