<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Guru extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin()) {
            // Jika Anda ingin guru lain bisa mengakses beberapa bagian, sesuaikan di sini atau per metode
            show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'ion_auth', 'form_validation']);
        $this->load->model('Master_model', 'master');
        $this->form_validation->set_error_delimiters('', ''); // Error delimiter kosong untuk respons JSON
    }

    public function output_json($data, $encode = true)
    {
        if ($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function index()
    {
        $data = [
            'user' => $this->ion_auth->user()->row(),
            'judul' => 'Guru',
            'subjudul' => 'Data Guru'
            // Tidak perlu $all_mapel karena tidak ada filter mapel di view data guru
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/guru/data', $data); // Mengirim $data agar $judul dan $subjudul bisa diakses
        $this->load->view('_templates/dashboard/_footer.php');
    }

    /**
     * Data untuk DataTables
     */
    public function data($id_guru = null) // Tambahkan parameter $id_guru
    {
        echo $this->master->getDataGuru($id_kelas);
    }

    public function add()
    {
        $data = [
            'user' => $this->ion_auth->user()->row(),
            'judul' => 'Tambah Guru',
            'subjudul' => 'Tambah Data Guru'
            // Tidak perlu $all_mapel
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/guru/add', $data); // Mengirim $data
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function edit($id_guru = null)
    {
        if (!$id_guru) {
            show_404();
            return;
        }
        $guru_data = $this->master->getGuruById($id_guru);
        if (!$guru_data) {
            show_error('Data guru tidak ditemukan.', 404, 'Not Found');
            return;
        }
        $data = [
            'user'     => $this->ion_auth->user()->row(),
            'judul'    => 'Edit Guru',
            'subjudul' => 'Edit Data Guru',
            'data'     => $guru_data // Data guru yang akan diedit (tanpa mapel)
            // Tidak perlu $all_mapel
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/guru/edit', $data); // Mengirim $data
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function save()
    {
        $method    = $this->input->post('method', true);
        $id_guru   = $this->input->post('id_guru', true);
        $nip       = $this->input->post('nip', true);
        $nama_guru = $this->input->post('nama_guru', true);
        $email     = $this->input->post('email', true);

        // Validasi
        $u_nip = '';
        $u_email = '';
        if ($method == 'add') {
            $u_nip = '|is_unique[guru.nip]';
            if (!empty($email)) { // Hanya validasi is_unique jika email diisi
                $u_email = '|is_unique[guru.email]';
            }
        } else { // Mode Edit
            if (empty($id_guru)) {
                $this->output_json(['status' => false, 'message' => 'ID Guru tidak valid untuk operasi edit.']);
                return;
            }
            $dbdata = $this->master->getGuruById($id_guru);
            if (!$dbdata) {
                $this->output_json(['status' => false, 'message' => 'Data Guru asli tidak ditemukan untuk pembaruan.']);
                return;
            }
            if ($dbdata->nip !== $nip) {
                $u_nip = '|is_unique[guru.nip]';
            }
            if (!empty($email) && $dbdata->email !== $email) {
                $u_email = '|is_unique[guru.email]';
            }
        }

        $this->form_validation->set_rules('nip', 'NIP', 'required|numeric|trim|min_length[8]|max_length[20]' . $u_nip);
        $this->form_validation->set_rules('nama_guru', 'Nama Guru', 'required|trim|min_length[3]|max_length[100]');
        // Email bisa opsional (nullable di DB), tapi jika diisi harus valid dan unik (jika berbeda/baru)
        if (!empty($email) || $method == 'add' && $this->input->post('email')) { // Validasi jika email diisi atau saat add diisi
            $this->form_validation->set_rules('email', 'Email', 'trim|valid_email' . $u_email);
        }


        if ($this->form_validation->run() == FALSE) {
            $errors = [
                'nip'       => form_error('nip'),
                'nama_guru' => form_error('nama_guru'),
                'email'     => form_error('email'),
            ];
            // Hapus key error yang kosong
            $errors = array_filter($errors, function($value) { return !empty($value); });
            $this->output_json(['status' => false, 'errors' => $errors, 'message' => 'Periksa kembali data yang Anda input.']);
        } else {
            $input_data = [
                'nip'       => $nip,
                'nama_guru' => $nama_guru,
                'email'     => !empty($email) ? $email : NULL,
            ];

            if ($method === 'add') {
                $action = $this->master->create('guru', $input_data);
                $message = 'Data guru berhasil ditambahkan.';
            } else if ($method === 'edit') {
                $action = $this->master->update('guru', $input_data, 'id_guru', $id_guru);
                $message = 'Data guru berhasil diperbarui.';
            } else {
                $action = false;
                $message = 'Metode tidak dikenali.';
            }

            if ($action) {
                $this->output_json(['status' => true, 'message' => $message]);
            } else {
                $this->output_json(['status' => false, 'message' => 'Gagal menyimpan data guru. '. $message]);
            }
        }
    }

    public function delete()
    {
        $chk = $this->input->post('checked', true);
        if (empty($chk) || !is_array($chk)) {
            $this->output_json(['status' => false, 'message' => 'Tidak ada data guru yang dipilih untuk dihapus.']);
            return;
        }

        // Memastikan semua ID adalah skalar
        foreach ($chk as $id_value) {
            if (!is_scalar($id_value)) {
                $this->output_json(['status' => false, 'message' => 'Format ID guru tidak valid.']);
                return;
            }
        }
        
        $can_delete_all = true;
        $error_messages_on_check = [];
        foreach ($chk as $id_guru_to_delete) {
            $guru_data = $this->master->getGuruById((string)$id_guru_to_delete);
            if ($guru_data) {
                if ($this->ion_auth->username_check($guru_data->nip)) {
                    $can_delete_all = false;
                    $error_messages_on_check[] = "Guru \"".htmlspecialchars($guru_data->nama_guru)."\" (NIP: ".htmlspecialchars($guru_data->nip).") memiliki akun pengguna dan tidak bisa dihapus.";
                }
            } else {
                $error_messages_on_check[] = "Data guru dengan ID: " . htmlspecialchars((string)$id_guru_to_delete) . " tidak ditemukan saat pengecekan.";
                $can_delete_all = false;
            }
        }

        if (!$can_delete_all) {
            $this->output_json(['status' => false, 'message' => implode("\n", $error_messages_on_check)]);
            return;
        }

        $deleted_successfully = $this->master->delete('guru', $chk, 'id_guru');
        if ($deleted_successfully) {
            $this->output_json(['status' => true, 'message' => count($chk) . ' data guru berhasil dihapus.', 'total' => count($chk)]);
        } else {
            $db_error_message = 'Gagal menghapus data guru dari database.';
            $db_error = $this->db->error();
            if (is_array($db_error) && !empty($db_error['message'])) {
                $db_error_message .= ' Detail: ' . $db_error['message'];
                log_message('error', 'Guru Delete DB Error: Code [' . $db_error['code'] . '] ' . $db_error['message']);
            }
            $this->output_json(['status' => false, 'message' => $db_error_message]);
        }
    }

    public function create_user()
    {
        // Fungsi ini akan tetap ada untuk aktivasi tunggal
        $id = $this->input->get('id', true); // Menggunakan GET untuk single activation

        // Panggil helper function DAN TANGKAP HASILNYA ke dalam variabel $response
        $response = $this->_create_single_user($id);

        // Tampilkan variabel $response sebagai output JSON
        // Asumsi Anda memiliki method output_json() di controller ini atau di base controller.
        // Jika tidak, Anda bisa menggunakan:
        // header('Content-Type: application/json');
        // echo json_encode($response);
        $this->output_json($response);
    }

    /**
     * Helper function untuk logika aktivasi user tunggal
     * Ini dipisah agar bisa dipanggil dari create_user() atau bulk_create_user()
     */
    private function _create_single_user($id_guru)
    {
        $response = ['status' => false, 'msg' => ''];

        $data = $this->master->getGuruById($id_guru);

        if (empty($data)) {
            $response['msg'] = 'Gagal Aktif! Data Guru dengan ID ' . $id_guru . ' tidak ditemukan.';
            return $response;
        }

        $nama = explode(' ', trim($data->nama_guru)); // Trim untuk menghilangkan whitespace di awal dan akhir
        $first_name = $nama[0];
        
        if (count($nama) > 1) {
            // Gabungkan sisa nama menjadi last_name
            $last_name = implode(' ', array_slice($nama, 1));
        } else {
            $last_name = ''; // Jika hanya satu kata, last_name kosong
        }

        $username = $data->email;
        $password = $data->nip;
        $email = $data->email;

        // Tambahkan data untuk proses aktivasi user
        $additional_data = [
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ];

        $group = array('2'); // Sets user to guru.

        // Daftarkan user
        $register = $this->ion_auth->register($username, $password, $email, $additional_data, $group);

        if ($register) {
            $response['status'] = true;
            $response['msg'] = 'Berhasil diaktifkan!';
        } else {
            $response['msg'] = 'Gagal mengaktifkan user: ' . strip_tags($this->ion_auth->errors());
        }

        return $response;
    }


    public function bulk_create_user()
    {
        // Pastikan request adalah POST dan ada data 'ids'
        if ($this->input->method() === 'post' && $this->input->post('ids')) {
            $guru_ids = $this->input->post('ids', true); // Dapatkan array ID Guru
            $total_processed = count($guru_ids);
            $total_success = 0;
            $failed_messages = [];

            foreach ($guru_ids as $id_guru) {
                $result = $this->_create_single_user($id_guru); // Panggil helper function
                if ($result['status']) {
                    $total_success++;
                } else {
                    $failed_messages[] = $result['msg']; // Kumpulkan pesan kegagalan
                }
            }

            if ($total_success > 0) {
                $this->output_json([
                    'status'          => true,
                    'total_processed' => $total_processed,
                    'total_success'   => $total_success,
                    'failed_messages' => $failed_messages, // Opsional: kirim pesan kegagalan
                    'msg'             => ($total_success === $total_processed) ? 'Semua akun Guru berhasil diaktifkan.' : $total_success . ' dari ' . $total_processed . ' akun guru berhasil diaktifkan.'
                ]);
            } else {
                $this->output_json([
                    'status'          => false,
                    'total_processed' => $total_processed,
                    'total_success'   => $total_success,
                    'failed_messages' => $failed_messages,
                    'msg'             => 'Tidak ada akun Guru yang berhasil diaktifkan. ' . implode(', ', $failed_messages)
                ]);
            }

        } else {
            $this->output_json([
                'status' => false,
                'msg'    => 'Request tidak valid atau tidak ada ID yang dikirim.'
            ]);
        }
    }
    
    // Metode import disederhanakan (tanpa mapel)
    public function import($import_data = null)
    {
        $data = [
            'user' => $this->ion_auth->user()->row(),
            'judul' => 'Guru',
            'subjudul' => 'Import Data Guru',
        ];
        if ($import_data !== null) {
            $data['import'] = $import_data;
            $data['show_preview'] = true; 
        } else {
            $data['show_preview'] = false; 
        }
        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('master/guru/import', $data); // View import disesuaikan (NIP, Nama, Email)
        $this->load->view('_templates/dashboard/_footer');
    }

    public function preview()
    {
        $config['upload_path']      = './uploads/import/';
        $config['allowed_types']    = 'xls|xlsx|csv';
        $config['max_size']         = 2048;
        $config['encrypt_name']     = true;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('upload_file')) {
            $error = $this->upload->display_errors();
            $this->session->set_flashdata('error_message', strip_tags($error));
            redirect('guru/import');
            return;
        }
        
        $file = $this->upload->data('full_path');
        $ext = $this->upload->data('file_ext');

        try {
            $reader = null;
            switch ($ext) {
                case '.xlsx': $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx(); break;
                case '.xls':  $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();  break;
                case '.csv':  $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();  break;
                default:
                    $this->session->set_flashdata('error_message', "Ekstensi file tidak dikenal.");
                    if (file_exists($file)) unlink($file);
                    redirect('guru/import');
            }

            $spreadsheet = $reader->load($file);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $data_for_preview = [];

            // Asumsi kolom Excel: A=NIP, B=Nama Guru, C=Email
            for ($i = 2; $i <= count($sheetData); $i++) {
                if (empty(trim((string)$sheetData[$i]['A'])) && empty(trim((string)$sheetData[$i]['B']))) { // Lewati baris jika NIP dan Nama kosong
                    continue; 
                }
                $data_for_preview[] = [
                    'nip'       => isset($sheetData[$i]['A']) ? trim($sheetData[$i]['A']) : null,
                    'nama_guru' => isset($sheetData[$i]['B']) ? trim($sheetData[$i]['B']) : null,
                    'email'     => isset($sheetData[$i]['C']) ? trim($sheetData[$i]['C']) : null,
                ];
            }
            unlink($file);

            if(empty($data_for_preview)){
                $this->session->set_flashdata('error_message', "Tidak ada data valid untuk diimpor dari file. Pastikan kolom NIP dan Nama Guru terisi.");
                redirect('guru/import');
            }
            $this->import($data_for_preview);

        } catch (Exception $e) {
            $this->session->set_flashdata('error_message', "Terjadi kesalahan saat memproses file: " . $e->getMessage());
            if (isset($file) && file_exists($file)) {
                unlink($file);
            }
            redirect('guru/import');
        }
    }

    public function do_import()
    {
        $input_json = $this->input->post('data', true);
        $preview_data = json_decode($input_json);

        if (empty($preview_data)) {
            $this->session->set_flashdata('error', 'Tidak ada data untuk diimpor.');
            redirect('guru/import');
            return;
        }

        $data_to_insert = [];
        $skipped_rows_info = [];

        foreach ($preview_data as $d) {
            // Lakukan validasi dasar lagi sebelum insert batch
            if (empty($d->nip) || empty($d->nama_guru)) {
                $skipped_rows_info[] = "Baris dengan NIP '".htmlspecialchars($d->nip)."' dan Nama '".htmlspecialchars($d->nama_guru)."' dilewati karena NIP atau Nama kosong.";
                continue;
            }
             // Anda mungkin ingin menambahkan validasi NIP/Email unik di sini sebelum batch insert
             // atau mengandalkan constraint database dan menangani errornya.
            $data_to_insert[] = [
                'nip'       => $d->nip,
                'nama_guru' => $d->nama_guru,
                'email'     => !empty($d->email) ? $d->email : NULL,
            ];
        }

        if (!empty($data_to_insert)) {
            $save = $this->master->create('guru', $data_to_insert, true);
            if ($save) {
                $message = count($data_to_insert) . ' data guru berhasil diimpor.';
                if(!empty($skipped_rows_info)){
                    $message .= "<br>Catatan: <br>- " . implode("<br>- ", $skipped_rows_info);
                }
                $this->session->set_flashdata('success', $message);
                redirect('guru');
            } else {
                $this->session->set_flashdata('error', 'Gagal menyimpan data impor ke database. Periksa duplikasi NIP/Email atau error database lainnya.');
                redirect('guru/import');
            }
        } else {
            $message = 'Tidak ada data valid untuk diimpor setelah pemrosesan.';
            if(!empty($skipped_rows_info)){
                $message .= "<br>Catatan: <br>- " . implode("<br>- ", $skipped_rows_info);
            }
            $this->session->set_flashdata('warning', $message);
            redirect('guru/import');
        }
    }
}