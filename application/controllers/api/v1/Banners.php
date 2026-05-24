<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Banners extends API_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Banner_model');
    }

    public function index() {
        $banners = $this->Banner_model->get_where(['status' => 1]);
        return json_response(true, 'Banners retrieved', $banners);
    }
}
