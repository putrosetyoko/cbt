<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    private $user; // Properti untuk menyimpan data user yang login

    public function __construct(){
        parent::__construct();
        if (!$this->ion_auth->logged_in()){
            redirect('auth');
        }
        $this->load->model('Dashboard_model', 'dashboard'); // Model untuk total data di admin_box
        $this->load->model('Master_model', 'master');    // Model utama Anda
        $this->user = $this->ion_auth->user()->row(); // Set data user di constructor
    }

    // Fungsi untuk info box Admin
    public function admin_box()
    {
        $box = [
            [
                'box'   => 'bg-blue',     // Biru muda untuk Mapel
                'total' => $this->dashboard->total('mapel'), 
                'title' => 'Mapel',   
                'icon'  => 'book',
                'url'   => 'mapel', 
            ],
            [
                'box'   => 'bg-green',    // Hijau untuk Kelas
                'total' => $this->dashboard->total('kelas'),
                'title' => 'Kelas',
                'icon'  => 'university',
                'url'   => 'kelas'
            ],
            [
                'box'   => 'bg-yellow',   // Kuning untuk Guru
                'total' => $this->dashboard->total('guru'),
                'title' => 'Guru',
                'icon'  => 'user-secret',
                'url'   => 'guru'
            ],
            [
                'box'   => 'bg-red',      // Merah untuk Siswa
                'total' => $this->dashboard->total('siswa'), 
                'title' => 'Siswa',   
                'icon'  => 'users',
                'url'   => 'siswa'
            ],
        ];
        return json_decode(json_encode($box)); 
    }

    public function index()
    {
        $user = $this->user;
        $data = [
            'user'     => $user,
            'judul'    => 'Dashboard',
            'subjudul' => 'Data Aplikasi', // Subjudul diubah
            'tahun_ajaran_aktif_info' => $this->master->getTahunAjaranAktif() // Ambil TA Aktif sekali
        ];

        if ( $this->ion_auth->is_admin() ) {
            $data['info_box'] = $this->admin_box();
        } elseif ( $this->ion_auth->in_group('guru') ) {
            
            $guru_detail = null;
            // Coba dapatkan data guru berdasarkan NIP (jika username adalah NIP) atau email
            if (!empty($user->username)) { 
                $guru_detail = $this->db->get_where('guru', ['nip' => $user->username])->row();
            }
            if (!$guru_detail && !empty($user->email)) { 
                $guru_detail = $this->db->get_where('guru', ['email' => $user->email])->row();
            }
            
            $data['guru_info_dashboard'] = $guru_detail; // Info dasar guru
            $data['mapel_pj_info_dashboard'] = null;     // Info mapel jika guru adalah PJ
            $data['penugasan_guru_dashboard'] = [];  // Daftar mapel dan kelas yang diajar
            $data['info_box_guru'] = [];           // Untuk info box khusus guru

            if ($guru_detail && $data['tahun_ajaran_aktif_info']) {
                $id_guru_login = $guru_detail->id_guru;
                $id_ta_aktif = $data['tahun_ajaran_aktif_info']->id_tahun_ajaran;

                // 1. Cek & Ambil Info Mapel PJ Soal
                $data['mapel_pj_info_dashboard'] = $this->master->getMapelPJByGuruTahun($id_guru_login, $id_ta_aktif);

                // 2. Ambil semua mapel dan kelas yang diajar guru ini untuk tahun ajaran aktif
                $this->db->select('m.nama_mapel, k.nama_kelas, j.nama_jenjang');
                $this->db->from('guru_mapel_kelas_ajaran gmka');
                $this->db->join('mapel m', 'gmka.mapel_id = m.id_mapel');
                $this->db->join('kelas k', 'gmka.kelas_id = k.id_kelas');
                $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
                $this->db->where('gmka.guru_id', $id_guru_login);
                $this->db->where('gmka.id_tahun_ajaran', $id_ta_aktif);
                $this->db->order_by('m.nama_mapel ASC, j.nama_jenjang ASC, k.nama_kelas ASC');
                $penugasan_raw = $this->db->get()->result();
                
                $penugasan_terstruktur = [];
                if($penugasan_raw){
                    foreach($penugasan_raw as $pg){
                        // Kelompokkan berdasarkan mapel, lalu list kelasnya
                        $penugasan_terstruktur[$pg->nama_mapel][] = (isset($pg->nama_jenjang) ? htmlspecialchars($pg->nama_jenjang) . ' ' : '') . htmlspecialchars($pg->nama_kelas);
                    }
                }
                $data['penugasan_guru_dashboard'] = $penugasan_terstruktur;

                // Data untuk info box guru
                $box_guru = [];
                if($data['mapel_pj_info_dashboard']){
                    $box_guru[] = ['boxClass' => 'bg-yellow', 'icon' => 'fa-shield', 'value' => 1, 'title' => 'Mapel PJ Soal', 'url' => 'pjsoal'];
                }
                $total_mapel_diajar = count($data['penugasan_guru_dashboard']);
                $box_guru[] = ['boxClass' => 'bg-blue', 'icon' => 'fa-book', 'value' => $total_mapel_diajar, 'title' => 'Mapel Diajar (TA Aktif)', 'url' => 'penugasanguru'];
                
                $unique_kelas_diajar = [];
                if ($penugasan_raw) { foreach($penugasan_raw as $pg) $unique_kelas_diajar[$pg->nama_kelas] = true; }
                $box_guru[] = ['boxClass' => 'bg-green', 'icon' => 'fa-university', 'value' => count($unique_kelas_diajar), 'title' => 'Kelas Diajar (TA Aktif)', 'url' => 'penugasanguru'];
                
                // Contoh: Ambil jumlah soal yang dibuat guru ini (jika login ini adalah guru_id di tb_soal)
                // $jumlah_soal_dibuat = $this->db->where('guru_id', $id_guru_login)->count_all_results('tb_soal');
                // $box_guru[] = ['boxClass' => 'bg-maroon', 'icon' => 'fa-file-text-o', 'value' => $jumlah_soal_dibuat, 'title' => 'Soal Dibuat di Bank Soal', 'url' => 'soal'];

                $data['info_box_guru'] = json_decode(json_encode($box_guru));

            } elseif (!$data['tahun_ajaran_aktif_info']) {
                $data['dashboard_message_guru'] = "Informasi Tahun Ajaran aktif tidak ditemukan. Beberapa fitur mungkin tidak berfungsi optimal.";
            } elseif (!$guru_detail) {
                $data['dashboard_message_guru'] = "Data detail guru untuk akun Anda tidak ditemukan. Hubungi Administrator.";
            }

        } elseif ( $this->ion_auth->in_group('siswa') ) { // Untuk Siswa
            $siswa_db_data = null;
            if(!empty($user->username)) { // Asumsi username siswa adalah NISN
                $siswa_db_data = $this->db->get_where('siswa', ['nisn' => $user->username])->row();
            }
            
            $data['siswa_dashboard_info'] = null; 

            if($siswa_db_data && $data['tahun_ajaran_aktif_info']){
                $this->db->select('s.id_siswa, s.nama as nama_siswa, s.nisn, s.jenis_kelamin, k.nama_kelas, j.nama_jenjang, ta.nama_tahun_ajaran');
                $this->db->from('siswa_kelas_ajaran ska');
                $this->db->join('siswa s', 'ska.siswa_id = s.id_siswa');
                $this->db->join('kelas k', 'ska.kelas_id = k.id_kelas');
                $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
                $this->db->join('tahun_ajaran ta', 'ska.id_tahun_ajaran = ta.id_tahun_ajaran');
                $this->db->where('s.id_siswa', $siswa_db_data->id_siswa);
                $this->db->where('ta.id_tahun_ajaran', $data['tahun_ajaran_aktif_info']->id_tahun_ajaran);
                $data['siswa_dashboard_info'] = $this->db->get()->row();
            } elseif (!$data['tahun_ajaran_aktif_info']) {
                $data['dashboard_message_siswa'] = "Informasi Tahun Ajaran aktif tidak tersedia. Beberapa fitur mungkin tidak berfungsi optimal.";
            } elseif (!$siswa_db_data) {
                $data['dashboard_message_siswa'] = "Data siswa tidak ditemukan untuk akun Anda. Hubungi Administrator.";
            }
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('dashboard', $data); 
        $this->load->view('_templates/dashboard/_footer.php');
    }
}
