<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Soal extends CI_Controller {

    public function __construct(){
        parent::__construct();
        if (!$this->ion_auth->logged_in()){
            redirect('auth');
        }else if ( !$this->ion_auth->is_admin() && !$this->ion_auth->in_group('guru') ){ // Changed 'dosen' to 'guru'
            show_error('Hanya Administrator dan guru yang diberi hak untuk mengakses halaman ini, <a href="'.base_url('dashboard').'">Kembali ke menu awal</a>', 403, 'Akses Terlarang'); // Changed 'dosen' to 'guru'
        }
        $this->load->library(['datatables', 'form_validation']);// Load Library Ignited-Datatables
        $this->load->helper('my');// Load Library Ignited-Datatables
        $this->load->model('Master_model', 'master');
        $this->load->model('Soal_model', 'soal');
        $this->form_validation->set_error_delimiters('','');
    }

    public function output_json($data, $encode = true)
    {
        if($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function index()
    {
        $user = $this->ion_auth->user()->row();
        $data = [
            'user' => $user,
            'judul' => 'Soal',
            'subjudul'=> 'Bank Soal'
        ];
        
        if($this->ion_auth->is_admin()){
            //Jika admin maka tampilkan semua mapel
            $data['mapel'] = $this->master->getAllMapel(); // Changed 'matkul' to 'mapel' and 'getAllMatkul' to 'getAllMapel'
        }else{
            //Jika bukan maka mapel dipilih otomatis sesuai mapel guru
            $data['mapel'] = $this->soal->getMapelGuru($user->username); // Changed 'matkul' to 'mapel' and 'getMatkulDosen' to 'getMapelGuru'
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/data');
        $this->load->view('_templates/dashboard/_footer.php');
    }
    
    public function detail($id)
    {
        $user = $this->ion_auth->user()->row();
        $data = [
            'user'      => $user,
            'judul'     => 'Soal',
            'subjudul'  => 'Edit Soal',
            'soal'      => $this->soal->getSoalById($id),
        ];

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/detail');
        $this->load->view('_templates/dashboard/_footer.php');
    }
    
    public function add()
    {
        $user = $this->ion_auth->user()->row();
        $data = [
            'user'      => $user,
            'judul'     => 'Soal',
            'subjudul'  => 'Buat Soal'
        ];

        if($this->ion_auth->is_admin()){
            //Jika admin maka tampilkan semua guru
            $data['guru'] = $this->soal->getAllGuru(); // Changed 'dosen' to 'guru' and 'getAllDosen' to 'getAllGuru'
        }else{
            //Jika bukan maka mapel dipilih otomatis sesuai mapel guru
            $data['guru'] = $this->soal->getMapelGuru($user->username); // Changed 'dosen' to 'guru' and 'getMatkulDosen' to 'getMapelGuru'
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/add');
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function edit($id)
    {
        $user = $this->ion_auth->user()->row();
        $data = [
            'user'      => $user,
            'judul'     => 'Soal',
            'subjudul'  => 'Edit Soal',
            'soal'      => $this->soal->getSoalById($id),
        ];
        
        if($this->ion_auth->is_admin()){
            //Jika admin maka tampilkan semua guru
            $data['guru'] = $this->soal->getAllGuru(); // Changed 'dosen' to 'guru' and 'getAllDosen' to 'getAllGuru'
        }else{
            //Jika bukan maka mapel dipilih otomatis sesuai mapel guru
            $data['guru'] = $this->soal->getMapelGuru($user->username); // Changed 'dosen' to 'guru' and 'getMatkulDosen' to 'getMapelGuru'
        }

        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('soal/edit');
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function data($id=null, $guru=null) // Changed $dosen to $guru
    {
        $this->output_json($this->soal->getDataSoal($id, $guru), false); // Changed $dosen to $guru
    }

    public function validasi()
    {
        if($this->ion_auth->is_admin()){
            $this->form_validation->set_rules('guru_id', 'Guru', 'required'); // Changed 'dosen_id' to 'guru_id' and 'Dosen' to 'Guru'
        }
        // $this->form_validation->set_rules('soal', 'Soal', 'required');
        // $this->form_validation->set_rules('jawaban_a', 'Jawaban A', 'required');
        // $this->form_validation->set_rules('jawaban_b', 'Jawaban B', 'required');
        // $this->form_validation->set_rules('jawaban_c', 'Jawaban C', 'required');
        // $this->form_validation->set_rules('jawaban_d', 'Jawaban D', 'required');
        // $this->form_validation->set_rules('jawaban_e', 'Jawaban E', 'required');
        $this->form_validation->set_rules('jawaban', 'Kunci Jawaban', 'required');
        $this->form_validation->set_rules('bobot', 'Bobot Soal', 'required|max_length[2]');
    }

    // Fungsi helper untuk mendapatkan array konfigurasi upload
    public function file_config_array()
    {
        $config['upload_path']      = FCPATH.'uploads/bank_soal/';
        $config['allowed_types']    = 'jpeg|jpg|png|gif|mpeg|mpg|mpeg3|mp3|wav|wave|mp4';
        $config['encrypt_name']     = TRUE;
        return $config;
    }

    public function save()
    {
        $method = $this->input->post('method', true);
        $this->validasi();
        // $this->file_config(); // Tidak perlu memanggil ini di sini, akan di-initialize ulang di bawah

        
        if($this->form_validation->run() === FALSE){
            $method==='add'? $this->add() : $this->edit($this->input->post('id_soal', true));
        }else{
            $data = [
                'soal'      => $this->input->post('soal', true),
                'jawaban'   => $this->input->post('jawaban', true),
                'bobot'     => $this->input->post('bobot', true),
            ];
            
            $abjad = ['a', 'b', 'c', 'd', 'e'];
            
            // Inputan Opsi
            foreach ($abjad as $abj) {
                $data['opsi_'.$abj]     = $this->input->post('jawaban_'.$abj, true);
            }

            $img_src = FCPATH.'uploads/bank_soal/'; // Path folder media
            $getsoal = null; // Initialize getsoal to null
            if ($method === 'edit') {
                $id_soal = $this->input->post('id_soal', true);
                $getsoal = $this->soal->getSoalById($id_soal); // Ambil data soal lama di sini hanya jika method adalah edit
            }
            
            // --- Handle file_soal (main question file) ---
            // Hanya coba upload jika ada file yang dipilih
            if (isset($_FILES['file_soal']) && !empty($_FILES['file_soal']['name'])) {
                $this->load->library('upload', $this->file_config_array()); // Inisialisasi library upload
                if (!$this->upload->do_upload('file_soal')){
                    $error = $this->upload->display_errors();
                    show_error($error, 500, 'File Soal Error');
                    exit();
                }else{
                    // Jika mode edit dan ada file lama, hapus file lama
                    if($method === 'edit' && $getsoal && !empty($getsoal->file) && file_exists($img_src.$getsoal->file)){
                        unlink($img_src.$getsoal->file);
                    }
                    $data['file'] = $this->upload->data('file_name');
                    $data['tipe_file'] = $this->upload->data('file_type');
                }
            } else if ($method === 'edit' && $this->input->post('hapus_file_soal') == '1') { // Jika checkbox hapus file dicentang
                if($getsoal && !empty($getsoal->file) && file_exists($img_src.$getsoal->file)){
                    unlink($img_src.$getsoal->file);
                    $data['file'] = NULL;
                    $data['tipe_file'] = NULL;
                }
            } else if ($method === 'edit' && $getsoal) { // Jika edit, tidak ada upload baru, dan tidak dihapus, pertahankan file lama
                $data['file'] = $getsoal->file;
                $data['tipe_file'] = $getsoal->tipe_file;
            }


            // --- Handle option files (file_a, file_b, etc.) ---
            foreach ($abjad as $abj) {
                $file_field_name = 'file_'.$abj; // e.g., 'file_a', 'file_b'
                
                // Hanya coba upload jika ada file yang dipilih untuk opsi ini
                if (isset($_FILES[$file_field_name]) && !empty($_FILES[$file_field_name]['name'])) {
                    $this->load->library('upload', $this->file_config_array(), 'option_upload'); // Inisialisasi library upload dengan nama berbeda
                    // Penting: CodeIgniter's upload library harus di-initialize ulang untuk setiap file yang berbeda,
                    // atau gunakan nama instance berbeda (misal 'option_upload')
                    if (!$this->option_upload->do_upload($file_field_name)){ // Gunakan instance yang berbeda
                        $error = $this->option_upload->display_errors();
                        show_error($error, 500, 'File Opsi '.strtoupper($abj).' Error');
                        exit();
                    }else{
                        // Jika mode edit dan ada file lama, hapus file lama
                        if($method === 'edit' && $getsoal && !empty($getsoal->$file_field_name) && file_exists($img_src.$getsoal->$file_field_name)){
                            unlink($img_src.$getsoal->$file_field_name);
                        }
                        $data[$file_field_name] = $this->option_upload->data('file_name'); // Gunakan instance yang berbeda
                    }
                } else if ($method === 'edit' && $this->input->post('hapus_'.$file_field_name) == '1') { // Jika checkbox hapus file opsi dicentang
                    if($getsoal && !empty($getsoal->$file_field_name) && file_exists($img_src.$getsoal->$file_field_name)){
                        unlink($img_src.$getsoal->$file_field_name);
                        $data[$file_field_name] = NULL;
                    }
                } else if ($method === 'edit' && $getsoal) { // Jika edit, tidak ada upload baru, dan tidak dihapus, pertahankan file lama
                    $data[$file_field_name] = $getsoal->$file_field_name;
                }
            }
                
            if($this->ion_auth->is_admin()){
                $pecah = $this->input->post('guru_id', true); // Changed 'dosen_id' to 'guru_id'
                $pecah = explode(':', $pecah);
                $data['guru_id'] = $pecah[0]; // Changed 'dosen_id' to 'guru_id'
                $data['mapel_id'] = end($pecah); // Changed 'matkul_id' to 'mapel_id'
            }else{
                $data['guru_id'] = $this->input->post('guru_id', true); // Changed 'dosen_id' to 'guru_id'
                $data['mapel_id'] = $this->input->post('mapel_id', true); // Changed 'matkul_id' to 'mapel_id'
            }

            if($method==='add'){
                //push array
                $data['created_on'] = time();
                $data['updated_on'] = time();
                //insert data
                $this->master->create('tb_soal', $data);
            }else if($method==='edit'){
                //push array
                $data['updated_on'] = time();
                //update data
                $id_soal = $this->input->post('id_soal', true);
                $this->master->update('tb_soal', $data, 'id_soal', $id_soal);
            }else{
                show_error('Method tidak diketahui', 404);
            }
            redirect('soal');
        }
    }

    public function delete()
    {
        $chk = $this->input->post('checked', true);
        
        // Delete File
        foreach($chk as $id){
            $abjad = ['a', 'b', 'c', 'd', 'e'];
            $path = FCPATH.'uploads/bank_soal/';
            $soal = $this->soal->getSoalById($id);
            
            // Hapus File Soal
            if(!empty($soal->file) && file_exists($path.$soal->file)){
                unlink($path.$soal->file);
            }
            // Hapus File Opsi
            foreach ($abjad as $abj) {
                $file_opsi = 'file_'.$abj;
                if(!empty($soal->$file_opsi) && file_exists($path.$soal->$file_opsi)){
                    unlink($path.$soal->$file_opsi);
                }
            }
        }

        if(!$chk){
            $this->output_json(['status'=>false]);
        }else{
            if($this->master->delete('tb_soal', $chk, 'id_soal')){
                $this->output_json(['status'=>true, 'total'=>count($chk)]);
            }
        }
    }
}