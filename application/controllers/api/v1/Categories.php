<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Categories extends API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Category_model');
    }

    public function index() {
        $categories = $this->Category_model->get_all();
        return json_response(true, 'Categories retrieved successfully', $categories);
    }
}
