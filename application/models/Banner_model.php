<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Banner_model extends MY_Model
{
    protected $table = 'banners';
    public function __construct()
    {
        $this->load->database();
        parent::__construct();
    }
}
