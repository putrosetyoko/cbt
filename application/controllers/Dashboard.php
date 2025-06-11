<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    private $user; // Properti untuk menyimpan data user yang login
    private $is_admin; // Tambahkan ini
    private $is_guru;  // Tambahkan ini
    private $is_siswa; // Tambahkan ini
    private $guru_data; // Tambahkan ini
    private $pj_mapel_data; // Tambahkan ini
    private $siswa_data; // Tambahkan ini

    public function __construct(){
        parent::__construct();
        if (!$this->ion_auth->logged_in()){
            redirect('auth');
        }
        $this->load->model('Dashboard_model', 'dashboard');
        $this->load->model('Master_model', 'master');
        $this->load->model('Ujian_model', 'ujian_m'); // Perlu diload untuk count_ujian
        $this->load->model('Soal_model', 'soal_m');   // Perlu diload untuk count_soal

        $this->user = $this->ion_auth->user()->row();
        // Inisialisasi properti hak akses dan data user
        $this->is_admin = $this->ion_auth->is_admin();
        $this->is_guru = $this->ion_auth->in_group('guru');
        $this->is_siswa = $this->ion_auth->in_group('siswa');

        $tahun_ajaran_aktif = $this->master->getTahunAjaranAktif();

        if ($this->is_guru) {
            if (!empty($this->user->username)) { // Asumsi username adalah NIP
                $this->guru_data = $this->db->get_where('guru', ['nip' => $this->user->username])->row();
            }
            if (!$this->guru_data && !empty($this->user->email)) { // Fallback ke email
                $this->guru_data = $this->db->get_where('guru', ['email' => $this->user->email])->row();
            }
            if ($this->guru_data && $tahun_ajaran_aktif) {
                $this->pj_mapel_data = $this->master->getMapelPJByGuruTahun($this->guru_data->id_guru, $tahun_ajaran_aktif->id_tahun_ajaran);
            } else {
                $this->pj_mapel_data = null;
            }
        } elseif ($this->is_siswa) {
            if (!empty($this->user->username) && $tahun_ajaran_aktif) {
                $this->siswa_data = $this->master->getSiswaDetailByNisnTahunAjaran($this->user->username, $tahun_ajaran_aktif->id_tahun_ajaran); // Pastikan ada method ini di Master_model
            }
        }
    }

    // Fungsi untuk info box Admin
    public function admin_box()
    {
        $box = [
            [
                'box'   => 'bg-blue',
                'total' => $this->dashboard->total('mapel'),
                'title' => 'Mapel',
                'icon'  => 'book',
                'url'   => 'mapel',
            ],
            [
                'box'   => 'bg-green',
                'total' => $this->dashboard->total('kelas'),
                'title' => 'Kelas',
                'icon'  => 'university',
                'url'   => 'kelas'
            ],
            [
                'box'   => 'bg-yellow',
                'total' => $this->dashboard->total('guru'),
                'title' => 'Guru',
                'icon'  => 'user-secret',
                'url'   => 'guru'
            ],
            [
                'box'   => 'bg-red',
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
            'user'          => $user,
            'judul'         => 'Dashboard',
            'subjudul'      => 'Selamat Datang', // Subjudul diubah
            'tahun_ajaran_aktif_info' => $this->master->getTahunAjaranAktif() // Ambil TA Aktif sekali
        ];

        // Mendapatkan tahun ajaran aktif untuk filter dan tampilan
        $tahun_ajaran_aktif = $data['tahun_ajaran_aktif_info'];
        $id_tahun_ajaran_aktif = $tahun_ajaran_aktif ? $tahun_ajaran_aktif->id_tahun_ajaran : null;

        // Passed to view for conditional rendering
        $data['is_admin'] = $this->is_admin;
        $data['is_guru'] = $this->is_guru;
        $data['is_siswa'] = $this->is_siswa;
        $data['guru_data'] = $this->guru_data;
        $data['pj_mapel_data'] = $this->pj_mapel_data;
        $data['siswa_data'] = $this->siswa_data; // Ensure siswa_data is passed for siswa role

        if ($this->is_admin) {
            $data['info_box'] = $this->admin_box();
        } elseif ($this->is_guru) {
            
            $data['guru_info_dashboard'] = $this->guru_data; // Info dasar guru
            $data['mapel_pj_info_dashboard'] = $this->pj_mapel_data; // Info mapel jika guru adalah PJ
            $data['penugasan_guru_dashboard'] = []; // Daftar mapel dan kelas yang diajar
            $data['info_box_guru'] = []; // Untuk info box khusus guru

            if ($this->guru_data && $id_tahun_ajaran_aktif) {
                $id_guru_login = $this->guru_data->id_guru;
                
                // 1. Cek & Ambil Info Mapel PJ Soal
                $data['mapel_pj_info_dashboard'] = $this->master->getMapelPJByGuruTahun($id_guru_login, $id_tahun_ajaran_aktif);

                // 2. Ambil semua mapel dan kelas yang diajar guru ini untuk tahun ajaran aktif
                $this->db->select('m.nama_mapel, k.nama_kelas, j.nama_jenjang');
                $this->db->from('guru_mapel_kelas_ajaran gmka');
                $this->db->join('mapel m', 'gmka.mapel_id = m.id_mapel');
                $this->db->join('kelas k', 'gmka.kelas_id = k.id_kelas');
                $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
                $this->db->where('gmka.guru_id', $id_guru_login);
                $this->db->where('gmka.id_tahun_ajaran', $id_tahun_ajaran_aktif);
                $this->db->order_by('m.nama_mapel ASC, j.nama_jenjang ASC, k.nama_kelas ASC');
                $penugasan_raw = $this->db->get()->result();
                
                $penugasan_terstruktur = [];
                if($penugasan_raw){
                    foreach($penugasan_raw as $pg){
                        $penugasan_terstruktur[$pg->nama_mapel][] = (isset($pg->nama_jenjang) ? htmlspecialchars($pg->nama_jenjang) . ' ' : '') . htmlspecialchars($pg->nama_kelas);
                    }
                }
                $data['penugasan_guru_dashboard'] = $penugasan_terstruktur;

                // Data untuk info box guru
                $box_guru = [];
                // if($data['mapel_pj_info_dashboard']){
                //     $box_guru[] = (object)['boxClass' => 'bg-yellow', 'icon' => 'shield', 'value' => 1, 'title' => 'Mapel PJ Soal', 'url' => 'pjsoal'];
                // }
                
                $total_mapel_diajar = count($data['penugasan_guru_dashboard']);
                $box_guru[] = (object)['boxClass' => 'bg-blue', 'icon' => 'book', 'value' => $total_mapel_diajar, 'title' => 'Mapel Diajar (TA Aktif)', 'url' => 'penugasanguru'];
                
                $unique_kelas_diajar = [];
                if ($penugasan_raw) { 
                    foreach($penugasan_raw as $pg) $unique_kelas_diajar[$pg->nama_kelas] = true; 
                }
                $box_guru[] = (object)['boxClass' => 'bg-green', 'icon' => 'university', 'value' => count($unique_kelas_diajar), 'title' => 'Kelas Diajar (TA Aktif)', 'url' => 'penugasanguru'];
                
                // // Info Box: Jumlah Soal Dibuat
                // $total_soal_dibuat = $this->soal_m->count_soal_by_guru_in_tahun_ajaran(
                //     $id_guru_login,
                //     $id_tahun_ajaran_aktif
                // );
                // $box_guru[] = (object) [
                //     'title'     => 'Soal Dibuat',
                //     'value'     => $total_soal_dibuat,
                //     'icon'      => 'file-alt',
                //     'boxClass'  => 'bg-maroon', // Warna baru
                //     'url'       => 'soal',
                // ];

                // // Info Box BARU: Jumlah Ujian Dibuat
                // $jumlah_ujian_dibuat = $this->ujian_m->count_ujian_by_guru_in_tahun_ajaran(
                //     $id_guru_login,
                //     $id_tahun_ajaran_aktif
                // );
                // $box_guru[] = (object) [
                //     'title'     => 'Ujian Dibuat',
                //     'value'     => $jumlah_ujian_dibuat,
                //     'icon'      => 'chalkboard-teacher',
                //     'boxClass'  => 'bg-purple',
                //     'url'       => 'ujian',
                // ];

                // Info Box BARU: Jumlah Siswa yang Diajar
                $total_siswa_diajar = $this->master->count_siswa_by_guru_in_tahun_ajaran(
                    $id_guru_login,
                    $id_tahun_ajaran_aktif
                );
                $box_guru[] = (object) [
                    'title'     => 'Siswa Diajar (Ta Aktif)',
                    'value'     => $total_siswa_diajar,
                    'icon'      => 'users', // Ikon untuk siswa
                    'boxClass'  => 'bg-orange', // Warna baru
                    'url'       => 'penugasanguru', // Link yang relevan
                ];

                $data['info_box_guru'] = $box_guru; // Assign the array to info_box_guru

            } elseif (!$data['tahun_ajaran_aktif_info']) {
                $data['dashboard_message_guru'] = "Informasi Tahun Ajaran aktif tidak ditemukan. Beberapa fitur mungkin tidak berfungsi optimal.";
            } elseif (!$guru_detail) {
                $data['dashboard_message_guru'] = "Data detail guru untuk akun Anda tidak ditemukan. Hubungi Administrator.";
            }

        } elseif ($this->is_siswa) {
            $data['siswa_dashboard_info'] = null;

            if($this->siswa_data && $data['tahun_ajaran_aktif_info']){
                // Corrected: Use $this->siswa_data already populated in constructor
                // No need to query again here if $this->siswa_data already has all necessary details.
                // If it only has basic info, you might need a more comprehensive method in Master_model
                $data['siswa_dashboard_info'] = $this->siswa_data;
            } elseif (!$data['tahun_ajaran_aktif_info']) {
                $data['dashboard_message_siswa'] = "Informasi Tahun Ajaran aktif tidak tersedia. Beberapa fitur mungkin tidak berfungsi optimal.";
            } elseif (!$this->siswa_data) {
                $data['dashboard_message_siswa'] = "Data siswa tidak ditemukan untuk akun Anda. Hubungi Administrator.";
            }
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('dashboard', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }
}
