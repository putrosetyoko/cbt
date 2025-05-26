<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Rename class from Mahasiswa to Siswa
class Siswa extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        // Adjust access control: Only Admin or Guru can access this page
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru')) { // Allow Guru to access if needed
            show_error('Hanya Administrator atau Guru yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'form_validation']); // Load Library Ignited-Datatables
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
            'subjudul' => 'Data Siswa'
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/siswa/data'); // Changed view path
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function data()
    {
        $this->output_json($this->master->getDataSiswa(), false); // Changed function name
    }

    public function add()
    {
        $data = [
            'user' => $this->ion_auth->user()->row(),
            'judul' => 'Siswa',
            'subjudul' => 'Tambah Data Siswa',
            // 'jurusan' => $this->master->getJurusan(), // Remove if jurusan is completely removed
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
            // 'jurusan' => $this->master->getJurusan(), // Remove if jurusan is completely removed
            // 'kelas'   => $this->master->getKelasByJurusan($siswa->jurusan_id), // Remove if jurusan is completely removed, adjust if kelas is filtered by something else
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
                    // 'email' => form_error('email'), // Removed
                    'jenis_kelamin' => form_error('jenis_kelamin'),
                    // 'jurusan' => form_error('jurusan'), // Removed
                    'kelas' => form_error('kelas'),
                ]
            ];
            $this->output_json($data);
        } else {
            $input = [
                'nisn'          => $this->input->post('nisn', true), // Changed field name
                // 'email'         => $this->input->post('email', true), // Removed
                'nama'          => $this->input->post('nama', true),
                'jenis_kelamin' => $this->input->post('jenis_kelamin', true),
                'kelas_id'      => $this->input->post('kelas', true),
                // 'jurusan_id'    => $this->input->post('jurusan', true), // Removed
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

    // This function is for creating an Ion_auth user for a student.
    // Given the new student login (NISN/Token), this might be redundant or require re-evaluation.
    // The 'siswa' table no longer has an 'email' column, which Ion_auth's register often needs.
    // If you intend for students to *also* have Ion_auth accounts, you might need to re-add 'email' to 'siswa' table or generate/use a dummy email.
    public function create_user()
    {
        $id = $this->input->get('id', true);
        $data = $this->master->getSiswaById($id); // Changed function call
        $nama = explode(' ', $data->nama);
        $first_name = $nama[0];
        $last_name = end($nama);

        $username = $data->nisn; // Changed to NISN
        $password = $data->nisn; // Password from NISN
        // $email = $data->email; // Removed as 'siswa' table no longer has 'email'

        $additional_data = [
            'first_name'    => $first_name,
            'last_name'     => $last_name
            // Consider if other data like 'phone' or 'address' is needed by Ion_auth
        ];
        $group = array('3'); // Assuming '3' is the group ID for Students in Ion_auth's 'groups' table

        if ($this->ion_auth->username_check($username)) {
            $data = [
                'status' => false,
                'msg'    => 'Gagal Aktif! NISN sudah digunakan.' // Changed message
            ];
        } 
        // else if ($this->ion_auth->email_check($email)) { // Removed email check
        //     $data = [
        //         'status' => false,
        //         'msg'    => 'Gagal Aktif! Email sudah digunakan.'
        //     ];
        // } 
        else {
            // Ion_auth's register method typically requires an email parameter.
            // If the 'siswa' table no longer has an email, you might need to:
            // 1. Add an email column back to the 'siswa' table.
            // 2. Generate a dummy email for the student (e.g., $username . '@example.com').
            // For now, I'm passing a placeholder empty string for email, which might cause issues if Ion_auth strictly validates it.
            $this->ion_auth->register($username, $password, '', $additional_data, $group); // Pass empty string or generate email
            $data = [
                'status'    => true,
                'msg'    => 'User berhasil diaktifkan.' // Changed message
            ];
        }
        $this->output_json($data);
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
                    // 'email'         => $sheetData[$i][2], // Removed as 'siswa' table no longer has 'email'
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
                // 'email'         => $d->email, // Removed
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