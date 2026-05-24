<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cart extends API_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Cart_model');
    }

    public function index()
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $cart = $this->Cart_model->get_user_cart($this->user_data->id);
        return json_response(true, 'Cart retrieved successfully', $cart);
    }

    public function add()
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $input = $this->get_input();
        $product_id = isset($input['product_id']) ? $input['product_id'] : null;
        $quantity = isset($input['quantity']) ? $input['quantity'] : 1;

        if (!$product_id) {
            return json_response(false, 'Product ID is required', null, 400);
        }

        if ($this->Cart_model->add_item($this->user_data->id, $product_id, $quantity)) {
            return json_response(true, 'Item added to cart');
        }

        return json_response(false, 'Failed to add item', null, 500);
    }

    public function update($item_id)
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $input = $this->get_input();
        $quantity = isset($input['quantity']) ? $input['quantity'] : 1;

        if ($this->Cart_model->update($item_id, ['quantity' => $quantity])) {
            return json_response(true, 'Cart updated');
        }

        return json_response(false, 'Failed to update cart', null, 500);
    }

    public function delete($item_id)
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        if ($this->Cart_model->delete($item_id)) {
            return json_response(true, 'Item removed from cart');
        }

        return json_response(false, 'Failed to remove item', null, 500);
    }

    public function sync()
    {
        // Pastikan user sudah login
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $input = $this->get_input();
        // Tangkap array item dari LocalStorage Vue
        $items = isset($input['items']) ? $input['items'] : [];

        if (empty($items) || !is_array($items)) {
            return json_response(false, 'Tidak ada keranjang yang perlu disinkronisasi', null, 400);
        }

        $success_count = 0;

        // Looping semua barang dari LocalStorage dan masukkan ke database
        foreach ($items as $item) {
            // Sesuaikan key 'id' atau 'product_id' dari format JSON Vue
            $product_id = isset($item['product_id']) ? $item['product_id'] : (isset($item['id']) ? $item['id'] : null);
            $quantity = isset($item['quantity']) ? $item['quantity'] : (isset($item['qty']) ? $item['qty'] : 1);

            if ($product_id) {
                if ($this->Cart_model->add_item($this->user_data->id, $product_id, $quantity)) {
                    $success_count++;
                }
            }
        }

        return json_response(true, "$success_count barang berhasil disinkronisasi ke keranjang akun");
    }
}
