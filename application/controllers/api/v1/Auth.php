<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Memastikan vendor autoload dari Composer termuat sempurna dari root proyek Laragon
if (file_exists(APPPATH . '../vendor/autoload.php')) {
    require_once APPPATH . '../vendor/autoload.php';
} elseif (file_exists(FCPATH . 'vendor/autoload.php')) {
    require_once FCPATH . 'vendor/autoload.php';
}

class Auth extends API_Controller
{
    /**
     * Konstruktor Utama
     * Urusan CORS sudah ditangani penuh & bersih oleh .htaccess terluar.
     */
    public function __construct()
    {
        parent::__construct();

        // Memastikan driver database aktif di latar belakang
        $this->load->database();

        // Load model dan library bawaan yang dibutuhkan
        $this->load->model('User_model');
        $this->load->library('form_validation');
    }

    /**
     * Endpoint: Login User
     */
    public function login()
    {
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Credentials: true");

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            set_status_header(200);
            exit;
        }

        $input = $this->get_input();
        $email = isset($input['email']) ? trim($input['email']) : null;
        $password = isset($input['password']) ? $input['password'] : null;

        if (empty($email) || empty($password)) {
            return json_response(false, 'Email dan password wajib diisi.', null, 400);
        }

        // 🔥 KITA KEMBALIKAN KE FUNGSI ASLI MODEL BACKEND AGAR TIDAK EROR 500 LAGI
        $user = $this->User_model->login($email, $password);

        // Jika user ditemukan lewat login() model bawaan, langsung loloskan
        if ($user) {

            // Siapkan susunan payload untuk JWT token
            $payload = [
                'id'    => $user->id,
                'email' => $user->email,
                'role'  => isset($user->role) ? $user->role : 'customer',
                'iat'   => time(),
                'exp'   => time() + 86400
            ];

            // Konfigurasi secret key pengaman token bawaan proyek
            $raw_secret = get_jwt_secret();
            $secret_key = str_pad($raw_secret, 32, "X", STR_PAD_RIGHT);

            // Generate token JWT murni secara aman
            $token = \Firebase\JWT\JWT::encode($payload, $secret_key, 'HS256');

            // Susun data user untuk dikembalikan ke frontend Vue
            $user_data = [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'role'  => isset($user->role) ? $user->role : 'member'
            ];

            return json_response(true, 'Login Berhasil! Selamat datang kembali.', [
                'token' => $token,
                'user'  => $user_data
            ]);
        }

