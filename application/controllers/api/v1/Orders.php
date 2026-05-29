<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Orders extends API_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->model(['Order_model', 'Cart_model']);
    }

    public function index()
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $orders = $this->Order_model->get_user_orders($this->user_data->id);
        return json_response(true, 'Orders retrieved', $orders);
    }

    public function detail($id)
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $order = $this->Order_model->get_order_detail($id, $this->user_data->id);
        if ($order) {
            return json_response(true, 'Order detail retrieved', $order);
        }
        return json_response(false, 'Order not found', null, 404);
    }

    public function checkout()
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;
        $user_id = $this->user_data->id;

        // 1. Ambil Keranjang User (Keamanan: Jangan percaya total harga dari Frontend)
        $cart = $this->db->get_where('carts', ['user_id' => $user_id])->row();
        if (!$cart) return json_response(false, 'Keranjang belanja tidak ditemukan.', null, 400);

        $cart_items = $this->db->select('cart_items.*, products.name, products.price, products.weight')
            ->from('cart_items')
            ->join('products', 'products.id = cart_items.product_id')
            ->where('cart_id', $cart->id)
            ->get()->result();

        if (empty($cart_items)) return json_response(false, 'Keranjang belanja kosong!', null, 400);

        // 2. Tangkap Input POST dari FormData Vue
        $p = $this->input->post();

        if (empty($p['targetBank']) || empty($p['selectedCourier']) || empty($p['selectedBank'])) {
            return json_response(false, 'Metode pembayaran, kurir, atau bank tujuan belum dipilih.', null, 400);
        }

        // 3. Kalkulasi Ulang Harga Asli di Backend
        $subtotal = 0;
        $total_weight = 0;

        foreach ($cart_items as $item) {
            $subtotal += ($item->price * $item->quantity);
            $total_weight += ($item->weight * $item->quantity);
        }

        $courier = $this->db->get_where('shipping_methods', ['id' => $p['selectedCourier']])->row();
        $shipping_cost = $courier ? $courier->cost : 0;

        $tax_setting = $this->db->get_where('settings', ['key' => 'tax_percentage'])->row();
        $tax_fee = $tax_setting ? $tax_setting->value : 0;

        // =================================================================
        // 🔥 4. LOGIKA VALIDASI VOUCHER & DISKON (ANTI-FRAUD)
        // =================================================================
        $voucher_id = isset($p['voucher_id']) && $p['voucher_id'] !== '' ? $p['voucher_id'] : null;
        $discount_amount = 0;

        if ($voucher_id) {
            $voucher = $this->db->get_where('vouchers', ['id' => $voucher_id, 'is_active' => 1])->row();

            if ($voucher) {
                $now = date('Y-m-d H:i:s');

                // Cek Riwayat Pemakaian User di Database (Mencegah Pemakaian Berulang)
                $this->db->where('user_id', $user_id);
                $this->db->where('voucher_id', $voucher_id);
                $this->db->where_not_in('order_status', ['cancelled', 'failed']); // Berikan kesempatan jika dulu batal
                $already_used = $this->db->get('orders')->num_rows() > 0;

                // Syarat Berlapis: Waktu Aktif AND Kuota Global Tersedia AND Min Belanja Cukup AND User Belum Pernah Pakai
                if (
                    $now >= $voucher->start_date && $now <= $voucher->end_date &&
                    ($voucher->usage_limit === NULL || $voucher->used_count < $voucher->usage_limit) &&
                    $subtotal >= $voucher->min_purchase &&
                    !$already_used
                ) {

                    // Hitung nominal potongan
                    if ($voucher->discount_type === 'fixed') {
                        $discount_amount = floatval($voucher->discount_value);
                    } else if ($voucher->discount_type === 'percent') {
                        $discount_amount = ($voucher->discount_value / 100) * $subtotal;
                        if ($voucher->max_discount !== NULL && $discount_amount > $voucher->max_discount) {
                            $discount_amount = floatval($voucher->max_discount);
                        }
                    }
                } else {
                    // Beri pesan detail kenapa ditolak saat detik-detik terakhir
                    $pesan_error = $already_used ? 'Anda sudah pernah memakai voucher ini.' : 'Voucher kedaluwarsa, kuota habis, atau syarat tidak terpenuhi.';
                    return json_response(false, 'Transaksi ditolak: ' . $pesan_error, null, 400);
                }
            } else {
                return json_response(false, 'Transaksi ditolak: Voucher tidak terdaftar di sistem.', null, 404);
            }
        }

        // Kalkulasi Akhir (Pastikan tidak minus)
        $grand_total = $subtotal + $shipping_cost + $tax_fee - $discount_amount;
        if ($grand_total < 0) $grand_total = 0;

        // =================================================================
        // 5. PROSES UPLOAD GAMBAR 
        // =================================================================
        $this->load->library('upload');

        // A. Upload Bukti Pembayaran (Wajib)
        $payment_proof_name = null;
        if (!empty($_FILES['buktiBlobFile']['name'])) {
            $config['upload_path'] = FCPATH . 'uploads/payment_proofs/';
            $config['allowed_types'] = 'jpg|jpeg|png|webp';
            $config['file_name'] = 'proof_' . $user_id . '_' . time();
            $this->upload->initialize($config);

            if ($this->upload->do_upload('buktiBlobFile')) {
                $payment_proof_name = $this->upload->data('file_name');
            } else {
                return json_response(false, 'Gagal upload bukti bayar: ' . strip_tags($this->upload->display_errors()), null, 400);
            }
        } else {
            return json_response(false, 'File bukti pembayaran wajib diunggah!', null, 400);
        }

        // B. Upload KTP 
        $ktp_name = null;
        if (!empty($_FILES['ktpBlobFile']['name'])) {
            $config_ktp['upload_path'] = FCPATH . 'uploads/documents/';
            $config_ktp['allowed_types'] = 'jpg|jpeg|png|webp';
            $config_ktp['file_name'] = 'ktp_' . $user_id . '_' . time();
            $this->upload->initialize($config_ktp);

            if ($this->upload->do_upload('ktpBlobFile')) {
                $ktp_name = $this->upload->data('file_name');
            }
        }

        // =================================================================
        // 6. SUSUN DATA UNTUK DATABASE
        // =================================================================
        $address_data = [
            'user_id' => $user_id,
            'receiver_name' => trim($p['firstName'] . ' ' . $p['lastName']),
            'phone' => $p['phone'],
            'province' => $p['province'],
            'city' => $p['regency'],
            'district' => $p['district'],
            'postal_code' => $p['postalCode'],
            'address_detail' => trim($p['fullAddress']) . ' (Desa/Kel: ' . $p['village'] . ')',
            'is_default' => 1
        ];

        $order_data = [
            'invoice_no' => !empty($p['invoiceNumber']) ? $p['invoiceNumber'] : 'INV/' . date('Ymd') . '/' . strtoupper(bin2hex(random_bytes(3))),
            'user_id' => $user_id,
            'shop_account_id' => $p['targetBank'],
            'shipping_method_id' => $p['selectedCourier'],
            'payment_method_id' => $p['selectedBank'],
            'total_weight' => $total_weight,

            // 🔥 REKAM JEJAK HARGA & VOUCHER
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping_cost,
            'admin_fee' => $tax_fee,
            'voucher_id' => $voucher_id,             // Menyimpan ID Voucher (bisa NULL)
            'discount_amount' => $discount_amount,   // Menyimpan nominal potongan
            'grand_total' => $grand_total,           // Total setelah didiskon

            'order_status' => 'pending',
            'account_number' => $p['accountNumber'],
            'account_name' => $p['accountName'],
            'payment_status' => 'unpaid',
            'payment_proof' => $payment_proof_name,
            'notes' => isset($p['note']) ? $p['note'] : ''
        ];

        $ktp_data = null;
        if ($ktp_name) {
            $ktp_data = [
                'user_id' => $user_id,
                'type' => 'ktp',
                'file_path' => $ktp_name,
                'status' => 'pending'
            ];
        }

        // =================================================================
        // 7. TEMBAK KE MODEL DENGAN TRANSACTION WRAPPER
        // =================================================================
        // Membuka payung pelindung. Jika ada error di tengah jalan, semua batal otomatis.
        $this->db->trans_start();

        // 🔥 PERBAIKAN: Pastikan 5 variabel ini terkirim semua ke Model, termasuk $ktp_data!
        $insert_status = $this->Order_model->process_checkout($user_id, $address_data, $order_data, $cart_items, $ktp_data);

        // 🔥 JIKA ORDER SUKSES DIBUAT, POTONG KUOTA VOUCHER!
        if ($insert_status && $voucher_id) {
            $this->db->set('used_count', 'used_count + 1', FALSE); // Atomik anti bentrok
            $this->db->where('id', $voucher_id);
            $this->db->update('vouchers');
        }

        // Menutup payung pelindung dan meresmikan/commit seluruh kueri.
        $this->db->trans_complete();

        // =================================================================
        // 8. FINALISASI RESPONSE
        // =================================================================
        if ($this->db->trans_status() === FALSE || !$insert_status) {
            // Jika transaksi gagal, hapus gambar yang telanjur terupload agar tidak jadi sampah
            @unlink(FCPATH . 'uploads/payment_proofs/' . $payment_proof_name);
            if ($ktp_name) @unlink(FCPATH . 'uploads/documents/' . $ktp_name);

            return json_response(false, 'Terjadi kegagalan sistem saat menyimpan pesanan ke database.', null, 500);
        }

        return json_response(true, 'Pesanan berhasil dibuat!', [
            'invoice_no'      => $order_data['invoice_no'],
            'grand_total'     => $grand_total,
            'discount_amount' => $discount_amount,
            'subtotal'        => $subtotal,
            'shipping_cost'   => $shipping_cost,
            'admin_fee'       => $tax_fee
        ]);
    }

    public function upload_payment_proof($id)
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        // Simplified upload logic for demonstration
        $config['upload_path'] = './uploads/payments/';
        $config['allowed_types'] = 'jpg|jpeg|png|webp';
        $config['encrypt_name'] = TRUE;

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, true);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('payment_proof')) {
            return json_response(false, $this->upload->display_errors('', ''), null, 400);
        }

        $upload_data = $this->upload->data();
        $this->Order_model->update($id, [
            'payment_proof' => $upload_data['file_name'],
            'payment_status' => 'unpaid' // Still unpaid until verified by admin
        ]);

        return json_response(true, 'Payment proof uploaded successfully');
    }

    public function transactions()
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        // Memanggil fungsi baru di Order_model
        $transactions = $this->Order_model->get_user_transactions($this->user_data->id);
        return json_response(true, 'Transactions retrieved', $transactions);
    }
}
