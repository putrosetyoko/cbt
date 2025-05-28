<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ujian extends CI_Controller {

    public $siswa, $user, $guru;

    public function __construct(){
        parent::__construct();
        if (!$this->ion_auth->logged_in()){
            redirect('auth');
        }
        $this->load->library(['datatables', 'form_validation', 'encryption']);
        $this->load->helper('my');
        $this->load->model('Master_model', 'master');
        $this->load->model('Soal_model', 'soal');
        $this->load->model('Ujian_model', 'ujian');
        $this->form_validation->set_error_delimiters('','');

        $this->user = $this->ion_auth->user()->row();

        if ($this->ion_auth->in_group('siswa')) {
            $this->siswa = $this->ujian->getIdSiswa($this->user->username);
        } elseif ($this->ion_auth->in_group('guru')) {
            $this->guru = $this->ujian->getIdGuru($this->user->username);
        }
    }

    public function akses_guru()
    {
        if ( !$this->ion_auth->in_group('guru') ){
            $this->session->set_flashdata('message', 'Akses tidak diizinkan. Halaman ini khusus untuk guru.');
            redirect('dashboard');
        }
    }

    public function akses_siswa()
    {
        if ( !$this->ion_auth->in_group('siswa') ){
            $this->session->set_flashdata('message', 'Akses tidak diizinkan. Halaman ini khusus untuk siswa.');
            redirect('dashboard');
        }
        if (empty($this->siswa)) {
            $this->session->set_flashdata('message', 'Data siswa tidak ditemukan.');
            redirect('dashboard');
        }
    }

    public function output_json($data, $encode = true)
    {
        if($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function json($id=null)
    {
        $this->akses_guru();
        $this->output_json($this->ujian->getDataUjian($id), false);
    }

    public function master()
    {
        $this->akses_guru();
        $user = $this->ion_auth->user()->row();
        $data = [
            'user' => $user,
            'judul' => 'Ujian',
            'subjudul'=> 'Data Ujian',
            'guru' => $this->ujian->getIdGuru($user->username),
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('ujian/data');
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function add()
    {
        $this->akses_guru();
        
        $user = $this->ion_auth->user()->row();

        $data = [
            'user'      => $user,
            'judul'     => 'Ujian',
            'subjudul'      => 'Tambah Ujian',
            'mapel'     => $this->soal->getMapelGuru($user->username),
            'guru'      => $this->ujian->getIdGuru($user->username),
        ];

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('ujian/add');
        $this->load->view('_templates/dashboard/_footer.php');
    }
    
    public function edit($id)
    {
        $this->akses_guru();
        
        $user = $this->ion_auth->user()->row();

        $data = [
            'user'      => $user,
            'judul'     => 'Ujian',
            'subjudul'      => 'Edit Ujian',
            'mapel'     => $this->soal->getMapelGuru($user->username),
            'guru'      => $this->ujian->getIdGuru($user->username),
            'ujian'     => $this->ujian->getUjianById($id),
        ];

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('ujian/edit');
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function convert_tgl($tgl)
    {
        return date('Y-m-d H:i:s', strtotime($tgl));
    }

    public function validasi()
    {
        $this->akses_guru();
        
        $user   = $this->ion_auth->user()->row();
        $guru   = $this->ujian->getIdGuru($user->username);
        $jml    = $this->ujian->getJumlahSoal($guru->id_guru)->jml_soal;
        $jml_a    = $jml + 1;

        $this->form_validation->set_rules('nama_ujian', 'Nama Ujian', 'required|alpha_numeric_spaces|max_length[50]');
        $this->form_validation->set_rules('jumlah_soal', 'Jumlah Soal', "required|integer|less_than[{$jml_a}]|greater_than[0]", ['less_than' => "Soal tidak cukup, anda hanya punya {$jml} soal"]);
        $this->form_validation->set_rules('tgl_mulai', 'Tanggal Mulai', 'required');
        $this->form_validation->set_rules('tgl_selesai', 'Tanggal Selesai', 'required');
        $this->form_validation->set_rules('waktu', 'Waktu', 'required|integer|max_length[4]|greater_than[0]');
        $this->form_validation->set_rules('jenis', 'Acak Soal', 'required|in_list[acak,urut]');
    }

    public function save()
    {
        $this->validasi();
        $this->load->helper('string');

        $method      = $this->input->post('method', true);
        $guru_id       = $this->input->post('guru_id', true);
        $mapel_id      = $this->input->post('mapel_id', true);
        $nama_ujian   = $this->input->post('nama_ujian', true);
        $jumlah_soal  = $this->input->post('jumlah_soal', true);
        $tgl_mulai    = $this->convert_tgl($this->input->post('tgl_mulai',   true));
        $tgl_selesai  = $this->convert_tgl($this->input->post('tgl_selesai', true));
        $waktu         = $this->input->post('waktu', true);
        $jenis         = $this->input->post('jenis', true);
        $token         = strtoupper(random_string('alpha', 5));

        if( $this->form_validation->run() === FALSE ){
            $data['status'] = false;
            $data['errors'] = [
                'nama_ujian'    => form_error('nama_ujian'),
                'jumlah_soal'   => form_error('jumlah_soal'),
                'tgl_mulai'     => form_error('tgl_mulai'),
                'tgl_selesai'   => form_error('tgl_selesai'),
                'waktu'         => form_error('waktu'),
                'jenis'         => form_error('jenis'),
            ];
        }else{
            $input = [
                'nama_ujian'    => $nama_ujian,
                'jumlah_soal'   => $jumlah_soal,
                'tgl_mulai'     => $tgl_mulai,
                'terlambat'     => $tgl_selesai,
                'waktu'         => $waktu,
                'jenis'         => $jenis,
                'aktif'         => 'Y'
            ];
            if($method === 'add'){
                $input['guru_id']   = $guru_id;
                $input['mapel_id'] = $mapel_id;
                $input['token']   = $token;
                $action = $this->master->create('m_ujian', $input);
            }else if($method === 'edit'){
                $id_ujian = $this->input->post('id_ujian', true);
                $action = $this->master->update('m_ujian', $input, 'id_ujian', $id_ujian);
            }
            $data['status'] = $action ? TRUE : FALSE;
        }
        $this->output_json($data);
    }

    public function delete()
    {
        $this->akses_guru();
        $chk = $this->input->post('checked', true);
        if(!$chk){
            $this->output_json(['status'=>false]);
        }else{
            if($this->master->delete('m_ujian', $chk, 'id_ujian')){
                $this->output_json(['status'=>true, 'total'=>count($chk)]);
            }
        }
    }

    public function refresh_token($id)
    {
        $this->load->helper('string');
        $data['token'] = strtoupper(random_string('alpha', 5));
        $refresh = $this->master->update('m_ujian', $data, 'id_ujian', $id);
        $data['status'] = $refresh ? TRUE : FALSE;
        $this->output_json($data);
    }

    /**
     * BAGIAN SISWA
     */

    public function list_json()
    {
        $this->akses_siswa();
        if (empty($this->siswa) || empty($this->siswa->id_siswa) || empty($this->siswa->kelas_id)) {
            $this->output_json(['data' => []], false);
            return;
        }
        $list = $this->ujian->getListUjian($this->siswa->id_siswa, $this->siswa->kelas_id);
        $this->output_json($list, false);
    }
    
    public function list()
    {
        $this->akses_siswa();

        $user = $this->ion_auth->user()->row();
        
        if (empty($this->siswa)) {
            $this->session->set_flashdata('message', 'Data siswa tidak ditemukan untuk menampilkan daftar ujian.');
            redirect('dashboard');
        }

        $data = [
            'user'      => $user,
            'judul'     => 'Ujian',
            'subjudul'      => 'List Ujian',
            'siswa'     => $this->siswa,
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('ujian/list');
        $this->load->view('_templates/dashboard/_footer.php');
    }
    
    public function token($id)
    {
        $this->akses_siswa();
        $user = $this->ion_auth->user()->row();

        if (empty($this->siswa)) {
            $this->session->set_flashdata('message', 'Data siswa tidak ditemukan.');
            redirect('dashboard');
        }

        $ujian = $this->ujian->getUjianById($id); // Ambil objek ujian
        if (!$ujian) {
            // Handle jika ujian tidak ditemukan (opsional, tapi disarankan)
            $this->session->set_flashdata('message', 'Ujian tidak ditemukan.');
            redirect('ujian/list');
        }

        // DEFINE VARIABEL WAKTU DI SINI SEBELUM DILEWATKAN KE VIEW
        $mulai = strtotime($ujian->tgl_mulai);
        $selesai = strtotime($ujian->terlambat);
        $now = time();
        $terlambat_mulai = strtotime($ujian->terlambat); // Ambil dari objek ujian, jika ada kolom 'terlambat' di DB

        $data = [
            'user'          => $user,
            'judul'         => 'Ujian',
            'subjudul'      => '',
            'siswa'         => $this->siswa,
            'ujian'         => $ujian, // Objek ujian sudah ada
            'encrypted_id'  => urlencode($this->encryption->encrypt($id)),
            // Tambahkan variabel waktu ke $data
            'mulai'         => $mulai,
            'selesai'       => $selesai,
            'now'           => $now,
            'terlambat_mulai' => $terlambat_mulai // Masukkan ke data
        ];
        $this->load->view('_templates/topnav/_header.php', $data);
        $this->load->view('ujian/token', $data); // Pastikan $data dilewatkan
        $this->load->view('_templates/topnav/_footer.php');
    }

    public function cektoken()
    {
        $id = $this->input->post('id_ujian', true);
        $token = $this->input->post('token', true);
        $cek = $this->ujian->getUjianById($id);
        
        $data['status'] = $token === $cek->token ? TRUE : FALSE;
        $this->output_json($data);
    }

    public function encrypt()
    {
        $id = $this->input->post('id', true);
        $key = urlencode($this->encryption->encrypt($id));
        $this->output_json(['key'=>$key]);
    }

    public function index()
    {
        $this->akses_siswa();
        $key = $this->input->get('key', true);
        $id  = $this->encryption->decrypt(rawurldecode($key));
        
        $ujian    = $this->ujian->getUjianById($id);
        $soal     = $this->ujian->getSoal($id);
        
        $siswa    = $this->siswa;
        $h_ujian  = $this->ujian->HslUjian($id, $siswa->id_siswa);
    
        $cek_sudah_ikut = $h_ujian->num_rows();

        if ($cek_sudah_ikut < 1) {
            $soal_urut_ok   = array();
            $i = 0;
            foreach ($soal as $s) {
                $soal_per = new stdClass();
                $soal_per->id_soal     = $s->id_soal;
                $soal_per->soal      = $s->soal;
                $soal_per->file      = $s->file;
                $soal_per->tipe_file   = $s->tipe_file;
                $soal_per->opsi_a      = $s->opsi_a;
                $soal_per->opsi_b      = $s->opsi_b;
                $soal_per->opsi_c      = $s->opsi_c;
                $soal_per->opsi_d      = $s->opsi_d;
                $soal_per->opsi_e      = $s->opsi_e;
                $soal_per->jawaban     = $s->jawaban;
                $soal_urut_ok[$i]      = $soal_per;
                $i++;
            }
            $soal_urut_ok   = $soal_urut_ok;
            $list_id_soal = "";
            $list_jw_soal   = "";
            if (!empty($soal)) {
                foreach ($soal as $d) {
                    $list_id_soal .= $d->id_soal.",";
                    $list_jw_soal .= $d->id_soal."::N::N,"; // Inisialisasi dengan jawaban kosong dan tidak ragu
                }
            }
            $list_id_soal   = substr($list_id_soal, 0, -1);
            $list_jw_soal   = substr($list_jw_soal, 0, -1);
            $waktu_selesai  = date('Y-m-d H:i:s', strtotime("+{$ujian->waktu} minute"));
            $time_mulai   = date('Y-m-d H:i:s');

            $input = [
                'ujian_id'      => $id,
                'siswa_id'      => $siswa->id_siswa,
                'list_soal'     => $list_id_soal,
                'list_jawaban'  => $list_jw_soal,
                'jml_benar'     => 0,
                'nilai'         => 0,
                'nilai_bobot'   => 0,
                'tgl_mulai'     => $time_mulai,
                'tgl_selesai'   => $waktu_selesai,
                'status'        => 'unfinished'
            ];
            $this->master->create('h_ujian', $input);

            redirect('ujian/?key='.urlencode($key), 'location', 301);
        }
        
        $q_soal = $h_ujian->row();
        
        $urut_soal      = explode(",", $q_soal->list_jawaban);
        $soal_urut_ok = array();
        for ($i = 0; $i < sizeof($urut_soal); $i++) {
            $pc_urut_soal = explode(":",$urut_soal[$i]);
            
            // Perbaikan utama di sini: Pastikan index 0 ada (id_soal)
            if (isset($pc_urut_soal[0]) && !empty($pc_urut_soal[0])) {
                $jawaban_from_db = isset($pc_urut_soal[1]) ? $pc_urut_soal[1] : '';
                $pc_urut_soal1  = empty($jawaban_from_db) ? "''" : "'{$jawaban_from_db}'";

                $ambil_soal   = $this->ujian->ambilSoal($pc_urut_soal1, $pc_urut_soal[0]);
                
                if ($ambil_soal) { // Hanya tambahkan jika ambilSoal mengembalikan objek
                    $soal_urut_ok[] = $ambil_soal;
                } else {
                    // Opsional: Log error jika soal tidak ditemukan di tb_soal
                    log_message('error', 'Soal dengan ID ' . $pc_urut_soal[0] . ' tidak ditemukan di tb_soal untuk ujian ID: ' . $id);
                }
            } else {
                // Opsional: Log error jika format list_jawaban tidak valid (id_soal hilang)
                log_message('error', 'Format list_jawaban tidak valid: ' . $urut_soal[$i] . ' untuk ujian ID: ' . $id);
            }
        }

        $detail_tes = $q_soal;
        // $soal_urut_ok = $soal_urut_ok; // Baris ini redundan, bisa dihapus

        $pc_list_jawaban = explode(",", $detail_tes->list_jawaban);
        $arr_jawab = array();
        foreach ($pc_list_jawaban as $v) {
            $pc_v   = explode(":", $v);
            $idx    = $pc_v[0];
            $val    = isset($pc_v[1]) ? $pc_v[1] : '';
            $rg     = isset($pc_v[2]) ? $pc_v[2] : 'N';

            $arr_jawab[$idx] = array("j"=>$val,"r"=>$rg);
        }

        $arr_opsi = array("a","b","c","d","e");
        $html = '';
        $no = 1;
        if (!empty($soal_urut_ok)) {
            foreach ($soal_urut_ok as $s) {
                $path = 'uploads/bank_soal/';
                // Pastikan $s adalah objek dan memiliki id_soal
                $vrg = (is_object($s) && isset($s->id_soal) && isset($arr_jawab[$s->id_soal]["r"])) ? $arr_jawab[$s->id_soal]["r"] : "N";
                $html .= '<input type="hidden" name="id_soal_'.$no.'" value="'.(is_object($s) && isset($s->id_soal) ? $s->id_soal : '').'">'; // Tambahkan pengecekan
                $html .= '<input type="hidden" name="rg_'.$no.'" id="rg_'.$no.'" value="'.$vrg.'">';
                $html .= '<div class="step" id="widget_'.$no.'">';

                $html .= '<div class="text-center"><div class="w-25">'.(is_object($s) && isset($s->file) ? tampil_media($path.$s->file) : '').'</div></div>'.(is_object($s) && isset($s->soal) ? $s->soal : '').'<div class="funkyradio">';
                for ($j = 0; $j < $this->config->item('jml_opsi'); $j++) {
                    $opsi     = "opsi_".$arr_opsi[$j];
                    $file     = "file_".$arr_opsi[$j];
                    
                    $checked    = (is_object($s) && isset($s->id_soal) && isset($arr_jawab[$s->id_soal]["j"]) && $arr_jawab[$s->id_soal]["j"] == strtoupper($arr_opsi[$j])) ? "checked" : "";
                    
                    $pilihan_opsi   = (is_object($s) && isset($s->$opsi) && !empty($s->$opsi)) ? $s->$opsi : "";
                    $tampil_media_opsi = (is_object($s) && isset($s->$file) && !empty($s->$file) && file_exists(FCPATH.$path.$s->$file)) ? tampil_media($path.$s->$file) : "";
                    
                    $html .= '<div class="funkyradio-success" onclick="return simpan_sementara();">
                        <input type="radio" id="opsi_'.strtolower($arr_opsi[$j]).'_'.(is_object($s) && isset($s->id_soal) ? $s->id_soal : '').'" name="opsi_'.$no.'" value="'.strtoupper($arr_opsi[$j]).'" '.$checked.'> <label for="opsi_'.strtolower($arr_opsi[$j]).'_'.(is_object($s) && isset($s->id_soal) ? $s->id_soal : '').'"><div class="huruf_opsi">'.$arr_opsi[$j].'</div> <p>'.$pilihan_opsi.'</p><div class="w-25">'.$tampil_media_opsi.'</div></label></div>';
                }
                $html .= '</div></div>';
                $no++;
            }
        }

        $id_tes = $this->encryption->encrypt($detail_tes->id);

        $data = [
            'user'    => $this->user,
            'siswa'   => $this->siswa,
            'judul'   => 'Ujian',
            'subjudul'      => 'Lembar Ujian',
            'soal'    => $detail_tes,
            'no'    => $no,
            'html'    => $html,
            'id_tes'  => $id_tes
        ];
        $this->load->view('_templates/topnav/_header.php', $data);
        $this->load->view('ujian/sheet');
        $this->load->view('_templates/topnav/_footer.php');
    }

    public function simpan_satu()
    {
        $id_tes = $this->input->post('id', true);
        $id_tes = $this->encryption->decrypt($id_tes);
        
        $input  = $this->input->post(null, true);
        $list_jawaban   = "";
        for ($i = 1; $i <= $input['jml_soal']; $i++) {
            $_tjawab   = "opsi_".$i;
            $_tidsoal    = "id_soal_".$i;
            $_ragu     = "rg_".$i;

            $jawaban_    = empty($input[$_tjawab]) ? "" : $input[$_tjawab];
            $ragu_ragu_ = empty($input[$_ragu]) ? "N" : $input[$_ragu];

            $list_jawaban .= $input[$_tidsoal].":".$jawaban_.":".$ragu_ragu_.",";
        }
        $list_jawaban = substr($list_jawaban, 0, -1);
        $d_simpan = [
            'list_jawaban' => $list_jawaban
        ];
        
        $this->master->update('h_ujian', $d_simpan, 'id', $id_tes);
        $this->output_json(['status'=>true]);
    }

    public function simpan_akhir()
    {
        $id_tes = $this->input->post('id', true);
        $id_tes = $this->encryption->decrypt($id_tes);

        $list_jawaban = $this->ujian->getJawaban($id_tes);

        $pc_jawaban = explode(",", $list_jawaban);

        $jumlah_benar   = 0;
        $jumlah_salah   = 0;
        $total_bobot_yang_diperoleh = 0; // Akumulasi bobot dari soal yang dijawab BENAR
        $total_bobot_maksimal_ujian = 0; // Akumulasi bobot dari SEMUA soal dalam ujian
        $jumlah_soal_dalam_ujian = sizeof($pc_jawaban); // Ini adalah jumlah soal yang terdaftar dalam list_jawaban

        foreach ($pc_jawaban as $jwb) {
            $pc_dt    = explode(":", $jwb);
            $id_soal  = $pc_dt[0];
            $jawaban  = isset($pc_dt[1]) ? $pc_dt[1] : '';
            $ragu     = isset($pc_dt[2]) ? $pc_dt[2] : 'N';

            $cek_jwb  = $this->soal->getSoalById($id_soal);
            if ($cek_jwb) {
                // Akumulasi total bobot maksimal dari semua soal
                $total_bobot_maksimal_ujian += $cek_jwb->bobot;

                if (!empty($jawaban) && $jawaban == $cek_jwb->jawaban) {
                    $jumlah_benar++;
                    // Akumulasi total bobot yang diperoleh dari jawaban benar
                    $total_bobot_yang_diperoleh += $cek_jwb->bobot;
                } else {
                    $jumlah_salah++;
                }
            } else {
                log_message('error', 'Soal dengan ID ' . $id_soal . ' tidak ditemukan di tb_soal saat menghitung nilai ujian ID: ' . $id_tes);
            }
        }

        // Perhitungan nilai berdasarkan jumlah soal benar (jika ingin ditampilkan)
        $nilai_berdasarkan_jumlah = ($jumlah_soal_dalam_ujian > 0) ? ($jumlah_benar / $jumlah_soal_dalam_ujian) * 100 : 0;

        // Perhitungan nilai berdasarkan bobot soal
        $nilai_berdasarkan_bobot = 0;
        if ($total_bobot_maksimal_ujian > 0) {
            $nilai_berdasarkan_bobot = ($total_bobot_yang_diperoleh / $total_bobot_maksimal_ujian) * 100;
        }

        $d_update = [
            'jml_benar'   => $jumlah_benar, // Ini akan menyimpan jumlah_benar yang terhitung
            'nilai'       => number_format(floor($nilai_berdasarkan_bobot), 0),
            'nilai_bobot' => number_format(floor($nilai_berdasarkan_bobot), 0),
            'status'      => 'completed'
        ];

        $this->master->update('h_ujian', $d_update, 'id', $id_tes);
        $this->output_json(['status'=>TRUE, 'data'=>$d_update, 'id'=>$id_tes]);
    }
}