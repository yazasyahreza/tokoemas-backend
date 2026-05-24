<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class API_Controller extends CI_Controller
{

    protected $user_data = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('api');
    }

    /**
     * Verify JWT Token from Authorization Header
     */
    protected function verify_token($required_role = null)
    {
        $header = $this->input->get_request_header('Authorization');

        if (!$header) {
            return json_response(false, 'Authorization header not found', null, 401);
        }

        $token = str_replace('Bearer ', '', $header);

        try {
            $raw_secret = get_jwt_secret();
            $secret_key = str_pad($raw_secret, 32, "X", STR_PAD_RIGHT);

            $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
            $this->user_data = $decoded;

            if ($required_role && $this->user_data->role !== $required_role) {
                return json_response(false, 'Forbidden: You do not have the required role', null, 403);
            }

            return true;
        } catch (Exception $e) {
            return json_response(false, 'Invalid or expired token: ' . $e->getMessage(), null, 401);
        }
    }

    /**
     * Get validated post data
     */
    protected function get_input()
    {
        $input = json_decode($this->input->raw_input_stream, true);
        return $input ?: $this->input->post();
    }
}
