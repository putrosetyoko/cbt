<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class KelasGuru extends CI_Controller {

  public function __construct(){
    parent::__construct();
    if (!$this->ion_auth->logged_in()){
      redirect('auth');
    }else if (!$this->ion_auth->is_admin()){
      show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href="'.base_url('dashboard').'">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
    }
    $this->load->library(['datatables', 'form_validation']);// Load Library Ignited-Datatables
    $this->load->model('Master_model', 'master');
    $this->form_validation->set_error_delimiters('','');
  }

  public function output_json($data, $encode = true)
  {
        if($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function index()
  {
    $data = [
      'user' => $this->ion_auth->user()->row(),
      'judul' => 'Kelas Guru',
      'subjudul'=> 'Data Kelas Guru'
    ];
    $this->load->view('_templates/dashboard/_header.php', $data);
    $this->load->view('relasi/kelasguru/data');
    $this->load->view('_templates/dashboard/_footer.php');
    }

    public function data()
    {
        $this->output_json($this->master->getKelasGuru(), false);
  }
  
  public function add()
  {
    $data = [
      'user'    => $this->ion_auth->user()->row(),
      'judul'   => 'Tambah Kelas Guru',
      'subjudul'    => 'Tambah Data Kelas Guru',
      'guru'    => $this->master->getAllGuru(),
      'kelas'     => $this->master->getAllKelas()
    ];
    $this->load->view('_templates/dashboard/_header.php', $data);
    $this->load->view('relasi/kelasguru/add');
    $this->load->view('_templates/dashboard/_footer.php');
  }

  public function edit($id)
  {
    $data = [
      'user'      => $this->ion_auth->user()->row(),
      'judul'     => 'Edit Kelas Guru',
      'subjudul'      => 'Edit Data Kelas Guru',
      'guru'      => $this->master->getGuruById($id),
      'id_guru'       => $id,
      'all_kelas'     => $this->master->getAllKelas(),
      'kelas'       => $this->master->getKelasByGuru($id)
    ];
    $this->load->view('_templates/dashboard/_header.php', $data);
    $this->load->view('relasi/kelasguru/edit');
    $this->load->view('_templates/dashboard/_footer.php');
  }

  public function save()
  {
    $method = $this->input->post('method', true);
    $this->form_validation->set_rules('guru_id', 'Guru', 'required');
    $this->form_validation->set_rules('kelas_id[]', 'Kelas', 'required');
  
    if($this->form_validation->run() == FALSE){
      $data = [
        'status'    => false,
        'errors'    => [
          'guru_id' => form_error('guru_id'),
          'kelas_id[]' => form_error('kelas_id[]'),
        ]
      ];
      $this->output_json($data);
    }else{
      $guru_id = $this->input->post('guru_id', true);
      $kelas_id = $this->input->post('kelas_id', true);
      $input = [];
      foreach ($kelas_id as $key => $val) {
        $input[] = [
          'guru_id'   => $guru_id,
          'kelas_id' => $val
        ];
      }
      if($method==='add'){
        $action = $this->master->create('kelas_guru', $input, true);
      }else if($method==='edit'){
        $id = $this->input->post('guru_id', true);
        $this->master->delete('kelas_guru', $id, 'guru_id');
        $action = $this->master->create('kelas_guru', $input, true);
      }
      $data['status'] = $action ? TRUE : FALSE ;
    }
    $this->output_json($data);
  }

  public function delete()
    {
        $chk = $this->input->post('checked', true);
        if(!$chk){
            $this->output_json(['status'=>false]);
        }else{
            if($this->master->delete('kelas_guru', $chk, 'guru_id')){
                $this->output_json(['status'=>true, 'total'=>count($chk)]);
            }
        }
  }
}