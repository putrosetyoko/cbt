<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ujian extends CI_Controller {

    private $user_id_ion_auth; // ID dari tabel users ion_auth
    private $is_admin;
    private $is_guru;
    private $guru_data;     // Objek data dari tabel 'guru' (termasuk id_guru PK-nya)
    private $pj_mapel_data; // Objek data mapel jika guru login adalah PJ Soal untuk TA aktif

    public function __construct(){
        parent::__construct();
        if (!$this->ion_auth->logged_in()){
            redirect('auth');
        }
        
        $this->load->library(['datatables', 'form_validation', 'session', 'encryption']); // Tambahkan encryption
        $this->load->helper(['my', 'form', 'url', 'string']); // Tambahkan string helper

        $this->load->model('Master_model', 'master');
        $this->load->model('Ujian_model', 'ujian_m'); 
        $this->load->model('Soal_model', 'soal_m'); // Ganti alias agar tidak konflik dengan variabel $soal

        $user_ion_auth = $this->ion_auth->user()->row();
        $this->user_id_ion_auth = $user_ion_auth->id;
        $this->is_admin = $this->ion_auth->is_admin();
        $this->is_guru = $this->ion_auth->in_group('guru');

        if ($this->is_guru) {
            if (!empty($user_ion_auth->username)) {
                $this->guru_data = $this->db->get_where('guru', ['nip' => $user_ion_auth->username])->row();
            }
            if (!$this->guru_data && !empty($user_ion_auth->email)) {
                $this->guru_data = $this->db->get_where('guru', ['email' => $user_ion_auth->email])->row();
            }

            if ($this->guru_data && isset($this->guru_data->id_guru)) {
                $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif();
                if ($tahun_ajaran_aktif) {
                    $this->pj_mapel_data = $this->master->getMapelPJByGuruTahun($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);
                } else {
                    $this->pj_mapel_data = null; // Pastikan null jika TA tidak aktif
                }
            } else {
                 $this->pj_mapel_data = null; // Pastikan null jika guru_data tidak ada
            }
        }
        $this->form_validation->set_error_delimiters('<span class="help-block text-danger">', '</span>');
    }

    private function _can_manage_ujian($id_ujian = null) {
        if ($this->is_admin) {
            return true;
        }
        if ($this->is_guru && $this->pj_mapel_data) {
            if ($id_ujian === null) { // Untuk akses umum membuat ujian
                return true;
            }
            // Untuk edit/delete, cek kepemilikan atau mapel PJ
            $ujian = $this->ujian_m->get_ujian_by_id($id_ujian);
            if ($ujian && $ujian->mapel_id == $this->pj_mapel_data->id_mapel) {
                // PJ Soal bisa edit/delete semua ujian di mapel PJ-nya,
                // atau hanya yang dia buat (ujian->guru_id == $this->guru_data->id_guru) - sesuaikan aturan
                return true; 
            }
        }
        return false;
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

        if ($this->is_guru && !$this->pj_mapel_data && $this->guru_data) { // Guru Non-PJ
            // Ambil TA dari filter jika ada, jika tidak, pakai TA aktif
            $id_ta_filter = ($filters['id_tahun_ajaran'] && $filters['id_tahun_ajaran'] !== 'all') 
                            ? $filters['id_tahun_ajaran'] 
                            : ($this->master->getTahunAjaranAktif()->id_tahun_ajaran ?? null);
            
            if ($id_ta_filter && isset($this->guru_data->id_guru)) {
                $guru_context['mapel_ids_diajar'] = $this->master->getMapelDiajarGuru($this->guru_data->id_guru, $id_ta_filter);
            }
        }
        
        $this->output_json($this->ujian_m->getUjianDatatables($filters, $guru_context));
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

}