<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct(){
        parent::__construct();
        if (!$this->ion_auth->logged_in()){
            redirect('auth');
        }
        $this->load->model('Dashboard_model', 'dashboard');
        $this->user = $this->ion_auth->user()->row();
    }

    public function admin_box()
    {
        $box = [
            [
                'box'   => 'light-blue',
                'total' => $this->dashboard->total('mapel'), // Ganti 'jurusan' menjadi 'mapel'
                'title' => 'Mapel',   // Ganti 'Jurusan' menjadi 'Mapel'
                'icon'  => 'graduation-cap' // Icon tetap
            ],
            [
                'box'   => 'olive',
                'total' => $this->dashboard->total('kelas'),
                'title' => 'Kelas',
                'icon'  => 'building-o'
            ],
            [
                'box'   => 'yellow-active',
                'total' => $this->dashboard->total('guru'),
                'title' => 'Guru',
                'icon'  => 'user-secret'
            ],
            [
                'box'   => 'red',
                'total' => $this->dashboard->total('siswa'), // Ganti 'mahasiswa' menjadi 'siswa'
                'title' => 'Siswa',   // Ganti 'Mahasiswa' menjadi 'Siswa'
                'icon'  => 'user'
            ],
        ];
        $info_box = json_decode(json_encode($box), FALSE);
        return $info_box;
    }

    public function index()
    {
        $user = $this->user;
        $data = [
            'user'    => $user,
            'judul'   => 'Dashboard',
            'subjudul'  => 'Data Aplikasi',
        ];

        if ( $this->ion_auth->is_admin() ) {
            $data['info_box'] = $this->admin_box();
        } elseif ( $this->ion_auth->in_group('guru') ) {
            $mapel = ['mapel' => 'guru.mapel_id=mapel.id_mapel']; // Perbaiki matkul menjadi mapel
            $data['guru'] = $this->dashboard->get_where('guru', 'nip', $user->username, $mapel)->row();

            $kelas = ['kelas' => 'kelas_guru.kelas_id=kelas.id_kelas'];
            $data['kelas'] = $this->dashboard->get_where('kelas_guru', 'guru_id' , $data['guru']->id_guru, $kelas, ['nama_kelas'=>'ASC'])->result();
        }else{
            $join = [
                'kelas b'   => 'a.kelas_id = b.id_kelas',
                // 'jurusan c' => 'b.jurusan_id = c.id_jurusan' // Jurusan dihapus dari siswa
            ];
            $data['siswa'] = $this->dashboard->get_where('siswa a', 'nisn', $user->username, $join)->row(); // Ganti 'mahasiswa' ke 'siswa' dan 'nim' ke 'nisn'
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('dashboard');
        $this->load->view('_templates/dashboard/_footer.php');
    }
}