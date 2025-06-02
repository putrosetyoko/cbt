<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Soal extends CI_Controller {

    private $user_id;
    private $is_admin;
    private $is_guru;
    private $guru_data; // Data guru jika login sebagai guru
    private $pj_mapel_data; // Data mapel yang diampu sebagai PJ Soal

    public function __construct(){
        parent::__construct();
        if (!$this->ion_auth->logged_in()){
            redirect('auth');
        }
        
        $this->load->library(['datatables', 'form_validation', 'session']); // Pastikan session diload
        $this->load->helper('my');
        $this->load->model('Master_model', 'master');
        $this->load->model('Soal_model', 'soal');
        $this->form_validation->set_error_delimiters('','');

        $user_ion_auth = $this->ion_auth->user()->row();
        $this->user_id = $user_ion_auth->id;
        $this->is_admin = $this->ion_auth->is_admin();
        $this->is_guru = $this->ion_auth->in_group('guru');

        if ($this->is_guru) {
            // Konsisten dengan Auth.php atau cara lain yang valid
            if (!empty($user_ion_auth->username)) {
                $this->guru_data = $this->db->get_where('guru', ['nip' => $user_ion_auth->username])->row();
            }
            if (!$this->guru_data && !empty($user_ion_auth->email)) {
                $this->guru_data = $this->db->get_where('guru', ['email' => $user_ion_auth->email])->row();
            }
            // Alternatif:
            // if (!$this->guru_data) {
            //    $this->guru_data = $this->db->get_where('guru', ['user_id' => $this->user_id])->row();
            // }

            if ($this->guru_data) {
                $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif();
                if ($tahun_ajaran_aktif && isset($this->guru_data->id_guru)) { // Pastikan id_guru ada
                    $this->pj_mapel_data = $this->master->getMapelPJByGuruTahun($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);
                }
            }
        }

        if (!$this->is_admin && !$this->is_guru) {
            show_error('Hanya Administrator dan Guru yang diberi hak untuk mengakses halaman ini.', 403, 'Akses Terlarang');
        }
    }

    public function output_json($data, $encode = true)
    {
        if($encode && !is_string($data)) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function index()
    {
        $data = [
            'user'      => $this->ion_auth->user()->row(), // Data user yang login
            'judul'     => 'Bank Soal',
            'subjudul'  => 'Daftar Soal',
            'is_admin'  => $this->is_admin,
            'is_guru'   => $this->is_guru,
            'guru_data'     => $this->guru_data,
            'pj_mapel_data' => $this->pj_mapel_data, // Kirim data mapel PJ ke view
            'all_jenjang' => $this->master->getAllJenjang() // Untuk filter jenjang
        ];
        
        if($this->is_admin){
            $data['all_mapel'] = $this->master->getAllMapel();
            $data['all_guru_pembuat'] = $this->master->getAllGuru(); // Untuk filter admin
        } else if ($this->is_guru) {
            // Guru (PJ atau Non-PJ) akan melihat filter mapel berdasarkan apa yang bisa mereka akses
            if ($this->pj_mapel_data) { // Jika dia PJ Soal
                $data['mapel_filter_options'] = [$this->pj_mapel_data]; // Hanya mapel PJ-nya
            } else { // Jika dia Guru Non-PJ
                $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif();
                if ($this->guru_data && $tahun_ajaran_aktif && isset($this->guru_data->id_guru)) { // Added isset for id_guru
                    // This method likely returns an array of objects or an array of IDs.
                    // Let's assume it might return objects like [{id_mapel: value}, ...]
                    $mapel_obj_diajar = $this->master->getMapelDiajarGuru($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);
                    
                    $actual_mapel_ids = []; // Array to store just the IDs
                    if (!empty($mapel_obj_diajar)) {
                        // Check if it's an array of objects and extract IDs
                        if (is_array($mapel_obj_diajar) && is_object($mapel_obj_diajar[0]) && property_exists($mapel_obj_diajar[0], 'id_mapel')) {
                            foreach ($mapel_obj_diajar as $mapel_item) {
                                $actual_mapel_ids[] = $mapel_item->id_mapel;
                            }
                        } elseif (is_array($mapel_obj_diajar)) { 
                            // If it's already an array of IDs (e.g., from a more direct query)
                            $actual_mapel_ids = $mapel_obj_diajar;
                        }
                    }
                    
                    if(!empty($actual_mapel_ids)){
                        // Use the array of actual IDs here
                        $this->db->where_in('id_mapel', $actual_mapel_ids);
                        $data['mapel_filter_options'] = $this->db->get('mapel')->result();
                    } else {
                        $data['mapel_filter_options'] = [];
                    }
                } else {
                    $data['mapel_filter_options'] = [];
                }
            }
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/data', $data); // Path view
        $this->load->view('_templates/dashboard/_footer.php');
    }
    
    public function data()
    {
        $filters = [
            'mapel_id'        => $this->input->post('filter_mapel', true),
            'jenjang_id'      => $this->input->post('filter_jenjang', true),
            'guru_pembuat_id' => $this->input->post('filter_guru_pembuat', true) // Hanya untuk Admin
        ];

        $guru_id_login = $this->is_guru && $this->guru_data ? $this->guru_data->id_guru : null;

        if (!$this->is_admin && $this->is_guru) {
            $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif();
            if ($this->pj_mapel_data && $tahun_ajaran_aktif) {
                // Jika Guru adalah PJ Soal, filter utama adalah mapel PJ-nya
                $filters['mapel_id'] = $this->pj_mapel_data->id_mapel; // Override filter mapel
                // PJ Soal bisa melihat semua soal di mapel PJ-nya, tidak peduli siapa pembuatnya (jika ada soal dari PJ lama/admin)
                unset($filters['guru_pembuat_id']); // Hapus filter guru pembuat untuk PJ
            } else if ($this->guru_data && $tahun_ajaran_aktif) {
                // Jika Guru Non-PJ, filter berdasarkan mapel yang diajar
                $mapel_obj_diajar = $this->master->getMapelDiajarGuru($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);
                $actual_mapel_ids_for_filter = []; 
                if (!empty($mapel_obj_diajar)) {
                    if (is_array($mapel_obj_diajar)) { // Pastikan itu array
                        if (count($mapel_obj_diajar) > 0 && is_object($mapel_obj_diajar[0]) && property_exists($mapel_obj_diajar[0], 'id_mapel')) {
                            foreach ($mapel_obj_diajar as $mapel_item) {
                                $actual_mapel_ids_for_filter[] = $mapel_item->id_mapel;
                            }
                        } elseif (count($mapel_obj_diajar) > 0 && !is_object($mapel_obj_diajar[0])) { 
                            // Jika sudah array flat (misalnya array of strings/integers)
                            $actual_mapel_ids_for_filter = $mapel_obj_diajar;
                        }
                    }
                }
                
                if (!empty($actual_mapel_ids_for_filter)) {
                    $filters['mapel_ids_for_guru'] = $actual_mapel_ids_for_filter; // Ini harus array flat [1, 2, 3]
                } else {
                    // Guru tidak mengajar mapel apapun / tidak ada penugasan, kirim data kosong
                    $this->output_json(['draw' => $this->input->post('draw', true), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]); //Hapus parameter false jika tidak ada
                    return;
                }

                // Logika untuk filter mapel spesifik vs semua mapel yang diajar
                if(!empty($filters['mapel_id']) && $filters['mapel_id'] !== 'all' && !in_array($filters['mapel_id'], $actual_mapel_ids_for_filter)){
                    // Jika guru filter mapel yg tidak diajar, jangan tampilkan apa2
                    $this->output_json(['draw' => $this->input->post('draw', true), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);//Hapus parameter false jika tidak ada
                    return;
                } elseif (empty($filters['mapel_id']) || $filters['mapel_id'] === 'all') {
                    // Jika filter mapel "semua", biarkan filter mapel_ids_for_guru yang bekerja
                    unset($filters['mapel_id']); // Hapus mapel_id agar tidak bentrok dengan mapel_ids_for_guru di model
                }
                unset($filters['guru_pembuat_id']);
            } else {
                // Guru tapi tidak ada data guru / TA aktif
                $this->output_json(['draw' => $this->input->post('draw', true), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
                return;
            }
        }
        log_message('debug', 'Filters being sent to model: ' . print_r($filters, true));
        $this->output_json($this->soal->getDataSoal($filters, $this->is_admin, $guru_id_login));
    }
    
    public function add()
    {
        // Hanya Admin atau Guru PJ Soal yang bisa menambah soal
        if (!$this->is_admin && !($this->is_guru && $this->pj_mapel_data)) {
            show_error('Anda tidak memiliki hak untuk menambah soal.', 403, 'Akses Ditolak');
            return;
        }

        $data = [
            'user'     => $this->ion_auth->user()->row(),
            'judul'    => 'Bank Soal',
            'subjudul' => 'Buat Soal Baru',
            'is_admin' => $this->is_admin,
            'all_jenjang' => $this->master->getAllJenjang(),
        ];

        if ($this->is_admin) {
            $data['all_mapel'] = $this->master->getAllMapel();
            // Admin bisa memilih guru pembuat (opsional, default ke diri sendiri)
            // $data['all_guru_for_admin'] = $this->master->getAllGuru(); 
        } else if ($this->is_guru && $this->pj_mapel_data) {
            // Jika Guru adalah PJ Soal, mapel sudah ditentukan
            $data['mapel_pj'] = $this->pj_mapel_data; // Kirim objek mapel PJ
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/add', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function edit($id_soal = null)
    {
        if (!$id_soal || !is_numeric($id_soal)) { show_404(); return; }

        $soal = $this->soal->getSoalById($id_soal);
        if (!$soal) { show_error('Soal tidak ditemukan.', 404); return; }

        // Pengecekan hak akses edit
        $can_edit = false;
        if ($this->is_admin) {
            $can_edit = true;
        } elseif ($this->is_guru && $this->guru_data && $this->pj_mapel_data) {
            // Guru PJ Soal hanya bisa edit soal miliknya DAN di mapel PJ-nya
            if ($soal->guru_id == $this->guru_data->id_guru && $soal->mapel_id == $this->pj_mapel_data->id_mapel) {
                $can_edit = true;
            }
        }

        if (!$can_edit) {
            show_error('Anda tidak memiliki hak untuk mengedit soal ini.', 403, 'Akses Ditolak');
            return;
        }

        $data = [
            'user'     => $this->ion_auth->user()->row(),
            'judul'    => 'Bank Soal',
            'subjudul' => 'Edit Soal',
            'soal'     => $soal,
            'is_admin' => $this->is_admin,
            'all_jenjang' => $this->master->getAllJenjang(),
        ];

        if ($this->is_admin) {
            $data['all_mapel'] = $this->master->getAllMapel();
        } else if ($this->is_guru && $this->pj_mapel_data) {
            $data['mapel_pj'] = $this->pj_mapel_data;
        }
        
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/edit', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function detail($id_soal = null)
    {
        if (!$id_soal || !is_numeric($id_soal)) {
            show_404();
            return;
        }
    
        $soal = $this->soal->getSoalById($id_soal); // Mengambil objek soal
        // Pastikan $soal memiliki properti mapel_id dan guru_id
        if (!$soal || !property_exists($soal, 'mapel_id') || !property_exists($soal, 'guru_id')) {
            show_error('Soal tidak ditemukan atau data soal tidak lengkap (mapel_id/guru_id missing).', 404);
            return;
        }
    
        $can_view = false; // Default, akses ditolak
    
        if ($this->is_admin) { // $this->is_admin dari constructor
            $can_view = true;
            log_message('debug', 'Soal_detail_can_view: Akses sebagai ADMIN.');
        } elseif ($this->is_guru && $this->guru_data && isset($this->guru_data->id_guru)) { // $this->is_guru dan $this->guru_data dari constructor
            $id_guru_login = $this->guru_data->id_guru;
            log_message('debug', 'Soal_detail_can_view: Akses sebagai GURU ID: ' . $id_guru_login);
    
            // Cek apakah Guru PJ dan mapel soal adalah mapel PJ nya
            if ($this->pj_mapel_data && isset($this->pj_mapel_data->id_mapel) && $soal->mapel_id == $this->pj_mapel_data->id_mapel) {
                $can_view = true;
                log_message('debug', 'Soal_detail_can_view: Guru adalah PJ Soal untuk mapel ini. Akses diberikan.');
            } else { 
                // Blok ini untuk GURU NON-PJ (atau Guru PJ yang melihat soal di luar mapel PJ nya)
                log_message('debug', 'Soal_detail_can_view: Memeriksa sebagai Guru Non-PJ (atau PJ di luar mapelnya).');
                $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif();
    
                if ($tahun_ajaran_aktif && isset($tahun_ajaran_aktif->id_tahun_ajaran)) {
                    log_message('debug', 'Soal_detail_can_view: TA Aktif ID: ' . $tahun_ajaran_aktif->id_tahun_ajaran . '. Soal Mapel ID: ' . $soal->mapel_id);
                    
                    // $mapel_ids_diajar sekarang HARUS berupa array ID flat dari model
                    $mapel_ids_diajar = $this->master->getMapelDiajarGuru($id_guru_login, $tahun_ajaran_aktif->id_tahun_ajaran);
                    // Log dari model getMapelDiajarGuru akan menampilkan isi $mapel_ids_diajar
    
                    if (is_array($mapel_ids_diajar) && !empty($mapel_ids_diajar)) {
                        // Lakukan perbandingan. Pastikan tipe data $soal->mapel_id konsisten atau lakukan casting jika perlu.
                        // Misalnya, jika $soal->mapel_id adalah string dan $mapel_ids_diajar adalah array integer.
                        // in_array secara default tidak strict.
                        if (in_array($soal->mapel_id, $mapel_ids_diajar)) {
                            $can_view = true;
                            log_message('debug', 'Soal_detail_can_view: AKSES DIBERIKAN. Mapel soal (' . $soal->mapel_id . ') ADA dalam daftar mapel yang diajar: ' . print_r($mapel_ids_diajar, true));
                        } else {
                            log_message('debug', 'Soal_detail_can_view: AKSES DITOLAK. Mapel soal (' . $soal->mapel_id . ') TIDAK ADA dalam daftar mapel yang diajar: ' . print_r($mapel_ids_diajar, true));
                        }
                    } else {
                        log_message('debug', 'Soal_detail_can_view: AKSES DITOLAK. Guru tidak mengajar mapel apapun di TA aktif ini atau daftar mapel kosong.');
                    }
                } else {
                    log_message('debug', 'Soal_detail_can_view: AKSES DITOLAK. Tidak ada Tahun Ajaran Aktif yang ditemukan.');
                }
            }
        } else {
            log_message('debug', 'Soal_detail_can_view: Pengguna bukan Admin atau data Guru tidak lengkap.');
        }
    
        if (!$can_view) {
            log_message('error', 'Soal_detail_FINAL_CHECK: Akses Ditolak. User ID IonAuth: ' . ($this->user_id ?? 'N/A') . ', is_admin: ' . ($this->is_admin ? 'true':'false') . ', is_guru: ' . ($this->is_guru ? 'true':'false') . ', id_guru_tabel_guru: ' . (isset($this->guru_data->id_guru) ? $this->guru_data->id_guru : 'N/A') . ', soal_id: ' . $id_soal . ', soal_mapel_id: ' . ($soal->mapel_id ?? 'N/A'));
            show_error('Anda tidak memiliki hak untuk melihat detail soal ini.', 403, 'Akses Ditolak');
            return;
        }
    
        // Jika $can_view true, lanjutkan ke memuat view
        $data = [
            'user'              => $this->ion_auth->user()->row(),
            'judul'             => 'Bank Soal',
            'subjudul'          => 'Detail Soal',
            'soal'              => $soal,
            'is_admin_view'     => $this->is_admin, // Untuk logika di view (misal tombol edit)
            'logged_in_guru_id' => $this->session->userdata('guru_id') // Untuk logika di view (tombol edit)
        ];
        
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/detail', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }


    // Fungsi helper untuk konfigurasi upload file
    private function _file_config()
    {
        $config['upload_path']   = FCPATH.'uploads/bank_soal/';
        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, TRUE);
        }
        $config['allowed_types'] = 'jpeg|jpg|png|gif|mpeg|mpg|mp3|wav|mp4|pdf|doc|docx|xls|xlsx|ppt|pptx'; // Tambahkan tipe file lain jika perlu
        $config['encrypt_name']  = TRUE;
        $config['max_size']      = '2048'; // 2MB, sesuaikan jika perlu
        return $config;
    }

    public function save()
    {
        $method = $this->input->post('method', true);
        $user_login = $this->ion_auth->user()->row();
        $current_guru_id = null;
        $current_mapel_id_pj = null;

        if ($this->is_guru && $this->guru_data) {
            $current_guru_id = $this->guru_data->id_guru;
            if($this->pj_mapel_data){
                $current_mapel_id_pj = $this->pj_mapel_data->id_mapel;
            }
        } elseif ($this->is_admin) {
            $current_guru_id = $user_login->id; // Atau cara lain untuk ID admin sebagai guru jika ada
        }

        // Validasi
        $this->form_validation->set_rules('id_jenjang', 'Jenjang', 'required|numeric');
        $this->form_validation->set_rules('soal', 'Isi Soal', 'required|min_length[10]');
        $this->form_validation->set_rules('jawaban', 'Kunci Jawaban', 'required|in_list[A,B,C,D,E]');
        $this->form_validation->set_rules('bobot', 'Bobot Soal', 'required|integer|greater_than[0]');
        
        $abjad = ['a', 'b', 'c', 'd', 'e'];
        foreach ($abjad as $abj) {
            $this->form_validation->set_rules('jawaban_'.$abj, 'Opsi '.strtoupper($abj), 'required');
        }

        if ($this->is_admin) {
            $this->form_validation->set_rules('mapel_id', 'Mata Pelajaran', 'required|numeric');
            // Jika admin bisa assign ke guru lain, tambahkan validasi guru_id dari form
            // $this->form_validation->set_rules('guru_id_assign', 'Guru Pembuat', 'required|numeric');
        }

        if ($this->form_validation->run() === FALSE) {
            $errors = $this->form_validation->error_array();
            $this->output_json(['status' => false, 'errors' => $errors, 'message' => 'Validasi gagal, periksa kembali inputan Anda.']);
            return;
        }

        $data_soal = [
            'soal'    => $this->input->post('soal', false), // Jangan XSS clean untuk HTML editor
            'jawaban' => $this->input->post('jawaban', true),
            'bobot'   => $this->input->post('bobot', true),
            'id_jenjang' => $this->input->post('id_jenjang', true),
        ];

        // Tentukan mapel_id dan guru_id
        if ($this->is_admin) {
            $data_soal['mapel_id'] = $this->input->post('mapel_id', true);
            // Jika admin bisa memilih guru pembuat soal:
            // $data_soal['guru_id'] = $this->input->post('guru_id_assign', true); 
            // Jika tidak, guru_id adalah ID admin (atau ID guru yang terkait dengan akun admin)
            $admin_as_guru = $this->db->get_where('guru', ['email' => $user_login->email])->row(); // Contoh jika admin punya entri di tabel guru
            $data_soal['guru_id'] = $admin_as_guru ? $admin_as_guru->id_guru : $current_guru_id; // Fallback
        } else if ($this->is_guru && $this->pj_mapel_data && $current_guru_id) {
            $data_soal['mapel_id'] = $this->pj_mapel_data->id_mapel;
            $data_soal['guru_id']  = $current_guru_id;
        } else {
            $this->output_json(['status' => false, 'message' => 'Tidak dapat menentukan Mapel atau Guru pembuat soal.']);
            return;
        }

        // Input Opsi
        foreach ($abjad as $abj) {
            $data_soal['opsi_'.$abj] = $this->input->post('jawaban_'.$abj, false); // Jangan XSS clean untuk HTML editor
        }

        // Upload file
        $this->load->library('upload');
        $upload_path = FCPATH.'uploads/bank_soal/';
        if (!is_dir($upload_path)) { mkdir($upload_path, 0777, TRUE); }

        $id_soal_for_edit = $this->input->post('id_soal', true);
        $soal_lama = null;
        if ($method === 'edit' && $id_soal_for_edit) {
            $soal_lama = $this->soal->getSoalById($id_soal_for_edit);
        }

        // File Soal Utama
        if (!empty($_FILES['file_soal']['name'])) {
            $this->upload->initialize($this->_file_config());
            if ($this->upload->do_upload('file_soal')) {
                if ($method === 'edit' && $soal_lama && !empty($soal_lama->file) && file_exists($upload_path . $soal_lama->file)) {
                    unlink($upload_path . $soal_lama->file);
                }
                $data_soal['file'] = $this->upload->data('file_name');
                $data_soal['tipe_file'] = $this->upload->data('file_type');
            } else {
                $this->output_json(['status' => false, 'errors' => ['file_soal' => strip_tags($this->upload->display_errors())], 'message' => 'Gagal mengunggah file soal.']);
                return;
            }
        } elseif ($method === 'edit' && $this->input->post('hapus_file_soal') === '1' && $soal_lama && !empty($soal_lama->file)) {
            if (file_exists($upload_path . $soal_lama->file)) unlink($upload_path . $soal_lama->file);
            $data_soal['file'] = null; $data_soal['tipe_file'] = null;
        } elseif ($method === 'edit' && $soal_lama) { // Pertahankan file lama jika tidak ada upload baru & tidak dihapus
            $data_soal['file'] = $soal_lama->file; $data_soal['tipe_file'] = $soal_lama->tipe_file;
        }


        // File Opsi
        foreach ($abjad as $abj) {
            $input_file_opsi = 'file_' . $abj;
            $db_file_opsi_field = 'file_' . $abj;
            if (!empty($_FILES[$input_file_opsi]['name'])) {
                $this->upload->initialize($this->_file_config()); // Re-initialize for each upload
                if ($this->upload->do_upload($input_file_opsi)) {
                    if ($method === 'edit' && $soal_lama && !empty($soal_lama->$db_file_opsi_field) && file_exists($upload_path . $soal_lama->$db_file_opsi_field)) {
                        unlink($upload_path . $soal_lama->$db_file_opsi_field);
                    }
                    $data_soal[$db_file_opsi_field] = $this->upload->data('file_name');
                } else {
                    $this->output_json(['status' => false, 'errors' => [$input_file_opsi => strip_tags($this->upload->display_errors())], 'message' => 'Gagal mengunggah file opsi '.strtoupper($abj).'.']);
                    return;
                }
            } elseif ($method === 'edit' && $this->input->post('hapus_' . $input_file_opsi) === '1' && $soal_lama && !empty($soal_lama->$db_file_opsi_field)) {
                if (file_exists($upload_path . $soal_lama->$db_file_opsi_field)) unlink($upload_path . $soal_lama->$db_file_opsi_field);
                $data_soal[$db_file_opsi_field] = null;
            } elseif ($method === 'edit' && $soal_lama) {
                $data_soal[$db_file_opsi_field] = $soal_lama->$db_file_opsi_field;
            }
        }

        $action_success = false;
        if ($method === 'add') {
            $data_soal['created_on'] = time();
            $data_soal['updated_on'] = time();
            $action_success = $this->master->create('tb_soal', $data_soal);
            $message = $action_success ? 'Soal berhasil disimpan.' : 'Gagal menyimpan soal.';
        } else if ($method === 'edit' && $id_soal_for_edit) {
            $data_soal['updated_on'] = time();
            $action_success = $this->master->update('tb_soal', $data_soal, 'id_soal', $id_soal_for_edit);
            $message = $action_success ? 'Soal berhasil diperbarui.' : 'Gagal memperbarui soal.';
        } else {
            $message = 'Metode tidak valid atau ID Soal tidak ditemukan untuk edit.';
        }

        if ($action_success) {
            // Redirect ke halaman daftar soal setelah sukses
            $this->session->set_flashdata('success', $message);
            $this->output_json(['status' => true, 'redirect' => base_url('soal')]);
        } else {
            $this->output_json(['status' => false, 'message' => $message]);
        }
    }

    public function delete()
    {
        // Hanya Admin atau Guru PJ Soal yang bisa menghapus soal miliknya
        $chk = $this->input->post('checked', true); // Array of id_soal
        if (empty($chk) || !is_array($chk)) {
            $this->output_json(['status' => false, 'message' => 'Tidak ada soal yang dipilih.']);
            return;
        }

        $deleted_count = 0;
        $error_messages = [];

        foreach ($chk as $id_soal) {
            if (!is_numeric($id_soal)) continue;

            $soal = $this->soal->getSoalById($id_soal);
            if (!$soal) {
                $error_messages[] = "Soal dengan ID {$id_soal} tidak ditemukan.";
                continue;
            }

            $can_delete = false;
            if ($this->is_admin) {
                $can_delete = true;
            } elseif ($this->is_guru && $this->guru_data && $this->pj_mapel_data) {
                if ($soal->guru_id == $this->guru_data->id_guru && $soal->mapel_id == $this->pj_mapel_data->id_mapel) {
                    $can_delete = true;
                }
            }

            if ($can_delete) {
                if ($this->soal->deleteSoalBatch([$id_soal])) { // deleteSoalBatch menghapus file juga
                    $deleted_count++;
                } else {
                    $error_messages[] = "Gagal menghapus soal ID {$id_soal}.";
                }
            } else {
                $error_messages[] = "Anda tidak berhak menghapus soal ID {$id_soal}.";
            }
        }

        if ($deleted_count > 0) {
            $message = $deleted_count . ' soal berhasil dihapus.';
            if (!empty($error_messages)) {
                $message .= " Beberapa soal gagal dihapus: " . implode(", ", $error_messages);
            }
            $this->output_json(['status' => true, 'message' => $message, 'total' => $deleted_count]);
        } else {
            $message = 'Gagal menghapus soal.';
            if (!empty($error_messages)) {
                $message .= " Detail: " . implode(", ", $error_messages);
            }
            $this->output_json(['status' => false, 'message' => $message]);
        }
    }
}
