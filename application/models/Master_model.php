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
        $this->datatables->select('k.id_kelas, k.nama_kelas, j.nama_jenjang')
        ->from('kelas k')
        ->join('jenjang j', 'j.id_jenjang = k.id_jenjang')
        ->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 'id_kelas')
        ->add_column('action', '<div class="text-center">
            <a href="'.base_url('kelas/edit/$1').'" class="btn btn-xs btn-warning">
                <i class="fa fa-pencil"></i>
            </a>
        </div>', 'id_kelas');
        
    return $this->datatables->generate();
    }

    public function getKelasByIds(array $arr_id_kelas) // Nama diubah agar lebih jelas
    {
        if (empty($arr_id_kelas)) {
            return [];
        }
        $this->db->select('k.id_kelas, k.nama_kelas, k.id_jenjang, j.nama_jenjang');
        $this->db->from('kelas k');
        $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
        $this->db->where_in('k.id_kelas', $arr_id_kelas);
        return $this->db->get()->result();
    }

    public function getKelasByIdSingle($id_kelas)
    {
        $this->db->select('k.id_kelas, k.nama_kelas, k.id_jenjang, j.nama_jenjang');
        $this->db->from('kelas k');
        $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
        $this->db->where('k.id_kelas', $id_kelas);
        return $this->db->get()->row();
    }

    public function getAllKelas() // Fungsi ini sudah ada, kita modifikasi
    {
        $this->db->select('k.id_kelas, k.nama_kelas, j.nama_jenjang');
        $this->db->from('kelas k');
        $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
        $this->db->order_by('j.nama_jenjang', 'ASC');
        $this->db->order_by('k.nama_kelas', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Data Siswa
     */

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
        $this->datatables->select('(SELECT COUNT(id) FROM users WHERE email = a.email) AS ada');
        $this->datatables->from('guru a');
        $this->datatables->join('users c', 'a.email = c.username', 'left');
        // $this->datatables->add_column('bulk_select', '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 'id_guru');
        return $this->datatables->generate();
    }

    public function getAllGuru() // Tanpa parameter wajib
    {
        $this->db->select('id_guru, nip, nama_guru'); // Ambil kolom yang dibutuhkan untuk dropdown
        $this->db->order_by('nama_guru', 'ASC');
        return $this->db->get('guru')->result();
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
     * Data untuk Penempatan Siswa di Kelas per Tahun Ajaran (DataTables)
     */
    public function getDataSiswaKelasAjaran($filter_tahun_ajaran = null, $filter_kelas = null)
    {
        $this->datatables->select('ska.id_ska, ta.nama_tahun_ajaran, j.nama_jenjang, k.nama_kelas, s.nisn, s.nama AS nama_siswa, s.id_siswa, k.id_kelas, ta.id_tahun_ajaran', FALSE); // Tambahkan j.nama_jenjang
        $this->datatables->from('siswa_kelas_ajaran ska');
        $this->datatables->join('siswa s', 'ska.siswa_id = s.id_siswa');
        $this->datatables->join('kelas k', 'ska.kelas_id = k.id_kelas');
        $this->datatables->join('tahun_ajaran ta', 'ska.id_tahun_ajaran = ta.id_tahun_ajaran');
        $this->datatables->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left'); // JOIN ke tabel jenjang

        if ($filter_tahun_ajaran && $filter_tahun_ajaran !== 'all') {
            $this->datatables->where('ska.id_tahun_ajaran', $filter_tahun_ajaran);
        }
        if ($filter_kelas && $filter_kelas !== 'all') {
            $this->datatables->where('ska.kelas_id', $filter_kelas);
        }
        
        $this->datatables->add_column(
            'bulk_select',
            '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>',
            'ska.id_ska' // Gunakan alias tabel jika ada ambiguitas, atau cukup id_ska jika sudah jelas
        );
        return $this->datatables->generate();
    }

    /**
     * Mendapatkan satu data penempatan siswa berdasarkan id_ska
     */
    public function getSiswaKelasAjaranById($id_ska)
    {
        $this->db->select('ska.*, s.nisn, s.nama AS nama_siswa, k.nama_kelas, ta.nama_tahun_ajaran');
        $this->db->from('siswa_kelas_ajaran ska');
        $this->db->join('siswa s', 'ska.siswa_id = s.id_siswa');
        $this->db->join('kelas k', 'ska.kelas_id = k.id_kelas');
        $this->db->join('tahun_ajaran ta', 'ska.id_tahun_ajaran = ta.id_tahun_ajaran');
        $this->db->where('ska.id_ska', $id_ska);
        return $this->db->get()->row();
    }

    /**
     * Mendapatkan siswa yang BELUM ditempatkan di kelas manapun pada tahun ajaran tertentu
     */
    public function getSiswaBelumDitempatkan($id_tahun_ajaran)
    {
        $this->db->select('s.id_siswa, s.nisn, s.nama');
        $this->db->from('siswa s');
        $this->db->where("s.id_siswa NOT IN (SELECT ska.siswa_id FROM siswa_kelas_ajaran ska WHERE ska.id_tahun_ajaran = ".$this->db->escape($id_tahun_ajaran).")", NULL, FALSE);
        $this->db->order_by('s.nama', 'ASC');
        return $this->db->get()->result();
    }
    
    /**
     * Mendapatkan semua siswa (untuk kasus edit dimana siswa sudah ditempatkan)
     */
    public function getAllSiswaSimple() // Untuk dropdown jika diperlukan
    {
        $this->db->select('id_siswa, nisn, nama');
        $this->db->order_by('nama', 'ASC');
        return $this->db->get('siswa')->result();
    }


    /**
     * Cek apakah siswa sudah ditempatkan di suatu kelas pada tahun ajaran tertentu
     * Digunakan untuk validasi sebelum insert/update untuk menjaga unique constraint (siswa_id, id_tahun_ajaran)
     * @param int $siswa_id
     * @param int $id_tahun_ajaran
     * @param int $current_id_ska (Opsional, untuk diabaikan saat edit record yang sama)
     * @return bool
     */
    public function isSiswaAssignedToYear($siswa_id, $id_tahun_ajaran, $current_id_ska = null)
    {
        $this->db->where('siswa_id', $siswa_id);
        $this->db->where('id_tahun_ajaran', $id_tahun_ajaran);
        if ($current_id_ska !== null) {
            $this->db->where('id_ska !=', $current_id_ska);
        }
        $query = $this->db->get('siswa_kelas_ajaran');
        return $query->num_rows() > 0;
    }

    /**
     * Data Penugasan Guru untuk DataTables
     */
    public function getDataPenugasanGuru($filter_tahun_ajaran = null, $filter_guru = null, $filter_mapel = null, $filter_kelas = null) 
    {
        // Tambahkan kolom untuk bulk_select
        $this->datatables->select('
            gmka.id_gmka,
            ta.nama_tahun_ajaran,
            g.nama_guru,
            m.nama_mapel,
            CONCAT(j.nama_jenjang, " ", k.nama_kelas) as kelas_info
        ', FALSE);
        
        $this->datatables->from('guru_mapel_kelas_ajaran gmka');
        $this->datatables->join('tahun_ajaran ta', 'ta.id_tahun_ajaran = gmka.id_tahun_ajaran');
        $this->datatables->join('guru g', 'g.id_guru = gmka.guru_id');
        $this->datatables->join('mapel m', 'm.id_mapel = gmka.mapel_id');
        $this->datatables->join('kelas k', 'k.id_kelas = gmka.kelas_id');
        $this->datatables->join('jenjang j', 'j.id_jenjang = k.id_jenjang');

        // Perbaikan filter
        if ($filter_tahun_ajaran !== null && $filter_tahun_ajaran !== 'all') {
            $this->datatables->where('gmka.id_tahun_ajaran', $filter_tahun_ajaran);
        }
        
        if ($filter_guru !== null && $filter_guru !== 'all') {
            $this->datatables->where('gmka.guru_id', $filter_guru);
        }
        
        if ($filter_mapel !== null && $filter_mapel !== 'all') {
            $this->datatables->where('gmka.mapel_id', $filter_mapel);
        }
        
        if ($filter_kelas !== null && $filter_kelas !== 'all') {
            $this->datatables->where('gmka.kelas_id', $filter_kelas);
        }

        // Tambahkan kolom bulk_select
        $this->datatables->add_column('bulk_select', 
            '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="$1"/></div>', 
            'id_gmka'
        );

        // Ganti order_by dengan pengaturan di DataTables JS
        return $this->datatables->generate();
    }

    /**
     * Mendapatkan satu data penugasan guru berdasarkan id_gmka
     */
    public function getPenugasanGuruById($id_gmka)
    {
        $this->db->select('gmka.*, g.nama_guru, m.nama_mapel, k.nama_kelas, ta.nama_tahun_ajaran, j.nama_jenjang');
        $this->db->from('guru_mapel_kelas_ajaran gmka');
        $this->db->join('guru g', 'gmka.guru_id = g.id_guru');
        $this->db->join('mapel m', 'gmka.mapel_id = m.id_mapel');
        $this->db->join('kelas k', 'gmka.kelas_id = k.id_kelas');
        $this->db->join('tahun_ajaran ta', 'gmka.id_tahun_ajaran = ta.id_tahun_ajaran');
        $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
        $this->db->where('gmka.id_gmka', $id_gmka);
        return $this->db->get()->row();
    }

    /**
     * Cek apakah kombinasi penugasan sudah ada (untuk validasi unique constraint)
     * @param int $guru_id
     * @param int $mapel_id
     * @param int $kelas_id
     * @param int $id_tahun_ajaran
     * @param int $current_id_gmka (Opsional, untuk diabaikan saat edit record yang sama)
     * @return bool
     */
    public function isPenugasanExists($guru_id, $mapel_id, $kelas_id, $id_tahun_ajaran, $current_id_gmka = null)
    {
        $this->db->where('guru_id', $guru_id);
        $this->db->where('mapel_id', $mapel_id);
        $this->db->where('kelas_id', $kelas_id);
        $this->db->where('id_tahun_ajaran', $id_tahun_ajaran);
        if ($current_id_gmka !== null) {
            $this->db->where('id_gmka !=', $current_id_gmka);
        }
        $query = $this->db->get('guru_mapel_kelas_ajaran');
        return $query->num_rows() > 0;
    }

}