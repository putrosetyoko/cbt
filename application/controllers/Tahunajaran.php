<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tahunajaran extends CI_Controller
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
        $this->load->model('Master_model', 'master'); // Menggunakan Master_model yang sudah ada
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
            'judul' => 'Tahun Ajaran',
            'subjudul' => 'Data Tahun Ajaran'
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/tahunajaran/data'); // View untuk menampilkan data
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function data()
    {
        $this->output_json($this->master->getDataTahunAjaran(), false); // Fungsi baru di Master_model
    }

    public function add()
    {
        $data = [
            'user'     => $this->ion_auth->user()->row(),
            'judul'    => 'Tambah Tahun Ajaran',
            'subjudul' => 'Tambah Data Tahun Ajaran',
            'form_action' => base_url('tahunajaran/save') // Aksi form ke method save
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/tahunajaran/add');    // View untuk form tambah
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function edit($id) // Menerima ID sebagai parameter
    {
        $tahun_ajaran = $this->master->getTahunAjaranById($id); // Fungsi baru di Master_model
        if (!$tahun_ajaran) {
            show_404(); // Jika data tidak ditemukan
        }
        $data = [
            'user'         => $this->ion_auth->user()->row(),
            'judul'        => 'Edit Tahun Ajaran',
            'subjudul'     => 'Edit Data Tahun Ajaran',
            'form_action'  => base_url('tahunajaran/save'), // Aksi form ke method save
            'tahun_ajaran' => $tahun_ajaran
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/tahunajaran/edit');   // View untuk form edit
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function save()
    {
        // Validasi Form
        $this->form_validation->set_rules('nama_tahun_ajaran', 'Nama Tahun Ajaran', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('semester', 'Semester', 'required|in_list[Ganjil,Genap]');
        $this->form_validation->set_rules('tgl_mulai', 'Tanggal Mulai', 'required');
        $this->form_validation->set_rules('tgl_selesai', 'Tanggal Selesai', 'required');
        $this->form_validation->set_rules('status', 'Status', 'required|in_list[aktif,tidak_aktif]');
        
        // Unique check untuk nama_tahun_ajaran (hanya saat add atau jika nama berubah saat edit)
        $id_tahun_ajaran = $this->input->post('id_tahun_ajaran', true);
        $original_value = '';
        if ($id_tahun_ajaran) { // Mode edit
            $original_data = $this->master->getTahunAjaranById($id_tahun_ajaran);
            if ($original_data) {
                $original_value = $original_data->nama_tahun_ajaran;
            }
        }
        if ($this->input->post('nama_tahun_ajaran') != $original_value) {
            $this->form_validation->set_rules('nama_tahun_ajaran', 'Nama Tahun Ajaran', 'is_unique[tahun_ajaran.nama_tahun_ajaran]');
        }


        if ($this->form_validation->run() === FALSE) {
            $errors = array(
                'nama_tahun_ajaran' => form_error('nama_tahun_ajaran'),
                'semester'          => form_error('semester'),
                'tgl_mulai'         => form_error('tgl_mulai'),
                'tgl_selesai'       => form_error('tgl_selesai'),
                'status'            => form_error('status')
            );
            $this->output_json(['status' => false, 'errors' => $errors]);
        } else {
            $data_tahun_ajaran = [
                'nama_tahun_ajaran' => $this->input->post('nama_tahun_ajaran', true),
                'semester'          => $this->input->post('semester', true),
                'tgl_mulai'         => $this->input->post('tgl_mulai', true),
                'tgl_selesai'       => $this->input->post('tgl_selesai', true),
                'status'            => $this->input->post('status', true)
            ];

            // Logika untuk memastikan hanya ada satu status 'aktif'
            if ($data_tahun_ajaran['status'] == 'aktif') {
                $this->master->setAllTahunAjaranTidakAktif($id_tahun_ajaran); // Fungsi baru di Master_model
            }

            if ($id_tahun_ajaran) { // Mode Edit
                $action = $this->master->update('tahun_ajaran', $data_tahun_ajaran, 'id_tahun_ajaran', $id_tahun_ajaran);
                $message = 'Data Tahun Ajaran berhasil diperbarui.';
            } else { // Mode Add
                $action = $this->master->create('tahun_ajaran', $data_tahun_ajaran);
                $message = 'Data Tahun Ajaran berhasil ditambahkan.';
            }

            if ($action) {
                $this->output_json(['status' => true, 'message' => $message]);
            } else {
                $this->output_json(['status' => false, 'message' => 'Gagal menyimpan data.']);
            }
        }
    }

    public function delete()
    {
        $chk = $this->input->post('checked', true);
        if (!$chk) {
            $this->output_json(['status' => false, 'message' => 'Tidak ada data yang dipilih.']);
        } else {
            // Cek apakah ada tahun ajaran aktif yang akan dihapus
            foreach ($chk as $id) {
                $ta = $this->master->getTahunAjaranById($id);
                if ($ta && $ta->status == 'aktif') {
                    $this->output_json(['status' => false, 'message' => 'Tahun ajaran yang aktif tidak boleh dihapus. Nonaktifkan terlebih dahulu.']);
                    return;
                }
            }

            if ($this->master->delete('tahun_ajaran', $chk, 'id_tahun_ajaran')) {
                $this->output_json(['status' => true, 'message' => count($chk) . ' data berhasil dihapus.']);
            } else {
                $this->output_json(['status' => false, 'message' => 'Gagal menghapus data.']);
            }
        }
    }

    // Fungsi untuk set status aktif (dipanggil via AJAX)
    public function set_status_aktif($id)
    {
        if ($this->master->setAllTahunAjaranTidakAktif($id)) {
            $update_status = $this->master->update('tahun_ajaran', ['status' => 'aktif'], 'id_tahun_ajaran', $id);
            if ($update_status) {
                $this->output_json(['status' => true, 'message' => 'Status Tahun Ajaran berhasil diaktifkan.']);
            } else {
                $this->output_json(['status' => false, 'message' => 'Gagal mengaktifkan status.']);
            }
        } else {
            $this->output_json(['status' => false, 'message' => 'Gagal menonaktifkan tahun ajaran lain.']);
        }
    }
}