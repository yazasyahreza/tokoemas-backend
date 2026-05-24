<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('json_response')) {
    /**
     * Standard JSON response for the API
     *
     * @param bool $status
     * @param string $message
     * @param mixed $data
     * @param int $http_code
     * @return void
     */
    function json_response($status = true, $message = 'Success', $data = null, $http_code = 200) {
        $ci =& get_instance();
        
        $response = [
            'status' => $status,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!$status && isset($data['errors'])) {
            $response['errors'] = $data['errors'];
            unset($response['data']);
        }

        return $ci->output
            ->set_content_type('application/json')
            ->set_status_header($http_code)
            ->set_output(json_encode($response));
    }
}

if (!function_exists('get_jwt_secret')) {
    function get_jwt_secret() {
        return getenv('JWT_SECRET') ?: 'default_secret';
    }
}
