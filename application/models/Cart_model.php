<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Cart_model extends MY_Model
{

    protected $table = 'cart_items';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function get_user_cart($user_id)
    {
        $cart = $this->db->get_where('carts', ['user_id' => $user_id])->row();

        if (!$cart) {
            // 🔥 KOREKSI DI SINI: Pastikan menggunakan 'carts'
            $this->db->insert('carts', ['user_id' => $user_id]);
            $cart_id = $this->db->insert_id();
        } else {
            $cart_id = $cart->id;
        }

        // Bagian select dan join di bawah ini tetap sama
        $items = $this->db->select('cart_items.*, products.name, products.price as product_price, products.image')
            ->from('cart_items')
            ->join('products', 'products.id = cart_items.product_id')
            ->where('cart_id', $cart_id)
            ->get()
            ->result();

        $total = 0;
        foreach ($items as $item) {
            $item->price = $item->product_price;
            $item->subtotal = $item->price * $item->quantity;
            $total += $item->subtotal;
        }

        return [
            'cart_id' => $cart_id,
            'items' => $items,
            'total' => $total
        ];
    }

    public function add_item($user_id, $product_id, $quantity)
    {
        $cart = $this->get_user_cart($user_id);
        $cart_id = $cart['cart_id'];

        $existing = $this->db->get_where('cart_items', [
            'cart_id' => $cart_id,
            'product_id' => $product_id,
        ])->row();

        if ($existing) {
            $this->db->where('id', $existing->id);
            return $this->db->update('cart_items', ['quantity' => $existing->quantity + $quantity]);
        } else {
            return $this->db->insert('cart_items', [
                'cart_id' => $cart_id,
                'product_id' => $product_id,
                'quantity' => $quantity
            ]);
        }
    }
}