        // Jika return dari model bernilai false atau null, berarti akun salah
        return json_response(false, 'Email atau password yang masukkan salah.', null, 401);
    }

    /**
     * Endpoint: Register User Baru
     */
    public function register()
    {
        $input = $this->get_input();
        $name = isset($input['name']) ? trim($input['name']) : null;
        $email = isset($input['email']) ? trim($input['email']) : null;
        $password = isset($input['password']) ? $input['password'] : null;
        $phone = isset($input['phone']) ? trim($input['phone']) : '';

        if (empty($name) || empty($email) || empty($password)) {
            return json_response(false, 'Semua kolom pendaftaran wajib diisi.', null, 400);
        }

        if ($this->User_model->get_by_email($email)) {
            return json_response(false, 'Email ini sudah terdaftar di sistem kita.', null, 400);
        }

        $data_user = [
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role_id'     => 2,
            'status'   => 'active'
        ];

        if ($this->User_model->insert($data_user)) {
            return json_response(true, 'Registrasi Akun Berhasil! Silakan coba login.');
        }

        return json_response(false, 'Gagal mendaftarkan akun ke database server.', null, 500);
    }

    public function profile()
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $user_id = $this->user_data->id;
        $user = $this->User_model->get_profile($user_id);

        // 🔥 UPGRADE: Ambil SEMUA dokumen milik user (KTP & NPWP)
        $docs = $this->db->get_where('customer_documents', ['user_id' => $user_id])->result();

        $user->ktp_file = null;
        $user->ktp_status = null;
        $user->npwp_file = null;
        $user->npwp_status = null;

        foreach ($docs as $doc) {
            if ($doc->type === 'ktp') {
                $user->ktp_file = $doc->file_path;
                $user->ktp_status = $doc->status;
            } elseif ($doc->type === 'npwp') {
                $user->npwp_file = $doc->file_path;
                $user->npwp_status = $doc->status;
            }
        }

        return json_response(true, 'Success', $user);
    }

    /**
     * Endpoint: Perbarui Identitas Profil (Nama & Nomor HP)
     */
    public function update_profile()
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $user_id = $this->user_data->id;
        $data_update = [];

        $name = $this->input->post('name');
        $phone = $this->input->post('phone');

        if (empty($name) && empty($phone)) {
            $input = $this->get_input();
            $name = isset($input['name']) ? trim($input['name']) : null;
            $phone = isset($input['phone']) ? trim($input['phone']) : null;
        }

        if (!empty($name)) $data_update['name'] = $name;
        if (!empty($phone)) $data_update['phone'] = $phone;

        // 🔥 LOGIKA UPLOAD AVATAR DENGAN FITUR "REPLACE & DELETE OLD FILE"
        if (isset($_FILES['avatar']['name']) && !empty($_FILES['avatar']['name'])) {

            $config['upload_path']   = FCPATH . 'uploads/avatars/';
            $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
            $config['max_size']      = 2048;
            $config['file_name']     = 'avatar_' . $user_id . '_' . time();

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('avatar')) {
                // 1. Ambil nama file foto lama dari database SEBELUM ditimpa
                $user = $this->User_model->get_profile($user_id);

                // 2. Jika foto lama ada, langsung HANCURKAN dari folder fisik
                if ($user && !empty($user->avatar)) {
                    $old_file_path = FCPATH . 'uploads/avatars/' . $user->avatar;
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                }

                // 3. Simpan nama file baru ke array update database
                $upload_data = $this->upload->data();
                $data_update['avatar'] = $upload_data['file_name'];
            } else {
                return json_response(false, 'Gagal Upload Foto: ' . strip_tags($this->upload->display_errors()), null, 400);
            }
        } elseif (isset($_FILES['avatar']['error']) && $_FILES['avatar']['error'] !== 0 && $_FILES['avatar']['error'] !== 4) {
            return json_response(false, 'File foto ditolak oleh server (Kode PHP: ' . $_FILES['avatar']['error'] . ')', null, 400);
        }

        if (empty($data_update)) {
            return json_response(false, 'Tidak ada data identitas atau foto yang dikirim untuk diubah.', null, 400);
        }

        if ($this->User_model->update($user_id, $data_update)) {
            return json_response(true, 'Profil dan Foto berhasil diperbarui!', $data_update);
        }

        return json_response(false, 'Gagal memperbarui data profil di server.', null, 500);
    }

    /**
     * Endpoint: Ganti Password Akun Member
     */
    public function change_password()
    {
        // 1. Validasi keaslian Token JWT user terlogin
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        // 2. Tangkap data input password baru
        $input = $this->get_input();
        $new_password = isset($input['new_password']) ? $input['new_password'] : null;

        if (empty($new_password) || strlen($new_password) < 6) {
            return json_response(false, 'Password baru minimal harus 6 karakter.', null, 400);
        }

        $user_id = $this->user_data->id;

        // 3. Enkripsi password menggunakan Bcrypt standar auth login
        $data_update = [
            'password' => password_hash($new_password, PASSWORD_BCRYPT)
        ];

        // 4. Eksekusi simpan ke database
        if ($this->User_model->update($user_id, $data_update)) {
            return json_response(true, 'Password baru berhasil disimpan di database!');
        }

        return json_response(false, 'Gagal memperbarui password baru di server.', null, 500);
    }

    public function upload_document()
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $user_id = $this->user_data->id;

        // 🔥 UPGRADE: Tangkap doc_type dari frontend (ktp atau npwp)
        $doc_type = $this->input->post('doc_type');
        if (!in_array($doc_type, ['ktp', 'npwp'])) $doc_type = 'ktp'; // Default aman

        $other_type = ($doc_type === 'ktp') ? 'npwp' : 'ktp';
        $existing_other = $this->db->get_where('customer_documents', ['user_id' => $user_id, 'type' => $other_type])->row();

        if ($existing_other && !empty($existing_other->file_path)) {
            return json_response(false, 'sudah mengunggah ' . strtoupper($other_type) . '. Hapus dokumen tersebut dulu jika ingin menggantinya dengan ' . strtoupper($doc_type) . '.', null, 400);
        }

        if (!isset($_FILES['document']['name']) || empty($_FILES['document']['name'])) {
            return json_response(false, 'File dokumen tidak ditemukan.', null, 400);
        }

        $config['upload_path']   = FCPATH . 'uploads/documents/';
        $config['allowed_types'] = 'jpg|jpeg|png';
        $config['max_size']      = 2048;
        // 🔥 NAMA FILE OTOMATIS MENYESUAIKAN TIPE (ktp_ / npwp_)
        $config['file_name']     = $doc_type . '_' . $user_id . '_' . time();

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('document')) {
            return json_response(false, 'Gagal Upload: ' . strip_tags($this->upload->display_errors()), null, 400);
        }

        $file_name = $this->upload->data('file_name');
        $existing_doc = $this->db->get_where('customer_documents', ['user_id' => $user_id, 'type' => $doc_type])->row();

        if ($existing_doc) {
            if (!empty($existing_doc->file_path)) {
                $old_path = FCPATH . 'uploads/documents/' . $existing_doc->file_path;
                if (file_exists($old_path)) unlink($old_path);
            }
            $this->db->where('id', $existing_doc->id)->update('customer_documents', [
                'file_path' => $file_name,
                'status' => 'pending',
                'rejection_reason' => null
            ]);
        } else {
            $this->db->insert('customer_documents', [
                'user_id' => $user_id,
                'type' => $doc_type,
                'file_path' => $file_name,
                'status' => 'pending'
            ]);
        }

        return json_response(true, strtoupper($doc_type) . ' berhasil diunggah!', ['file_path' => $file_name]);
    }

    public function delete_avatar()
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $user_id = $this->user_data->id;
        $user = $this->User_model->get_profile($user_id);

        if ($user && !empty($user->avatar)) {
            // 1. Cari file fisiknya
            $file_path = FCPATH . 'uploads/avatars/' . $user->avatar;

            // 2. Jika file ada, HANCURKAN dari folder server!
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // 3. Set kolom avatar jadi NULL di database
            $this->User_model->update($user_id, ['avatar' => NULL]);

            return json_response(true, 'Foto profil berhasil dihapus secara permanen.');
        }

        return json_response(false, 'Tidak ada foto profil untuk dihapus.', null, 400);
    }

    public function delete_document()
    {
        $auth = $this->verify_token();
        if ($auth !== true) return $auth;

        $user_id = $this->user_data->id;
        // 🔥 UPGRADE: Tangkap parameter tipe dari frontend URL
        $doc_type = $this->input->get('type');
        if (!in_array($doc_type, ['ktp', 'npwp'])) $doc_type = 'ktp';

        $existing_doc = $this->db->get_where('customer_documents', ['user_id' => $user_id, 'type' => $doc_type])->row();

        if ($existing_doc && !empty($existing_doc->file_path)) {
            $file_path = FCPATH . 'uploads/documents/' . $existing_doc->file_path;
            if (file_exists($file_path)) unlink($file_path);

            $this->db->delete('customer_documents', ['id' => $existing_doc->id]);
            return json_response(true, 'Dokumen ' . strtoupper($doc_type) . ' berhasil dihapus.');
        }

        return json_response(false, 'Tidak ada dokumen untuk dihapus.', null, 400);
    }
}
