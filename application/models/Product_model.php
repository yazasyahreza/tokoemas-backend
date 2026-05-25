<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends MY_Model {

    protected $table = 'products';

    public function __construct() {
        $this->load->database();
        parent::__construct();
    }

    public function get_list($filters = [], $limit = 10, $offset = 0) {
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

    public function get_detail($slug) {
        $product = $this->db->select('products.*, categories.name as category_name')
                            ->from($this->table)
                            ->join('categories', 'categories.id = products.category_id')
                            ->where('products.slug', $slug)
                            ->get()
                            ->row();

        if ($product) {
            $product->images = $this->db->get_where('product_images', ['product_id' => $product->id])->result();
            $product->variants = $this->db->get_where('product_variants', ['product_id' => $product->id])->result();
            $product->reviews = $this->db->select('reviews.*, users.name as user_name')
                                         ->from('reviews')
                                         ->join('users', 'users.id = reviews.user_id')
                                         ->where('product_id', $product->id)
                                         ->get()
                                         ->result();
            
            $rating = $this->db->select_avg('rating')->where('product_id', $product->id)->get('reviews')->row();
            $product->rating_avg = round($rating->rating ?: 0, 1);
        }

        return $product;
    }
}
