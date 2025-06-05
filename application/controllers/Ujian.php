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

            $result = $this->ujian_m->get_list_ujian_for_siswa_dt(
                $this->siswa_data->id_siswa,
                $this->siswa_data->id_kelas,
                $this->siswa_data->id_jenjang,
                $this->siswa_data->id_tahun_ajaran
            );

            // Ensure proper DataTables response format
            $response = [
                'draw' => $this->input->post('draw'),
                'recordsTotal' => $this->ujian_m->count_all(),
                'recordsFiltered' => $this->ujian_m->count_filtered(),
                'data' => json_decode($result, true)['data'] ?? []
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

            $ujian = $this->ujian_m->get_ujian_by_id_with_guru($id_ujian);
            
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
                        'message' => 'Token valid, memulai ujian...',
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

            // Use the consistent decryption method
            $id_h_ujian = $this->ujian_m->decrypt_exam_id($id_h_ujian_enc);
            
            if (!$id_h_ujian) {
                throw new Exception('ID ujian tidak valid');
            }

            // Get h_ujian data
            $h_ujian = $this->db->get_where('h_ujian', [
                'id' => $id_h_ujian,
                'siswa_id' => $this->siswa_data->id_siswa
            ])
            ->row();

            if (!$h_ujian) {
                throw new Exception('Data ujian tidak ditemukan');
            }

            // Cek status dan waktu
            $now = time();
            $waktu_selesai = strtotime($h_ujian->tgl_selesai);

            // Ambil data ujian dan master ujian
            $h_ujian = $this->db->query("
                SELECT h.*, u.terlambat as batas_masuk 
                FROM h_ujian h 
                JOIN m_ujian u ON h.ujian_id = u.id_ujian 
                WHERE h.id = ? AND h.siswa_id = ?
            ", [$id_h_ujian, $this->siswa_data->id_siswa])->row();

            if (!$h_ujian) {
                throw new Exception('Data ujian tidak ditemukan');
            }

            log_message('debug', 'Query result h_ujian: ' . print_r($h_ujian, true));

            // Hitung sisa waktu berdasarkan batas terlambat
            $waktu_sekarang = time();
            $waktu_terlambat = strtotime($h_ujian->batas_masuk);
            $sisa_waktu = max(0, $waktu_terlambat - $waktu_sekarang);

            if ($waktu_terlambat === false) {
                // Fallback ke waktu dari m_ujian jika parsing gagal
                $waktu_terlambat = strtotime($ujian->terlambat);
            }
            
            if ($waktu_terlambat === false) {
                throw new Exception('Format waktu tidak valid');
            }

            // Set waktu selesai untuk ditampilkan di view
            $data = $this->ujian_m->get_lembar_ujian_siswa($id_h_ujian, $this->siswa_data->id_siswa);
            $data['waktu_selesai'] = date('Y-m-d H:i:s', $waktu_terlambat);
            $data['sisa_waktu'] = $sisa_waktu;

            if ($h_ujian->status == 'completed') {
                redirect('ujian/list_ujian_siswa');
                return;
            }

            // Update waktu mulai saat pertama kali masuk ke lembar ujian
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

            // Load data untuk view
            $data = $this->ujian_m->get_lembar_ujian_siswa($id_h_ujian, $this->siswa_data->id_siswa);
            
            if (!$data) {
                throw new Exception('Gagal memuat data ujian');
            }

            // Use the same encrypted ID
            $data['id_h_ujian_enc'] = $this->session->userdata('active_exam_enc');

            $data = [
                'user' => $this->ion_auth->user()->row(),
                'siswa' => $this->siswa_data,
                'judul' => 'Lembar Ujian',
                'subjudul' => $h_ujian->nama_ujian,
                'ujian' => $ujian,
                'h_ujian' => $h_ujian,
                'soal_collection' => $this->ujian_m->get_soal_by_id_ujian($id_h_ujian),
                'jawaban_tersimpan_php' => json_decode($h_ujian->list_jawaban, true) ?: [],
                'jawaban_tersimpan' => json_decode($h_ujian->list_jawaban, true) ?: [],
                'id_h_ujian_enc' => $id_h_ujian_enc,
                'waktu_selesai' => date('Y-m-d H:i:s', $waktu_terlambat),
                'sisa_waktu' => $sisa_waktu,
                // Tambahkan debug
                'debug_jawaban' => [
                    'raw' => $h_ujian->list_jawaban,
                    'decoded' => json_decode($h_ujian->list_jawaban, true)
                ],
                // Tambahan untuk debugging
                'debug_info' => [
                    'waktu_sekarang' => date('Y-m-d H:i:s', $waktu_sekarang),
                    'waktu_terlambat' => date('Y-m-d H:i:s', $waktu_terlambat),
                    'sisa_waktu' => $sisa_waktu
                ],
            ];
            $data['debug_soal'] = array_map(function($soal) {
                return [
                    'id_soal' => $soal->id_soal,
                    'soal_text' => substr($soal->soal, 0, 100) . '...', // First 100 chars
                    'has_file' => !empty($soal->file),
                    'file_path' => !empty($soal->file) ? FCPATH . 'uploads/bank_soal/' . $soal->file : null,
                    'file_exists' => !empty($soal->file) ? file_exists(FCPATH . 'uploads/bank_soal/' . $soal->file) : false
                ];
            }, $data['soal_collection']);

            $this->load->view('_templates/topnav/_header.php', $data);
            $this->load->view('ujian/lembar_ujian', $data);
            $this->load->view('_templates/topnav/_footer.php');

        } catch (Exception $e) {
            log_message('error', 'Error in lembar_ujian: ' . $e->getMessage());
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('ujian/list_ujian_siswa');
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
}