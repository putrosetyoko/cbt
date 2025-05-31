<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Siswakelas extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin()) { // Hanya Admin yang bisa akses
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
            'id_ska' => $row->id_ska,  // pastikan ini adalah ID numerik
            'nama_tahun_ajaran' => $row->nama_tahun_ajaran,
            'user'          => $this->ion_auth->user()->row(),
            'judul'         => 'Distribusi Kelas Siswa',
            'subjudul'      => 'Penempatan Siswa per Kelas & Tahun Ajaran',
            'all_tahun_ajaran' => $this->master->getAllTahunAjaran(), // Untuk filter
            'all_kelas'     => $this->master->getAllKelas()       // Untuk filter
        ];
        // Path view disesuaikan jika controller ada di subfolder 'relasi'
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('relasi/siswakelas/data', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function data()
    {
        $filter_tahun_ajaran = $this->input->post('filter_tahun_ajaran', true);
        $filter_kelas = $this->input->post('filter_kelas', true);
        $this->output_json($this->master->getDataSiswaKelasAjaran($filter_tahun_ajaran, $filter_kelas), false);
    }

    public function add()
    {
        $data = [
            'user'             => $this->ion_auth->user()->row(),
            'judul'            => 'Tambah Penempatan Siswa',
            'subjudul'         => 'Pilih Tahun Ajaran, Kelas, dan Siswa',
            'all_tahun_ajaran' => $this->master->getAllTahunAjaran(),
            'all_kelas'        => $this->master->getAllKelas(),
            // Siswa akan di-load via AJAX berdasarkan tahun ajaran yang dipilih
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('relasi/siswakelas/add', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }
    
    // AJAX call untuk mendapatkan siswa yang belum ditempatkan
    public function get_siswa_available($id_tahun_ajaran)
    {
        if (!$this->input->is_ajax_request()) { show_404(); return; }
        if (empty($id_tahun_ajaran) || !is_numeric($id_tahun_ajaran)) {
            $this->output_json(['status' => false, 'message' => 'ID Tahun Ajaran tidak valid.']);
            return;
        }
        $siswa_available = $this->master->getSiswaBelumDitempatkan($id_tahun_ajaran);
        $this->output_json(['status' => true, 'data_siswa' => $siswa_available]);
    }


    public function save()
    {
        $this->form_validation->set_rules('id_tahun_ajaran', 'Tahun Ajaran', 'required|numeric');
        $this->form_validation->set_rules('kelas_id', 'Kelas', 'required|numeric');
        $this->form_validation->set_rules('siswa_ids[]', 'Siswa', 'required'); // Mengharapkan array siswa_ids

        if ($this->form_validation->run() == FALSE) {
            $errors = [
                'id_tahun_ajaran' => form_error('id_tahun_ajaran'),
                'kelas_id'        => form_error('kelas_id'),
                'siswa_ids'       => form_error('siswa_ids[]') // Key untuk error array
            ];
            $this->output_json(['status' => false, 'errors' => array_filter($errors), 'message' => 'Periksa inputan Anda.']);
            return;
        }

        $id_tahun_ajaran = $this->input->post('id_tahun_ajaran', true);
        $kelas_id        = $this->input->post('kelas_id', true);
        $siswa_ids       = $this->input->post('siswa_ids', true); // Array ID siswa

        $batch_data = [];
        $error_messages = [];
        $success_count = 0;

        foreach ($siswa_ids as $siswa_id) {
            if ($this->master->isSiswaAssignedToYear($siswa_id, $id_tahun_ajaran)) {
                $siswa = $this->master->getSiswaById($siswa_id); // Ambil nama siswa untuk pesan error
                $error_messages[] = "Siswa " . ($siswa ? htmlspecialchars($siswa->nama) : "ID ".$siswa_id) . " sudah ditempatkan di kelas lain pada tahun ajaran ini.";
            } else {
                $batch_data[] = [
                    'siswa_id'        => $siswa_id,
                    'kelas_id'        => $kelas_id,
                    'id_tahun_ajaran' => $id_tahun_ajaran
                ];
            }
        }

        if (!empty($batch_data)) {
            if ($this->master->create('siswa_kelas_ajaran', $batch_data, true)) { // Batch insert
                $success_count = count($batch_data);
            } else {
                $error_messages[] = "Gagal menyimpan data ke database.";
            }
        }
        
        if ($success_count > 0 && empty($error_messages)) {
            $this->output_json(['status' => true, 'message' => $success_count . ' siswa berhasil ditempatkan.']);
        } else {
            $final_message = $success_count . " siswa berhasil ditempatkan.";
            if(!empty($error_messages)){
                $final_message .= "\nBeberapa siswa gagal ditempatkan:\n- " . implode("\n- ", $error_messages);
            }
            $this->output_json(['status' => false, 'message' => $final_message]);
        }
    }

    public function edit($id_ska = null) // id_ska adalah id dari tabel siswa_kelas_ajaran
    {
        if (!$id_ska || !is_numeric($id_ska)) { show_404(); return; }
        $penempatan = $this->master->getSiswaKelasAjaranById($id_ska);
        if (!$penempatan) {
            show_error('Data penempatan siswa tidak ditemukan.', 404, 'Not Found');
            return;
        }

        $data = [
            'user'              => $this->ion_auth->user()->row(),
            'judul'             => 'Edit Penempatan Siswa',
            'subjudul'          => 'Ubah Kelas Siswa untuk Tahun Ajaran Tertentu',
            'penempatan'        => $penempatan,
            'all_kelas'         => $this->master->getAllKelas(),
            // Tahun Ajaran dan Siswa tidak diubah di sini, hanya kelasnya
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('relasi/siswakelas/edit', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function update()
    {
        $id_ska = $this->input->post('id_ska', true);
        $this->form_validation->set_rules('kelas_id', 'Kelas', 'required|numeric');
        // Tidak perlu validasi siswa_id dan id_tahun_ajaran karena tidak diubah di form edit ini

        if (empty($id_ska) || !is_numeric($id_ska)) {
            $this->output_json(['status' => false, 'message' => 'ID Penempatan tidak valid.']);
            return;
        }
        
        // Opsional: Ambil data lama untuk validasi jika siswa dipindah ke kelas di tahun ajaran yang sama
        // namun dengan constraint `(siswa_id, id_tahun_ajaran)` UNIQUE, kita hanya update `kelas_id`.
        // Jika ada perubahan `siswa_id` atau `id_tahun_ajaran` maka itu lebih kompleks dan
        // mungkin lebih baik dihapus dan dibuat baru. Untuk kasus ini, kita hanya update kelas_id.

        if ($this->form_validation->run() == FALSE) {
            $errors = ['kelas_id' => form_error('kelas_id')];
            $this->output_json(['status' => false, 'errors' => array_filter($errors), 'message' => 'Periksa inputan Anda.']);
        } else {
            $data_update = [
                'kelas_id' => $this->input->post('kelas_id', true)
            ];

            if ($this->master->update('siswa_kelas_ajaran', $data_update, 'id_ska', $id_ska)) {
                $this->output_json(['status' => true, 'message' => 'Data penempatan siswa berhasil diperbarui.']);
            } else {
                $this->output_json(['status' => false, 'message' => 'Gagal memperbarui data.']);
            }
        }
    }

    public function delete($id = null)
    {
        // Handle URL parameter delete
        if ($id !== null) {
            $chk = [$id];
        } else {
            // Handle POST data delete
            $chk = $this->input->post('checked', true);
            
            // Handle JSON POST data
            if (empty($chk)) {
                $json_data = json_decode(file_get_contents('php://input'), true);
                $chk = isset($json_data['checked']) ? $json_data['checked'] : null;
            }
        }
        
        // Validasi input
        if (!$chk) {
            $this->output_json([
                'status' => false, 
                'message' => 'Tidak ada data yang dipilih'
            ]);
            return;
        }

        // Pastikan $chk selalu dalam bentuk array
        if (!is_array($chk)) {
            $chk = [$chk];
        }

        // Filter array dari nilai kosong atau tidak valid
        $chk = array_filter($chk, function($value) {
            return !empty($value) && is_numeric($value);
        });

        // Cek ulang setelah filtering
        if (empty($chk)) {
            $this->output_json([
                'status' => false, 
                'message' => 'Data yang dipilih tidak valid'
            ]);
            return;
        }

        try {
            $this->db->trans_start();
            
            if ($this->master->delete('siswa_kelas_ajaran', $chk, 'id_ska')) {
                $this->db->trans_commit();
                $this->output_json([
                    'status' => true, 
                    'message' => count($chk) . ' data penempatan Siswa berhasil dihapus.',
                    'total' => count($chk)
                ]);
            } else {
                $this->db->trans_rollback();
                $this->output_json([
                    'status' => false, 
                    'message' => 'Gagal menghapus data. Silakan coba lagi.'
                ]);
            }

        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->output_json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function import($import_data = null, $error_messages = null, $success_messages = null)
    {
        $data = [
            'user'                => $this->ion_auth->user()->row(),
            'judul'               => 'Distribusi Kelas Siswa', // Judul disesuaikan
            'subjudul'            => 'Import Data Penempatan Siswa', // Subjudul disesuaikan
            'all_tahun_ajaran_ref'=> $this->master->getAllTahunAjaran(), // Untuk modal referensi
            'all_kelas_ref'       => $this->master->getAllKelas(), // Untuk modal referensi (pastikan sudah join jenjang)
            'all_siswa_ref'       => $this->master->getAllSiswaSimple() // Untuk modal referensi (fungsi baru di model)
        ];

        if ($import_data !== null) {
            $data['import'] = $import_data; // Data dari preview()
            $data['show_preview'] = true;
        } else {
            $data['show_preview'] = false;
        }
        
        // Tambahkan pesan error/sukses dari redirect jika ada
        if ($error_messages || $this->session->flashdata('error_message')) {
            $data['error_message'] = $error_messages ?: $this->session->flashdata('error_message');
        }
        if ($success_messages || $this->session->flashdata('success_message')) {
            $data['success_message'] = $success_messages ?: $this->session->flashdata('success_message');
        }
        if ($this->session->flashdata('warning_message')) {
            $data['warning_message'] = $this->session->flashdata('warning_message');
        }


        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('relasi/siswakelas/import', $data); // Path view untuk import siswakelas
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function preview()
    {
        $config['upload_path']      = './uploads/import/'; // Pastikan folder ini ada dan writeable
        $config['allowed_types']    = 'xls|xlsx|csv';
        $config['max_size']         = 2048; // 2MB
        $config['encrypt_name']     = true;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('upload_file')) {
            $error_msg = strip_tags($this->upload->display_errors());
            $this->session->set_flashdata('error_message', $error_msg);
            redirect('siswakelas/import');
            return;
        }
        
        $file_data = $this->upload->data();
        $file_path = $file_data['full_path'];
        $file_ext = $file_data['file_ext'];

        try {
            $reader = null;
            if ($file_ext == '.xlsx') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            } elseif ($file_ext == '.xls') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            } elseif ($file_ext == '.csv') {
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            } else {
                $this->session->set_flashdata('error_message', "Format file tidak didukung: " . $file_ext);
                if (file_exists($file_path)) { unlink($file_path); }
                redirect('siswakelas/import');
                return;
            }

            $spreadsheet = $reader->load($file_path);
            // Ambil data dari sheet pertama, menggunakan format kolom (A, B, C)
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true); 
            
            unlink($file_path); // Hapus file setelah dibaca

            $data_for_preview = [];
            $row_count = count($sheetData);

            if ($row_count <= 1) { // Hanya header atau kosong
                $this->session->set_flashdata('warning_message', "File Excel kosong atau hanya berisi header. Pastikan file Anda memiliki data.");
                redirect('siswakelas/import');
                return;
            }
            
            // Ambil data referensi sekali untuk efisiensi
            $all_db_siswa_by_nisn = array_column($this->master->getAllSiswaSimple(), null, 'nisn');
            $all_db_kelas_by_id = array_column($this->master->getAllKelas(), null, 'id_kelas'); // getAllKelas harus join jenjang
            $all_db_tahun_ajaran_by_id = array_column($this->master->getAllTahunAjaran(), null, 'id_tahun_ajaran');

            // Mulai dari baris ke-2 (mengabaikan header)
            for ($i = 2; $i <= $row_count; $i++) {
                // Asumsi kolom: A = NISN Siswa, B = ID Kelas, C = ID Tahun Ajaran
                $nisn_excel            = isset($sheetData[$i]['A']) ? trim($sheetData[$i]['A']) : null;
                $id_kelas_excel        = isset($sheetData[$i]['D']) ? trim($sheetData[$i]['D']) : null;
                $id_tahun_ajaran_excel = isset($sheetData[$i]['E']) ? trim($sheetData[$i]['E']) : null;

                // Lewati baris jika data kunci kosong
                if (empty($nisn_excel) && empty($id_kelas_excel) && empty($id_tahun_ajaran_excel)) {
                    continue;
                }

                $row_data = [
                    'nisn_excel'                 => $nisn_excel,
                    'id_kelas_excel'             => $id_kelas_excel,
                    'id_tahun_ajaran_excel'      => $id_tahun_ajaran_excel,
                    'siswa_id_resolved'          => null,
                    'nama_siswa_resolved'        => 'N/A',
                    'kelas_id_resolved'          => null,
                    'nama_kelas_resolved'        => 'N/A',
                    'nama_jenjang_resolved'      => 'N/A',
                    'id_tahun_ajaran_resolved'   => null,
                    'nama_tahun_ajaran_resolved' => 'N/A',
                    'is_valid_nisn'              => false,
                    'is_valid_kelas'             => false,
                    'is_valid_tahun_ajaran'      => false,
                    'is_already_assigned'        => false,
                    'is_importable'              => false,
                    'validation_messages'        => []
                ];

                // Validasi NISN
                if (!empty($nisn_excel) && isset($all_db_siswa_by_nisn[$nisn_excel])) {
                    $row_data['siswa_id_resolved'] = $all_db_siswa_by_nisn[$nisn_excel]->id_siswa;
                    $row_data['nama_siswa_resolved'] = $all_db_siswa_by_nisn[$nisn_excel]->nama;
                    $row_data['is_valid_nisn'] = true;
                } else if (!empty($nisn_excel)) {
                    $row_data['validation_messages'][] = "NISN tidak ditemukan";
                } else {
                    $row_data['validation_messages'][] = "NISN kosong";
                }

                // Validasi ID Kelas
                if (!empty($id_kelas_excel) && isset($all_db_kelas_by_id[$id_kelas_excel])) {
                    $row_data['kelas_id_resolved'] = $all_db_kelas_by_id[$id_kelas_excel]->id_kelas;
                    $row_data['nama_kelas_resolved'] = $all_db_kelas_by_id[$id_kelas_excel]->nama_kelas;
                    $row_data['nama_jenjang_resolved'] = $all_db_kelas_by_id[$id_kelas_excel]->nama_jenjang ?? '-'; // Asumsi nama_jenjang ada
                    $row_data['is_valid_kelas'] = true;
                } else if (!empty($id_kelas_excel)) {
                    $row_data['validation_messages'][] = "ID Kelas tidak ditemukan";
                } else {
                    $row_data['validation_messages'][] = "ID Kelas kosong";
                }
                
                // Validasi ID Tahun Ajaran
                if (!empty($id_tahun_ajaran_excel) && isset($all_db_tahun_ajaran_by_id[$id_tahun_ajaran_excel])) {
                    $row_data['id_tahun_ajaran_resolved'] = $all_db_tahun_ajaran_by_id[$id_tahun_ajaran_excel]->id_tahun_ajaran;
                    $row_data['nama_tahun_ajaran_resolved'] = $all_db_tahun_ajaran_by_id[$id_tahun_ajaran_excel]->nama_tahun_ajaran;
                    $row_data['is_valid_tahun_ajaran'] = true;
                } else if (!empty($id_tahun_ajaran_excel)) {
                    $row_data['validation_messages'][] = "ID Tahun Ajaran tidak ditemukan";
                } else {
                    $row_data['validation_messages'][] = "ID Tahun Ajaran kosong";
                }

                // Cek duplikasi jika semua ID valid
                if ($row_data['is_valid_nisn'] && $row_data['is_valid_tahun_ajaran']) {
                    if ($this->master->isSiswaAssignedToYear($row_data['siswa_id_resolved'], $row_data['id_tahun_ajaran_resolved'])) {
                        $row_data['is_already_assigned'] = true;
                        $row_data['validation_messages'][] = "Siswa sudah ditempatkan di TA ini";
                    }
                }
                
                // Tentukan apakah baris bisa diimpor
                if ($row_data['is_valid_nisn'] && $row_data['is_valid_kelas'] && $row_data['is_valid_tahun_ajaran'] && !$row_data['is_already_assigned']) {
                    $row_data['is_importable'] = true;
                }

                $data_for_preview[] = $row_data;
            } // End for loop

            if(empty($data_for_preview)){
                $this->session->set_flashdata('warning_message', "Tidak ada data yang dapat diproses dari file. Periksa format dan isi file Anda.");
                redirect('siswakelas/import');
                return;
            }
            $this->import($data_for_preview);

        } catch (Exception $e) {
            $this->session->set_flashdata('error_message', "Terjadi kesalahan saat memproses file: " . $e->getMessage());
            if (isset($file_path) && file_exists($file_path)) { unlink($file_path); }
            redirect('siswakelas/import');
        }
    }

    public function do_import()
    {
        $input_json = $this->input->post('data_import_json', true); // Sesuaikan dengan nama input di form preview
        $data_from_preview = json_decode($input_json, true); // true untuk array asosiatif

        if (empty($data_from_preview) || !is_array($data_from_preview)) {
            $this->session->set_flashdata('error_message', 'Tidak ada data untuk diimpor atau format data salah.');
            redirect('siswakelas/import');
            return;
        }

        $data_to_insert = [];
        $processed_count = 0;
        $success_count = 0;
        $skipped_info = [];

        foreach ($data_from_preview as $row) {
            $processed_count++;
            // Hanya proses baris yang ditandai 'is_importable' oleh fungsi preview()
            if (isset($row['is_importable']) && $row['is_importable'] === true) {
                 // Pastikan ID yang di-resolve tidak kosong
                if (!empty($row['siswa_id_resolved']) && !empty($row['kelas_id_resolved']) && !empty($row['id_tahun_ajaran_resolved'])) {
                    $data_to_insert[] = [
                        'siswa_id'        => $row['siswa_id_resolved'],
                        'kelas_id'        => $row['kelas_id_resolved'],
                        'id_tahun_ajaran' => $row['id_tahun_ajaran_resolved']
                    ];
                } else {
                    $skipped_info[] = "Baris dengan NISN {$row['nisn_excel']} dilewati karena ada ID yang tidak ter-resolve dengan benar.";
                }
            } else {
                $skipped_info[] = "Baris dengan NISN {$row['nisn_excel']} dilewati karena tidak valid atau sudah ada: " . implode(", ", $row['validation_messages'] ?? ['Alasan tidak diketahui']);
            }
        }

        if (!empty($data_to_insert)) {
            // Lakukan batch insert. Model create() Anda harus bisa menangani duplikasi jika ada (misal, dengan 'insert ignore' atau cek lagi)
            // Untuk sekarang, kita asumsikan 'is_already_assigned' sudah menangani duplikasi utama (siswa_id, id_tahun_ajaran)
            if ($this->master->create('siswa_kelas_ajaran', $data_to_insert, true)) {
                $success_count = count($data_to_insert);
                $this->session->set_flashdata('success_message', $success_count . ' data penempatan siswa berhasil diimpor.');
                if (!empty($skipped_info)) {
                    $this->session->set_flashdata('warning_message', "Beberapa data dilewati: <br>- " . implode("<br>- ", array_map('htmlspecialchars', $skipped_info)));
                }
                redirect('siswakelas');
            } else {
                $this->session->set_flashdata('error_message', 'Gagal menyimpan data impor ke database. Mungkin ada duplikasi atau error database.');
                $this->import($data_from_preview, $this->session->flashdata('error_message')); // Kembali ke preview dengan data & error
            }
        } else {
            $message = 'Tidak ada data valid untuk diimpor setelah pemrosesan.';
            if(!empty($skipped_info)){
                $message .= "<br>Detail: <br>- " . implode("<br>- ", array_map('htmlspecialchars', $skipped_info));
            }
            $this->session->set_flashdata('warning_message', $message);
            redirect('siswakelas/import');
        }
    }
}