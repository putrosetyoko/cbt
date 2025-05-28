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
     * Data Kelas
     */

    public function getDataKelas()
    {
        $this->datatables->select('id_kelas, nama_kelas');
        $this->datatables->from('kelas');
        $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 'id_kelas, nama_kelas');
        return $this->datatables->generate();
    }

    public function getKelasById($id)
    {
        $this->db->where_in('id_kelas', $id);
        $this->db->order_by('nama_kelas');
        $query = $this->db->get('kelas')->result();
        return $query;
    }

    public function getAllKelas()
    {
        $this->db->select('id_kelas, nama_kelas');
        $this->db->from('kelas');
        $this->db->order_by('nama_kelas');
        return $this->db->get()->result();
    }

    public function getDataSiswa($id_kelas = null) // Tambahkan parameter $id_kelas
    {
        $this->datatables->select('a.id_siswa, a.nama, a.nisn, a.jenis_kelamin, b.nama_kelas, c.email AS email');
        $this->datatables->select('(SELECT COUNT(id) FROM users WHERE username = a.nisn) AS ada');
        $this->datatables->from('siswa a');
        $this->datatables->join('kelas b', 'a.kelas_id=b.id_kelas');
        $this->datatables->join('users c', 'a.nisn = c.username', 'left');

        // Tambahkan filter kelas jika id_kelas diberikan dan bukan 'all'
        if ($id_kelas !== null && $id_kelas !== 'all') {
            $this->datatables->where('a.kelas_id', $id_kelas);
        }

        return $this->datatables->generate();
    }

    public function getSiswaById($id)
    {
        $this->db->select(
            'siswa.*, ' .
            'kelas.id_kelas, kelas.nama_kelas'
        );
        $this->db->from('siswa');
        $this->db->join('kelas', 'siswa.kelas_id = kelas.id_kelas');
        $this->db->where('siswa.id_siswa', $id);
        return $this->db->get()->row();
    }

    /**
     * Data Guru
     */

    public function getDataGuru($id_mapel = null) // Tambahkan parameter $id_mapel
    {
        $this->datatables->select('a.id_guru, a.nip, a.nama_guru, a.email, a.mapel_id, b.nama_mapel, (SELECT COUNT(id) FROM users WHERE username = a.nip) AS ada');
        $this->datatables->from('guru a');
        $this->datatables->join('mapel b', 'a.mapel_id=b.id_mapel');
        $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"></div>', 'id_guru');

        // Tambahkan filter mata pelajaran jika id_mapel diberikan dan bukan 'all'
        if ($id_mapel !== null && $id_mapel !== 'all') {
            $this->datatables->where('a.mapel_id', $id_mapel);
        }
        
        return $this->datatables->generate();
    }

    public function getGuruById($id)
    {
        $query = $this->db->get_where('guru', array('id_guru'=>$id));
        return $query->row();
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