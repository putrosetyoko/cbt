<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_model extends CI_Model
{
    public function __construct()
    {
        $this->db->query("SET sql_mode=(SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''));");
    }

    public function create($table, $data, $batch = false)
    {
        if ($batch === false) {
            $insert = $this->db->insert($table, $data);
        } else {
            $insert = $this->db->insert_batch($table, $data);
        }
        return $insert;
    }

    public function update($table, $data, $pk, $id = null, $batch = false)
    {
        if ($batch === false) {
            $insert = $this->db->update($table, $data, array($pk => $id));
        } else {
            $insert = $this->db->update_batch($table, $data, $pk);
        }
        return $insert;
    }

    public function delete($table, $data, $pk)
    {
        $this->db->where_in($pk, $data);
        return $this->db->delete($table);
    }

    /**
     * Data Tahun Ajaran
     */
    public function getDataTahunAjaran()
    {
        $this->datatables->select('id_tahun_ajaran, nama_tahun_ajaran, semester, DATE_FORMAT(tgl_mulai, "%d-%m-%Y") as tgl_mulai, DATE_FORMAT(tgl_selesai, "%d-%m-%Y") as tgl_selesai, status');
        $this->datatables->from('tahun_ajaran');
        // JANGAN tambahkan kolom 'action' di sini jika akan di-render oleh JS
        $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 'id_tahun_ajaran');
        return $this->datatables->generate();
    }

    public function getTahunAjaranById($id) // Mengambil satu data berdasarkan ID
    {
        $this->db->where('id_tahun_ajaran', $id);
        return $this->db->get('tahun_ajaran')->row();
    }

    public function getAllTahunAjaran() // Untuk dropdown, dll
    {
        $this->db->order_by('nama_tahun_ajaran', 'DESC'); // Atau ASC
        return $this->db->get('tahun_ajaran')->result();
    }

    public function setAllTahunAjaranTidakAktif($kecuali_id = null)
    {
        if ($kecuali_id !== null) {
            $this->db->where('id_tahun_ajaran !=', $kecuali_id);
        }
        return $this->db->update('tahun_ajaran', ['status' => 'tidak_aktif']);
    }

    /**
     * Data Jenjang
     */
    public function getDataJenjang()
    {
        $this->datatables->select('id_jenjang, nama_jenjang, deskripsi');
        $this->datatables->from('jenjang'); // Nama tabel jenjang
        // Tambahkan kolom aksi untuk tombol Edit (Delete ditangani oleh bulk delete)
        $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 'id_jenjang');
        $this->datatables->add_column(
            'action',
            '<div class="text-center">
                <a href="'.base_url('jenjang/edit/$1').'" class="btn btn-xs btn-warning" title="Edit"><i class="fa fa-pencil"></i></a>
            </div>',
            'id_jenjang'
        );
        return $this->datatables->generate();
    }

    public function getJenjangById($id)
    {
        $this->db->where('id_jenjang', $id);
        return $this->db->get('jenjang')->row(); // Nama tabel jenjang
    }

    public function getAllJenjang()
    {
        $this->db->order_by('nama_jenjang', 'ASC');
        return $this->db->get('jenjang')->result();
    }

    /**
     * Data Kelas
     */

    public function getDataKelas()
    {
        $this->datatables->select('kelas.id_kelas, kelas.nama_kelas, jenjang.nama_jenjang');
        $this->datatables->from('kelas');
        $this->datatables->join('jenjang', 'jenjang.id_jenjang = kelas.id_jenjang', 'left'); // LEFT JOIN agar kelas tanpa jenjang tetap tampil
        // Kolom aksi Anda yang sudah ada di getDataKelas sebelumnya
        $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 'id_kelas, nama_kelas');
        // Anda bisa menyesuaikan add_column untuk aksi jika perlu
        return $this->datatables->generate();
    }

    public function getKelasById($id)
    {
        $this->db->select('k.*, j.nama_jenjang');
        $this->db->from('kelas k');
        $this->db->join('jenjang j', 'j.id_jenjang = k.id_jenjang', 'left');
        $this->db->where_in('k.id_kelas', $ids);
        return $this->db->get()->result();
    }

    public function getAllKelas()
    {
        $this->db->select('id_kelas, nama_kelas');
        $this->db->from('kelas');
        $this->db->order_by('nama_kelas');
        return $this->db->get()->result();
    }

    public function getDataSiswa() // Parameter $id_kelas dihilangkan
    {
        $this->datatables->select('a.id_siswa, a.nama, a.nisn, a.jenis_kelamin, c.email AS email');
        $this->datatables->select('(SELECT COUNT(id) FROM users WHERE username = a.nisn) AS ada');
        $this->datatables->from('siswa a');
        $this->datatables->join('users c', 'a.nisn = c.username', 'left');

        return $this->datatables->generate();
    }

    public function getSiswaById($id_siswa)
    {
        // $this->db->from('siswa');
        $this->db->where('id_siswa', $id_siswa);
        return $this->db->get('siswa')->row(); // Hanya dari tabel siswa
    }

    /**
     * Data Guru
     */

    public function getDataGuru()
    {
        $this->datatables->select('a.id_guru, a.nip, a.nama_guru, a.email');
        $this->datatables->select('(SELECT COUNT(id) FROM users WHERE username = a.nip) AS ada');
        $this->datatables->from('guru a');
        $this->datatables->join('users u', 'a.nip = u.username', 'left');
        // $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 'id_guru');
        return $this->datatables->generate();
    }

    public function getGuruById($id_guru)
    {
        // Hanya mengambil data dari tabel guru
        $this->db->where('id_guru', $id_guru);
        return $this->db->get('guru')->row();
    }

    /**
     * Data Mapel
     */

    public function getDataMapel()
    {
        $this->datatables->select('id_mapel, nama_mapel');
        $this->datatables->from('mapel');
        return $this->datatables->generate();
    }

    public function getAllMapel()
    {
        return $this->db->get('mapel')->result();
    }

    public function getMapelById($id, $single = false)
    {
        if ($single === false) {
            $this->db->where_in('id_mapel', $id);
            $this->db->order_by('nama_mapel');
            $query = $this->db->get('mapel')->result();
        } else {
            $query = $this->db->get_where('mapel', array('id_mapel'=>$id))->row();
        }
        return $query;
    }

    /**
     * Data Kelas Guru
     */

    public function getKelasGuru() // Mengganti getKelasDosen menjadi getKelasGuru
    {
        $this->datatables->select('kelas_guru.id, guru.id_guru, guru.nip, guru.nama_guru, GROUP_CONCAT(kelas.nama_kelas) as kelas'); // Mengganti dosen.* menjadi guru.*
        $this->datatables->from('kelas_guru'); // Mengganti kelas_dosen menjadi kelas_guru
        $this->datatables->join('kelas', 'kelas_id=id_kelas');
        $this->datatables->join('guru', 'guru_id=id_guru'); // Mengganti dosen menjadi guru
        $this->datatables->group_by('guru.nama_guru'); // Mengganti dosen.nama_dosen menjadi guru.nama_guru
        return $this->datatables->generate();
    }

    public function getAllGuru($id = null) // Mengganti getAllDosen menjadi getAllGuru
    {
        $this->db->select('guru_id'); // Mengganti dosen_id menjadi guru_id
        $this->db->from('kelas_guru'); // Mengganti kelas_dosen menjadi kelas_guru
        if ($id !== null) {
            $this->db->where_not_in('guru_id', [$id]); // Mengganti dosen_id menjadi guru_id
        }
        $guru = $this->db->get()->result(); // Mengganti $dosen menjadi $guru
        $id_guru = []; // Mengganti $id_dosen menjadi $id_guru
        foreach ($guru as $g) { // Mengganti $d menjadi $g
            $id_guru[] = $g->guru_id; // Mengganti dosen_id menjadi guru_id
        }
        if ($id_guru === []) {
            $id_guru = null;
        }

        $this->db->select('id_guru, nip, nama_guru, email, mapel_id'); // Mengganti id_dosen, nip, nama_dosen menjadi id_guru, nip, nama_guru
        $this->db->from('guru'); // Mengganti dosen menjadi guru
        $this->db->where_not_in('id_guru', $id_guru); // Mengganti id_dosen menjadi id_guru
        return $this->db->get()->result();
    }

    /**
     * Data Siswa - Modified for DataTables
     */
    private function _get_datatables_siswa_query($id_kelas = null) // Tambahkan parameter $id_kelas
    {
        $this->db->select('a.*, b.nama_kelas, c.id as id_user'); // Ambil id_user dari tabel users
        $this->db->from($this->table_siswa . ' a'); // Gunakan properti tabel siswa
        $this->db->join('kelas b', 'a.kelas_id = b.id_kelas', 'left'); // Menggunakan a.kelas_id sesuai struktur tabel siswa
        $this->db->join('users c', 'a.nisn = c.username', 'left'); // JOIN dengan tabel users untuk cek apakah sudah aktif

        if ($id_kelas !== null && $id_kelas !== 'all') { // Tambahkan kondisi filter kelas
            $this->db->where('a.kelas_id', $id_kelas); // Filter berdasarkan kolom kelas_id di tabel siswa
        }

        $i = 0;
        foreach ($this->column_search_siswa as $item) { // Gunakan properti search siswa
            if ($_POST['search']['value']) {
                if ($i === 0) {
                    $this->db->group_start();
                    $this->db->like($item, $_POST['search']['value']);
                } else {
                    $this->db->or_like($item, $_POST['search']['value']);
                }
                if (count($this->column_search_siswa) - 1 == $i) // Gunakan properti search siswa
                    $this->db->group_end();
            }
            $i++;
        }

        if (isset($_POST['order'])) {
            $this->db->order_by($this->column_order_siswa[$_POST['order']['0']['column']], $_POST['order']['0']['dir']); // Gunakan properti order siswa
        } else if (isset($this->order_default_siswa)) { // Gunakan properti order default siswa
            $order = $this->order_default_siswa;
            $this->db->order_by(key($order), $order[key($order)]);
        }
    }

    function get_datatables_siswa($id_kelas = null) // Ganti nama fungsi untuk siswa
    {
        $this->_get_datatables_siswa_query($id_kelas); // Kirim id_kelas ke query builder
        if ($_POST['length'] != -1)
            $this->db->limit($_POST['length'], $_POST['start']);
        return $this->db->get()->result();
    }

    function count_filtered_siswa($id_kelas = null) // Ganti nama fungsi untuk siswa
    {
        $this->_get_datatables_siswa_query($id_kelas); // Kirim id_kelas ke query builder
        return $this->db->get()->num_rows();
    }

    public function count_all_siswa($id_kelas = null) // Ganti nama fungsi untuk siswa
    {
        $this->db->from($this->table_siswa . ' a'); // Gunakan properti tabel siswa
        $this->db->join('kelas b', 'a.kelas_id = b.id_kelas', 'left');
        $this->db->join('users c', 'a.nisn = c.username', 'left');
        if ($id_kelas !== null && $id_kelas !== 'all') {
            $this->db->where('a.kelas_id', $id_kelas);
        }
        return $this->db->count_all_results();
    }
    
    public function getKelasByGuru($id) // Mengganti getKelasByDosen menjadi getKelasByGuru
    {
        $this->db->select('kelas.id_kelas');
        $this->db->from('kelas_guru'); // Mengganti kelas_dosen menjadi kelas_guru
        $this->db->join('kelas', 'kelas_guru.kelas_id=kelas.id_kelas'); // Mengganti kelas_dosen menjadi kelas_guru
        $this->db->where('guru_id', $id); // Mengganti dosen_id menjadi guru_id
        $query = $this->db->get()->result();
        return $query;
    }

    // Fungsi-fungsi terkait Jurusan Matkul dihapus karena tabel jurusan_matkul tidak ada lagi
    // public function getJurusanMatkul() { ... }
    // public function getMatkul() { ... }
    // public function getJurusanByIdMatkul() { ... }
}