<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kelas extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        if (!$this->ion_auth->logged_in()) {
            redirect('auth');
        } else if (!$this->ion_auth->is_admin()) {
            show_error('Hanya Administrator yang diberi hak untuk mengakses halaman ini, <a href="' . base_url('dashboard') . '">Kembali ke menu awal</a>', 403, 'Akses Terlarang');
        }
        $this->load->library(['datatables', 'form_validation']);
        $this->load->model('Master_model', 'master');
        $this->form_validation->set_error_delimiters('', '');
    }

    public function output_json($data, $encode = true)
    {
        if ($encode) $data = json_encode($data);
        $this->output->set_content_type('application/json')->set_output($data);
    }

    public function index()
    {
        $data = [
            'user' => $this->ion_auth->user()->row(),
            'judul' => 'Kelas',
            'subjudul' => 'Data Kelas'
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/kelas/data'); // View ini perlu disesuaikan untuk menampilkan nama_jenjang
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function data() // Untuk DataTables
    {
        // Pastikan Master_model->getDataKelas() sudah di-join dengan tabel jenjang
        $this->output_json($this->master->getDataKelas(), false);
    }

    public function add()
    {
        $data = [
            'user'     => $this->ion_auth->user()->row(),
            'judul'    => 'Tambah Kelas',
            'subjudul' => 'Tambah Data Kelas',
            'banyak'   => $this->input->post('banyak', true),
            'all_jenjang' => $this->master->getAllJenjang() // Mengambil data semua jenjang
        ];
        $this->load->view('_templates/dashboard/_header.php', $data);
        $this->load->view('master/kelas/add', $data);
        $this->load->view('_templates/dashboard/_footer.php');
    }

    public function edit()
    {
        $chk = $this->input->post('checked', true);
        if (!$chk) {
            // Sebaiknya redirect ke halaman kelas dengan pesan error, bukan admin/kelas jika controllernya Kelas
            $this->session->set_flashdata('error', 'Tidak ada data yang dipilih untuk diedit.');
            redirect('kelas');
        } else {
            $kelas_data = $this->master->getKelasById($chk); // getKelasById sudah dijoin dengan jenjang
            $data = [
                'user'      => $this->ion_auth->user()->row(),
                'judul'     => 'Edit Kelas',
                'subjudul'  => 'Edit Data Kelas',
                'all_jenjang' => $this->master->getAllJenjang(), // Mengambil data semua jenjang
                'kelas'     => $kelas_data // Mengirim data kelas yang akan diedit
            ];
            $this->load->view('_templates/dashboard/_header.php', $data);
            $this->load->view('master/kelas/edit', $data);
            $this->load->view('_templates/dashboard/_footer.php');
        }
    }

    public function save()
    {
        $rows = count($this->input->post('nama_kelas', true));
        $mode = $this->input->post('mode', true);
        $status = true; // Asumsi awal status true
        $error = [];    // Inisialisasi array error
        $insert = [];   // Inisialisasi array insert
        $update = [];   // Inisialisasi array update

        for ($i = 1; $i <= $rows; $i++) {
            $nama_kelas_field = 'nama_kelas[' . $i . ']';
            $id_jenjang_field = 'id_jenjang[' . $i . ']'; // Field baru untuk id_jenjang

            $this->form_validation->set_rules($nama_kelas_field, 'Nama Kelas ke-' . $i, 'required|trim');
            $this->form_validation->set_rules($id_jenjang_field, 'Jenjang Kelas ke-' . $i, 'required|numeric'); // Validasi jenjang
            $this->form_validation->set_message('required', '{field} Wajib diisi.');
            $this->form_validation->set_message('numeric', '{field} harus berupa angka.');

            if ($this->form_validation->run() === FALSE) {
                $current_error = [];
                if(form_error($nama_kelas_field)) $current_error[$nama_kelas_field] = form_error($nama_kelas_field);
                if(form_error($id_jenjang_field)) $current_error[$id_jenjang_field] = form_error($id_jenjang_field);

                if(!empty($current_error)) {
                    $error[] = $current_error;
                }
                $status = FALSE;
            } else {
                if ($status === TRUE) { // Hanya proses jika tidak ada error sebelumnya di iterasi ini
                    $input_data = [
                        'nama_kelas' => $this->input->post($nama_kelas_field, true),
                        'id_jenjang' => $this->input->post($id_jenjang_field, true) ?: NULL, // Simpan NULL jika kosong/tidak valid
                    ];

                    if ($mode == 'add') {
                        $insert[] = $input_data;
                    } else if ($mode == 'edit') {
                        $update[] = array_merge(['id_kelas' => $this->input->post('id_kelas[' . $i . ']', true)], $input_data);
                    }
                }
            }
        }

        if ($status) {
            if ($mode == 'add' && !empty($insert)) {
                $this->master->create('kelas', $insert, true); // true untuk batch insert
                $data_response['message'] = 'Data kelas berhasil ditambahkan.';
            } else if ($mode == 'edit' && !empty($update)) {
                $this->master->update('kelas', $update, 'id_kelas', null, true); // true untuk batch update
                $data_response['message'] = 'Data kelas berhasil diperbarui.';
            } else {
                // Kasus jika status TRUE tapi tidak ada data di insert/update (seharusnya tidak terjadi jika validasi lolos)
                $status = FALSE;
                $data_response['message'] = 'Tidak ada data untuk diproses.';
            }
        } else {
            $data_response['message'] = 'Terdapat kesalahan pada input Anda.';
            if (!empty($error)) {
                $data_response['errors'] = $error;
            }
        }
        $data_response['status'] = $status;
        $this->output_json($data_response);
    }

    public function delete()
    {
        $chk = $this->input->post('checked', true);
        if (!$chk) {
            $this->output_json(['status' => false, 'message' => 'Tidak ada data yang dipilih']);
        } else {
            // Anda bisa menambahkan pengecekan di sini apakah kelas masih digunakan (misal di tabel siswa)
            // sebelum melakukan penghapusan.
            if ($this->master->delete('kelas', $chk, 'id_kelas')) {
                $this->output_json(['status' => true, 'message' => count($chk) . ' data kelas berhasil dihapus.']);
            } else {
                $this->output_json(['status' => false, 'message' => 'Gagal menghapus data kelas.']);
            }
        }
    }

    // Metode import, preview, do_import perlu disesuaikan juga jika ingin support jenjang
    // Untuk saat ini, saya akan fokus pada add, edit, save, delete manual dulu.
    // ... (sisa metode import Anda, perlu penyesuaian untuk kolom jenjang) ...
    public function import($import_data = null)
    {
        $data = [
            'user' => $this->ion_auth->user()->row(),
            'judul' => 'Kelas',
            'subjudul' => 'Import Kelas',
            'all_jenjang' => $this->master->getAllJenjang() // Untuk memetakan nama jenjang ke id_jenjang saat import
        ];
        if ($import_data != null) $data['import'] = $import_data;

        $this->load->view('_templates/dashboard/_header', $data);
        $this->load->view('master/kelas/import', $data); // View import perlu kolom untuk jenjang juga
        $this->load->view('_templates/dashboard/_footer');
    }

    public function preview()
    {
        $config['upload_path']      = './uploads/import/';
        $config['allowed_types']    = 'xls|xlsx|csv';
        $config['max_size']         = 2048;
        $config['encrypt_name']     = true;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('upload_file')) {
            $error = $this->upload->display_errors();
            // Berikan response JSON untuk error upload agar bisa ditangani di JS jika perlu
            $this->output_json(['status' => false, 'message' => strip_tags($error)]);
            return;
        } else {
            $file = $this->upload->data('full_path');
            $ext = $this->upload->data('file_ext');

            switch ($ext) {
                case '.xlsx':
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                    break;
                case '.xls':
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                    break;
                case '.csv':
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                    break;
                default:
                    unlink($file); // Hapus file jika tidak dikenal
                    $this->output_json(['status' => false, 'message' => 'Format file tidak dikenal: ' . $ext]);
                    return;
            }

            $spreadsheet = $reader->load($file);
            $sheetData = $spreadsheet->getActiveSheet()->toArray();
            $data_preview = [];
            // Asumsi: Kolom 1 = Nama Kelas, Kolom 2 = Nama Jenjang
            if (count($sheetData) > 1) { // Cek jika ada data selain header
                for ($i = 1; $i < count($sheetData); $i++) { // Mulai dari baris kedua (data)
                    if (empty($sheetData[$i][0])) continue; // Lewati baris jika nama kelas kosong
                    $data_preview[] = [
                        'kelas' => $sheetData[$i][0],
                        'jenjang' => isset($sheetData[$i][1]) ? $sheetData[$i][1] : null // Kolom kedua untuk jenjang
                    ];
                }
            }

            unlink($file); // Hapus file setelah dibaca

            if (empty($data_preview)) {
                $this->output_json(['status' => false, 'message' => 'Tidak ada data valid untuk diimpor atau file kosong. Pastikan kolom Nama Kelas (Kolom A) dan Nama Jenjang (Kolom B) terisi.']);
                return;
            }
            // Kirim data preview ke fungsi import untuk ditampilkan
            $this->import($data_preview);
        }
    }
    public function do_import()
    {
        $input_data = json_decode($this->input->post('data', true));
        if (empty($input_data)) {
             $this->session->set_flashdata('error', 'Tidak ada data untuk diimpor.');
            redirect('kelas/import');
        }

        $data_to_insert = [];
        $all_jenjang_db = $this->master->getAllJenjang();
        $jenjang_map = []; // Buat peta nama_jenjang => id_jenjang
        foreach($all_jenjang_db as $j) {
            $jenjang_map[strtolower($j->nama_jenjang)] = $j->id_jenjang;
        }

        foreach ($input_data as $d) {
            $id_jenjang_resolved = null;
            if (!empty($d->jenjang)) {
                $nama_jenjang_lower = strtolower(trim($d->jenjang));
                if (isset($jenjang_map[$nama_jenjang_lower])) {
                    $id_jenjang_resolved = $jenjang_map[$nama_jenjang_lower];
                } else {
                    // Opsi: abaikan, buat jenjang baru, atau laporkan error
                    // Untuk saat ini, kita set null jika jenjang tidak ditemukan
                }
            }
            $data_to_insert[] = [
                'nama_kelas' => $d->kelas,
                'id_jenjang' => $id_jenjang_resolved
            ];
        }

        if (!empty($data_to_insert)) {
            $save = $this->master->create('kelas', $data_to_insert, true); // Batch create
            if ($save) {
                $this->session->set_flashdata('success', 'Data kelas berhasil diimpor.');
                redirect('kelas');
            } else {
                $this->session->set_flashdata('error', 'Gagal menyimpan data impor ke database.');
                redirect('kelas/import');
            }
        } else {
            $this->session->set_flashdata('error', 'Tidak ada data valid setelah pemetaan jenjang.');
            redirect('kelas/import');
        }
    }
}