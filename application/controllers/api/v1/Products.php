<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Products extends API_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Product_model');
    }

    public function index()
    {
        $limit = $this->input->get('limit') ?: 10;
        $page = $this->input->get('page') ?: 1;
        $offset = ($page - 1) * $limit;

        $filters = [
            'category_id' => $this->input->get('category_id'),
            'search' => $this->input->get('search'),
            'min_price' => $this->input->get('min_price'),
            'max_price' => $this->input->get('max_price'),
        ];

        $products = $this->Product_model->get_list($filters, $limit, $offset);
        $total = $this->Product_model->count_all(['status' => 'active']);

        return json_response(true, 'Products retrieved successfully', [
            'products' => $products,
            'pagination' => [
                'total' => $total,
                'limit' => (int)$limit,
                'page' => (int)$page,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function detail($slug = null)
    {
        if (!$slug) {
            return json_response(false, 'Slug is required', null, 400);
        }

        $product = $this->Product_model->get_detail($slug);

        if ($product) {
            return json_response(true, 'Product details retrieved', $product);
        }

        return json_response(false, 'Product not found', null, 404);
    }
}
