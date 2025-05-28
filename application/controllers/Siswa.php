<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Rename class from Mahasiswa to Siswa
class Siswa extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        // if (!function_exists('output_json')) {
        //     function output_json($data) {
        //         header('Content-Type: application/json');
        //         echo json_encode($data);
        //     }
        // }
        // Adjust access control: Only Admin or Guru can access this page
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) { // Allow Guru to access if needed
            show_error('Hanya Administrator atau Guru yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'ion_auth', 'form_validation']); // Load Library Ignited-Datatables
        $this->load->model('Master_model', 'master'); // Master_model handles data for master tables
        $this->form_validation->set_error_delimiters('', '');
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
            'judul' => 'Siswa',
            'subjudul' => 'Data Siswa',
            'kelas'     => $this->master->getAllKelas()
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/siswa/data'); // Changed view path
        $this->load->view('_templates/dashboard/_footer.php');
    }

    // Fungsi DataTables untuk mengambil data siswa
    public function data($id_kelas = null) // Tambahkan parameter $id_kelas
    {
        echo $this->master->getDataSiswa($id_kelas);
    }

    public function add()
    {
        $data = [
            'user' => $this->ion_auth->user()->row(),
            'judul' => 'Siswa',
            'subjudul' => 'Tambah Data Siswa',
            'kelas' => $this->master->getAllKelas() // Get all classes if jurusan is removed
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/siswa/add'); // Changed view path
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function edit($id)
    {
        $siswa = $this->master->getSiswaById($id); // Changed variable name and function call
        $data = [
            'user'      => $this->ion_auth->user()->row(),
            'judul'     => 'Siswa',
            'subjudul'  => 'Edit Data Siswa',
            'kelas' => $this->master->getAllKelas(), // Get all classes if jurusan is removed
            'siswa'     => $siswa // Changed variable name
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/siswa/edit'); // Changed view path
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function validasi_siswa($method) // Renamed function
    {
        $id_siswa   = $this->input->post('id_siswa', true); // Changed variable name
        $nisn       = $this->input->post('nisn', true); // Changed variable name and input field

        if ($method == 'add') {
            $u_nisn = '|is_unique[siswa.nisn]'; // Changed table and column name
            // $u_email = '|is_unique[siswa.email]'; // Removed as 'siswa' table no longer has 'email'
        } else {
            $dbdata     = $this->master->getSiswaById($id_siswa); // Changed function call
            $u_nisn     = $dbdata->nisn === $nisn ? "" : "|is_unique[siswa.nisn]"; // Changed column name
            // $u_email  = $dbdata->email === $email ? "" : "|is_unique[siswa.email]"; // Removed
        }
        $this->form_validation->set_rules('nisn', 'NISN', 'required|numeric|trim|min_length[8]|max_length[12]' . $u_nisn); // Changed label and validation
        $this->form_validation->set_rules('nama', 'Nama', 'required|trim|min_length[3]|max_length[50]');
        // $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email' . $u_email); // Removed as 'siswa' table no longer has 'email'
        $this->form_validation->set_rules('jenis_kelamin', 'Jenis Kelamin', 'required');
        // $this->form_validation->set_rules('jurusan', 'Jurusan', 'required'); // Remove if jurusan is completely removed
        $this->form_validation->set_rules('kelas', 'Kelas', 'required');

        $this->form_validation->set_message('required', 'Kolom {field} wajib diisi');
    }

    public function save()
    {
        $method = $this->input->post('method', true);
        $this->validasi_siswa($method); // Changed function call

        if ($this->form_validation->run() == FALSE) {
            $data = [
                'status'    => false,
                'errors'    => [
                    'nisn' => form_error('nisn'), // Changed field name
                    'nama' => form_error('nama'),
                    'jenis_kelamin' => form_error('jenis_kelamin'),
                    'kelas' => form_error('kelas'),
                ]
            ];
            $this->output_json($data);
        } else {
            $input = [
                'nisn'          => $this->input->post('nisn', true), // Changed field name
                'nama'          => $this->input->post('nama', true),
                'jenis_kelamin' => $this->input->post('jenis_kelamin', true),
                'kelas_id'      => $this->input->post('kelas', true),
            ];
            if ($method === 'add') {
                $action = $this->master->create('siswa', $input); // Changed table name
            } else if ($method === 'edit') {
                $id = $this->input->post('id_siswa', true); // Changed id parameter
                $action = $this->master->update('siswa', $input, 'id_siswa', $id); // Changed table and id column name
            }

            if ($action) {
                $this->output_json(['status' => true]);
            } else {
                $this->output_json(['status' => false]);
            }
        }
    }

    public function delete()
    {
        $chk = $this->input->post('checked', true);
        if (!$chk) {
            $this->output_json(['status' => false]);
        } else {
            if ($this->master->delete('siswa', $chk, 'id_siswa')) { // Changed table and id column name
                $this->output_json(['status' => true, 'total' => count($chk)]);
            }
        }
    }

    public function create_user()
    {
        // Fungsi ini akan tetap ada untuk aktivasi tunggal
        $id = $this->input->get('id', true); // Menggunakan GET untuk single activation

        // Modifikasi kecil: ubah pemanggilan output_json menjadi fungsi helper jika ada
        $this->_create_single_user($id);
    }

    /**
     * Helper function untuk logika aktivasi user tunggal
     * Ini dipisah agar bisa dipanggil dari create_user() atau bulk_create_user()
     */
    private function _create_single_user($id_siswa)
    {
        $response = ['status' => false, 'msg' => ''];

        $data = $this->master->getSiswaById($id_siswa);

        if (empty($data)) {
            $response['msg'] = 'Gagal Aktif! Data siswa dengan ID ' . $id_siswa . ' tidak ditemukan.';
            return $response; // Mengembalikan array respons
        }

        $nama = explode(' ', $data->nama);
        $first_name = $nama[0];
        $last_name = end($nama);

        $username = $data->nisn;
        $password = $data->nisn;

        // Generate email dari NISN (pastikan unik)
        $nisn_str = (string) $data->nisn;
        $email = $nisn_str . '@gmail.com'; // Ubah '@gmail.com' menjadi '@example.com' atau domain default Anda untuk menghindari konflik nyata dengan Gmail

        $additional_data = [
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            // Anda bisa menambahkan kolom lain yang dibutuhkan Ion Auth di sini,
            // seperti 'phone', 'company', dll.
        ];
        $group = array('3'); // Asumsi '3' adalah group ID untuk Siswa

        if ($this->ion_auth->username_check($username)) {
            $response['msg'] = 'Gagal Aktif! Username (NISN: ' . $username . ') sudah pernah diaktifkan.';
        } else if ($this->ion_auth->email_check($email)) {
            $response['msg'] = 'Gagal Aktif! Email yang digenerate (' . $email . ') sudah pernah digenerate.';
        } else {
            // Cek apakah Ion Auth berhasil register user
            if ($this->ion_auth->register($username, $password, $email, $additional_data, $group)) {
                $response = [
                    'status'    => true,
                    'msg'       => 'User untuk NISN ' . $username . ' berhasil diaktifkan.'
                ];
            } else {
                $response['msg'] = 'Gagal Aktif! Error saat registrasi Ion Auth: ' . $this->ion_auth->errors();
            }
        }
        return $response; // Mengembalikan array respons
    }


    public function bulk_create_user()
    {
        // Pastikan request adalah POST dan ada data 'ids'
        if ($this->input->method() === 'post' && $this->input->post('ids')) {
            $siswa_ids = $this->input->post('ids', true); // Dapatkan array ID siswa
            $total_processed = count($siswa_ids);
            $total_success = 0;
            $failed_messages = [];

            foreach ($siswa_ids as $id_siswa) {
                $result = $this->_create_single_user($id_siswa); // Panggil helper function
                if ($result['status']) {
                    $total_success++;
                } else {
                    $failed_messages[] = $result['msg']; // Kumpulkan pesan kegagalan
                }
            }

            if ($total_success > 0) {
                output_json([
                    'status'          => true,
                    'total_processed' => $total_processed,
                    'total_success'   => $total_success,
                    'failed_messages' => $failed_messages, // Opsional: kirim pesan kegagalan
                    'msg'             => ($total_success === $total_processed) ? 'Semua akun siswa berhasil diaktifkan.' : $total_success . ' dari ' . $total_processed . ' akun siswa berhasil diaktifkan.'
                ]);
            } else {
                output_json([
                    'status'          => false,
                    'total_processed' => $total_processed,
                    'total_success'   => $total_success,
                    'failed_messages' => $failed_messages,
                    'msg'             => 'Tidak ada akun siswa yang berhasil diaktifkan. ' . implode(', ', $failed_messages)
                ]);
            }

        } else {
            output_json([
                'status' => false,
                'msg'    => 'Request tidak valid atau tidak ada ID yang dikirim.'
            ]);
        }
    }

    public function import($import_data = null)
    {
        $data = [
            'user' => $this->ion_auth->user()->row(),
            'judul' => 'Siswa',
            'subjudul' => 'Import Data Siswa',
            'kelas' => $this->master->getAllKelas()
        ];
        if ($import_data != null) $data['import'] = $import_data;

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('master/siswa/import'); // Changed view path
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
            echo $error;
            die;
        } else {
            $file = $this->upload->data('full_path');
            $ext = $this->upload->data('file_ext');

            switch ($ext) {
                case '.xlsx':
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                    break;
                case '.xls':
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                    break;
                case '.csv':
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                    break;
                default:
                    echo "unknown file ext";
                    die;
            }

            $spreadsheet = $reader->load($file);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();
            $data = [];
            for ($i = 1; $i < count($sheetData); $i++) {
                $data[] = [
                    'nisn'          => $sheetData[$i][0], // Changed to nisn
                    'nama'          => $sheetData[$i][1],
                    'jenis_kelamin' => $sheetData[$i][2], // Shifted index if email removed
                    'kelas_id'      => $sheetData[$i][3] // Shifted index if email removed
                ];
            }

            unlink($file);

            $this->import($data);
        }
    }

    public function do_import()
    {
        $input = json_decode($this->input->post('data', true));
        $data = [];
        foreach ($input as $d) {
            $data[] = [
                'nisn'          => $d->nisn, // Changed to nisn
                'nama'          => $d->nama,
                'jenis_kelamin' => $d->jenis_kelamin,
                'kelas_id'      => $d->kelas_id
            ];
        }

        $save = $this->master->create('siswa', $data, true); // Changed table name
        if ($save) {
            redirect('siswa'); // Changed redirect path
        } else {
            redirect('siswa/import'); // Changed redirect path
        }
    }
}