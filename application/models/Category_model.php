<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Category_model extends MY_Model
{
    protected $table = 'categories';
    public function __construct()
    {
        $this->load->database();
        parent::__construct();
    }
}
