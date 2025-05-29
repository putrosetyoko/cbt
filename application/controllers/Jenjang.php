<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Jenjang extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin()) {
            show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'form_validation']);
        $this->load->model('Master_model', 'master'); // Menggunakan Master_model
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
            'judul' => 'Jenjang Pendidikan',
            'subjudul' => 'Data Jenjang Pendidikan'
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/jenjang/data'); // View untuk menampilkan data jenjang
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function data()
    {
        // Akan memanggil fungsi getDataJenjang() di Master_model
        $this->output_json($this->master->getDataJenjang(), false);
    }

    public function add()
    {
        $data = [
            'user'     => $this->ion_auth->user()->row(),
            'judul'    => 'Tambah Jenjang',
            'subjudul' => 'Tambah Data Jenjang',
            'form_action' => base_url('jenjang/save')
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/jenjang/add'); // View untuk form tambah jenjang
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function edit($id)
    {
        $jenjang_data = $this->master->getJenjangById($id); // Fungsi baru di Master_model
        if (!$jenjang_data) {
            show_error('Data Jenjang tidak ditemukan', 404, 'Not Found');
        }
        $data = [
            'user'        => $this->ion_auth->user()->row(),
            'judul'       => 'Edit Jenjang',
            'subjudul'    => 'Edit Data Jenjang',
            'form_action' => base_url('jenjang/save'),
            'jenjang'  => $jenjang_data // Mengirim data jenjang ke view
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/jenjang/edit'); // View untuk form edit jenjang
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function save()
    {
        $id_jenjang = $this->input->post('id_jenjang', true);

        // Validasi Form
        $this->form_validation->set_rules('nama_jenjang', 'Nama Jenjang', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('deskripsi', 'Deskripsi', 'trim|max_length[255]');

        // Unique check untuk nama_jenjang
        $original_value = '';
        if ($id_jenjang) { // Mode edit
            $original_data = $this->master->getJenjangById($id_jenjang);
            if ($original_data) {
                $original_value = $original_data->nama_jenjang;
            }
        }
        if ($this->input->post('nama_jenjang') != $original_value) {
            $this->form_validation->set_rules('nama_jenjang', 'Nama Jenjang', 'is_unique[jenjang.nama_jenjang]');
        }

        if ($this->form_validation->run() === FALSE) {
            $errors = [
                'nama_jenjang' => form_error('nama_jenjang'),
                'deskripsi'   => form_error('deskripsi'),
            ];
            $this->output_json(['status' => false, 'errors' => $errors]);
        } else {
            $data_jenjang = [
                'nama_jenjang' => $this->input->post('nama_jenjang', true),
                'deskripsi'   => $this->input->post('deskripsi', true),
            ];

            if ($id_jenjang) { // Mode Edit
                $action = $this->master->update('jenjang', $data_jenjang, 'id_jenjang', $id_jenjang);
                $message = 'Data Jenjang berhasil diperbarui.';
            } else { // Mode Add
                $action = $this->master->create('jenjang', $data_jenjang);
                $message = 'Data Jenjang berhasil ditambahkan.';
            }

            if ($action) {
                $this->output_json(['status' => true, 'message' => $message]);
            } else {
                $this->output_json(['status' => false, 'message' => 'Gagal menyimpan data. Database error.']);
            }
        }
    }

    public function delete()
    {
        $chk = $this->input->post('checked', true);
        if (!$chk) {
            $this->output_json(['status' => false, 'message' => 'Tidak ada data yang dipilih untuk dihapus.']);
        } else {
            // Di sini Anda bisa menambahkan validasi jika jenjang digunakan di tabel lain (misal: kelas)
            // sebelum menghapus. Contoh:
            // foreach ($chk as $id) {
            //     $is_used = $this->master->isJenjangUsed($id); // Buat fungsi ini di Master_model
            //     if ($is_used) {
            //         $this->output_json(['status' => false, 'message' => 'Salah satu jenjang tidak bisa dihapus karena masih digunakan.']);
            //         return;
            //     }
            // }

            if ($this->master->delete('jenjang', $chk, 'id_jenjang')) {
                $this->output_json(['status' => true, 'message' => count($chk) . ' data jenjang berhasil dihapus.']);
            } else {
                $this->output_json(['status' => false, 'message' => 'Gagal menghapus data jenjang.']);
            }
        }
    }
}