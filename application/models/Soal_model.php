<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Soal_model extends CI_Model {
    
    /**
     * Mengambil data soal untuk DataTables dengan berbagai filter.
     * @param array $filters Berisi filter seperti mapel_id, jenjang_id, guru_id_pembuat, mapel_ids_diajar
     * @param bool $is_admin
     * @param int|null $guru_id_login (ID guru yang sedang login, untuk filter kepemilikan)
     * @return string JSON DataTables
     */
    public function getDataSoal($filters = [], $is_admin = false, $guru_id_login = null)
    {
        $this->datatables->select('s.id_soal, m.nama_mapel, j.nama_jenjang, g.nama_guru AS pembuat_soal, SUBSTRING(REPLACE(REPLACE(s.soal, \'<p>\', \'\'), \'</p>\', \'\'), 1, 50) as cuplikan_soal, s.jawaban, s.bobot, s.guru_id, s.mapel_id, s.id_jenjang, DATE_FORMAT(FROM_UNIXTIME(s.created_on), "%d-%m-%Y %H:%i") as created_on_formatted', FALSE);
        $this->datatables->from('tb_soal s');
        $this->datatables->join('mapel m', 's.mapel_id = m.id_mapel');
        $this->datatables->join('jenjang j', 's.id_jenjang = j.id_jenjang', 'left');
        $this->datatables->join('guru g', 's.guru_id = g.id_guru'); // Guru pembuat soal (PJ atau Admin)

        // Filter berdasarkan mapel_id
        if (!empty($filters['mapel_id']) && $filters['mapel_id'] !== 'all') {
            $this->datatables->where('s.mapel_id', $filters['mapel_id']);
        }
        // Filter berdasarkan jenjang_id
        if (!empty($filters['jenjang_id']) && $filters['jenjang_id'] !== 'all') {
            $this->datatables->where('s.id_jenjang', $filters['jenjang_id']);
        }
        // Filter berdasarkan guru pembuat (jika Admin memfilter)
        if ($is_admin && !empty($filters['guru_pembuat_id']) && $filters['guru_pembuat_id'] !== 'all') {
            $this->datatables->where('s.guru_id', $filters['guru_pembuat_id']);
        }
        
        // Filter untuk Guru (Non-PJ atau PJ) hanya soal yang relevan dengan mapel yang diajar atau di-PJ-kan
        // Jika $filters['mapel_ids_for_guru'] berisi mapel_id PJ atau mapel_id yang diajar guru non-PJ
        if (!$is_admin && isset($filters['mapel_ids_for_guru']) && !empty($filters['mapel_ids_for_guru'])) {
            // BARIS SEKITAR INI (line 37 di model Anda) YANG MUNGKIN MENYEBABKAN ERROR
            // JIKA $filters['mapel_ids_for_guru'] MASIH BERUPA ARRAY OBJEK
            $this->datatables->where_in('m.id_mapel', $filters['mapel_ids_for_guru']);
            // atau jika menggunakan CI Query Builder:
            $this->db->where_in('m.id_mapel', $filters['mapel_ids_for_guru']);
        } elseif (isset($filters['mapel_id']) && $filters['mapel_id'] !== 'all') {
            $this->datatables->where('m.id_mapel', $filters['mapel_id']);
            // atau: $this->db->where('nama_tabel_mapel.id_mapel', $filters['mapel_id']);
        }


        // Kolom Aksi akan dirender oleh JavaScript berdasarkan hak akses dan kepemilikan
        // Tambahkan id_soal lagi untuk digunakan di data 'action' jika perlu (atau ambil dari row di JS)
        // $this->datatables->add_column('action', '$1', 's.id_soal'); // Placeholder
        
        return $this->datatables->generate();
    }

    public function getSoalById($id_soal)
    {
        $this->db->select('s.*, m.nama_mapel, j.nama_jenjang, g.nama_guru as nama_pembuat');
        $this->db->from('tb_soal s');
        $this->db->join('mapel m', 's.mapel_id = m.id_mapel', 'left');
        $this->db->join('jenjang j', 's.id_jenjang = j.id_jenjang', 'left');
        $this->db->join('guru g', 's.guru_id = g.id_guru', 'left'); // guru pembuat soal
        $this->db->where('s.id_soal', $id_soal);
        return $this->db->get()->row();
    }

    // Fungsi create, update, delete akan menggunakan Master_model generik
    // Namun, jika ada logika spesifik seperti menghapus file, bisa ditambahkan di sini.

    /**
     * Menghapus soal dan file-file terkaitnya
     * @param array $ids_soal Array berisi ID soal yang akan dihapus
     * @return bool True jika semua berhasil, false jika ada yg gagal
     */
    public function deleteSoalBatch(array $ids_soal)
    {
        $this->db->trans_start();
        foreach ($ids_soal as $id_soal) {
            $soal = $this->getSoalById($id_soal);
            if ($soal) {
                $files_to_delete = [];
                if (!empty($soal->file)) $files_to_delete[] = $soal->file;
                if (!empty($soal->file_a)) $files_to_delete[] = $soal->file_a;
                if (!empty($soal->file_b)) $files_to_delete[] = $soal->file_b;
                if (!empty($soal->file_c)) $files_to_delete[] = $soal->file_c;
                if (!empty($soal->file_d)) $files_to_delete[] = $soal->file_d;
                if (!empty($soal->file_e)) $files_to_delete[] = $soal->file_e;

                foreach ($files_to_delete as $file_name) {
                    $file_path = FCPATH . 'uploads/bank_soal/' . $file_name;
                    if (file_exists($file_path) && is_file($file_path)) {
                        unlink($file_path);
                    }
                }
                $this->db->where('id_soal', $id_soal);
                $this->db->delete('tb_soal');
            }
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function get_soal_by_mapel_jenjang($mapel_id, $jenjang_id, $limit = null)
    {
        $this->db->select('id_soal, soal, bobot');
        $this->db->from('tb_soal');
        $this->db->where([
            'mapel_id' => $mapel_id,
            'id_jenjang' => $jenjang_id
            // Remove status_soal check since column doesn't exist
        ]);
        $this->db->order_by('RAND()');
        
        if ($limit !== null) {
            $this->db->limit($limit);
        }
        
        $result = $this->db->get();
        log_message('debug', 'Query get_soal_by_mapel_jenjang: ' . $this->db->last_query());
        log_message('debug', 'Result count: ' . $result->num_rows());
        
        return $result->result();
    }

    public function count_available_soal($mapel_id, $jenjang_id)
    {
        return $this->db->where([
            'mapel_id' => $mapel_id,
            'id_jenjang' => $jenjang_id
            // Remove status_soal check since column doesn't exist
        ])->from('tb_soal')->count_all_results();
    }
}