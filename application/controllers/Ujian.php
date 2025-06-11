<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ujian extends MY_Controller {

    private $user_id_ion_auth; // ID dari tabel users ion_auth
    private $is_admin;
    private $is_guru;
    private $is_siswa;      // Ditambahkan
    private $guru_data;     // Objek data dari tabel 'guru'
    private $pj_mapel_data; // Objek data mapel jika guru login adalah PJ Soal untuk TA aktif
    private $siswa_data;    // Objek data siswa yang login (termasuk id_kelas, id_jenjang, id_tahun_ajaran aktif) - Ditambahkan

    public function __construct(){
        parent::__construct();
        if (!$this->ion_auth->logged_in()){
            redirect('auth');
        }
        
        $this->load->library(['datatables', 'form_validation', 'session', 'encryption']);
        $this->load->helper(['my', 'form', 'url', 'string', 'date']); // Tambahkan date helper

        $this->load->model('Master_model', 'master');
        $this->load->model('Ujian_model', 'ujian_m'); 
        $this->load->model('Soal_model', 'soal_m');

        $user_ion_auth = $this->ion_auth->user()->row();
        $this->user_id_ion_auth = $user_ion_auth->id;
        $this->is_admin = $this->ion_auth->is_admin();
        $this->is_guru = $this->ion_auth->in_group('guru');
        $this->is_siswa = $this->ion_auth->in_group('siswa'); // Ditambahkan

        $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif(); // Ambil sekali

        if ($this->is_guru) {
            if (!empty($user_ion_auth->username)) { // Asumsi username adalah NIP
                $this->guru_data = $this->db->get_where('guru', ['nip' => $user_ion_auth->username])->row();
            }
            if (!$this->guru_data && !empty($user_ion_auth->email)) { // Fallback ke email
                $this->guru_data = $this->db->get_where('guru', ['email' => $user_ion_auth->email])->row();
            }

            if ($this->guru_data && isset($this->guru_data->id_guru) && $tahun_ajaran_aktif) {
                $this->pj_mapel_data = $this->master->getMapelPJByGuruTahun($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);
            } else {
                $this->pj_mapel_data = null;
            }
        } elseif ($this->is_siswa) { // Ditambahkan: Ambil data siswa jika login sebagai siswa
            if (!empty($user_ion_auth->username) && $tahun_ajaran_aktif) { // Asumsi username siswa adalah NISN
                // Method get_siswa_detail_for_ujian akan mengambil id_siswa, nama, id_kelas, nama_kelas, id_jenjang, nama_jenjang, id_tahun_ajaran
                $this->siswa_data = $this->ujian_m->get_siswa_detail_for_ujian($user_ion_auth->username, $tahun_ajaran_aktif->id_tahun_ajaran);
            }
            if (empty($this->siswa_data)) {
                log_message('error', 'UjianController: Data siswa atau penempatan kelas siswa tidak ditemukan untuk user: ' . ($user_ion_auth->username ?? 'N/A') . ' pada TA aktif.');
                // Pertimbangkan untuk redirect atau menampilkan pesan error jika data siswa tidak lengkap
                // $this->session->set_flashdata('error', 'Data profil siswa atau penempatan kelas Anda tidak lengkap. Hubungi administrator.');
                // redirect('dashboard');
            }
        }
        $this->form_validation->set_error_delimiters('<span class="help-block text-danger">', '</span>');
    }

    /**
     * Helper untuk memeriksa apakah user saat ini (Admin/PJ Soal) bisa mengelola ujian.
     */
    private function _can_manage_ujian($id_ujian = null) {
        if ($this->is_admin) {
            return true;
        }
        if ($this->is_guru && $this->pj_mapel_data) {
            if ($id_ujian === null) { // Untuk akses umum membuat ujian
                return true;
            }
            // Untuk edit/delete, cek apakah ujian ini adalah mapel PJ-nya
            $ujian = $this->ujian_m->get_ujian_by_id($id_ujian);
            if ($ujian && isset($this->pj_mapel_data->id_mapel) && $ujian->mapel_id == $this->pj_mapel_data->id_mapel) {
                // PJ Soal bisa edit/delete semua ujian di mapel PJ-nya.
                // Atau tambahkan: && $ujian->guru_id == $this->guru_data->id_guru jika PJ hanya boleh kelola yg dia buat.
                return true; 
            }
        }
        return false;
    }

    /**
     * Helper untuk memastikan akses hanya untuk siswa dan data siswa valid.
     */
    private function _akses_siswa_required() {
        if (!$this->is_siswa) {
            $this->session->set_flashdata('error', 'Halaman ini hanya untuk siswa.');
            redirect('dashboard'); // Atau halaman login jika belum login
            exit; // Hentikan eksekusi lebih lanjut
        }
        if (empty($this->siswa_data) || !isset($this->siswa_data->id_siswa) || !isset($this->siswa_data->id_kelas) || !isset($this->siswa_data->id_jenjang) || !isset($this->siswa_data->id_tahun_ajaran)) {
            log_message('error', 'UjianController (_akses_siswa_required): Akses siswa ditolak karena data siswa tidak lengkap. User ID Ion Auth: ' . $this->user_id_ion_auth);
            $this->session->set_flashdata('error', 'Data profil siswa atau penempatan kelas Anda tidak lengkap. Hubungi administrator.');
            redirect('dashboard');
            exit; // Hentikan eksekusi lebih lanjut
        }
    }


    public function output_json($data, $encode = true)
    {
        if($encode && (is_array($data) || is_object($data))) {
            $data = json_encode($data);
        }
        $this->output->set_content_type('application/json')->set_output($data);
    }

    /**
     * Halaman utama Kelola Ujian, menampilkan daftar ujian.
     * URL: /ujian atau /ujian/index
     */
    public function index()
    {
        // Semua guru (PJ & Non-PJ) dan Admin bisa melihat daftar ini,
        // tapi data yang tampil akan difilter di method data() & model.
        
        $data = [
            'user'          => $this->ion_auth->user()->row(),
            'judul'         => 'Kelola Ujian',
            'subjudul'      => 'Daftar Ujian',
            'is_admin'      => $this->is_admin,
            'is_guru'       => $this->is_guru,
            'guru_data'     => $this->guru_data,
            'pj_mapel_data' => $this->pj_mapel_data,
            'can_add_ujian' => $this->_can_manage_ujian() // Cek apakah bisa menambah ujian baru
        ];

        // Opsi filter untuk dropdown di view
        $data['filter_tahun_ajaran_options'] = $this->master->getAllTahunAjaran(); // Ambil semua TA untuk filter
        $data['filter_jenjang_options'] = $this->master->getAllJenjang();

        if ($this->is_admin) {
            $data['filter_mapel_options'] = $this->master->getAllMapel();
        } elseif ($this->is_guru && $this->pj_mapel_data) {
            $data['filter_mapel_options'] = [$this->pj_mapel_data]; 
        } elseif ($this->is_guru && !$this->pj_mapel_data && $this->guru_data) { // Guru Non-PJ
            $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif();
            if($tahun_ajaran_aktif && isset($this->guru_data->id_guru)){
                $mapel_ids_diajar = $this->master->getMapelDiajarGuru($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);
                if(!empty($mapel_ids_diajar)){
                    $data['filter_mapel_options'] = $this->db->where_in('id_mapel', $mapel_ids_diajar)->order_by('nama_mapel', 'ASC')->get('mapel')->result();
                } else {
                    $data['filter_mapel_options'] = [];
                }
            } else {
                $data['filter_mapel_options'] = [];
            }
        } else {
            $data['filter_mapel_options'] = []; // Default kosong jika tidak ada kondisi cocok
        }


        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('ujian/data', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }

    /**
     * Endpoint AJAX untuk DataTables.
     */
    public function data()
    {
        $filters = [
            'id_tahun_ajaran'   => $this->input->post('filter_tahun_ajaran_ujian', true),
            'mapel_id'          => $this->input->post('filter_mapel_ujian', true),
            'id_jenjang_target' => $this->input->post('filter_jenjang_ujian', true),
        ];

        $guru_context = [
            'is_admin'      => $this->is_admin,
            'is_guru'       => $this->is_guru,
            'id_guru'       => ($this->is_guru && $this->guru_data) ? $this->guru_data->id_guru : null,
            'id_mapel_pj'   => ($this->is_guru && $this->pj_mapel_data) ? $this->pj_mapel_data->id_mapel : null,
            'mapel_ids_diajar' => []
        ];

        if ($this->is_guru && !$this->pj_mapel_data && $this->guru_data) {
            $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif();
            
            $id_ta_filter = ($filters['id_tahun_ajaran'] && $filters['id_tahun_ajaran'] !== 'all') 
                            ? $filters['id_tahun_ajaran'] 
                            : ($tahun_ajaran_aktif->id_tahun_ajaran ?? null);
            
            if ($id_ta_filter && isset($this->guru_data->id_guru)) {
                $guru_context['mapel_ids_diajar'] = $this->master->getMapelDiajarGuru($this->guru_data->id_guru, $id_ta_filter);
            }
        }
        
        // Panggil model dan kirim hasilnya langsung
        // Model sekarang akan mengembalikan JSON string yang sudah diformat
        $this->output_json($this->ujian_m->getUjianDatatables($filters, $guru_context), false); // FALSE karena model sudah meng-encode ke JSON
    }

    /**
     * Menampilkan form tambah ujian baru.
     * URL: /ujian/add
     */
    public function add()
    {
        if (!$this->_can_manage_ujian()) {
            $this->session->set_flashdata('error', 'Anda tidak memiliki hak untuk menambah ujian.');
            redirect('ujian');
        }

        $data = [
            'user'          => $this->ion_auth->user()->row(),
            'judul'         => 'Kelola Ujian',
            'subjudul'      => 'Buat Ujian Baru',
            'is_admin'      => $this->is_admin,
            'guru_data'     => $this->guru_data,
            'pj_mapel_data' => $this->pj_mapel_data,
            
            // SESUAIKAN NAMA KEY DI SINI:
            'all_jenjang'           => $this->master->getAllJenjang(),
            'all_tahun_ajaran'      => $this->master->getAllTahunAjaran(), 
            // Anda mungkin punya parameter di getAllTahunAjaran() untuk mengambil yg aktif saja,
            // contoh dari controller Anda sebelumnya: $this->master->getAllTahunAjaran(true)
            // Jika tidak, logika selected di view sudah cukup.
        ];
        
        if ($this->is_admin) {
            $data['all_mapel_options_for_admin'] = $this->master->getAllMapel();
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('ujian/add', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }

    /**
     * Menyimpan data ujian baru.
     * URL: /ujian/save (Method POST)
     */
    public function save()
    {
        if (!$this->_can_manage_ujian()) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $this->form_validation->set_rules('nama_ujian', 'Nama Ujian', 'required|trim|min_length[3]|max_length[100]');
        $this->form_validation->set_rules('id_jenjang_target', 'Jenjang Target', 'required|integer');
        $this->form_validation->set_rules('id_tahun_ajaran', 'Tahun Ajaran', 'required|integer');
        $this->form_validation->set_rules('jumlah_soal', 'Jumlah Soal Ditampilkan', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('waktu', 'Waktu Ujian (menit)', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('tgl_mulai', 'Tanggal Mulai', 'required');
        $this->form_validation->set_rules('terlambat', 'Batas Akhir Masuk', 'required');
        // Tidak perlu validasi 'jenis' jika sudah dihapus, gunakan 'acak_soal'
        $this->form_validation->set_rules('acak_soal', 'Acak Soal', 'required|in_list[Y,N]');
        $this->form_validation->set_rules('acak_opsi', 'Acak Opsi', 'required|in_list[Y,N]');
        $this->form_validation->set_rules('aktif', 'Status Aktif', 'required|in_list[Y,N]');

        $mapel_id_input = $this->input->post('mapel_id', true);

        if ($this->is_admin) {
            $this->form_validation->set_rules('mapel_id', 'Mata Pelajaran', 'required|integer');
            // Jika admin menentukan guru_id (PJ) untuk ujian ini:
            // $this->form_validation->set_rules('guru_id_assign', 'Guru PJ', 'required|integer');
        } elseif ($this->is_guru && $this->pj_mapel_data) {
            // Untuk PJ Soal, mapel_id sudah ditentukan, tidak perlu validasi dari input
        } else { // Seharusnya tidak sampai sini karena _can_manage_ujian()
            $this->output_json(['status' => false, 'message' => 'Konfigurasi pengguna tidak valid.']);
            return;
        }


        if ($this->form_validation->run() === FALSE) {
            $errors = array();
            // Ambil semua error
            foreach ($this->form_validation->get_error_array() as $key => $value) {
                if (!empty($value)) $errors[$key] = $value;
            }
            $this->output_json(['status' => false, 'errors' => $errors, 'message' => 'Form tidak valid. Periksa kembali isian Anda.']);
            return;
        }

        // Siapkan data untuk disimpan
        $data_ujian = [
            'nama_ujian'        => $this->input->post('nama_ujian', true),
            'id_jenjang_target' => $this->input->post('id_jenjang_target', true),
            'id_tahun_ajaran'   => $this->input->post('id_tahun_ajaran', true),
            'jumlah_soal'       => $this->input->post('jumlah_soal', true),
            'waktu'             => $this->input->post('waktu', true),
            'tgl_mulai'         => $this->convert_datetime_to_db($this->input->post('tgl_mulai', true)),
            'terlambat'         => $this->convert_datetime_to_db($this->input->post('terlambat', true)),
            'acak_soal'         => $this->input->post('acak_soal', true),
            'acak_opsi'         => $this->input->post('acak_opsi', true),
            'aktif'             => $this->input->post('aktif', true),
            'token'             => strtoupper(random_string('alnum', 6))
        ];

        if ($this->is_admin) {
            $data_ujian['mapel_id'] = $mapel_id_input;
            // Jika admin bisa menunjuk Guru PJ lain untuk ujian ini
            // $data_ujian['guru_id'] = $this->input->post('guru_id_assign', true); 
            // Jika tidak, guru_id bisa ID admin sendiri (jika admin juga terdaftar sbg guru) atau ID PJ default.
            // Untuk sekarang, asumsikan Admin menunjuk dirinya jika perlu (perlu entri guru untuk admin).
            $admin_guru_data = $this->db->get_where('guru', ['nip' => $this->ion_auth->user()->row()->username])->row();
             $data_ujian['guru_id'] = $admin_guru_data ? $admin_guru_data->id_guru : $this->guru_data->id_guru; // Fallback ke $this->guru_data jika admin adalah guru aktif juga
        } else { // PJ Soal
            $data_ujian['mapel_id'] = $this->pj_mapel_data->id_mapel;
            $data_ujian['guru_id']  = $this->guru_data->id_guru;
        }

        try {
            $this->db->trans_start();
            
            // Insert ujian
            $id_ujian = $this->ujian_m->create_ujian($data_ujian);
            
            if (!$id_ujian) {
                throw new Exception('Gagal menyimpan data ujian.');
            }

            // Assign soal ke ujian
            $this->_assign_soal_to_ujian($id_ujian);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Gagal menyimpan data ujian dan soal.');
            }

            $this->output_json([
                'status' => true,
                'message' => 'Data ujian berhasil disimpan.'
            ]);
            return;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->output_json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Menampilkan form edit ujian.
     * URL: /ujian/edit/{id_ujian}
     */
    public function edit($id_ujian = null)
    {
        if (!$this->_can_manage_ujian($id_ujian)) {
            $this->session->set_flashdata('error', 'Anda tidak memiliki hak untuk mengedit ujian ini.');
            redirect('ujian');
        }

        $ujian = $this->ujian_m->get_ujian_by_id($id_ujian);
        if (!$ujian) {
            show_404(); return;
        }

        $data = [
            'user'          => $this->ion_auth->user()->row(),
            'judul'         => 'Kelola Ujian',
            'subjudul'      => 'Edit Ujian & Kelola Soal',
            'ujian'         => $ujian, // Data ujian yang akan diedit
            'is_admin'      => $this->is_admin,
            'guru_data'     => $this->guru_data,
            'pj_mapel_data' => $this->pj_mapel_data, 
            'all_jenjang_options'   => $this->master->getAllJenjang(),
            'all_tahun_ajaran_options' => $this->master->getAllTahunAjaran(),
        ];
        
        if ($this->is_admin) {
            $data['all_mapel_options'] = $this->master->getAllMapel();
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('ujian/edit', $data); // View untuk edit ujian
        $this->load->view('_templates/dashboard/_footer.php');
    }

    /**
     * Memproses update data ujian.
     * URL: /ujian/update/{id_ujian} (Method POST)
     */
    public function update($id_ujian = null)
    {
        if (!$id_ujian) redirect('ujian');
        
        if (!$this->_can_manage_ujian($id_ujian)) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('ujian');
        }

        $ujian = $this->ujian_m->get_ujian_by_id($id_ujian);
        if (!$ujian) {
            $this->session->set_flashdata('error', 'Data ujian tidak ditemukan.');
            redirect('ujian');
        }

        $data = $this->input->post(null, true);
        
        // Validasi form
        $this->form_validation->set_rules('nama_ujian', 'Nama Ujian', 'required');
        $this->form_validation->set_rules('id_jenjang_target', 'Jenjang Target', 'required|integer');
        $this->form_validation->set_rules('id_tahun_ajaran', 'Tahun Ajaran', 'required|integer');
        $this->form_validation->set_rules('jumlah_soal', 'Jumlah Soal Ditampilkan', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('waktu', 'Waktu Ujian (menit)', 'required|integer|greater_than[0]');
        $this->form_validation->set_rules('tgl_mulai', 'Tanggal Mulai', 'required');
        $this->form_validation->set_rules('terlambat', 'Batas Akhir Masuk', 'required');
        $this->form_validation->set_rules('acak_soal', 'Acak Soal', 'required|in_list[Y,N]');
        $this->form_validation->set_rules('acak_opsi', 'Acak Opsi', 'required|in_list[Y,N]');
        $this->form_validation->set_rules('aktif', 'Status Aktif', 'required|in_list[Y,N]');

        if ($this->form_validation->run() === FALSE) {
            $this->output_json(['status' => false, 'errors' => $this->form_validation->error_array()]);
            return;
        }

        // Start transaction
        $this->db->trans_start();

        try {
            // Update data ujian
            $update_data = [
                'nama_ujian' => $data['nama_ujian'],
                'id_jenjang_target' => $data['id_jenjang_target'],
                'id_tahun_ajaran' => $data['id_tahun_ajaran'],
                'jumlah_soal' => $data['jumlah_soal'],
                'waktu' => $data['waktu'],
                'tgl_mulai' => $data['tgl_mulai'],
                'terlambat' => $data['terlambat'],
                'acak_soal' => $data['acak_soal'],
                'acak_opsi' => $data['acak_opsi'],
                'aktif' => $data['aktif']
            ];

            // Update ujian
            $this->ujian_m->update_ujian($id_ujian, $update_data);

            // Reset dan tambahkan soal baru secara otomatis
            $this->_assign_soal_to_ujian($id_ujian);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Gagal memperbarui data ujian.');
            }

            $this->output_json([
                'status' => true,
                'message' => 'Data ujian berhasil diperbarui.'
            ]);

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->output_json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function _assign_soal_to_ujian($id_ujian)
    {
        $ujian = $this->ujian_m->get_ujian_by_id($id_ujian);
        if (!$ujian) {
            throw new Exception('Data ujian tidak ditemukan.');
        }

        // Debug log
        log_message('debug', 'Assigning soal to ujian ID: ' . $id_ujian);
        log_message('debug', 'Mapel ID: ' . $ujian->mapel_id . ', Jenjang ID: ' . $ujian->id_jenjang_target);

        // Cek jumlah soal tersedia
        $total_available = $this->soal_m->count_available_soal(
            $ujian->mapel_id,
            $ujian->id_jenjang_target
        );

        if ($total_available < $ujian->jumlah_soal) {
            throw new Exception(
                'Jumlah soal yang tersedia (' . $total_available . ') ' .
                'kurang dari jumlah soal yang dibutuhkan (' . $ujian->jumlah_soal . ').'
            );
        }

        // Hapus soal yang sudah ada
        $this->db->delete('d_ujian_soal', ['id_ujian' => $id_ujian]);

        // Ambil soal dari bank soal
        $available_soal = $this->soal_m->get_soal_by_mapel_jenjang(
            $ujian->mapel_id,
            $ujian->id_jenjang_target,
            $ujian->jumlah_soal
        );

        // Debug log
        log_message('debug', 'Soal retrieved: ' . count($available_soal));

        if (empty($available_soal)) {
            throw new Exception('Tidak ada soal yang tersedia untuk kriteria yang dipilih.');
        }

        // Insert soal ke d_ujian_soal
        $batch_data = [];
        foreach ($available_soal as $index => $soal) {
            $batch_data[] = [
                'id_ujian' => $id_ujian,
                'id_soal' => $soal->id_soal,
                'nomor_urut' => $index + 1
            ];
        }

        if (!empty($batch_data)) {
            $insert_result = $this->db->insert_batch('d_ujian_soal', $batch_data);
            
            // Debug log
            log_message('debug', 'Insert batch result: ' . ($insert_result ? 'success' : 'failed'));
            log_message('debug', 'Last query: ' . $this->db->last_query());
            
            if (!$insert_result) {
                throw new Exception('Gagal menyimpan soal ke ujian.');
            }
        }

        return true;
    }

    /**
     * Menghapus data ujian (bisa bulk).
     * URL: /ujian/delete (Method POST)
     */
    public function delete()
    {
        $response = ['status' => false, 'message' => ''];
        
        if (!$this->input->is_ajax_request()) {
            $response['message'] = 'Invalid request!';
            exit(json_encode($response));
        }

        // Ambil data ID yang akan dihapus dari POST
        $checked = $this->input->post('checked', true);
        
        if (empty($checked)) {
            $response['message'] = 'Tidak ada data yang dipilih untuk dihapus!';
            exit(json_encode($response));
        }

        try {
            // Mulai transaksi
            $this->db->trans_start();
            
            // Hapus dari tabel terkait terlebih dahulu
            $this->db->where_in('id_ujian', $checked);
            $this->db->delete('d_ujian_soal');
            
            // Kemudian hapus dari tabel utama
            $this->db->where_in('id_ujian', $checked);
            $delete = $this->db->delete('m_ujian');
            
            // Selesaikan transaksi
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Gagal menghapus data ujian!');
            }
            
            $response['status'] = true;
            $response['message'] = 'Data berhasil dihapus!';
            
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        
        exit(json_encode($response));
    }

    /**
     * Refresh token ujian.
     * URL: /ujian/refresh_token/{id_ujian}
     */
    public function refresh_token($id_ujian)
    {
        if (!$this->_can_manage_ujian($id_ujian)) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak.', 'new_token' => '']);
            return;
        }
        $new_token = strtoupper(random_string('alnum', 5));
        if ($this->ujian_m->update_ujian($id_ujian, ['token' => $new_token])) {
            $this->output_json(['status' => true, 'message' => 'Token berhasil diperbarui.', 'new_token' => $new_token]);
        } else {
            $this->output_json(['status' => false, 'message' => 'Gagal memperbarui token.', 'new_token' => '']);
        }
    }

    /**
     * Helper function untuk konversi datetime dari input form ke format DB.
     */
    private function convert_datetime_to_db($datetime_str) {
        if (empty($datetime_str)) return null;
        try {
            // Input format dari <input type="datetime-local"> adalah YYYY-MM-DDTHH:MM
            $dt = new DateTime($datetime_str);
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null; // Atau handle error jika format tidak valid
        }
    }

    // --- Method untuk Manajemen Soal dalam Ujian (via AJAX, akan dikembangkan) ---
    
    /**
     * AJAX: Mengambil daftar soal dari bank soal yang relevan untuk ditambahkan ke ujian.
     * Filter berdasarkan mapel_id dan id_jenjang_target dari ujian.
     * Juga mengecualikan soal yang sudah ada di ujian ini.
     */
    public function get_soal_bank_for_ujian($id_ujian) {
        // Validasi hak akses dan $id_ujian
        if (!$this->_can_manage_ujian($id_ujian)) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak.', 'data' => []]);
            return;
        }
        $ujian = $this->ujian_m->get_ujian_by_id($id_ujian);
        if (!$ujian) {
            $this->output_json(['status' => false, 'message' => 'Ujian tidak ditemukan.', 'data' => []]);
            return;
        }

        $soal_sudah_ada_ids = $this->ujian_m->get_assigned_soal_ids($id_ujian); // Perlu method ini di model
        $soal_bank = $this->ujian_m->get_soal_bank_for_ujian($ujian->mapel_id, $ujian->id_jenjang_target, $soal_sudah_ada_ids);
        
        $this->output_json(['status' => true, 'data' => $soal_bank]);
    }

    /**
     * AJAX: Menyimpan soal-soal yang dipilih dari bank soal ke dalam ujian (d_ujian_soal).
     */
    public function assign_soal_to_ujian() {
        $id_ujian = $this->input->post('id_ujian', true);
        if (!$this->_can_manage_ujian($id_ujian)) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak.']);
            return;
        }

        $soal_ids = $this->input->post('soal_ids', true); // Array ID soal dari bank
        if (empty($id_ujian) || empty($soal_ids) || !is_array($soal_ids)) {
            $this->output_json(['status' => false, 'message' => 'Data tidak lengkap.']);
            return;
        }

        $ujian = $this->ujian_m->get_ujian_by_id($id_ujian);
        if (!$ujian) {
            $this->output_json(['status' => false, 'message' => 'Ujian tidak ditemukan.']);
            return;
        }

        // Cek apakah jumlah soal yang ditambahkan + yang sudah ada tidak melebihi $ujian->jumlah_soal
        // (Ini opsional, validasi jumlah soal bisa saat ujian akan diaktifkan)

        $data_to_insert = [];
        $nomor_urut_terakhir = $this->ujian_m->get_last_nomor_urut($id_ujian); // Perlu method ini di model

        foreach ($soal_ids as $id_soal) {
            // Cek apakah soal ini valid (mapel & jenjang sesuai) - sebaiknya sudah difilter di get_soal_bank_for_ujian
            // Cek apakah soal belum ada di ujian ini
            if (!$this->ujian_m->is_soal_in_ujian($id_ujian, $id_soal)) { // Perlu method ini
                $nomor_urut_terakhir++;
                $data_to_insert[] = [
                    'id_ujian' => $id_ujian,
                    'id_soal'  => $id_soal,
                    'nomor_urut' => $nomor_urut_terakhir
                ];
            }
        }

        if (!empty($data_to_insert)) {
            if ($this->ujian_m->assign_batch_soal_to_ujian($data_to_insert)) { // Model insert_batch
                $current_total_soal = $this->ujian_m->count_soal_in_ujian($id_ujian);
                $this->output_json(['status' => true, 'message' => count($data_to_insert) . ' soal berhasil ditambahkan.', 'total_soal_di_ujian' => $current_total_soal]);
            } else {
                $this->output_json(['status' => false, 'message' => 'Gagal menambahkan soal ke ujian.']);
            }
        } else {
            $this->output_json(['status' => false, 'message' => 'Tidak ada soal baru untuk ditambahkan atau soal sudah ada.']);
        }
    }

    /**
     * AJAX: Mengambil daftar soal yang sudah ada di ujian.
     */
    public function get_assigned_soal($id_ujian){
        // Tidak perlu cek _can_manage_ujian jika guru non-PJ juga boleh lihat ini di halaman detail ujian
        // Namun, jika ini khusus untuk halaman edit, maka perlu.
        // Untuk sekarang asumsikan ini untuk halaman edit.
         if (!$this->_can_manage_ujian($id_ujian) && !$this->is_admin) { // Admin selalu bisa lihat
            $this->output_json(['status' => false, 'message' => 'Akses ditolak.', 'data' => []]);
            return;
        }

        $assigned_soal = $this->ujian_m->get_assigned_soal_for_ujian($id_ujian);
        $this->output_json(['status' => true, 'data' => $assigned_soal]);
    }

    /**
     * AJAX: Menghapus soal dari ujian (dari tabel d_ujian_soal).
     */
    public function remove_soal_from_ujian() {
        $id_ujian = $this->input->post('id_ujian', true);
        $id_d_ujian_soal = $this->input->post('id_d_ujian_soal', true); // PK dari d_ujian_soal

        if (!$this->_can_manage_ujian($id_ujian)) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak.']);
            return;
        }
        if (empty($id_d_ujian_soal)) {
            $this->output_json(['status' => false, 'message' => 'ID soal ujian tidak valid.']);
            return;
        }

        if ($this->ujian_m->remove_soal_from_ujian($id_ujian, $id_d_ujian_soal)) {
            // Setelah menghapus, mungkin perlu re-number nomor_urut jika tidak ingin ada gap.
            // $this->ujian_m->renumber_soal_in_ujian($id_ujian); // Fungsi opsional
            $current_total_soal = $this->ujian_m->count_soal_in_ujian($id_ujian);
            $this->output_json(['status' => true, 'message' => 'Soal berhasil dihapus dari ujian.', 'total_soal_di_ujian' => $current_total_soal]);
        } else {
            $this->output_json(['status' => false, 'message' => 'Gagal menghapus soal dari ujian.']);
        }
    }

    /**
     * AJAX: Mengupdate urutan soal dalam ujian.
     */
    public function update_soal_order() {
        $id_ujian = $this->input->post('id_ujian', true);
        if (!$this->_can_manage_ujian($id_ujian)) {
            $this->output_json(['status' => false, 'message' => 'Akses ditolak.']);
            return;
        }
        // Data urutan soal, misal: [{id_d_ujian_soal: 1, nomor_urut: 1}, {id_d_ujian_soal: 5, nomor_urut: 2}, ...]
        $soal_orders = $this->input->post('soal_orders', true); 
        if (empty($id_ujian) || empty($soal_orders) || !is_array($soal_orders)) {
            $this->output_json(['status' => false, 'message' => 'Data urutan tidak valid.']);
            return;
        }

        if ($this->ujian_m->update_soal_order_in_ujian($soal_orders)) { // Model pakai update_batch
            $this->output_json(['status' => true, 'message' => 'Urutan soal berhasil diperbarui.']);
        } else {
            $this->output_json(['status' => false, 'message' => 'Gagal memperbarui urutan soal.']);
        }
    }

    // ========================================================================
    // METHOD UNTUK SISWA MENGERJAKAN UJIAN - Adaptasi dari controller lama
    // ========================================================================

    /**
     * Menampilkan daftar ujian yang tersedia untuk siswa.
     * URL: /ujian/list_ujian_siswa (menggantikan /ujian/list dari controller lama)
     */
    public function list_ujian_siswa() {
        $this->_akses_siswa_required();

        // Ambil data tahun ajaran aktif untuk ditampilkan di view
        $tahun_ajaran_aktif_display = $this->master->getTahunAjaranAktif();

        $data = [
            'user'     => $this->ion_auth->user()->row(),
            'siswa'    => $this->siswa_data, // Data siswa lengkap
            'judul'    => 'Ujian Saya',
            'subjudul' => 'Daftar Ujian Tersedia',
            'tahun_ajaran_aktif_display' => $tahun_ajaran_aktif_display 
        ];

        $this->load->view('_templates/dashboard/_header.php', $data); // Menggunakan template dashboard standar
        $this->load->view('ujian/list_ujian_siswa', $data); // View baru: ujian/list_siswa.php
        $this->load->view('_templates/dashboard/_footer.php');
    }

    /**
     * Endpoint AJAX untuk DataTables daftar ujian siswa.
     * URL: /ujian/data_list_siswa (menggantikan /ujian/list_json dari controller lama)
     */
    public function data_list_siswa()    
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        try {
            if (!isset($this->siswa_data)) {
                throw new Exception('Data siswa tidak ditemukan.');
            }

            // Panggil model dan biarkan model mengembalikan JSON string
            $result_json_string = $this->ujian_m->get_list_ujian_for_siswa_dt(
                $this->siswa_data->id_siswa,
                $this->siswa_data->id_kelas,
                $this->siswa_data->id_jenjang,
                $this->siswa_data->id_tahun_ajaran
            );

            // Karena model sudah mengembalikan JSON string, kita perlu decode dulu
            // untuk mengambil properti draw, recordsTotal, recordsFiltered, dan data.
            $decoded_result = json_decode($result_json_string, true);
            
            if ($decoded_result === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response from model: ' . json_last_error_msg());
            }

            // DataTables expects 'data' to be an array of arrays
            $data_for_datatables = $decoded_result['data'] ?? [];

            $response = [
                'draw' => $this->input->post('draw'),
                'recordsTotal' => $decoded_result['recordsTotal'] ?? 0,
                'recordsFiltered' => $decoded_result['recordsFiltered'] ?? 0,
                'data' => $data_for_datatables
            ];

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode($response));

        } catch (Exception $e) {
            log_message('error', 'Error in data_list_siswa: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'error' => true,
                    'message' => $e->getMessage()
                ]));
        }
    }

    /**
     * Halaman konfirmasi sebelum memulai ujian & input token.
     * URL: /ujian/token/{id_ujian_encrypted} (Sama seperti controller lama)
     */
    public function token($id_ujian_enc = null) 
    {
        $this->_akses_siswa_required();
        
        if (empty($id_ujian_enc)) {
            show_404(); 
            return;
        }

        try {
            // Decode the URL-safe string
            $id_ujian_enc = rawurldecode($id_ujian_enc);
            
            // Convert hex back to binary and decrypt
            $id_ujian = $this->db->query(
                "SELECT CAST(AES_DECRYPT(UNHEX(?), ?) AS UNSIGNED) as id",
                array($id_ujian_enc, $this->config->item('encryption_key'))
            )->row();

            if (!$id_ujian || !$id_ujian->id) {
                show_error('ID Ujian tidak valid.', 400);
                return;
            }

            $id_ujian = $id_ujian->id;
            
            // Model mengambil ujian berdasarkan id_ujian dan memastikan relevan dengan jenjang siswa
            $ujian = $this->ujian_m->get_ujian_for_konfirmasi_siswa($id_ujian, $this->siswa_data->id_jenjang); 
            if (!$ujian || $ujian->aktif !== 'Y') {
                $this->session->set_flashdata('error', 'Ujian tidak ditemukan, tidak aktif, atau tidak sesuai dengan jenjang Anda.');
                redirect('ujian/list_ujian_siswa');
            }

            $hasil_ujian_sebelumnya = $this->ujian_m->get_hasil_ujian_by_ujian_and_siswa($id_ujian, $this->siswa_data->id_siswa);
            
            $now_time = time();
            $tgl_mulai_ujian_time = strtotime($ujian->tgl_mulai);
            $tgl_terlambat_ujian_time = strtotime($ujian->terlambat);

            if ($now_time < $tgl_mulai_ujian_time) {
                $this->session->set_flashdata('warning', 'Ujian "'.htmlspecialchars($ujian->nama_ujian).'" belum dapat dimulai.');
                redirect('ujian/list_ujian_siswa');
            }

            if ($hasil_ujian_sebelumnya && $hasil_ujian_sebelumnya->status == 'completed') {
                $this->session->set_flashdata('info', 'Anda sudah menyelesaikan ujian "'.htmlspecialchars($ujian->nama_ujian).'".');
                redirect('ujian/hasil/'.rawurlencode($this->encryption->encrypt($hasil_ujian_sebelumnya->id))); // Arahkan ke hasil
                return;
            }

            if ($hasil_ujian_sebelumnya && $hasil_ujian_sebelumnya->status == 'sedang_dikerjakan') {
                $waktu_selesai_pengerjaan_siswa = strtotime($hasil_ujian_sebelumnya->tgl_selesai);
                if ($now_time < $waktu_selesai_pengerjaan_siswa) {
                    // Set session dan redirect langsung ke lembar ujian
                    $this->session->set_userdata([
                        'active_exam_id' => $hasil_ujian_sebelumnya->id,
                        'exam_token_verified' => true
                    ]);
                    
                    $id_h_ujian_enc = strtr(base64_encode($this->encryption->encrypt($hasil_ujian_sebelumnya->id)), '+/=', '-_,');
                    redirect('ujian/lembar_ujian/'.urlencode($id_h_ujian_enc));
                    return;
                } else {
                    if($hasil_ujian_sebelumnya->status != 'completed'){ // Finalisasi jika belum
                        $this->ujian_m->calculate_and_finalize_score($hasil_ujian_sebelumnya->id);
                    }
                    $this->session->set_flashdata('error', 'Waktu pengerjaan Anda untuk ujian "'.htmlspecialchars($ujian->nama_ujian).'" telah habis.');
                    redirect('ujian/list_ujian_siswa');
                    return;
                }
            }
            
            if ($now_time > $tgl_terlambat_ujian_time && !$hasil_ujian_sebelumnya) {
                $this->session->set_flashdata('error', 'Anda sudah melewati batas waktu untuk memulai ujian "'.htmlspecialchars($ujian->nama_ujian).'".');
                redirect('ujian/list_ujian_siswa');
                return;
            }

            // Pastikan siswa_data sudah tersedia dan memiliki id_kelas
            if (empty($this->siswa_data) || !isset($this->siswa_data->id_kelas)) {
                log_message('error', 'UjianController->token(): Data siswa atau ID kelas tidak ditemukan untuk user: ' . ($this->ion_auth->user()->row()->username ?? 'N/A'));
                $this->session->set_flashdata('error', 'Data profil siswa Anda tidak lengkap. Hubungi administrator.');
                redirect('ujian/list_ujian_siswa'); // Redirect kembali jika data siswa tidak lengkap
                return;
            }

            $ujian = $this->ujian_m->get_ujian_by_id_with_guru($id_ujian, $this->siswa_data->id_kelas);
            
            $data = [
                'user'      => $this->ion_auth->user()->row(),
                'siswa'     => $this->siswa_data,
                'judul'     => 'Konfirmasi Ujian',
                'subjudul'  => htmlspecialchars($ujian->nama_ujian),
                'ujian'     => $ujian,
                'encrypted_id_ujian' => $id_ujian_enc // Kirim lagi untuk form
            ];

            $this->load->view('_templates/topnav/_header.php', $data); 
            $this->load->view('ujian/token', $data); // View baru: ujian/token_siswa.php
            $this->load->view('_templates/topnav/_footer.php');
        } catch (Exception $e) {
            log_message('error', 'Error decrypting ID ujian: ' . $e->getMessage());
            show_error('ID Ujian tidak valid.', 400);
            return;
        }
    }

    /**
     * AJAX: Memproses token dan memulai/melanjutkan ujian jika valid.
     * URL: /ujian/proses_token (menggantikan /ujian/cektoken dan logika mulai ujian)
     */
    public function proses_token() {
        $this->_akses_siswa_required();

        $id_ujian_enc_from_form = $this->input->post('id_ujian_enc', true); // Ini adalah ID m_ujian yang dienkripsi SQL
        $token_input = $this->input->post('token', true);

        if (empty($id_ujian_enc_from_form)) {
            $this->output_json(['status' => false, 'message' => 'ID Ujian tidak disertakan.']); return;
        }

        // Dekripsi ID m_ujian menggunakan metode SQL
        $id_ujian = null;
        $encryption_key = $this->config->item('encryption_key');
        if (empty($encryption_key)) {
            log_message('error', 'proses_token: Encryption key tidak diset.');
            $this->output_json(['status' => false, 'message' => 'Kesalahan konfigurasi server.']); return;
        }
        if (!preg_match('/^[a-f0-9]+$/i', $id_ujian_enc_from_form)) {
            log_message('error', 'proses_token: ID ujian terenkripsi bukan format HEX: ' . $id_ujian_enc_from_form);
            $this->output_json(['status' => false, 'message' => 'Format ID Ujian tidak valid.']); return;
        }

        $query_dekripsi = $this->db->query(
            "SELECT CAST(AES_DECRYPT(UNHEX(?), ?) AS UNSIGNED) as decrypted_id",
            [$id_ujian_enc_from_form, $encryption_key]
        );
        $result_dekripsi = $query_dekripsi->row();
        if ($result_dekripsi && isset($result_dekripsi->decrypted_id) && is_numeric($result_dekripsi->decrypted_id) && $result_dekripsi->decrypted_id > 0) {
            $id_ujian = (int) $result_dekripsi->decrypted_id;
        }

        if (!$id_ujian) {
            log_message('error', 'proses_token: Gagal dekripsi SQL untuk id_ujian_enc: ' . $id_ujian_enc_from_form);
            $this->output_json(['status' => false, 'message' => 'ID Ujian tidak dapat diproses.']); return;
        }

        $ujian = $this->ujian_m->get_ujian_for_konfirmasi_siswa($id_ujian, $this->siswa_data->id_jenjang);
        if (!$ujian || $ujian->aktif !== 'Y') {
            $this->output_json(['status' => false, 'message' => 'Ujian tidak ditemukan, tidak aktif, atau tidak sesuai jenjang Anda.']); return;
        }

        if (!empty($ujian->token) && strtoupper($token_input) !== strtoupper($ujian->token)) {
            $this->output_json(['status' => false, 'message' => 'Token ujian salah.']); return;
        }

        $now_time = time();
        if ($now_time < strtotime($ujian->tgl_mulai)) {
            $this->output_json(['status' => false, 'message' => 'Ujian belum dapat dimulai.']); return;
        }
        
        $hasil_ujian_sebelumnya = $this->ujian_m->get_hasil_ujian_by_ujian_and_siswa($id_ujian, $this->siswa_data->id_siswa);

        if ($hasil_ujian_sebelumnya && $hasil_ujian_sebelumnya->status == 'completed') {
            $id_h_ujian_enc_ci = rawurlencode($this->encryption->encrypt($hasil_ujian_sebelumnya->id));
            $this->output_json(['status' => false, 'message' => 'Anda sudah menyelesaikan ujian ini.', 'redirect_url' => base_url('ujian/hasil/'.$id_h_ujian_enc_ci)]); return;
        }

        if ($hasil_ujian_sebelumnya && $hasil_ujian_sebelumnya->status == 'sedang_dikerjakan') {
            $waktu_selesai_pengerjaan_siswa = $hasil_ujian_sebelumnya->waktu_habis_timestamp; // Sudah UNIX timestamp
            if ($now_time < $waktu_selesai_pengerjaan_siswa) {
                $id_h_ujian_enc_ci = rawurlencode($this->encryption->encrypt($hasil_ujian_sebelumnya->id));
                $this->output_json(['status' => true, 'message' => 'Melanjutkan ujian...', 'redirect_url' => base_url('ujian/lembar_ujian/'.$id_h_ujian_enc_ci)]); return;
            } else {
                if($hasil_ujian_sebelumnya->status != 'completed'){
                    $this->ujian_m->calculate_and_finalize_score($hasil_ujian_sebelumnya->id);
                }
                $this->output_json(['status' => false, 'message' => 'Waktu pengerjaan Anda telah habis.']); return;
            }
        }
        
        if ($now_time > strtotime($ujian->terlambat) && !$hasil_ujian_sebelumnya) {
            $this->output_json(['status' => false, 'message' => 'Batas waktu untuk memulai ujian telah terlewat.']); return;
        }
        if (!$hasil_ujian_sebelumnya || $hasil_ujian_sebelumnya->status === 'expired' || ($hasil_ujian_sebelumnya->status === 'sedang_dikerjakan' && $now_time >= $hasil_ujian_sebelumnya->waktu_habis_timestamp) ) {
            $list_id_soal_obj = $this->ujian_m->get_soal_ids_for_ujian($id_ujian, $ujian->jumlah_soal, $ujian->acak_soal);
            if (empty($list_id_soal_obj)) {
                $this->output_json(['status' => false, 'message' => 'Tidak ada soal yang dikonfigurasi untuk ujian ini. Hubungi pengawas.']); return;
            }
            
            $list_id_soal_array = array_column($list_id_soal_obj, 'id_soal');
            $list_id_soal_json = json_encode($list_id_soal_array);
            
            $list_jawaban_init = [];
            foreach($list_id_soal_array as $id_s) {
                $list_jawaban_init[$id_s] = ["j" => "", "r" => "N"];
            }
            $list_jawaban_json = json_encode($list_jawaban_init);

            // PERBAIKAN PERHITUNGAN tgl_selesai
            $waktu_mulai_timestamp = $now_time;
            $durasi_ujian_detik = (int)$ujian->waktu * 60;
            $waktu_selesai_pengerjaan_timestamp = $waktu_mulai_timestamp + $durasi_ujian_detik;
            $waktu_selesai_pengerjaan_db_format = date('Y-m-d H:i:s', $waktu_selesai_pengerjaan_timestamp);

            log_message('debug', 'proses_token - Durasi Ujian (menit): ' . $ujian->waktu . ', Durasi Detik: ' . $durasi_ujian_detik . ', Waktu Selesai DB: ' . $waktu_selesai_pengerjaan_db_format);

            $data_h_ujian = [
                'ujian_id'     => $id_ujian,
                'siswa_id'     => $this->siswa_data->id_siswa,
                'list_soal'    => $list_id_soal_json,
                'list_jawaban' => $list_jawaban_json,
                'tgl_mulai'    => date('Y-m-d H:i:s', $waktu_mulai_timestamp),
                'tgl_selesai'  => $waktu_selesai_pengerjaan_db_format, // Simpan tgl selesai yang benar
                'status'       => 'sedang_dikerjakan'
            ];

            $id_h_ujian = $this->ujian_m->create_hasil_ujian_entry($data_h_ujian);

            if ($id_h_ujian) {
                try {
                    // Use consistent encryption method
                    $encrypted = $this->encryption->encrypt($id_h_ujian);
                    $safe_url = strtr(base64_encode($encrypted), '+/=', '-_,');
                    
                    $redirect_url = base_url('ujian/lembar_ujian/' . urlencode($safe_url));
                    
                    // Add session data for validation
                    $this->session->set_userdata([
                        'active_exam_id' => $id_h_ujian,
                        'exam_token_verified' => true
                    ]);
                    
                    $this->output_json([
                        'status' => true,
                        'message' => 'Token valid.',
                        'redirect_url' => $redirect_url
                    ]);
                    return;
                } catch (Exception $e) {
                    log_message('error', 'Error encrypting exam ID: ' . $e->getMessage());
                    $this->output_json([
                        'status' => false,
                        'message' => 'Terjadi kesalahan sistem'
                    ]);
                    return;
                }
            }
        }
    }

    public function lembar_ujian($id_h_ujian_enc = null)
    {
        $this->_akses_siswa_required();

        try {
            if (empty($id_h_ujian_enc)) {
                throw new Exception('ID ujian tidak ditemukan');
            }

            $id_h_ujian = $this->ujian_m->decrypt_exam_id($id_h_ujian_enc);
            
            if (!$id_h_ujian) {
                throw new Exception('ID ujian tidak valid');
            }

            $h_ujian = $this->db->get_where('h_ujian', [
                'id' => $id_h_ujian,
                'siswa_id' => $this->siswa_data->id_siswa
            ])->row();

            if (!$h_ujian) {
                throw new Exception('Data ujian tidak ditemukan');
            }
            
            $m_ujian_master = $this->ujian_m->get_ujian_by_id($h_ujian->ujian_id); // Dapatkan data m_ujian

            // Cek status dan waktu
            $now = time();
            $waktu_selesai_h_ujian_timestamp = strtotime($h_ujian->tgl_selesai); // Waktu selesai dari h_ujian
            
            if ($waktu_selesai_h_ujian_timestamp === false) { // Fallback jika parsing gagal
                $waktu_selesai_h_ujian_timestamp = time() + (60 * 60); // Default 1 jam
                log_message('error', 'lembar_ujian: Gagal parsing tgl_selesai dari h_ujian, defaulting to +1 hour.');
            }

            $sisa_waktu = max(0, $waktu_selesai_h_ujian_timestamp - $now);

            if ($h_ujian->status == 'completed') {
                redirect('ujian/list_ujian_siswa');
                return;
            }

            // Update waktu mulai saat pertama kali masuk ke lembar ujian (jika null)
            if ($h_ujian && $h_ujian->tgl_mulai === null) {
                $this->db->where('id', $id_h_ujian)
                         ->update('h_ujian', [
                             'tgl_mulai' => date('Y-m-d H:i:s')
                         ]);
            }
            
            // Set session with the encrypted ID
            $this->session->set_userdata([
                'active_exam_id' => $id_h_ujian,
                'active_exam_enc' => $id_h_ujian_enc,
                'exam_token_verified' => true
            ]);

            $list_soal_ids_from_h_ujian = json_decode($h_ujian->list_soal, true);

            // Panggil model untuk mendapatkan detail soal
            $soal_collection_raw = $this->ujian_m->get_soal_details_for_lembar_ujian(
                $list_soal_ids_from_h_ujian,
                ($m_ujian_master->acak_opsi ?? 'N') === 'Y' // Teruskan status acak_opsi
            );

            // === PERBAIKAN KRUSIAL DI CONTROLLER: PASTIKAN SOAL_COLLECTION ADALAH ARRAY OF ARRAYS ===
            $soal_collection = [];
            if (!empty($soal_collection_raw)) {
                foreach ($soal_collection_raw as $soal_obj) {
                    // Konversi objek soal utama ke array asosiatif
                    $soal_arr = (array) $soal_obj;
                    
                    // Pastikan opsi_display juga dikonversi ke array asosiatif jika itu objek
                    if (isset($soal_arr['opsi_display']) && is_object($soal_arr['opsi_display'])) {
                        $soal_arr['opsi_display'] = (array) $soal_arr['opsi_display'];
                        // Konversi elemen di dalam opsi_display (A, B, C, D, E) menjadi array juga
                        foreach ($soal_arr['opsi_display'] as $key => $value) {
                            if (is_object($value)) {
                                $soal_arr['opsi_display'][$key] = (array) $value;
                            }
                        }
                    }
                    $soal_collection[] = $soal_arr;
                }
            }
            // === AKHIR PERBAIKAN KRUSIAL DI CONTROLLER ===

            // === DEBUG KRUSIAL DI CONTROLLER: $soal_collection sebelum diteruskan ke view ===
            // log_message('debug', 'UjianController: $soal_collection received from model (raw): ' . print_r($soal_collection_raw, true));
            // log_message('debug', 'UjianController: FINAL $soal_collection for view: ' . print_r($soal_collection, true));
            // log_message('debug', 'UjianController: JSON representation of FINAL $soal_collection for view: ' . json_encode($soal_collection));

            // if (!empty($soal_collection) && isset($soal_collection[0])) {
            //     log_message('debug', 'UjianController: Type of $soal_collection[0][\'opsi_display\'] in Controller (final): ' . gettype($soal_collection[0]['opsi_display'] ?? null)); // Akses array
            //     log_message('debug', 'UjianController: Content of $soal_collection[0][\'opsi_display\'] in Controller (final): ' . print_r($soal_collection[0]['opsi_display'] ?? null, true)); // Akses array
            //     log_message('debug', 'UjianController: JSON of $soal_collection[0][\'opsi_display\'] in Controller (final): ' . json_encode($soal_collection[0]['opsi_display'] ?? null));
            // } else {
            //     log_message('debug', 'UjianController: $soal_collection is empty or first item is missing after conversion.');
            // }
            // === AKHIR DEBUG KRUSIAL ===

            $data = [
                'user' => $this->ion_auth->user()->row(),
                'siswa' => $this->siswa_data,
                'judul' => 'Lembar Ujian',
                'subjudul' => $m_ujian_master->nama_ujian ?? 'Ujian', // Gunakan nama ujian dari m_ujian_master
                'ujian' => $m_ujian_master, // Teruskan objek m_ujian_master
                'h_ujian' => $h_ujian,
                'soal_collection' => $soal_collection, // Ini yang diteruskan ke view
                'jawaban_tersimpan_php' => json_decode($h_ujian->list_jawaban, true) ?: [],
                'jawaban_tersimpan' => json_decode($h_ujian->list_jawaban, true) ?: [],
                'id_h_ujian_enc' => $id_h_ujian_enc,
                'waktu_selesai' => date('Y-m-d H:i:s', $waktu_selesai_h_ujian_timestamp), // Pastikan ini adalah waktu selesai dari h_ujian
                'sisa_waktu' => $sisa_waktu,
            ];

            // Tambahkan debug (opsional, jika Anda ingin melihat di halaman)
            // $data['debug_data_for_view'] = $data; 

            $this->load->view('_templates/topnav/_header.php', $data);
            $this->load->view('ujian/lembar_ujian', $data);
            $this->load->view('_templates/topnav/_footer.php');

        } catch (Exception $e) {
            log_message('error', 'Error in lembar_ujian (overall catch): ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Terjadi kesalahan saat memuat ujian: ' . $e->getMessage());
            redirect('ujian/list_ujian_siswa');
            return;
        }
    }
    
    public function simpan_jawaban_ajax() 
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        try {
            // Get and decode exam ID
            $id_h_ujian_enc = $this->input->post('id_h_ujian_enc');
            $encrypted = strtr($id_h_ujian_enc, '-_,', '+/=');
            $binary = base64_decode($encrypted);
            
            if ($binary === false) {
                throw new Exception('Format ID tidak valid');
            }

            $id_h_ujian = $this->encryption->decrypt($binary);
            
            if (!$id_h_ujian) {
                throw new Exception('ID hasil ujian tidak valid');
            }

            // Validasi data ujian
            $h_ujian = $this->db->get_where('h_ujian', [
                'id' => $id_h_ujian,
                'siswa_id' => $this->siswa_data->id_siswa,
                'status' => 'sedang_dikerjakan'
            ])->row();

            if (!$h_ujian) {
                throw new Exception('Data ujian tidak ditemukan atau sudah selesai');
            }

            // Get answer data
            $id_soal = $this->input->post('id_soal');
            $jawaban = $this->input->post('jawaban');
            $ragu_ragu = $this->input->post('ragu_ragu');

            // Validate required fields
            if (!$id_soal) {
                throw new Exception('ID soal tidak valid');
            }

            // Get existing answers
            $list_jawaban = json_decode($h_ujian->list_jawaban, true) ?: [];
            
            // Update answer
            $list_jawaban[$id_soal] = [
                'j' => $jawaban,
                'r' => $ragu_ragu
            ];

            // Save to database
            $this->db->where('id', $id_h_ujian)
                ->update('h_ujian', [
                    'list_jawaban' => json_encode($list_jawaban),
                    // 'updated_at' => date('Y-m-d H:i:s')
                ]);

            $this->output_json([
                'status' => true,
                'message' => 'Jawaban berhasil disimpan',
                'csrf_hash_new' => $this->security->get_csrf_hash()
            ]);

        } catch (Exception $e) {
            $this->output_json([
                'status' => false,
                'message' => $e->getMessage(),
                'csrf_hash_new' => $this->security->get_csrf_hash()
            ]);
        }
    }

    /**
     * Endpoint untuk menyelesaikan ujian
     */
    public function selesaikan_ujian()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        try {
            // Get and validate exam ID
            $id_h_ujian_enc = $this->input->post('id_h_ujian_enc');
            $encrypted = strtr($id_h_ujian_enc, '-_,', '+/=');
            $binary = base64_decode($encrypted);
            
            if ($binary === false) {
                throw new Exception('Format ID tidak valid');
            }

            $id_h_ujian = $this->encryption->decrypt($binary);
            
            if (!$id_h_ujian) {
                throw new Exception('ID hasil ujian tidak valid');
            }

            // Get exam data
            $h_ujian = $this->db->get_where('h_ujian', [
                'id' => $id_h_ujian,
                'siswa_id' => $this->siswa_data->id_siswa,
                'status' => 'sedang_dikerjakan'
            ])->row();

            if (!$h_ujian) {
                throw new Exception('Data ujian tidak ditemukan atau sudah selesai');
            }

            // Update answers and status
            $jawaban_akhir = json_decode($this->input->post('jawaban_akhir_batch'), true);
            
            $this->db->trans_start();
            
            // Calculate scores before updating
            $list_soal = json_decode($h_ujian->list_soal);
            
            // Get answer keys and weights from question bank
            $soal_kunci = $this->db->select('id_soal, jawaban, bobot')
                            ->from('tb_soal')
                            ->where_in('id_soal', $list_soal)
                            ->get()->result();

            $jml_benar = 0;
            $total_bobot = 0;
            $total_bobot_maksimal = 0;
            
            foreach ($soal_kunci as $soal) {
                $total_bobot_maksimal += $soal->bobot;
                if (isset($jawaban_akhir[$soal->id_soal])) {
                    $jawaban_siswa = $jawaban_akhir[$soal->id_soal]['j'];
                    if (strtoupper($jawaban_siswa) === strtoupper($soal->jawaban)) {
                        $jml_benar++;
                        $total_bobot += $soal->bobot;
                    }
                }
            }

            // Calculate final score (scale 0-100)
            $nilai = ($total_bobot / $total_bobot_maksimal) * 100;
            
            // Update h_ujian with answers and scores
            $this->db->where('id', $id_h_ujian)
                ->update('h_ujian', [
                    'list_jawaban' => json_encode($jawaban_akhir),
                    'jml_benar' => $jml_benar,
                    'nilai_bobot' => $total_bobot,
                    'nilai' => number_format($nilai, 2, '.', ''),
                    'status' => 'completed',
                    'tgl_selesai' => date('Y-m-d H:i:s')
                ]);

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('Gagal menyimpan hasil ujian');
            }

            $this->output_json([
                'status' => true,
                'message' => 'Ujian berhasil diselesaikan',
                'csrf_hash_new' => $this->security->get_csrf_hash()
            ]);

        } catch (Exception $e) {
            log_message('error', 'Error in selesaikan_ujian: ' . $e->getMessage());
            $this->output_json([
                'status' => false,
                'message' => $e->getMessage(),
                'csrf_hash_new' => $this->security->get_csrf_hash()
            ]);
        }
    }

    /**
     * Helper untuk menghitung nilai ujian
     */
    private function hitung_nilai($id_h_ujian)
    {
        // Ambil data ujian dan jawaban
        $h_ujian = $this->db->get_where('h_ujian', ['id' => $id_h_ujian])->row();
        $list_jawaban = json_decode($h_ujian->list_jawaban, true);
        $list_soal = json_decode($h_ujian->list_soal);

        // Ambil kunci jawaban dari bank soal
        $soal_kunci = $this->db->select('id_soal, jawaban, bobot')
                        ->from('tb_soal')
                        ->where_in('id_soal', $list_soal)
                        ->get()->result();

        $jml_benar = 0;
        $total_bobot = 0;
        
        foreach ($soal_kunci as $soal) {
            if (isset($list_jawaban[$soal->id_soal])) {
                $jawaban_siswa = $list_jawaban[$soal->id_soal]['j'];
                if (strtoupper($jawaban_siswa) === strtoupper($soal->jawaban)) {
                    $jml_benar++;
                    $total_bobot += $soal->bobot;
                }
            }
        }

        // Hitung nilai (skala 100)
        $nilai = ($total_bobot / count($soal_kunci)) * 100;

        return [
            'jml_benar' => $jml_benar,
            'nilai' => round($nilai, 2),
            'nilai_bobot' => $total_bobot
        ];
    }

    // ========================================================================
    // FITUR HASIL UJIAN SISWA (UNTUK GURU DAN ADMIN)
    // ========================================================================

    /**
     * Halaman utama untuk menampilkan daftar hasil ujian siswa (bagi guru dan admin).
     * URL: /ujian/hasil_ujian_siswa
     */
    public function hasil_ujian_siswa()
    {
        if (!$this->is_admin && !$this->is_guru) {
            $this->session->set_flashdata('error', 'Anda tidak memiliki hak akses ke halaman ini.');
            redirect('dashboard');
            return;
        }

        $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif(); // Dapatkan TA aktif
        $default_ta_id = $tahun_ajaran_aktif ? $tahun_ajaran_aktif->id_tahun_ajaran : 'all'; // Default 'all' jika tidak ada yang aktif

        $data = [
            'user'          => $this->ion_auth->user()->row(),
            'judul'         => 'Hasil Ujian Siswa',
            'subjudul'      => 'Daftar Hasil Ujian',
            'is_admin'      => $this->is_admin,
            'is_guru'       => $this->is_guru,
            'guru_data'     => $this->guru_data,
            'pj_mapel_data' => $this->pj_mapel_data,
            'default_ta_id' => $default_ta_id, // Kirim ke view
        ];

        $data['filter_tahun_ajaran_options'] = $this->master->getAllTahunAjaran();
        $data['filter_jenjang_options']      = $this->master->getAllJenjang(); // Mungkin tidak terpakai jika filter hanya berdasarkan kelas

        if ($this->is_admin) {
            $data['filter_mapel_options'] = $this->master->getAllMapel();
            $data['filter_kelas_options'] = $this->master->getAllKelas();
        } elseif ($this->is_guru && $this->guru_data) {
            if ($tahun_ajaran_aktif) {
                $mapel_diajar = $this->master->getMapelDiajarGuru($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);
                if (!empty($mapel_diajar)) {
                    $data['filter_mapel_options'] = $this->db->where_in('id_mapel', $mapel_diajar)->get('mapel')->result();
                } else {
                    $data['filter_mapel_options'] = [];
                }
                
                $kelas_diajar = $this->master->getKelasDiajarGuru($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);
                if (!empty($kelas_diajar)) {
                     // Get full kelas data (with jenjang) for dropdown
                     $data['filter_kelas_options'] = $this->master->getKelasByIds($kelas_diajar);
                } else {
                    $data['filter_kelas_options'] = [];
                }
            } else {
                $data['filter_mapel_options'] = [];
                $data['filter_kelas_options'] = [];
            }
        } else {
            $data['filter_mapel_options'] = [];
            $data['filter_kelas_options'] = [];
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('ujian/hasil_ujian_siswa', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }

    // B. Method `get_summary_hasil_ujian()`
    // Modifikasi output untuk format tanggal/waktu dan menambahkan Guru Mata Pelajaran
    public function get_summary_hasil_ujian()
    {
        if (!$this->input->is_ajax_request()) {
            show_404(); return;
        }

        $filters = [
            'id_tahun_ajaran' => $this->input->post('filter_tahun_ajaran', true),
            'mapel_id'        => $this->input->post('filter_mapel', true),
            'kelas_id'        => $this->input->post('filter_kelas', true),
        ];

        $context = [
            'is_admin'  => $this->is_admin,
            'is_guru'   => $this->is_guru,
            'id_guru'   => ($this->is_guru && $this->guru_data) ? $this->guru_data->id_guru : null,
            'tahun_ajaran_aktif_id' => $this->master->getTahunAjaranAktif()->id_tahun_ajaran ?? null
        ];

        $summary = $this->ujian_m->getSummaryUjianData($filters, $context);
        
        // Inisialisasi summary_output dengan nilai default
        $summary_output = (object)[
            'nama_ujian' => '-',
            'jumlah_soal' => '-',
            'waktu_ujian_formatted' => '-',
            'hari_tanggal_formatted' => '-',
            'nama_mapel' => '-',
            'nama_guru_pembuat' => '-',
            'guru_mapel_mengajar' => '-',
            'nilai_terendah' => '-',
            'nilai_tertinggi' => '-',
            'rata_rata_nilai' => '-',
            'total_peserta_selesai' => '-',
            'siswa_nilai_terendah' => [], // Penting: Inisialisasi sebagai array kosong
            'siswa_nilai_tertinggi' => [] // Penting: Inisialisasi sebagai array kosong
        ];

        if ($summary) {
            // Format data dari $summary utama
            $summary_output->nama_ujian = $summary->nama_ujian;
            $summary_output->jumlah_soal = $summary->jumlah_soal;
            $summary_output->waktu_ujian_formatted = ($summary->tgl_mulai && $summary->terlambat) ?
                                                date('H.i', strtotime($summary->tgl_mulai)) . '-' . date('H.i', strtotime($summary->terlambat)) . ' WIB' : '-';
            setlocale(LC_TIME, 'id_ID', 'Indonesian', 'id');
            $summary_output->hari_tanggal_formatted = ($summary->tgl_mulai) ? strftime('%A, %d %B %Y', strtotime($summary->tgl_mulai)) : '-';
            
            $summary_output->nama_mapel = $summary->nama_mapel;
            $summary_output->nama_guru_pembuat = $summary->nama_guru_pembuat;

            // Ambil Guru Mata Pelajaran (yang mengajar mapel ujian di kelas tersebut)
            $guru_mapel_mengajar = $this->master->getGuruMengajarMapelKelas(
                $summary->mapel_id_raw,
                $filters['kelas_id'], // Gunakan filter kelas yang dipilih
                $summary->id_tahun_ajaran_raw
            );
            $summary_output->guru_mapel_mengajar = $guru_mapel_mengajar ? implode(', ', array_column($guru_mapel_mengajar, 'nama_guru')) : '-';

            // Ambil nilai terendah dan tertinggi secara terpisah
            // PENTING: Panggil get_nilai_terendah/tertinggi dengan filter dan context yang sama
            $nilai_terendah_obj = $this->ujian_m->get_nilai_terendah($filters, $context);
            $nilai_tertinggi_obj = $this->ujian_m->get_nilai_tertinggi($filters, $context);

            $nilai_terendah_val = $nilai_terendah_obj ? $nilai_terendah_obj->nilai_terendah : null;
            $nilai_tertinggi_val = $nilai_tertinggi_obj ? $nilai_tertinggi_obj->nilai_tertinggi : null;

            $summary_output->nilai_terendah = ($nilai_terendah_val !== null) ? number_format($nilai_terendah_val, 2) : '-';
            $summary_output->nilai_tertinggi = ($nilai_tertinggi_val !== null) ? number_format($nilai_tertinggi_val, 2) : '-';
            
            // Ambil nama siswa dengan nilai tersebut
            $summary_output->siswa_nilai_terendah = $this->ujian_m->get_siswa_dengan_nilai($nilai_terendah_val, $filters, $context);
            $summary_output->siswa_nilai_tertinggi = $this->ujian_m->get_siswa_dengan_nilai($nilai_tertinggi_val, $filters, $context);

            $summary_output->rata_rata_nilai = ($summary->rata_rata_nilai !== null) ? number_format($summary->rata_rata_nilai, 2) : '-';
            $summary_output->total_peserta_selesai = ($summary->total_peserta_selesai !== null) ? $summary->total_peserta_selesai : '-';

        }
        // Jika $summary (dari getSummaryUjianData) adalah null, maka summary_output tetap dengan nilai default '-'

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => true, 'summary' => $summary_output, 'csrf_hash_new' => $this->security->get_csrf_hash()]));
    }

    /**
     * Endpoint AJAX untuk DataTables daftar hasil ujian siswa.
     * URL: /ujian/data_hasil_ujian_siswa
     */
    public function data_hasil_ujian_siswa()
    {
        if (!$this->input->is_ajax_request()) {
            show_404(); return;
        }

        $filters = [
            'id_tahun_ajaran' => $this->input->post('filter_tahun_ajaran', true),
            'kelas_id'        => $this->input->post('filter_kelas', true),
            'mapel_id'        => $this->input->post('filter_mapel', true),
        ];

        $context = [
            'is_admin'  => $this->is_admin,
            'is_guru'   => $this->is_guru,
            'id_guru'   => ($this->is_guru && $this->guru_data) ? $this->guru_data->id_guru : null,
            'tahun_ajaran_aktif_id' => $this->master->getTahunAjaranAktif()->id_tahun_ajaran ?? null
        ];
        
        $this->output_json($this->ujian_m->getHasilUjianDatatables($filters, $context));
    }

    /**
     * Halaman detail hasil ujian siswa.
     * URL: /ujian/detail_hasil_ujian/{id_h_ujian_encrypted}
     */
    public function detail_hasil_ujian($id_h_ujian_enc = null)
    {
        // Akses hanya untuk Admin atau Guru
        if (!$this->is_admin && !$this->is_guru) {
            $this->session->set_flashdata('error', 'Anda tidak memiliki hak akses ke halaman ini.');
            redirect('dashboard');
            return; // Penting: Tambahkan return setelah redirect
        }

        if (empty($id_h_ujian_enc)) {
            show_404(); 
            return;
        }

        try {
            log_message('debug', 'detail_hasil_ujian: REQUESTED Encrypted ID = ' . ($id_h_ujian_enc ?? 'NULL'));

            $id_h_ujian = $this->ujian_m->decrypt_examm_id($id_h_ujian_enc);
            log_message('debug', 'detail_hasil_ujian: Decrypted ID H Ujian (after decrypt_exam_id) = ' . ($id_h_ujian ?? 'NULL'));
            log_message('debug', 'detail_hasil_ujian: Type of Decrypted ID = ' . gettype($id_h_ujian));

            if (!$id_h_ujian || !is_numeric($id_h_ujian)) { 
                log_message('error', 'detail_hasil_ujian: Decrypted ID is not valid or not numeric: ' . ($id_h_ujian ?? 'NULL'));
                $this->session->set_flashdata('error', 'ID hasil ujian tidak valid atau tidak dapat diproses.');
                redirect('ujian/hasil_ujian_siswa');
                return;
            }

            $hasil_ujian = $this->ujian_m->get_hasil_ujian_detail_for_guru_admin($id_h_ujian);
            log_message('debug', 'detail_hasil_ujian: Hasil Ujian Data (from model) = ' . print_r($hasil_ujian, true));

            if (!$hasil_ujian) {
                log_message('error', 'detail_hasil_ujian: Data hasil ujian tidak ditemukan untuk ID: ' . $id_h_ujian);
                $this->session->set_flashdata('error', 'Data hasil ujian tidak ditemukan.');
                redirect('ujian/hasil_ujian_siswa');
                return;
            }

            // Validasi hak akses guru ke hasil ujian ini
            if ($this->is_guru && !$this->is_admin) {
                $can_view = false;
                $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif();
                
                log_message('debug', 'detail_hasil_ujian (Guru Access Check): Current Guru ID = ' . ($this->guru_data->id_guru ?? 'N/A'));
                log_message('debug', 'detail_hasil_ujian (Guru Access Check): TA Aktif ID = ' . ($tahun_ajaran_aktif->id_tahun_ajaran ?? 'N/A'));
                log_message('debug', 'detail_hasil_ujian (Guru Access Check): Hasil Ujian Mapel ID = ' . ($hasil_ujian->mapel_id ?? 'N/A'));
                log_message('debug', 'detail_hasil_ujian (Guru Access Check): Hasil Ujian Siswa Kelas ID = ' . ($hasil_ujian->kelas_id_siswa ?? 'N/A'));

                if ($tahun_ajaran_aktif && isset($this->guru_data->id_guru)) {
                    $mapel_diajar = $this->master->getMapelDiajarGuru($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);
                    $kelas_diajar = $this->master->getKelasDiajarGuru($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);

                    log_message('debug', 'detail_hasil_ujian (Guru Access Check): Mapel Diajar (from Master_model) = ' . print_r($mapel_diajar, true));
                    log_message('debug', 'detail_hasil_ujian (Guru Access Check): Kelas Diajar (from Master_model) = ' . print_r($kelas_diajar, true));

                    if (in_array($hasil_ujian->mapel_id, $mapel_diajar) && in_array($hasil_ujian->kelas_id_siswa, $kelas_diajar)) {
                        $can_view = true;
                    } else {
                        log_message('debug', 'detail_hasil_ujian (Guru Access Check): Mapel/Kelas tidak cocok.');
                    }
                } else {
                    log_message('debug', 'detail_hasil_ujian (Guru Access Check): Data guru atau TA aktif tidak lengkap.');
                }

                if (!$can_view) {
                    log_message('error', 'detail_hasil_ujian: Akses ditolak untuk guru ini ke hasil ujian ID: ' . $id_h_ujian);
                    $this->session->set_flashdata('error', 'Anda tidak memiliki hak untuk melihat hasil ujian ini.');
                    redirect('ujian/hasil_ujian_siswa');
                    return;
                }
            }
            log_message('debug', 'detail_hasil_ujian: Validasi akses berhasil. Memuat detail ujian.');

            // Ambil list soal dan jawaban siswa
            $list_soal_ids = json_decode($hasil_ujian->list_soal, true);
            $list_jawaban_siswa = json_decode($hasil_ujian->list_jawaban, true);

            // Ambil detail soal dari bank soal (termasuk kunci jawaban dan bobot)
            $soal_details = $this->ujian_m->get_soal_details_with_kunci_and_bobot($list_soal_ids);
            
            // Map soal details to be ordered by list_soal_ids and include student answers
            $ordered_soal_data = [];
            foreach ($list_soal_ids as $soal_id) {
                $found_soal = null;
                foreach ($soal_details as $detail) {
                    if ($detail->id_soal == $soal_id) {
                        $found_soal = $detail;
                        break;
                    }
                }

                if ($found_soal) {
                    $jawaban_siswa_soal_ini = $list_jawaban_siswa[$soal_id]['j'] ?? '';
                    $is_correct = (strtoupper($jawaban_siswa_soal_ini) === strtoupper($found_soal->jawaban));
                    $poin_diperoleh = $is_correct ? $found_soal->bobot : 0;

                    $found_soal->jawaban_siswa = $jawaban_siswa_soal_ini;
                    $found_soal->is_correct = $is_correct;
                    $found_soal->poin_diperoleh = $poin_diperoleh;
                    $found_soal->opsi_display = []; // Init untuk ditampilkan
                    // Populating options for display
                    $options = ['A', 'B', 'C', 'D', 'E'];
                    foreach($options as $opt) {
                        $opsi_prop = 'opsi_' . strtolower($opt);
                        $file_prop = 'file_' . strtolower($opt);
                        if (!empty($found_soal->$opsi_prop) || !empty($found_soal->$file_prop)) { // Cek opsi atau file opsi ada
                            $found_soal->opsi_display[$opt] = [
                                'teks' => $found_soal->$opsi_prop,
                                'file' => $found_soal->$file_prop
                            ];
                        }
                    }
                    $ordered_soal_data[] = $found_soal;
                }
            }
            
            $data = [
                'user'          => $this->ion_auth->user()->row(),
                'judul'         => 'Detail Hasil Ujian',
                'subjudul'      => 'Hasil Ujian ' . htmlspecialchars($hasil_ujian->nama_siswa),
                'hasil_ujian'   => $hasil_ujian,
                'soal_data'     => $ordered_soal_data,
                'path_bank_soal_files' => base_url('uploads/bank_soal/') // Path untuk gambar/audio soal
            ];

            // VVVVVV TAMBAHKAN BARIS INI VVVVVV
            $this->load->view('_templates/dashboard/_header.php', $data);
            $this->load->view('ujian/detail_hasil_ujian', $data);
            $this->load->view('_templates/dashboard/_footer.php');
            // ^^^^^^ TAMBAHKAN BARIS INI ^^^^^^

        } catch (Exception $e) {
            log_message('error', 'Error in detail_hasil_ujian (overall catch): ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Terjadi kesalahan: ' . $e->getMessage());
            redirect('ujian/hasil_ujian_siswa');
            return;
        }
    }

    /**
     * Helper untuk memastikan akses hanya untuk Admin atau Guru
     */
    private function _akses_admin_guru_required() {
        if (!$this->is_admin && !$this->is_guru) {
            $this->session->set_flashdata('error', 'Anda tidak memiliki hak akses ke halaman ini.');
            redirect('dashboard');
            exit;
        }
    }

    /**
     * Menghapus data hasil ujian (dari tabel h_ujian).
     * Dapat menghapus tunggal atau massal.
     * URL: /ujian/delete_hasil_ujian (Method POST)
     */
    public function delete_hasil_ujian()
    {
        $this->_akses_admin_guru_required(); // Pastikan pemanggilan ini ada

        $response = ['status' => false, 'message' => ''];

        if (!$this->input->is_ajax_request()) {
            $response['message'] = 'Invalid request!';
            echo json_encode($response);
            return;
        }

        $ids = $this->input->post('ids', true); // Array of IDs from the checkboxes

        if (empty($ids)) {
            $response['message'] = 'Tidak ada data hasil ujian yang dipilih untuk direset.';
            echo json_encode($response);
            return;
        }

        // Pastikan $ids adalah array
        if (!is_array($ids)) {
            $ids = [$ids]; // Konversi ke array jika hanya satu ID
        }
        $clean_ids = array_map('intval', $ids); // Pastikan semua ID adalah integer

        try {
            $this->db->trans_start();

            $deleted_count = $this->ujian_m->delete_hasil_ujian($clean_ids); // Pastikan nama method sudah benar

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                // Ini akan menangkap kesalahan transaksi database
                throw new Exception('Gagal reset data hasil ujian. Database error: ' . $this->db->error()['message']);
            }

            if ($deleted_count > 0) {
                $response['status'] = true;
                $response['message'] = $deleted_count . ' hasil ujian berhasil direset.';
            } else {
                $response['message'] = 'Tidak ada hasil ujian yang direset. Mungkin data tidak ditemukan atau sudah terhapus.';
            }

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $response['message'] = $e->getMessage();
        }

        $response['csrf_hash_new'] = $this->security->get_csrf_hash();

        // Pastikan header content-type diatur agar browser tahu ini JSON
        $this->output->set_content_type('application/json')->set_output(json_encode($response));
        // echo json_encode($response); // Hapus ini jika menggunakan set_output
    }
}