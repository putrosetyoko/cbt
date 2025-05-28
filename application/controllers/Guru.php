<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Guru extends CI_Controller // Changed class name from Dosen to Guru
{

    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin()) {
            show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'ion_auth', 'form_validation']); // Load Library Ignited-Datatables
        $this->load->model('Master_model', 'master');
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
            'judul' => 'Guru', // Changed Dosen to Guru
            'subjudul' => 'Data Guru', // Changed Data Dosen to Data Guru
            'mapel'     => $this->master->getAllMapel()
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/guru/data'); // View path remains master/dosen for now
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function data($id_mapel = null)
    {
        echo $this->master->getDataGuru($id_mapel);
    }

    public function add()
    {
        $data = [
            'user' => $this->ion_auth->user()->row(),
            'judul' => 'Tambah Guru', // Changed Tambah Dosen to Tambah Guru
            'subjudul' => 'Tambah Data Guru', // Changed Tambah Data Dosen to Tambah Data Guru
            'mapel'  => $this->master->getAllMapel()
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/guru/add'); // View path remains master/dosen for now
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function edit($id)
    {
        $data = [
            'user'    => $this->ion_auth->user()->row(),
            'judul'   => 'Edit Guru', // Changed Edit Dosen to Edit Guru
            'subjudul'  => 'Edit Data Guru', // Changed Edit Data Dosen to Edit Data Guru
            'mapel'  => $this->master->getAllMapel(),
            'data'    => $this->master->getGuruById($id) // Changed getDosenById to getGuruById
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/guru/edit'); // View path remains master/dosen for now
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function save()
    {
        $method     = $this->input->post('method', true);
        $id_guru    = $this->input->post('id_guru', true); // Changed id_dosen to id_guru
        $nip        = $this->input->post('nip', true);
        $nama_guru  = $this->input->post('nama_guru', true); // Changed nama_dosen to nama_guru
        $email      = $this->input->post('email', true);
        $mapel     = $this->input->post('mapel', true);

        if ($method == 'add') {
            $u_nip = '|is_unique[guru.nip]'; // Changed dosen.nip to guru.nip
            $u_email = '|is_unique[guru.email]'; // Changed dosen.email to guru.email
        } else {
            $dbdata    = $this->master->getGuruById($id_guru); // Changed getDosenById to getGuruById and id_dosen to id_guru
            $u_nip     = $dbdata->nip === $nip ? "" : "|is_unique[guru.nip]"; // Changed dosen.nip to guru.nip
            $u_email   = $dbdata->email === $email ? "" : "|is_unique[guru.email]"; // Changed dosen.email to guru.email
        }
        $this->form_validation->set_rules('nip', 'NIP', 'required|numeric|trim|min_length[8]|max_length[20]' . $u_nip);
        $this->form_validation->set_rules('nama_guru', 'Nama Guru', 'required|trim|min_length[3]|max_length[50]'); // Changed Nama Dosen to Nama Guru
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email' . $u_email);
        $this->form_validation->set_rules('mapel', 'Mata Pelajaran', 'required');

        if ($this->form_validation->run() == FALSE) {
            $data = [
                'status'  => false,
                'errors'  => [
                    'nip' => form_error('nip'),
                    'nama_guru' => form_error('nama_guru'), // Changed nama_dosen to nama_guru
                    'email' => form_error('email'),
                    'mapel' => form_error('mapel'),
                ]
            ];
            $this->output_json($data);
        } else {
            $input = [
                'nip'         => $nip,
                'nama_guru'   => $nama_guru, // Changed nama_dosen to nama_guru
                'email'       => $email,
                'mapel_id'    => $mapel // 'mapel_id' is the database column name
            ];
            if ($method === 'add') {
                $action = $this->master->create('guru', $input); // Changed dosen to guru
            } else if ($method === 'edit') {
                $action = $this->master->update('guru', $input, 'id_guru', $id_guru); // Changed dosen to guru and id_dosen to id_guru
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
            if ($this->master->delete('guru', $chk, 'id_guru')) { // Changed dosen to guru and id_dosen to id_guru
                $this->output_json(['status' => true, 'total' => count($chk)]);
            }
        }
    }

    public function create_user()
    {
        $id = $this->input->get('id', true);
        $data = $this->master->getGuruById($id); // Changed getDosenById to getGuruById
        $nama = explode(' ', $data->nama_guru); // Changed nama_dosen to nama_guru
        $first_name = $nama[0];
        $last_name = end($nama);

        $username = $data->nip;
        $password = $data->nip;
        $email = $data->email;
        $additional_data = [
            'first_name'  => $first_name,
            'last_name'   => $last_name
        ];
        $group = array('2'); // Sets user to dosen (group 2 should be 'guru' in groups table)

        if ($this->ion_auth->username_check($username)) {
            $data = [
                'status' => false,
                'msg'  => 'Gagal Aktif! NIP/NIK/NUPTK sudah digunakan.'
            ];
        } else if ($this->ion_auth->email_check($email)) {
            $data = [
                'status' => false,
                'msg'  => 'Gagal Aktif! Email sudah digunakan.'
            ];
        } else {
            $this->ion_auth->register($username, $password, $email, $additional_data, $group);
            $data = [
                'status'  => true,
                'msg'  => 'User berhasil diaktifkan. NIP/NIK/NUPTK digunakan sebagai password pada saat login.'
            ];
        }
        $this->output_json($data);
    }

    public function import($import_data = null)
    {
        $data = [
            'user' => $this->ion_auth->user()->row(),
            'judul' => 'Guru',
            'subjudul' => 'Import Data Guru',
            'mapel' => $this->master->getAllMapel()
        ];

        // Buat map dari id_mapel ke nama_mapel
        $mapel_nama_map = [];
        foreach ($data['mapel'] as $m) {
            $mapel_nama_map[$m->id_mapel] = $m->nama_mapel;
        }
        $data['mapel_nama_map'] = $mapel_nama_map; // Kirim map ini ke view

        // This is where you pass the import data.
        // If $import_data is provided (from preview() call), it will be set.
        // Otherwise, it will be null, and the view can handle that.
        if ($import_data !== null) { // Use strict comparison to differentiate from empty array
            $data['import'] = $import_data;
            $data['show_preview'] = true; // Add a flag to indicate that preview data exists
        } else {
            $data['show_preview'] = false; // No preview data to show initially
        }


        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('master/guru/import', $data); // Pass $data to the view
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
            $this->session->set_flashdata('error_message', $error);
            redirect('guru/import');
        } else {
            $file = $this->upload->data('full_path');
            $ext = $this->upload->data('file_ext'); // Akan menghasilkan '.xlsx', '.xls', atau '.csv'

            try {
                $reader = null;
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
                        $this->session->set_flashdata('error_message', "Ekstensi file tidak dikenal.");
                        if (file_exists($file)) {
                            unlink($file); // Hapus file yang diupload
                        }
                        redirect('guru/import');
                }

                $spreadsheet = $reader->load($file);
                // toArray(null, true, true, true) akan mengembalikan array 1-based dengan referensi sel (A, B, C...)
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

                $data_for_preview = [];

                // Ambil semua data mapel dari database untuk mapping nama
                $all_mapel = $this->master->getAllMapel(); // Pastikan method ini ada di Master_model
                $mapel_nama_map_preview = [];
                foreach ($all_mapel as $m) {
                    $mapel_nama_map_preview[$m->id_mapel] = $m->nama_mapel;
                }

                // Mulai dari baris ke-2 (mengabaikan header)
                // Karena toArray(null, true, true, true) mengembalikan array 1-based,
                // baris pertama adalah indeks 1, baris data pertama adalah indeks 2.
                for ($i = 2; $i <= count($sheetData); $i++) {
                    // Periksa apakah baris benar-benar kosong (semua sel pentingnya null/kosong)
                    // Ini untuk mengabaikan baris "hantu" di Excel
                    if (empty($sheetData[$i]['A']) && empty($sheetData[$i]['B']) && empty($sheetData[$i]['C']) && empty($sheetData[$i]['D'])) {
                        continue; // Lewati baris kosong
                    }

                    // Tambahkan validasi dan trim whitespace dari nilai yang dibaca
                    $nip = isset($sheetData[$i]['A']) ? trim($sheetData[$i]['A']) : null;
                    $nama_guru = isset($sheetData[$i]['B']) ? trim($sheetData[$i]['B']) : null;
                    $email = isset($sheetData[$i]['C']) ? trim($sheetData[$i]['C']) : null;
                    $mapel_id = isset($sheetData[$i]['D']) ? trim($sheetData[$i]['D']) : null;

                    // Dapatkan nama mapel berdasarkan ID
                    // Jika mapel_id kosong dari Excel, nama_mapel juga akan dianggap null.
                    // Jika mapel_id ada tapi tidak ditemukan di database, akan menampilkan 'ID tidak ditemukan'.
                    $nama_mapel = null;
                    if (!empty($mapel_id)) {
                        $nama_mapel = isset($mapel_nama_map_preview[$mapel_id]) ? $mapel_nama_map_preview[$mapel_id] : 'ID tidak ditemukan';
                    }

                    $data_for_preview[] = [
                        'nip'         => $nip,
                        'nama_guru'   => $nama_guru,
                        'email'       => $email,
                        'mapel_id'    => $mapel_id,
                        'nama_mapel'  => $nama_mapel // Tambahkan field nama_mapel untuk preview
                    ];
                }

                unlink($file); // Hapus file yang diupload setelah diproses

                // Panggil metode import untuk me-render view dengan data preview
                $this->import($data_for_preview);

            } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
                // Tangani error spesifik dari PhpSpreadsheet Reader
                $this->session->set_flashdata('error_message', "Error membaca spreadsheet: " . $e->getMessage());
                if (file_exists($file)) {
                    unlink($file);
                }
                redirect('guru/import');
            } catch (Exception $e) {
                // Tangani error umum lainnya
                $this->session->set_flashdata('error_message', "Terjadi kesalahan tak terduga: " . $e->getMessage());
                if (file_exists($file)) {
                    unlink($file);
                }
                redirect('guru/import');
            }
        }
    }

    public function do_import()
    {
        $input = json_decode($this->input->post('data', true));
        $data = [];
        foreach ($input as $d) {
            $data[] = [
                'nip'         => $d->nip,
                'nama_guru'   => $d->nama_guru, // Changed nama_dosen to nama_guru
                'email'       => $d->email,
                'mapel_id'    => $d->mapel_id // 'mapel_id' is the database column name
            ];
        }

        $save = $this->master->create('guru', $data, true); // Changed dosen to guru
        if ($save) {
            redirect('guru'); // Changed dosen to guru
        } else {
            redirect('guru/import'); // Changed dosen/import to guru/import
        }
    }
}