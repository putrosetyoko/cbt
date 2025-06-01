<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pjsoal extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin()) {
            show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini.', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'form_validation']);
        $this->load->model('Master_model', 'master'); // Menggunakan Master_model yang sudah ada
        $this->form_validation->set_error_delimiters('', '');
    }

    /**
     * Helper function untuk output JSON
     */
    public function output_json($data, $encode = true)
    {
        if ($encode && !is_string($data)) {
            $data = json_encode($data);
        }
        $this->output->set_content_type('application/json')->set_output($data);
    }

    /**
     * Halaman utama untuk menampilkan daftar PJ Soal
     */
    public function index()
    {
        $data = [
            'user'             => $this->ion_auth->user()->row(),
            'judul'            => 'PJ Soal per Mapel',
            'subjudul'         => 'Data Penugasan PJ Soal per Mapel',
            'all_tahun_ajaran' => $this->master->getAllTahunAjaran(), // Untuk filter
            'all_mapel'        => $this->master->getAllMapel(),       // Untuk filter
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('relasi/pjsoal/data', $data); // Path ke view daftar PJ Soal
        $this->load->view('_templates/dashboard/_footer.php');
    }

    /**
     * Data untuk DataTables PJ Soal
     */
    public function data()
    {
        $filter_tahun_ajaran = $this->input->post('filter_tahun_ajaran', true);
        $filter_mapel        = $this->input->post('filter_mapel', true);
        // Menggunakan fungsi getDataPJSoal dari Master_model
        $this->output_json($this->master->getDataPJSoal($filter_tahun_ajaran, $filter_mapel), false);
    }

    /**
     * Menampilkan form untuk menambah/mengatur PJ Soal.
     */
    public function add()
    {
        $data = [
            'user'             => $this->ion_auth->user()->row(),
            'judul'            => 'Tambah PJ Soal',
            'subjudul'         => 'Tambah Data PJ Soal per Mapel',
            // Ambil semua tahun ajaran, bisa difilter hanya yang aktif jika perlu
            'all_tahun_ajaran' => $this->master->getAllTahunAjaran(true), 
            // Mapel dan Guru akan di-load via AJAX berdasarkan Tahun Ajaran yang dipilih di view
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('relasi/pjsoal/add', $data); // Path ke view form tambah
        $this->load->view('_templates/dashboard/_footer.php');
    }
    
    /**
     * AJAX call: Mendapatkan mapel yang belum punya PJ di tahun ajaran tertentu
     * atau mapel yang sedang diedit PJ-nya.
     */
    public function get_mapel_available_for_pj($id_tahun_ajaran, $current_mapel_id = null)
    {
        if (!$this->input->is_ajax_request() || empty($id_tahun_ajaran) || !is_numeric($id_tahun_ajaran)) {
            return $this->output_json(['status' => false, 'message' => 'Request tidak valid atau ID Tahun Ajaran kosong.']);
        }
        $mapel_available = $this->master->getMapelAvailableForPJ($id_tahun_ajaran, $current_mapel_id);
        $this->output_json(['status' => true, 'data_mapel' => $mapel_available]);
    }

    /**
     * AJAX call: Mendapatkan guru yang belum jadi PJ di tahun ajaran tertentu
     * atau adalah guru PJ saat ini (untuk edit).
     */
    public function get_guru_available_for_pj($id_tahun_ajaran, $current_guru_id = null)
    {
        if (!$this->input->is_ajax_request() || empty($id_tahun_ajaran) || !is_numeric($id_tahun_ajaran)) {
            return $this->output_json(['status' => false, 'message' => 'Request tidak valid atau ID Tahun Ajaran kosong.']);
        }
        $guru_available = $this->master->getGuruAvailableForPJ($id_tahun_ajaran, $current_guru_id);
        $this->output_json(['status' => true, 'data_guru' => $guru_available]);
    }

    /**
     * AJAX call: Mendapatkan detail PJ Soal yang sudah ada untuk mapel dan tahun ajaran tertentu.
     * Digunakan di form 'add' untuk memberi info jika mapel sudah ada PJ-nya.
     */
    public function get_pj_for_mapel_ta()
    {
        if (!$this->input->is_ajax_request() || $this->input->method(TRUE) !== 'POST') {
            return $this->output_json(['status' => false, 'message' => 'Request tidak valid.']);
        }
        $mapel_id = $this->input->post('mapel_id', true);
        $id_tahun_ajaran = $this->input->post('id_tahun_ajaran', true);

        if (empty($mapel_id) || !is_numeric($mapel_id) || empty($id_tahun_ajaran) || !is_numeric($id_tahun_ajaran)) {
            return $this->output_json(['status' => false, 'message' => 'Parameter tidak lengkap.']);
        }
        $pj_data = $this->master->getPJSoalByMapelTahun($mapel_id, $id_tahun_ajaran);
        if ($pj_data) {
            $this->output_json(['status' => true, 'pj_data' => $pj_data]);
        } else {
            $this->output_json(['status' => false, 'message' => 'Belum ada PJ untuk mapel ini di tahun ajaran tersebut.']);
        }
    }

    /**
     * Menyimpan data PJ Soal baru atau mengupdate PJ Soal yang sudah ada.
     * Logika ini menangani unique constraint:
     * 1. Satu mapel hanya bisa punya satu PJ per tahun ajaran.
     * 2. Satu guru hanya bisa jadi PJ untuk satu mapel per tahun ajaran.
     */
    public function save()
    {
        $this->form_validation->set_rules('id_tahun_ajaran', 'Tahun Ajaran', 'required|numeric');
        $this->form_validation->set_rules('mapel_id', 'Mata Pelajaran', 'required|numeric');
        $this->form_validation->set_rules('guru_id', 'Guru Penanggung Jawab', 'required|numeric');
        $this->form_validation->set_rules('keterangan', 'Keterangan', 'trim');
        $this->form_validation->set_message('required', '{field} wajib diisi.');

        if ($this->form_validation->run() == FALSE) {
            $errors = array_filter($this->form_validation->error_array());
            $this->output_json(['status' => false, 'errors' => $errors, 'message' => 'Periksa kembali inputan Anda.']);
            return;
        }

        $id_tahun_ajaran = $this->input->post('id_tahun_ajaran', true);
        $mapel_id        = $this->input->post('mapel_id', true);
        $guru_id_baru    = $this->input->post('guru_id', true); // Guru yang akan dijadikan PJ
        $keterangan      = $this->input->post('keterangan', true);

        // 1. Cek apakah guru_id_baru sudah menjadi PJ untuk mapel LAIN di tahun ajaran yang sama.
        $pj_existing_untuk_guru_baru = $this->master->getPJSoalByGuruTahun($guru_id_baru, $id_tahun_ajaran);
        if ($pj_existing_untuk_guru_baru && $pj_existing_untuk_guru_baru->mapel_id != $mapel_id) {
            $mapel_diampu = $this->master->getMapelById($pj_existing_untuk_guru_baru->mapel_id, true);
            $nama_mapel_diampu = $mapel_diampu ? $mapel_diampu->nama_mapel : "lain";
            $this->output_json(['status' => false, 'message' => 'Guru tersebut sudah menjadi PJ untuk mata pelajaran "'.$nama_mapel_diampu.'" di tahun ajaran ini.']);
            return;
        }
        
        // 2. Cek apakah mapel_id di tahun_ajaran_id ini sudah memiliki PJ.
        $pj_existing_untuk_mapel = $this->master->getPJSoalByMapelTahun($mapel_id, $id_tahun_ajaran);

        $data_pjsa = [
            'id_tahun_ajaran' => $id_tahun_ajaran,
            'mapel_id'        => $mapel_id,
            'guru_id'         => $guru_id_baru,
            'keterangan'      => $keterangan,
            // 'ditetapkan_pada' akan otomatis oleh MySQL jika default CURRENT_TIMESTAMP
        ];

        $action_success = false;
        $message = '';

        if ($pj_existing_untuk_mapel) {
            // Jika mapel ini sudah ada PJ-nya (id_pjsa = $pj_existing_untuk_mapel->id_pjsa)
            // Kita UPDATE guru_id dan keterangan.
            // Validasi unik guru-TA untuk guru_id_baru sudah dilakukan di atas.
            if ($pj_existing_untuk_mapel->guru_id == $guru_id_baru && $pj_existing_untuk_mapel->keterangan == $keterangan) {
                $this->output_json(['status' => true, 'message' => 'Tidak ada perubahan data.']);
                return;
            }
            $action_success = $this->master->update('penanggung_jawab_soal_ajaran', $data_pjsa, 'id_pjsa', $pj_existing_untuk_mapel->id_pjsa);
            $message = $action_success ? 'Penanggung Jawab Soal berhasil diperbarui.' : 'Gagal memperbarui Penanggung Jawab Soal.';
        } else {
            // Jika mapel ini belum ada PJ-nya, kita INSERT baru.
            // Validasi unik guru-TA untuk guru_id_baru sudah dilakukan di atas.
            $action_success = $this->master->create('penanggung_jawab_soal_ajaran', $data_pjsa);
            $message = $action_success ? 'Penanggung Jawab Soal berhasil ditetapkan.' : 'Gagal menetapkan Penanggung Jawab Soal.';
        }

        $this->output_json(['status' => $action_success, 'message' => $message]);
    }
    
    /**
     * Menampilkan form edit untuk satu entri PJ Soal.
     * Di sini, Admin hanya bisa mengubah Guru PJ atau Keterangan untuk Mapel dan TA yang sudah ada.
     */
    public function edit($id_pjsa = null)
    {
        if (!$id_pjsa || !is_numeric($id_pjsa)) { show_404(); return; }
        $pjsa_data = $this->master->getPJSoalById($id_pjsa);
        if (!$pjsa_data) {
            show_error('Data Penanggung Jawab Soal tidak ditemukan.', 404, 'Not Found');
            return;
        }

        $data = [
            'user'             => $this->ion_auth->user()->row(),
            'judul'            => 'Edit PJ Soal',
            'subjudul'         => 'Edit PJ Soal per Mapel',
            'pjsa'             => $pjsa_data,
            // Guru yang available untuk tahun ajaran ini, KECUALI yang sudah jadi PJ mapel LAIN.
            // Dan sertakan guru PJ saat ini agar tetap bisa dipilih.
            'all_guru'         => $this->master->getGuruAvailableForPJ($pjsa_data->id_tahun_ajaran, $pjsa_data->guru_id) 
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('relasi/pjsoal/edit', $data); // Path ke view form edit
        $this->load->view('_templates/dashboard/_footer.php');
    }

    /**
     * Memproses update data PJ Soal (hanya guru_id dan keterangan).
     */
    public function update()
    {
        $id_pjsa = $this->input->post('id_pjsa', true);
        if (empty($id_pjsa) || !is_numeric($id_pjsa)) {
            $this->output_json(['status' => false, 'message' => 'ID Penugasan PJ Soal tidak valid.']);
            return;
        }

        $this->form_validation->set_rules('guru_id', 'Guru Penanggung Jawab', 'required|numeric');
        $this->form_validation->set_rules('keterangan', 'Keterangan', 'trim');
        $this->form_validation->set_message('required', '{field} wajib diisi.');

        if ($this->form_validation->run() == FALSE) {
            $errors = ['guru_id' => form_error('guru_id'), 'keterangan' => form_error('keterangan')];
            $this->output_json(['status' => false, 'errors' => array_filter($errors), 'message' => 'Periksa inputan Anda.']);
            return;
        }
        
        $pjsa_current = $this->master->getPJSoalById($id_pjsa);
        if(!$pjsa_current){
            $this->output_json(['status' => false, 'message' => 'Data penugasan asli tidak ditemukan.']);
            return;
        }

        $guru_id_baru    = $this->input->post('guru_id', true);
        $keterangan_baru = $this->input->post('keterangan', true);

        // Cek apakah guru_id_baru (jika berubah) sudah menjadi PJ untuk mapel LAIN di tahun ajaran yang sama.
        if ($pjsa_current->guru_id != $guru_id_baru) {
            $existing_pj_for_new_guru = $this->master->getPJSoalByGuruTahun($guru_id_baru, $pjsa_current->id_tahun_ajaran);
            if ($existing_pj_for_new_guru) { // Jika guru baru sudah jadi PJ (untuk mapel apapun di TA itu)
                $mapel_diampu = $this->master->getMapelById($existing_pj_for_new_guru->mapel_id, true);
                $nama_mapel_diampu = $mapel_diampu ? $mapel_diampu->nama_mapel : "lain";
                $this->output_json(['status' => false, 'message' => 'Guru yang dipilih sudah menjadi PJ untuk mata pelajaran "'.$nama_mapel_diampu.'" di tahun ajaran ini.']);
                return;
            }
        }
        
        // Cek apakah ada perubahan
        if ($pjsa_current->guru_id == $guru_id_baru && $pjsa_current->keterangan == $keterangan_baru) {
            $this->output_json(['status' => true, 'message' => 'Tidak ada perubahan data.']);
            return;
        }

        $data_update = [
            'guru_id'    => $guru_id_baru,
            'keterangan' => $keterangan_baru,
        ];

        if ($this->master->update('penanggung_jawab_soal_ajaran', $data_update, 'id_pjsa', $id_pjsa)) {
            $this->output_json(['status' => true, 'message' => 'Data Penanggung Jawab Soal berhasil diperbarui.']);
        } else {
            $this->output_json(['status' => false, 'message' => 'Gagal memperbarui data.']);
        }
    }

    /**
     * Menghapus satu entri PJ Soal.
     */
    public function delete()
    {
        $id_pjsa = $this->input->post('id_pjsa', true);
        
        if (empty($id_pjsa) || !is_numeric($id_pjsa)) {
            $this->output_json(['status' => false, 'message' => 'ID Penugasan PJ Soal tidak valid.']);
            return;
        }

        if ($this->master->delete('penanggung_jawab_soal_ajaran', ['id_pjsa' => $id_pjsa])) {
            $this->output_json(['status' => true, 'message' => 'Data Penanggung Jawab Soal berhasil dihapus.']);
        } else {
            $this->output_json(['status' => false, 'message' => 'Gagal menghapus data.']);
        }
    }
}
