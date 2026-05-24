<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Model extends CI_Model {

    protected $table = '';
    protected $primary_key = 'id';

    public function __construct() {
        parent::__construct();
    }

    public function get_all($limit = null, $offset = null, $order_by = null) {
        if ($order_by) {
            $this->db->order_by($order_by);
        }
        return $this->db->get($this->table, $limit, $offset)->result();
    }

    public function get_by_id($id) {
        return $this->db->get_where($this->table, [$this->primary_key => $id])->row();
    }

    public function get_where($where, $limit = null, $offset = null) {
        return $this->db->get_where($this->table, $where, $limit, $offset)->result();
    }

    public function insert($data) {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where($this->primary_key, $id);
        return $this->db->update($this->table, $data);
    }

    public function delete($id) {
        $this->db->where($this->primary_key, $id);
        return $this->db->delete($this->table);
    }

    public function count_all($where = null) {
        if ($where) {
            $this->db->where($where);
        }
        return $this->db->count_all_results($this->table);
    }
}
