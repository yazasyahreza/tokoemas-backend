    <?php
    defined('BASEPATH') or exit('No direct script access allowed');

    class Order_model extends MY_Model
    {

        protected $table = 'orders';

        public function __construct()
        {
            parent::__construct();
        }

        public function process_checkout($user_id, $address_data, $order_data, $cart_items, $ktp_data)
        {
            // 🔥 SABUK PENGAMAN DIMULAI
            $this->db->trans_start();

            // TAHAP 1: Insert Alamat Pengiriman
            $this->db->insert('shipping_addresses', $address_data);
            $shipping_address_id = $this->db->insert_id();

            // Sisipkan ID Alamat ke data Order
            $order_data['shipping_address_id'] = $shipping_address_id;

            // TAHAP 2: Insert Data Pesanan Utama (Orders)
            $this->db->insert('orders', $order_data);
            $order_id = $this->db->insert_id();

            // TAHAP 3: Looping Insert Order Items & Potong Stok
            foreach ($cart_items as $item) {
                $item_data = [
                    'order_id' => $order_id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => ($item->price * $item->quantity)
                ];
                $this->db->insert('order_items', $item_data);

                // Potong Stok Emas
                $this->db->set('stock', 'stock - ' . $item->quantity, FALSE)
                    ->where('id', $item->product_id)
                    ->update('products');
            }

            // TAHAP 4: Insert atau Update Dokumen KTP
            if ($ktp_data) {
                $existing_ktp = $this->db->get_where('customer_documents', ['user_id' => $user_id, 'type' => 'ktp'])->row();
                if ($existing_ktp) {
                    $this->db->where('id', $existing_ktp->id)->update('customer_documents', ['file_path' => $ktp_data['file_path'], 'status' => 'pending']);
                } else {
                    $this->db->insert('customer_documents', $ktp_data);
                }
            }

            // TAHAP 5: Pembasmian Keranjang Belanja
            $cart = $this->db->get_where('carts', ['user_id' => $user_id])->row();
            if ($cart) {
                $this->db->delete('cart_items', ['cart_id' => $cart->id]);
            }

            // TAHAP 6: Pre-populate Data Transaksi Finansial (transactions)
            $transaction_data = [
                'order_id' => $order_id,
                'type'     => 'payment', // Sesuai instruksi, otomatis 'payment'
                'amount'   => $order_data['grand_total'] // Ambil dari total belanja
                // transaction_date otomatis terisi oleh database (CURRENT_TIMESTAMP)
            ];
            $this->db->insert('transactions', $transaction_data);

            // TAHAP 7: Pre-populate Data Pengiriman (shipments)
            // Kita intip nama kurirnya dari tabel shipping_methods
            $courier_info = $this->db->get_where('shipping_methods', ['id' => $order_data['shipping_method_id']])->row();

            $shipment_data = [
                'order_id'        => $order_id,
                'courier'         => $courier_info ? strtoupper($courier_info->code) : 'LAINNYA',
                'service'         => $courier_info ? $courier_info->name : 'Layanan Standar',
                'shipping_status' => 'Processing' // Sesuai instruksi, otomatis 'Processing'
                // tracking_number, shipped_at, delivered_at sengaja tidak di-insert agar bernilai NULL
            ];
            $this->db->insert('shipments', $shipment_data);

            // 🔥 SABUK PENGAMAN DIKUNCI
            $this->db->trans_complete();

            // Return True jika ke-7 Tahap sukses semua, Return False jika ada yang gagal dan di-rollback
            return $this->db->trans_status();
        }

        public function get_user_orders($user_id)
        {
            // Tambahkan list_produk_detail dan courier_name
            $this->db->select('orders.*, 
                       GROUP_CONCAT(CONCAT(order_items.product_name, "(", order_items.quantity, "x)") SEPARATOR ", ") as list_produk_detail,
                       shipping_methods.name as courier_name'); // Tambahkan ini
            $this->db->from($this->table);
            $this->db->join('order_items', 'order_items.order_id = orders.id', 'left');
            // Join ke tabel shipping_methods untuk ambil nama logistik
            $this->db->join('shipping_methods', 'orders.shipping_method_id = shipping_methods.id', 'left');
            $this->db->where('orders.user_id', $user_id);
            $this->db->group_by('orders.id');
            $this->db->order_by('orders.created_at', 'DESC');

            return $this->db->get()->result();
        }

        public function get_order_detail($order_id, $user_id = null)
        {
            $this->db->select("
            orders.*, 
            CONCAT(shipping_addresses.address_detail, ', ', shipping_addresses.district, ', ', shipping_addresses.city, ', ', shipping_addresses.province, ' ', shipping_addresses.postal_code) as shipping_address, 
            shop_accounts.name as shop_account, 
            shipping_methods.name as courier_name,
            shipping_methods.cost as shipping_cost, 
            shipments.courier, 
            shipments.tracking_number, 
            shipments.shipping_status,
            shipments.shipped_at,
            shipments.delivered_at,
            shipments.service
        ");
            $this->db->from($this->table);
            $this->db->join('shipping_addresses', 'shipping_addresses.id = orders.shipping_address_id', 'left');
            $this->db->join('shop_accounts', 'shop_accounts.id = orders.shop_accounts_id', 'left');
            $this->db->join('shipping_methods', 'shipping_methods.id = orders.shipping_method_id', 'left');
            $this->db->join('shipments', 'shipments.order_id = orders.id', 'left');

            $this->db->where('orders.id', $order_id);

            if ($user_id) {
                $this->db->where('orders.user_id', $user_id);
            }

            $order = $this->db->get()->row();

            if ($order) {
                // Ambil nomor whatsapp dinamis dari tabel settings
                $setting_wa = $this->db->get_where('settings', ['key' => 'shop_whatsapp'])->row();
                $order->shop_whatsapp = $setting_wa ? $setting_wa->value : '';

                // Ambil PPN/Service Fee dari tabel settings ke variabel admin_fee
                $setting_fee = $this->db->get_where('settings', ['key' => 'service_fee'])->row();
                $order->admin_fee = $setting_fee ? (int)$setting_fee->value : 0;

                // Mengambil detail produk (items)
                $this->db->select('order_items.*, products.image, products.weight');
                $this->db->from('order_items');
                $this->db->join('products', 'products.id = order_items.product_id', 'left');
                $this->db->where('order_items.order_id', $order_id);
                $order->items = $this->db->get()->result();
            }

            return $order;
        }

        public function get_user_transactions($user_id)
        {
            $this->db->select('
            transactions.id,
            transactions.type,
            transactions.amount,
            transactions.transaction_date as created_at,
            orders.id as order_id,
            orders.invoice_no,
            orders.payment_status,
            shop_accounts.name as shop_account_name
        ');
            $this->db->from('transactions');
            $this->db->join('orders', 'orders.id = transactions.order_id');
            $this->db->join('shop_accounts', 'shop_accounts.id = orders.shop_accounts_id', 'left');
            $this->db->where('orders.user_id', $user_id);
            $this->db->order_by('transactions.transaction_date', 'DESC');

            return $this->db->get()->result();
        }
    }
