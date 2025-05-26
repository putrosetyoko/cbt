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
        // Menghapus join ke tabel jurusan karena tidak lagi relevan
        $this->datatables->select('id_kelas, nama_kelas');
        $this->datatables->from('kelas');
        // $this->datatables->join('jurusan', 'jurusan_id=id_jurusan'); // Dihapus
        $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 'id_kelas, nama_kelas'); // Menyesuaikan kolom
        return $this->datatables->generate();
    }

    public function getKelasById($id)
    {
        $this->db->where_in('id_kelas', $id);
        $this->db->order_by('nama_kelas');
        $query = $this->db->get('kelas')->result();
        return $query;
    }

    // Fungsi-fungsi terkait Jurusan dihapus karena tabel jurusan tidak ada lagi
    // public function getDataJurusan() { ... }
    // public function getJurusanById() { ... }
    // public function getJurusan() { ... }
    // public function getAllJurusan() { ... }
    // public function getKelasByJurusan() { ... }

    /**
     * Data Siswa
     */

    public function getDataSiswa() // Mengganti getDataMahasiswa menjadi getDataSiswa
    {
        $this->datatables->select('a.id_siswa, a.nama, a.nisn, a.jenis_kelamin, b.nama_kelas'); // Menyesuaikan kolom: id_siswa, nisn, jenis_kelamin, tanpa email dan jurusan
        $this->datatables->select('(SELECT COUNT(id) FROM users WHERE username = a.nisn) AS ada'); // Mengubah a.nim ke a.nisn dan menghapus a.email
        $this->datatables->from('siswa a'); // Mengganti mahasiswa a menjadi siswa a
        $this->datatables->join('kelas b', 'a.kelas_id=b.id_kelas');
        // $this->datatables->join('jurusan c', 'b.jurusan_id=c.id_jurusan'); // Dihapus karena siswa tidak lagi terhubung ke jurusan
        return $this->datatables->generate();
    }

    public function getSiswaById($id) // Mengganti getMahasiswaById menjadi getSiswaById
    {
        $this->db->select(
            'siswa.*, ' . // Mengambil semua kolom dari tabel siswa
            'kelas.id_kelas, kelas.nama_kelas' // Pilih kolom dari tabel kelas
            // Kolom jurusan dihapus
        );
        $this->db->from('siswa'); // Mengganti mahasiswa menjadi siswa
        $this->db->join('kelas', 'siswa.kelas_id = kelas.id_kelas');
        // $this->db->join('jurusan', 'mahasiswa.jurusan_id = jurusan.id_jurusan'); // Dihapus
        $this->db->where('siswa.id_siswa', $id); // Mengganti id_mahasiswa menjadi id_siswa
        return $this->db->get()->row();
    }

    /**
     * Data Guru
     */

    public function getDataGuru() // Mengganti getDataDosen menjadi getDataGuru
    {
        $this->datatables->select('a.id_guru,a.nip, a.nama_guru, a.email, a.mapel_id, b.nama_mapel, (SELECT COUNT(id) FROM users WHERE username = a.nip) AS ada'); // Menyesuaikan kolom email dan mengganti matkul/nama_matkul menjadi mapel/nama_mapel
        $this->datatables->from('guru a'); // Mengganti dosen a menjadi guru a
        $this->datatables->join('mapel b', 'a.mapel_id=b.id_mapel'); // Mengganti matkul b menjadi mapel b
        $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"></div>', 'id_guru'); // Changed id_dosen
        return $this->datatables->generate();
    }

    public function getGuruById($id) // Mengganti getDosenById menjadi getGuruById
    {
        $query = $this->db->get_where('guru', array('id_guru'=>$id)); // Mengganti dosen menjadi guru dan id_dosen menjadi id_guru
        return $query->row();
    }

    /**
     * Data Mapel
     */

    public function getDataMapel() // Mengganti getDataMatkul menjadi getDataMapel
    {
        $this->datatables->select('id_mapel, nama_mapel'); // Mengganti id_matkul, menjadi id_mapel, nama_mapel
        $this->datatables->from('mapel'); // Mengganti matkul menjadi mapel
        return $this->datatables->generate();
    }

    public function getAllMapel() // Mengganti getAllMatkul menjadi getAllMapel
    {
        return $this->db->get('mapel')->result(); // Mengganti matkul menjadi mapel
    }

    public function getMapelById($id, $single = false) // Mengganti getMatkulById menjadi getMapelById
    {
        if ($single === false) {
            $this->db->where_in('id_mapel', $id); // Mengganti id_matkul menjadi id_mapel
            $this->db->order_by('nama_mapel'); // Mengganti nama_matkul menjadi nama_mapel
            $query = $this->db->get('mapel')->result(); // Mengganti matkul menjadi mapel
        } else {
            $query = $this->db->get_where('mapel', array('id_mapel'=>$id))->row(); // Mengganti matkul menjadi mapel dan id_matkul menjadi id_mapel
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
    
    public function getAllKelas()
    {
        $this->db->select('id_kelas, nama_kelas'); // Menghapus nama_jurusan
        $this->db->from('kelas');
        // $this->db->join('jurusan', 'jurusan_id=id_jurusan'); // Dihapus
        $this->db->order_by('nama_kelas');
        return $this->db->get()->result();
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