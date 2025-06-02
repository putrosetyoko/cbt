<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ujian_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // $this->load->database(); // Biasanya sudah autoload
    }

    /**
     * Mengambil data ujian untuk DataTables server-side.
     * @param array $filters Filter dari UI (id_tahun_ajaran, mapel_id, id_jenjang_target)
     * @param array $guru_context Info tentang user yang login 
     * (is_admin, is_guru, id_guru, id_mapel_pj, mapel_ids_diajar)
     * @return string JSON yang dihasilkan oleh library DataTables
     */
    public function getUjianDatatables($filters = [], $guru_context = []) {
        $this->datatables->select(
            'u.id_ujian, u.nama_ujian, m.nama_mapel, j.nama_jenjang AS nama_jenjang_target, '.
            'g.nama_guru AS pembuat_ujian, u.jumlah_soal, u.waktu, u.token, '.
            'DATE_FORMAT(u.tgl_mulai, "%d-%m-%Y %H:%i") as tgl_mulai_formatted, '.
            'CASE u.aktif WHEN "Y" THEN "Aktif" ELSE "Tidak Aktif" END as status_aktif, '. // Kolom status yang lebih deskriptif
            'ta.nama_tahun_ajaran, u.guru_id AS id_pembuat_ujian, u.mapel_id AS id_mapel_ujian' // Tambahan untuk JS & hak akses
        , FALSE);
        $this->datatables->from('m_ujian u');
        $this->datatables->join('mapel m', 'u.mapel_id = m.id_mapel');
        $this->datatables->join('guru g', 'u.guru_id = g.id_guru'); // guru_id di m_ujian adalah PJ Pembuat
        $this->datatables->join('jenjang j', 'u.id_jenjang_target = j.id_jenjang', 'left');
        $this->datatables->join('tahun_ajaran ta', 'u.id_tahun_ajaran = ta.id_tahun_ajaran', 'left');

        // Apply UI Filters
        if (!empty($filters['id_tahun_ajaran']) && $filters['id_tahun_ajaran'] !== 'all') {
            $this->datatables->where('u.id_tahun_ajaran', $filters['id_tahun_ajaran']);
        }
        if (!empty($filters['mapel_id']) && $filters['mapel_id'] !== 'all') {
            $this->datatables->where('u.mapel_id', $filters['mapel_id']);
        }
        if (!empty($filters['id_jenjang_target']) && $filters['id_jenjang_target'] !== 'all') {
            $this->datatables->where('u.id_jenjang_target', $filters['id_jenjang_target']);
        }

        // Apply Role-Based Filters
        if (!$guru_context['is_admin']) { // Jika bukan admin, maka dia guru
            if ($guru_context['is_guru'] && $guru_context['id_mapel_pj']) { // Guru PJ Soal
                // PJ Soal hanya melihat ujian untuk mapel yang di-PJ-kannya
                $this->datatables->where('u.mapel_id', $guru_context['id_mapel_pj']);
                // Opsional: PJ Soal hanya melihat ujian yang dia buat sendiri
                // $this->datatables->where('u.guru_id', $guru_context['id_guru']);
            } elseif ($guru_context['is_guru'] && !empty($guru_context['mapel_ids_diajar'])) { // Guru Non-PJ
                // Guru Non-PJ melihat ujian yang mapel_id-nya ada di daftar mapel_ids_diajar
                $this->datatables->where_in('u.mapel_id', $guru_context['mapel_ids_diajar']);
                // TODO: Implementasi filter jenjang target berdasarkan kelas yang diajar Guru Non-PJ jika diperlukan.
                // Ini memerlukan query tambahan untuk mendapatkan jenjang_id dari kelas yang diajar guru,
                // lalu $this->datatables->where_in('u.id_jenjang_target', $jenjang_ids_diajar_guru_non_pj);
            } else {
                // Guru tapi tidak teridentifikasi sebagai PJ atau tidak punya mapel diajar
                $this->datatables->where('1', '0'); // Trik agar tidak ada data yang tampil
            }
        }
        // Jika Admin, bisa melihat semua ujian (tidak ada filter peran tambahan).
        
        // Menambahkan kolom 'aksi' yang akan di-render di JavaScript (view)
        // berdasarkan hak akses pengguna dan ID ujian.
        // $this->datatables->add_column('aksi_placeholder', '$1', 'u.id_ujian');


        return $this->datatables->generate();
    }

    /**
     * Menyimpan data ujian baru ke tabel m_ujian.
     */
    public function create_ujian($data) {
        $this->db->insert('m_ujian', $data);
        return $this->db->insert_id() ? $this->db->insert_id() : false;
    }

    /**
     * Mengambil detail satu ujian berdasarkan ID.
     */
    public function get_ujian_by_id($id_ujian) {
        $this->db->select('u.*, m.nama_mapel, j.nama_jenjang AS nama_jenjang_target, g.nama_guru AS nama_pembuat_ujian, ta.nama_tahun_ajaran');
        $this->db->from('m_ujian u');
        $this->db->join('mapel m', 'u.mapel_id = m.id_mapel', 'left');
        $this->db->join('jenjang j', 'u.id_jenjang_target = j.id_jenjang', 'left');
        $this->db->join('guru g', 'u.guru_id = g.id_guru', 'left');
        $this->db->join('tahun_ajaran ta', 'u.id_tahun_ajaran = ta.id_tahun_ajaran', 'left');
        $this->db->where('u.id_ujian', $id_ujian);
        return $this->db->get()->row();
    }

    /**
     * Memperbarui data ujian.
     */
    public function update_ujian($id_ujian, $data) {
        $this->db->where('id_ujian', $id_ujian);
        return $this->db->update('m_ujian', $data);
    }

    /**
     * Menghapus data ujian. Relasi di d_ujian_soal akan terhapus oleh ON DELETE CASCADE.
     */
    public function delete_ujian($id_ujian) {
        // Pastikan juga menghapus dari h_ujian jika diperlukan atau set ON DELETE CASCADE di sana
        // $this->db->where('ujian_id', $id_ujian);
        // $this->db->delete('h_ujian'); // Contoh jika perlu hapus manual

        $this->db->where('id_ujian', $id_ujian);
        return $this->db->delete('m_ujian');
    }

    // --- Fungsi untuk Manajemen Soal dalam Ujian (Tabel d_ujian_soal) ---

    /**
     * Mengambil soal dari bank soal yang relevan (mapel & jenjang) dan belum ada di ujian.
     */
    public function get_soal_bank_for_ujian($mapel_id, $id_jenjang_target, $exclude_soal_ids = []) {
        $this->db->select('id_soal, SUBSTRING(REPLACE(REPLACE(soal, \'<p>\', \'\'), \'</p>\', \'\'), 1, 100) as cuplikan_soal, jawaban, bobot'); // Ambil hanya cuplikan
        $this->db->from('tb_soal');
        $this->db->where('mapel_id', $mapel_id);
        $this->db->where('id_jenjang', $id_jenjang_target);
        if (!empty($exclude_soal_ids) && is_array($exclude_soal_ids)) {
            $this->db->where_not_in('id_soal', $exclude_soal_ids);
        }
        $this->db->order_by('id_soal', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Mengambil ID soal yang sudah ada di suatu ujian.
     * @return array Array ID soal yang flat.
     */
    public function get_assigned_soal_ids($id_ujian) {
        $this->db->select('id_soal');
        $this->db->from('d_ujian_soal');
        $this->db->where('id_ujian', $id_ujian);
        $query = $this->db->get();
        return array_column($query->result_array(), 'id_soal');
    }

    /**
     * Cek apakah sebuah soal sudah ada dalam ujian tertentu.
     */
    public function is_soal_in_ujian($id_ujian, $id_soal) {
        $this->db->where('id_ujian', $id_ujian);
        $this->db->where('id_soal', $id_soal);
        $query = $this->db->get('d_ujian_soal');
        return $query->num_rows() > 0;
    }

    /**
     * Mendapatkan nomor urut terakhir untuk soal dalam ujian.
     */
    public function get_last_nomor_urut($id_ujian) {
        $this->db->select_max('nomor_urut', 'max_urut');
        $this->db->where('id_ujian', $id_ujian);
        $query = $this->db->get('d_ujian_soal')->row();
        return $query && $query->max_urut !== null ? (int)$query->max_urut : 0;
    }

    /**
     * Menyimpan batch soal ke d_ujian_soal.
     * $data_to_insert adalah array of arrays: [['id_ujian'=>x, 'id_soal'=>y, 'nomor_urut'=>z], ...]
     */
    public function assign_batch_soal_to_ujian($data_to_insert) {
        if (empty($data_to_insert)) {
            return true;
        }
        return $this->db->insert_batch('d_ujian_soal', $data_to_insert);
    }

    /**
     * Mengambil daftar soal yang sudah ada di sebuah ujian beserta detailnya.
     */
    public function get_assigned_soal_for_ujian($id_ujian) {
        $this->db->select('dus.id_d_ujian_soal, dus.id_soal, dus.nomor_urut, SUBSTRING(REPLACE(REPLACE(ts.soal, \'<p>\', \'\'), \'</p>\', \'\'), 1, 100) as cuplikan_soal_bank, ts.jawaban as kunci_bank, ts.bobot');
        $this->db->from('d_ujian_soal dus');
        $this->db->join('tb_soal ts', 'dus.id_soal = ts.id_soal');
        $this->db->where('dus.id_ujian', $id_ujian);
        $this->db->order_by('dus.nomor_urut', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Menghapus satu soal spesifik dari ujian berdasarkan id_d_ujian_soal.
     */
    public function remove_soal_from_ujian($id_ujian, $id_d_ujian_soal) {
        $this->db->where('id_ujian', $id_ujian); // Tambahan keamanan
        $this->db->where('id_d_ujian_soal', $id_d_ujian_soal);
        return $this->db->delete('d_ujian_soal');
    }
    
    /**
     * Mengupdate urutan soal dalam ujian menggunakan update_batch.
     * $soal_orders_data adalah array of arrays: [['id_d_ujian_soal'=>x, 'nomor_urut'=>new_val], ...]
     */
    public function update_soal_order_in_ujian($soal_orders_data) {
        if (empty($soal_orders_data)) {
            return 0;
        }
        return $this->db->update_batch('d_ujian_soal', $soal_orders_data, 'id_d_ujian_soal');
    }

    /**
     * Menghitung jumlah soal yang sudah ada di ujian.
     */
    public function count_soal_in_ujian($id_ujian) {
        $this->db->where('id_ujian', $id_ujian);
        return $this->db->count_all_results('d_ujian_soal');
    }

    /**
     * (Opsional) Merapikan nomor urut soal setelah ada yang dihapus.
     */
    public function renumber_soal_in_ujian($id_ujian) {
        $this->db->select('id_d_ujian_soal');
        $this->db->from('d_ujian_soal');
        $this->db->where('id_ujian', $id_ujian);
        $this->db->order_by('nomor_urut', 'ASC'); // Ambil berdasarkan urutan lama
        $soal_di_ujian = $this->db->get()->result_array();

        $update_batch_data = [];
        foreach ($soal_di_ujian as $index => $item) {
            $update_batch_data[] = [
                'id_d_ujian_soal' => $item['id_d_ujian_soal'],
                'nomor_urut' => $index + 1 // Nomor urut baru mulai dari 1
            ];
        }

        if (!empty($update_batch_data)) {
            return $this->db->update_batch('d_ujian_soal', $update_batch_data, 'id_d_ujian_soal');
        }
        return true;
    }

    public function get_available_soal_for_ujian($id_ujian, $id_mapel)
    {
        // Subquery untuk mendapatkan soal yang sudah diassign ke ujian ini
        $this->db->select('id_soal');
        $this->db->from('d_ujian_soal');
        $this->db->where('id_ujian', $id_ujian);
        $assigned_soal = $this->db->get_compiled_select();

        // Query utama
        $this->db->select('id_soal, soal, jenis, created_on');
        $this->db->from('m_soal');
        $this->db->where('id_mapel', $id_mapel);
        $this->db->where('status_soal', 'aktif');
        $this->db->where("id_soal NOT IN ($assigned_soal)", NULL, FALSE);
        $this->db->order_by('created_on', 'DESC');
        
        return $this->db->get()->result();
    }

}