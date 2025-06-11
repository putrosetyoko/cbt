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

    public function delete($table, $data, $pk = null)
    {
        if (is_array($data)) {
            if ($pk !== null) {
                $this->db->where_in($pk, $data);
            } else {
                log_message('error', 'Master_model::delete() called with array data but no primary key (pk) specified.');
                return false;
            }
        } else {
            $this->db->where($pk, $data);
        }
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

    public function getTahunAjaranAktif()
    {
        $this->db->where('status', 'aktif');
        return $this->db->get('tahun_ajaran')->row();
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

    public function getSiswaDetailByNisnTahunAjaran($nisn, $id_tahun_ajaran_aktif)
    {
        if (empty($nisn) || empty($id_tahun_ajaran_aktif)) {
            return null;
        }

        $this->db->select('s.id_siswa, s.nama as nama_siswa, s.nisn, s.jenis_kelamin, k.id_kelas, k.nama_kelas, j.id_jenjang, j.nama_jenjang, ska.id_tahun_ajaran, ta.nama_tahun_ajaran');
        $this->db->from('siswa s');
        $this->db->join('siswa_kelas_ajaran ska', 's.id_siswa = ska.siswa_id');
        $this->db->join('kelas k', 'ska.kelas_id = k.id_kelas');
        $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
        $this->db->join('tahun_ajaran ta', 'ska.id_tahun_ajaran = ta.id_tahun_ajaran');
        $this->db->where('s.nisn', $nisn);
        $this->db->where('ska.id_tahun_ajaran', $id_tahun_ajaran_aktif);
        
        $query = $this->db->get();
        return $query->row();
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

    public function count_siswa_by_guru_in_tahun_ajaran($guru_id, $id_tahun_ajaran)
    {
        $this->db->select('COUNT(DISTINCT sk.siswa_id) as total_siswa');
        $this->db->from('guru_mapel_kelas_ajaran gmka');
        $this->db->join('siswa_kelas_ajaran sk', 'gmka.kelas_id = sk.kelas_id AND gmka.id_tahun_ajaran = sk.id_tahun_ajaran');
        $this->db->where('gmka.guru_id', $guru_id);
        $this->db->where('gmka.id_tahun_ajaran', $id_tahun_ajaran);
        $query = $this->db->get();
        $result = $query->row();
        return $result ? (int)$result->total_siswa : 0;
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

    public function getMapelPJByGuruTahun($guru_id, $id_tahun_ajaran)
    {
        if (empty($guru_id) || empty($id_tahun_ajaran)) {
            return null;
        }

        $this->db->select('m.id_mapel, m.nama_mapel, pjsa.keterangan AS keterangan_pj');
        $this->db->from('penanggung_jawab_soal_ajaran pjsa');
        $this->db->join('mapel m', 'pjsa.mapel_id = m.id_mapel');
        $this->db->where('pjsa.guru_id', $guru_id);
        $this->db->where('pjsa.id_tahun_ajaran', $id_tahun_ajaran);
        return $this->db->get()->row(); // Mengembalikan satu baris data mapel PJ
    }

    public function getMapelDiajarGuru($guru_id, $id_tahun_ajaran)
    {
        if (empty($guru_id) || empty($id_tahun_ajaran)) {
            log_message('debug', 'Master_model (getMapelDiajarGuru) - Guru ID ('.$guru_id.') atau TA ID ('.$id_tahun_ajaran.') kosong.');
            return []; // Kembalikan array kosong jika parameter tidak lengkap
        }

        $this->db->select('DISTINCT(gmka.mapel_id)'); // Hanya pilih kolom mapel_id dari tabel gmka
        $this->db->from('guru_mapel_kelas_ajaran gmka');
        $this->db->where('gmka.guru_id', $guru_id);
        $this->db->where('gmka.id_tahun_ajaran', $id_tahun_ajaran);
        $query = $this->db->get();

        log_message('debug', 'Master_model (getMapelDiajarGuru) - Last Query: ' . $this->db->last_query());

        $result_objects = $query->result();
        $flat_mapel_ids = array_column($result_objects, 'mapel_id');
        
        log_message('debug', 'Master_model (getMapelDiajarGuru) - GuruID: '.$guru_id.', TA_ID: '.$id_tahun_ajaran.', Returned flat_mapel_ids: '.print_r($flat_mapel_ids, true));
        return $flat_mapel_ids;
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
        // Modifikasi SELECT untuk menggabungkan kelas dalam satu baris
        $this->datatables->select('
            gmka.id_gmka,
            ta.nama_tahun_ajaran,
            g.nama_guru,
            m.nama_mapel,
            g.id_guru as guru_id,
            m.id_mapel as mapel_id,
            ta.id_tahun_ajaran,
            GROUP_CONCAT(CONCAT(j.nama_jenjang, " ", k.nama_kelas)) as kelas_info,
            GROUP_CONCAT(JSON_OBJECT(
                "id_kelas", k.id_kelas,
                "nama_kelas", CONCAT(j.nama_jenjang, " ", k.nama_kelas)
            )) as kelas_data
        ', FALSE);
        
        $this->datatables->from('guru_mapel_kelas_ajaran gmka');
        $this->datatables->join('tahun_ajaran ta', 'ta.id_tahun_ajaran = gmka.id_tahun_ajaran');
        $this->datatables->join('guru g', 'g.id_guru = gmka.guru_id');
        $this->datatables->join('mapel m', 'm.id_mapel = gmka.mapel_id');
        $this->datatables->join('kelas k', 'k.id_kelas = gmka.kelas_id');
        $this->datatables->join('jenjang j', 'j.id_jenjang = k.id_jenjang');

        // Tambahkan GROUP BY untuk mengelompokkan berdasarkan guru, mapel, dan tahun ajaran
        $this->datatables->group_by('gmka.guru_id, gmka.mapel_id, gmka.id_tahun_ajaran');

        // Filter tetap sama
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

        return $this->datatables->generate();
    }

    /**
     * Mendapatkan satu data penugasan guru berdasarkan id_gmka
     */
    public function getPenugasanGuruById($id_gmka)
    {
        // Ambil referensi data dari id_gmka yang dipilih
        $ref = $this->db->get_where('guru_mapel_kelas_ajaran', ['id_gmka' => $id_gmka])->row();
        
        if (!$ref) return null;

        $this->db->select('
            gmka.id_gmka,
            gmka.guru_id,
            gmka.mapel_id,
            gmka.id_tahun_ajaran,
            g.nama_guru,
            m.nama_mapel,
            ta.nama_tahun_ajaran,
            GROUP_CONCAT(k.id_kelas) as kelas_id,
            GROUP_CONCAT(CONCAT(j.nama_jenjang, " ", k.nama_kelas)) as kelas_info
        ');
        $this->db->from('guru_mapel_kelas_ajaran gmka');
        $this->db->join('guru g', 'gmka.guru_id = g.id_guru');
        $this->db->join('mapel m', 'gmka.mapel_id = m.id_mapel');
        $this->db->join('kelas k', 'gmka.kelas_id = k.id_kelas');
        $this->db->join('tahun_ajaran ta', 'gmka.id_tahun_ajaran = ta.id_tahun_ajaran');
        $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
        
        // Gunakan data referensi untuk filter
        $this->db->where([
            'gmka.guru_id' => $ref->guru_id,
            'gmka.mapel_id' => $ref->mapel_id,
            'gmka.id_tahun_ajaran' => $ref->id_tahun_ajaran
        ]);
        
        $this->db->group_by('gmka.guru_id, gmka.mapel_id, gmka.id_tahun_ajaran');
        
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

    /**
     * Data PJ Soal
     */
    public function getDataPJSoal($filter_tahun_ajaran = null, $filter_mapel = null)
    {
        $this->datatables->select(
            'pjsa.id_pjsa, ta.nama_tahun_ajaran, m.nama_mapel, g.nip AS nip_guru, g.nama_guru, pjsa.keterangan, DATE_FORMAT(pjsa.ditetapkan_pada, "%d-%m-%Y %H:%i") as ditetapkan_pada, '.
            'pjsa.mapel_id, pjsa.guru_id, pjsa.id_tahun_ajaran', 
            FALSE
        );
        $this->datatables->from('penanggung_jawab_soal_ajaran pjsa');
        $this->datatables->join('tahun_ajaran ta', 'pjsa.id_tahun_ajaran = ta.id_tahun_ajaran');
        $this->datatables->join('mapel m', 'pjsa.mapel_id = m.id_mapel');
        $this->datatables->join('guru g', 'pjsa.guru_id = g.id_guru');

        if ($filter_tahun_ajaran && $filter_tahun_ajaran !== 'all' && !empty($filter_tahun_ajaran)) {
            $this->datatables->where('pjsa.id_tahun_ajaran', $filter_tahun_ajaran);
        }
        if ($filter_mapel && $filter_mapel !== 'all' && !empty($filter_mapel)) {
            $this->datatables->where('pjsa.mapel_id', $filter_mapel);
        }
        
        // Kolom aksi akan dirender oleh JavaScript
        return $this->datatables->generate();
    }

    /**
     * Mendapatkan satu data PJ Soal berdasarkan id_pjsa
     * @param int $id_pjsa
     * @return object|null
     */
    public function getPJSoalById($id_pjsa)
    {
        $this->db->select('pjsa.*, ta.nama_tahun_ajaran, m.nama_mapel, g.nama_guru, g.nip');
        $this->db->from('penanggung_jawab_soal_ajaran pjsa');
        $this->db->join('tahun_ajaran ta', 'pjsa.id_tahun_ajaran = ta.id_tahun_ajaran', 'left');
        $this->db->join('mapel m', 'pjsa.mapel_id = m.id_mapel', 'left');
        $this->db->join('guru g', 'pjsa.guru_id = g.id_guru', 'left');
        $this->db->where('pjsa.id_pjsa', $id_pjsa);
        return $this->db->get()->row();
    }
    
    /**
     * Mendapatkan data PJ Soal berdasarkan mapel_id dan id_tahun_ajaran
     * @param int $mapel_id
     * @param int $id_tahun_ajaran
     * @return object|null
     */
    public function getPJSoalByMapelTahun($mapel_id, $id_tahun_ajaran)
    {
        $this->db->select('pjsa.*, g.nama_guru, g.nip'); // Ambil juga info guru PJ
        $this->db->from('penanggung_jawab_soal_ajaran pjsa');
        $this->db->join('guru g', 'pjsa.guru_id = g.id_guru', 'left');
        $this->db->where('pjsa.mapel_id', $mapel_id);
        $this->db->where('pjsa.id_tahun_ajaran', $id_tahun_ajaran);
        return $this->db->get()->row();
    }

    /**
     * Mendapatkan data PJ Soal berdasarkan guru_id dan id_tahun_ajaran
     * @param int $guru_id
     * @param int $id_tahun_ajaran
     * @return object|null
     */
    public function getPJSoalByGuruTahun($guru_id, $id_tahun_ajaran)
    {
        $this->db->select('pjsa.*, m.nama_mapel'); // Ambil juga info mapel
        $this->db->from('penanggung_jawab_soal_ajaran pjsa');
        $this->db->join('mapel m', 'pjsa.mapel_id = m.id_mapel', 'left');
        $this->db->where('pjsa.guru_id', $guru_id);
        $this->db->where('pjsa.id_tahun_ajaran', $id_tahun_ajaran);
        return $this->db->get()->row();
    }

    /**
     * Mendapatkan guru yang BELUM menjadi PJ Soal di tahun ajaran tertentu,
     * atau adalah guru PJ saat ini (untuk edit).
     * @param int $id_tahun_ajaran
     * @param int|null $current_editing_guru_id (Guru ID yang sedang diedit, agar tetap muncul di list)
     * @return array
     */
    public function getGuruAvailableForPJ($id_tahun_ajaran, $current_editing_guru_id = null)
    {
        // Ambil semua guru dulu
        $all_guru_query = $this->db->select('id_guru, nip, nama_guru')->order_by('nama_guru', 'ASC')->get('guru');
        $all_guru = $all_guru_query->result();

        // Ambil guru yang sudah jadi PJ di tahun ajaran ini
        $this->db->select('guru_id');
        $this->db->from('penanggung_jawab_soal_ajaran');
        $this->db->where('id_tahun_ajaran', $id_tahun_ajaran);
        if ($current_editing_guru_id !== null) {
            $this->db->where('guru_id !=', $current_editing_guru_id); // Kecualikan guru yang sedang diedit dari daftar "sudah jadi PJ"
        }
        $assigned_gurus_query = $this->db->get();
        $assigned_guru_ids = array_column($assigned_gurus_query->result_array(), 'guru_id');

        $available_gurus = [];
        foreach ($all_guru as $guru) {
            if (!in_array($guru->id_guru, $assigned_guru_ids) || $guru->id_guru == $current_editing_guru_id) {
                $available_gurus[] = $guru;
            }
        }
        return $available_gurus;
    }
    
    /**
     * Mendapatkan mapel yang BELUM memiliki PJ Soal di tahun ajaran tertentu,
     * atau adalah mapel yang sedang diedit PJ-nya.
     * @param int $id_tahun_ajaran
     * @param int|null $current_editing_mapel_id (Mapel ID yang sedang diedit, agar tetap muncul di list)
     * @return array
     */
    public function getMapelAvailableForPJ($id_tahun_ajaran, $current_editing_mapel_id = null)
    {
        // Ambil semua mapel dulu
        $all_mapel_query = $this->db->select('id_mapel, nama_mapel')->order_by('nama_mapel', 'ASC')->get('mapel');
        $all_mapel = $all_mapel_query->result();

        // Ambil mapel yang sudah punya PJ di tahun ajaran ini
        $this->db->select('mapel_id');
        $this->db->from('penanggung_jawab_soal_ajaran');
        $this->db->where('id_tahun_ajaran', $id_tahun_ajaran);
        if ($current_editing_mapel_id !== null) {
            $this->db->where('mapel_id !=', $current_editing_mapel_id); // Kecualikan mapel yang sedang diedit
        }
        $assigned_mapels_query = $this->db->get();
        $assigned_mapel_ids = array_column($assigned_mapels_query->result_array(), 'mapel_id');

        $available_mapels = [];
        foreach ($all_mapel as $mapel) {
            if (!in_array($mapel->id_mapel, $assigned_mapel_ids) || $mapel->id_mapel == $current_editing_mapel_id) {
                $available_mapels[] = $mapel;
            }
        }
        return $available_mapels;
    }

    /**
     * Mengambil daftar ID kelas yang diajar oleh seorang guru pada tahun ajaran tertentu.
     * Digunakan untuk filter hasil ujian siswa bagi guru.
     *
     * @param int $id_guru ID guru
     * @param int $id_tahun_ajaran ID tahun ajaran
     * @return array Array berisi ID kelas
     */
    public function getKelasDiajarGuru($id_guru, $id_tahun_ajaran)
    {
        $this->db->select('kelas_id');
        $this->db->from('guru_mapel_kelas_ajaran');
        $this->db->where('guru_id', $id_guru);
        $this->db->where('id_tahun_ajaran', $id_tahun_ajaran);
        $this->db->distinct();
        $query = $this->db->get();
        return array_column($query->result_array(), 'kelas_id');
    }

    /**
     * Mengambil daftar guru yang mengajar mapel di kelas tertentu pada tahun ajaran tertentu.
     *
     * @param int $mapel_id ID Mata Pelajaran
     * @param int $kelas_id ID Kelas
     * @param int $id_tahun_ajaran ID Tahun Ajaran
     * @return array Array objek guru {id_guru, nama_guru}
     */
    public function getGuruMengajarMapelKelas($mapel_id, $kelas_id, $id_tahun_ajaran)
    {
        if (empty($mapel_id) || empty($kelas_id) || empty($id_tahun_ajaran)) {
            return [];
        }

        // PERBAIKAN UTAMA: Tambahkan FALSE sebagai parameter kedua di select()
        $this->db->select('DISTINCT g.id_guru, g.nama_guru', FALSE); // <-- Tambahkan FALSE di sini
        $this->db->from('guru_mapel_kelas_ajaran gmka');
        $this->db->join('guru g', 'gmka.guru_id = g.id_guru');
        $this->db->where('gmka.mapel_id', $mapel_id);
        $this->db->where('gmka.kelas_id', $kelas_id);
        $this->db->where('gmka.id_tahun_ajaran', $id_tahun_ajaran);
        $this->db->order_by('g.nama_guru', 'ASC');

        $query = $this->db->get();

        // Tambahkan logging untuk debugging
        log_message('debug', 'Master_model (getGuruMengajarMapelKelas) - Query: ' . $this->db->last_query());
        log_message('debug', 'Master_model (getGuruMengajarMapelKelas) - Result: ' . print_r($query->result(), true));

        return $query->result();
    }

}