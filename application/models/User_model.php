<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends MY_Model
{

    protected $table = 'users';

    public function __construct()
    {
        parent::__construct();
    }

    public function login($email, $password)
    {
        $user = $this->db->select('users.*, roles.name as role')
            ->from($this->table)
            ->join('roles', 'roles.id = users.role_id')
            ->where('email', $email)
            ->get()
            ->row();

        if ($user && password_verify($password, $user->password)) {
            unset($user->password);
            return $user;
        }

        return false;
    }

    public function get_profile($user_id)
    {
        $user = $this->db->select('users.id, users.name, users.email, users.phone, users.avatar, users.status, roles.name as role')
            ->from($this->table)
            ->join('roles', 'roles.id = users.role_id')
            ->where('users.id', $user_id)
            ->get()
            ->row();
        return $user;
    }

    public function email_exists($email)
    {
        return $this->db->where('email', $email)->count_all_results($this->table) > 0;
    }

    public function get_by_email($email)
    {
        // Mengembalikan data user jika email ditemukan
        return $this->db->where('email', $email)->get($this->table)->row_array();
    }

    public function insert($data)
    {
        // Mengeksekusi query INSERT ke tabel users
        return $this->db->insert($this->table, $data);
    }
}
