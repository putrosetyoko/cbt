<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class HasilUjian extends CI_Controller {

  public function __construct(){
    parent::__construct();
    if (!$this->ion_auth->logged_in()){
      redirect('auth');
    }
    
    $this->load->library(['datatables']);// Load Library Ignited-Datatables
    $this->load->model('Master_model', 'master');
    $this->load->model('Ujian_model', 'ujian');
    
    $this->user = $this->ion_auth->user()->row();
  }

  public function output_json($data, $encode = true)
  {
    if($encode) $data = json_encode($data);
    $this->output->set_content_type('application/json')->set_output($data);
  }

  public function data()
  {
    $nip_guru = null; // Changed $nip_dosen to $nip_guru
    
    if( $this->ion_auth->in_group('guru') ) { // Changed 'dosen' to 'guru'
      $nip_guru = $this->user->username; // Changed $nip_dosen to $nip_guru
    }

    $this->output_json($this->ujian->getHasilUjian($nip_guru), false); // Changed $nip_dosen to $nip_guru
  }

  public function NilaiSiswa($id) // Changed NilaiMhs to NilaiSiswa
  {
    $this->output_json($this->ujian->HslUjianById($id, true), false);
  }

  public function index()
  {
    $data = [
      'user' => $this->user,
      'judul' => 'Ujian',
      'subjudul'=> 'Hasil Ujian',
    ];
    $this->load->view('_templates/dashboard/_header.php', $data);
    $this->load->view('ujian/hasil');
    $this->load->view('_templates/dashboard/_footer.php');
  }
  
  public function detail($id)
  {
    $ujian = $this->ujian->getUjianById($id);
    $nilai = $this->ujian->bandingNilai($id);

    $data = [
      'user' => $this->user,
      'judul' => 'Ujian',
      'subjudul'=> 'Detail Hasil Ujian',
      'ujian' => $ujian,
      'nilai' => $nilai
    ];

    $this->load->view('_templates/dashboard/_header.php', $data);
    $this->load->view('ujian/detail_hasil');
    $this->load->view('_templates/dashboard/_footer.php');
  }

  public function cetak($id)
  {
    $this->load->library('Pdf');

    $siswa  = $this->ujian->getIdSiswa($this->user->username); // Changed getIdMahasiswa to getIdSiswa
    $hasil  = $this->ujian->HslUjian($id, $siswa->id_siswa)->row(); // Changed $mhs to $siswa and id_mahasiswa to id_siswa
    $ujian  = $this->ujian->getUjianById($id);
    
    $data = [
      'ujian' => $ujian,
      'hasil' => $hasil,
      'siswa' => $siswa // Changed mhs to siswa
    ];
    
    $this->load->view('ujian/cetak', $data);
  }

  public function cetak_detail($id)
    {
        $this->load->library('Pdf');

        $ujian = $this->ujian->getUjianById($id);
        $nilai = $this->ujian->bandingNilai($id);
        $hasil = $this->ujian->HslUjianById($id)->result();

        // Ambil nama guru dari objek ujian
        $nama_guru = $ujian->nama_guru; 
        
        // Ambil nama kelas dari hasil siswa pertama (asumsi ujian ini terkait dengan satu kelas utama untuk penamaan)
        $nama_kelas = 'Umum'; // Default value jika tidak ada siswa
        if (!empty($hasil)) {
            $nama_kelas = $hasil[0]->nama_kelas; // Ambil nama kelas dari siswa pertama
        }

        $data = [
            'ujian' => $ujian,
            'nilai' => $nilai,
            'hasil' => $hasil,
            'nama_kelas_pdf' => $nama_kelas, // Teruskan nama kelas ke view PDF
            'nama_guru_pdf'  => $nama_guru   // Teruskan nama guru ke view PDF
        ];

        $this->load->view('ujian/cetak_detail', $data);
    }
  
}