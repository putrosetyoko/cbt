<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penugasanguru extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin()) {
            show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini.', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'form_validation']);
        $this->load->model('Master_model', 'master');
        $this->form_validation->set_error_delimiters('', '');
    }

    public function output_json($data, $encode = true)
    {
        if ($encode && !is_string($data)) { $data = json_encode($data); }
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function index()
    {
        $data = [
            'user'             => $this->ion_auth->user()->row(),
            'judul'            => 'Penugasan Guru',
            'subjudul'         => 'Tahun Ajaran - Guru - Mapel - Kelas',
            'all_tahun_ajaran' => $this->master->getAllTahunAjaran(),
            'all_guru'         => $this->master->getAllGuru(), // Asumsi nama fungsi ini benar
            'all_mapel'        => $this->master->getAllMapel(),
            'all_kelas'        => $this->master->getAllKelas(), // Pastikan ini join jenjang
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('relasi/penugasanguru/data', $data); // Path view
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function data()
    {
        $filter_tahun_ajaran = $this->input->post('filter_tahun_ajaran');
        $filter_guru = $this->input->post('filter_guru');
        $filter_mapel = $this->input->post('filter_mapel');
        $filter_kelas = $this->input->post('filter_kelas');

        $this->output_json($this->master->getDataPenugasanGuru(
            $filter_tahun_ajaran === 'all' ? null : $filter_tahun_ajaran,
            $filter_guru === 'all' ? null : $filter_guru,
            $filter_mapel === 'all' ? null : $filter_mapel,
            $filter_kelas === 'all' ? null : $filter_kelas
        ), false);
    }

    public function add()
    {
        $data = [
            'user'             => $this->ion_auth->user()->row(),
            'judul'            => 'Tambah Penugasan Guru',
            'subjudul'         => 'Assign Guru ke Mapel, Kelas, dan Tahun Ajaran',
            'all_tahun_ajaran' => $this->master->getAllTahunAjaran(),
            'all_guru'         => $this->master->getAllGuru(),
            'all_mapel'        => $this->master->getAllMapel(),
            'all_kelas'        => $this->master->getAllKelas(), // Pastikan ini join jenjang
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('relasi/penugasanguru/add', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function save()
    {
        $this->form_validation->set_rules('id_tahun_ajaran', 'Tahun Ajaran', 'required|numeric');
        $this->form_validation->set_rules('guru_id', 'Guru', 'required|numeric');
        $this->form_validation->set_rules('mapel_ids[]', 'Mata Pelajaran', 'required'); // Bisa multiple mapel
        $this->form_validation->set_rules('kelas_ids[]', 'Kelas', 'required');       // Bisa multiple kelas

        if ($this->form_validation->run() == FALSE) {
            $errors = [
                'id_tahun_ajaran' => form_error('id_tahun_ajaran'),
                'guru_id'         => form_error('guru_id'),
                'mapel_ids'       => form_error('mapel_ids[]'),
                'kelas_ids'       => form_error('kelas_ids[]')
            ];
            $this->output_json(['status' => false, 'errors' => array_filter($errors), 'message' => 'Periksa inputan Anda.']);
            return;
        }

        $id_tahun_ajaran = $this->input->post('id_tahun_ajaran', true);
        $guru_id         = $this->input->post('guru_id', true);
        $mapel_ids       = $this->input->post('mapel_ids', true); // Array
        $kelas_ids       = $this->input->post('kelas_ids', true); // Array

        $batch_data = [];
        $error_messages = [];
        $success_count = 0;
        $skipped_count = 0;

        foreach ($mapel_ids as $mapel_id) {
            foreach ($kelas_ids as $kelas_id) {
                if ($this->master->isPenugasanExists($guru_id, $mapel_id, $kelas_id, $id_tahun_ajaran)) {
                    // Dapatkan nama untuk pesan error yang lebih baik
                    $guru = $this->master->getGuruById($guru_id);
                    $mapel = $this->master->getMapelById($mapel_id, true); // true untuk single
                    $kelas = $this->master->getKelasByIdSingle($kelas_id); // ambil satu kelas
                    $ta = $this->master->getTahunAjaranById($id_tahun_ajaran);

                    $error_messages[] = "Guru ".($guru->nama_guru ?? '')." sudah ditugaskan mapel ".($mapel->nama_mapel ?? '')." di kelas ".($kelas->nama_kelas ?? '')." pada TA ".($ta->nama_tahun_ajaran ?? '');
                    $skipped_count++;
                } else {
                    $batch_data[] = [
                        'guru_id'         => $guru_id,
                        'mapel_id'        => $mapel_id,
                        'kelas_id'        => $kelas_id,
                        'id_tahun_ajaran' => $id_tahun_ajaran
                    ];
                }
            }
        }

        if (!empty($batch_data)) {
            if ($this->master->create('guru_mapel_kelas_ajaran', $batch_data, true)) { // Batch insert
                $success_count = count($batch_data);
            } else {
                $error_messages[] = "Gagal menyimpan beberapa data ke database.";
            }
        }
        
        $final_message = "";
        if ($success_count > 0) {
            $final_message .= $success_count . ' penugasan berhasil disimpan. ';
        }
        if ($skipped_count > 0 || !empty($error_messages)) {
            // if(empty($final_message)) $final_message = "Operasi selesai dengan catatan: "; else $final_message .= " ";
            // $final_message .= $skipped_count . " penugasan dilewati karena sudah ada atau error lain.";
            if(!empty($error_messages)) $final_message .= "\nDetail:\n" . implode("\n- ", $error_messages);
        }
        if (empty($final_message)) $final_message = "Tidak ada data baru untuk ditambahkan atau semua data sudah ada.";


        $this->output_json([
            'status' => $success_count > 0, // Anggap true jika ada yang berhasil
            'message' => $final_message
        ]);
    }

    // Untuk Edit, kita akan mengedit satu entri id_gmka. 
    // Mengubah guru/mapel/kelas/TA pada satu entri.
    public function edit($id_gmka = null)
    {
        if (!$id_gmka || !is_numeric($id_gmka)) { show_404(); return; }
        $penugasan = $this->master->getPenugasanGuruById($id_gmka);
        if (!$penugasan) {
            show_error('Data penugasan tidak ditemukan.', 404, 'Not Found');
            return;
        }

        $data = [
            'user'             => $this->ion_auth->user()->row(),
            'judul'            => 'Edit Penugasan Guru',
            'subjudul'         => 'Ubah Detail Penugasan',
            'penugasan'        => $penugasan,
            'all_tahun_ajaran' => $this->master->getAllTahunAjaran(),
            'all_guru'         => $this->master->getAllGuru(),
            'all_mapel'        => $this->master->getAllMapel(),
            'all_kelas'        => $this->master->getAllKelas(),
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('relasi/penugasanguru/edit', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function update()
    {
        $id_gmka = $this->input->post('id_gmka', true);
        if (empty($id_gmka) || !is_numeric($id_gmka)) {
            $this->output_json(['status' => false, 'message' => 'ID Penugasan tidak valid.']);
            return;
        }

        $this->form_validation->set_rules('id_tahun_ajaran', 'Tahun Ajaran', 'required|numeric');
        $this->form_validation->set_rules('guru_id', 'Guru', 'required|numeric');
        $this->form_validation->set_rules('mapel_id', 'Mata Pelajaran', 'required|numeric');
        $this->form_validation->set_rules('kelas_id', 'Kelas', 'required|numeric');

        if ($this->form_validation->run() == FALSE) {
            $errors = [ /* ... kumpulkan errors ... */ ];
            $this->output_json(['status' => false, 'errors' => array_filter($errors), 'message' => 'Periksa inputan Anda.']);
            return;
        }

        $id_tahun_ajaran = $this->input->post('id_tahun_ajaran', true);
        $guru_id         = $this->input->post('guru_id', true);
        $mapel_id        = $this->input->post('mapel_id', true);
        $kelas_id        = $this->input->post('kelas_id', true);

        // Cek unique constraint, kecuali untuk record saat ini ($id_gmka)
        if ($this->master->isPenugasanExists($guru_id, $mapel_id, $kelas_id, $id_tahun_ajaran, $id_gmka)) {
            $this->output_json(['status' => false, 'message' => 'Kombinasi Guru, Mapel, Kelas, dan Tahun Ajaran ini sudah ada.']);
            return;
        }

        $data_update = [
            'guru_id'         => $guru_id,
            'mapel_id'        => $mapel_id,
            'kelas_id'        => $kelas_id,
            'id_tahun_ajaran' => $id_tahun_ajaran
        ];

        if ($this->master->update('guru_mapel_kelas_ajaran', $data_update, 'id_gmka', $id_gmka)) {
            $this->output_json(['status' => true, 'message' => 'Data penugasan berhasil diperbarui.']);
        } else {
            $this->output_json(['status' => false, 'message' => 'Gagal memperbarui data penugasan.']);
        }
    }


    public function delete($id = null)
    {
        $checked = $id ? [$id] : $this->input->post('checked', true);
        
        if (!$checked) {
            $this->output_json([
                'status' => false,
                'message' => 'Tidak ada data yang dipilih'
            ]);
            return;
        }

        if (!is_array($checked)) {
            $checked = [$checked];
        }

        $this->db->trans_start();
        $this->db->where_in('id_gmka', $checked);
        $delete = $this->db->delete('guru_mapel_kelas_ajaran');
        $this->db->trans_complete();

        $this->output_json([
            'status' => $delete,
            'total' => count($checked),
            'message' => $delete ? 'Data berhasil dihapus' : 'Gagal menghapus data'
        ]);
    }
}