<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ujian_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // $this->load->database(); // Biasanya sudah autoload
    }

    public function getUjianDatatables($filters = [], $guru_context = []) {
        $this->datatables->select(
            'u.id_ujian, u.nama_ujian, m.nama_mapel, j.nama_jenjang AS nama_jenjang_target, '.
            'g.nama_guru AS pembuat_ujian, u.jumlah_soal, u.waktu, u.token, '.
            'DATE_FORMAT(u.tgl_mulai, "%d-%m-%Y %H:%i") as tgl_mulai_formatted, '.
            'CASE u.aktif WHEN "Y" THEN "Aktif" ELSE "Tidak Aktif" END as status_aktif, '.
            'ta.nama_tahun_ajaran, u.guru_id AS id_pembuat_ujian, u.mapel_id AS id_mapel_ujian'
        , FALSE);
        $this->datatables->from('m_ujian u');
        $this->datatables->join('mapel m', 'u.mapel_id = m.id_mapel');
        $this->datatables->join('guru g', 'u.guru_id = g.id_guru');
        $this->datatables->join('jenjang j', 'u.id_jenjang_target = j.id_jenjang', 'left');
        $this->datatables->join('tahun_ajaran ta', 'u.id_tahun_ajaran = ta.id_tahun_ajaran', 'left');

        if (!empty($filters['id_tahun_ajaran']) && $filters['id_tahun_ajaran'] !== 'all') {
            $this->datatables->where('u.id_tahun_ajaran', $filters['id_tahun_ajaran']);
        }
        if (!empty($filters['mapel_id']) && $filters['mapel_id'] !== 'all') {
            $this->datatables->where('u.mapel_id', $filters['mapel_id']);
        }
        if (!empty($filters['id_jenjang_target']) && $filters['id_jenjang_target'] !== 'all') {
            $this->datatables->where('u.id_jenjang_target', $filters['id_jenjang_target']);
        }

        if (!$guru_context['is_admin']) {
            if ($guru_context['is_guru'] && isset($guru_context['id_mapel_pj']) && $guru_context['id_mapel_pj']) {
                $this->datatables->where('u.mapel_id', $guru_context['id_mapel_pj']);
            } elseif ($guru_context['is_guru'] && !empty($guru_context['mapel_ids_diajar'])) {
                $this->datatables->where_in('u.mapel_id', $guru_context['mapel_ids_diajar']);
            } else {
                $this->datatables->where('1', '0');
            }
        }
        return $this->datatables->generate();
    }

    public function create_ujian($data) {
        $this->db->insert('m_ujian', $data);
        return $this->db->insert_id() ? $this->db->insert_id() : false;
    }

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

    public function update_ujian($id_ujian, $data) {
        $this->db->where('id_ujian', $id_ujian);
        return $this->db->update('m_ujian', $data);
    }

    public function delete_ujian($id_ujian) {
        $this->db->where('id_ujian', $id_ujian);
        return $this->db->delete('m_ujian');
    }

    public function get_soal_bank_for_ujian($mapel_id, $id_jenjang_target, $exclude_soal_ids = []) {
        $this->db->select('id_soal, SUBSTRING(REPLACE(REPLACE(soal, \'<p>\', \'\'), \'</p>\', \'\'), 1, 100) as cuplikan_soal, jawaban, bobot');
        $this->db->from('tb_soal');
        $this->db->where('mapel_id', $mapel_id);
        $this->db->where('id_jenjang', $id_jenjang_target);
        if (!empty($exclude_soal_ids) && is_array($exclude_soal_ids)) {
            $this->db->where_not_in('id_soal', $exclude_soal_ids);
        }
        $this->db->order_by('id_soal', 'ASC');
        return $this->db->get()->result();
    }

    public function get_assigned_soal_ids($id_ujian) {
        $this->db->select('id_soal');
        $this->db->from('d_ujian_soal');
        $this->db->where('id_ujian', $id_ujian);
        $query = $this->db->get();
        return array_column($query->result_array(), 'id_soal');
    }

    public function is_soal_in_ujian($id_ujian, $id_soal) {
        $this->db->where('id_ujian', $id_ujian);
        $this->db->where('id_soal', $id_soal);
        return $this->db->count_all_results('d_ujian_soal') > 0;
    }

    public function get_last_nomor_urut($id_ujian) {
        $this->db->select_max('nomor_urut', 'max_urut');
        $this->db->where('id_ujian', $id_ujian);
        $query = $this->db->get('d_ujian_soal')->row();
        return $query && $query->max_urut !== null ? (int)$query->max_urut : 0;
    }

    public function assign_batch_soal_to_ujian($data_to_insert) {
        if (empty($data_to_insert)) return true;
        return $this->db->insert_batch('d_ujian_soal', $data_to_insert);
    }

    public function get_assigned_soal_for_ujian($id_ujian) {
        $this->db->select('dus.id_d_ujian_soal, dus.id_soal, dus.nomor_urut, SUBSTRING(REPLACE(REPLACE(ts.soal, \'<p>\', \'\'), \'</p>\', \'\'), 1, 100) as cuplikan_soal_bank, ts.jawaban as kunci_bank, ts.bobot');
        $this->db->from('d_ujian_soal dus');
        $this->db->join('tb_soal ts', 'dus.id_soal = ts.id_soal');
        $this->db->where('dus.id_ujian', $id_ujian);
        $this->db->order_by('dus.nomor_urut', 'ASC');
        return $this->db->get()->result();
    }

    public function remove_soal_from_ujian($id_ujian, $id_d_ujian_soal) {
        $this->db->where('id_ujian', $id_ujian);
        $this->db->where('id_d_ujian_soal', $id_d_ujian_soal);
        return $this->db->delete('d_ujian_soal');
    }
    
    public function update_soal_order_in_ujian($soal_orders_data) {
        if (empty($soal_orders_data)) return 0;
        return $this->db->update_batch('d_ujian_soal', $soal_orders_data, 'id_d_ujian_soal');
    }

    public function count_soal_in_ujian($id_ujian) {
        $this->db->where('id_ujian', $id_ujian);
        return $this->db->count_all_results('d_ujian_soal');
    }

    public function renumber_soal_in_ujian($id_ujian) {
        $this->db->select('id_d_ujian_soal');
        $this->db->from('d_ujian_soal');
        $this->db->where('id_ujian', $id_ujian);
        $this->db->order_by('nomor_urut', 'ASC');
        $soal_di_ujian = $this->db->get()->result_array();
        $update_batch_data = [];
        foreach ($soal_di_ujian as $index => $item) {
            $update_batch_data[] = [
                'id_d_ujian_soal' => $item['id_d_ujian_soal'],
                'nomor_urut' => $index + 1
            ];
        }
        if (!empty($update_batch_data)) {
            return $this->db->update_batch('d_ujian_soal', $update_batch_data, 'id_d_ujian_soal');
        }
        return true;
    }

    // ========================================================================
    // METHOD UNTUK FITUR UJIAN SISWA (BARU / ADAPTASI DARI LAMA)
    // ========================================================================

    public function get_siswa_detail_for_ujian($username_nisn, $id_tahun_ajaran_aktif) {
        if (empty($username_nisn) || empty($id_tahun_ajaran_aktif)) {
            return null;
        }
        $this->db->select('s.id_siswa, s.nama as nama_siswa, s.nisn, k.id_kelas, k.nama_kelas, j.id_jenjang, j.nama_jenjang, ska.id_tahun_ajaran');
        $this->db->from('siswa s');
        $this->db->join('siswa_kelas_ajaran ska', 's.id_siswa = ska.siswa_id');
        $this->db->join('kelas k', 'ska.kelas_id = k.id_kelas');
        $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
        $this->db->where('s.nisn', $username_nisn);
        $this->db->where('ska.id_tahun_ajaran', $id_tahun_ajaran_aktif);
        return $this->db->get()->row();
    }

    public function get_list_ujian_for_siswa_dt($id_siswa, $id_kelas_siswa, $id_jenjang_siswa, $id_tahun_ajaran_siswa) 
    {
        try {
            $CI = &get_instance();
            $CI->load->config('config');

            // Handle encryption using HEX representation instead of raw binary
            $this->datatables->select(
                'u.id_ujian, u.nama_ujian, m.nama_mapel, g.nama_guru AS nama_pembuat_ujian, '.
                'u.jumlah_soal, u.waktu, '.
                'u.tgl_mulai AS tgl_mulai_server_format, '.
                'u.terlambat AS terlambat_server_format, '.
                'DATE_FORMAT(u.tgl_mulai, "%d-%m-%Y %H:%i") as tgl_mulai_formatted, '.
                'DATE_FORMAT(u.terlambat, "%d-%m-%Y %H:%i") as terlambat_formatted, '.
                '(SELECT hu.status FROM h_ujian hu WHERE hu.ujian_id = u.id_ujian AND hu.siswa_id = '.$this->db->escape($id_siswa).') AS status_pengerjaan_siswa, '.
                // Convert encrypted IDs to HEX
                'HEX(AES_ENCRYPT(CAST(u.id_ujian AS CHAR), "'.$CI->config->item('encryption_key').'")) as id_ujian_encrypted, '.
                '(SELECT HEX(AES_ENCRYPT(CAST(hu.id AS CHAR), "'.$CI->config->item('encryption_key').'")) FROM h_ujian hu WHERE hu.ujian_id = u.id_ujian AND hu.siswa_id = '.$this->db->escape($id_siswa).') AS id_hasil_ujian_encrypted, '.
                '(SELECT UNIX_TIMESTAMP(hu.tgl_selesai) FROM h_ujian hu WHERE hu.ujian_id = u.id_ujian AND hu.siswa_id = '.$this->db->escape($id_siswa).') AS tgl_selesai_pengerjaan_timestamp'
            );
            
            $this->datatables->from('m_ujian u');
            $this->datatables->join('mapel m', 'u.mapel_id = m.id_mapel');
            $this->datatables->join('guru g', 'u.guru_id = g.id_guru');
            
            $this->datatables->where('u.aktif', 'Y');
            $this->datatables->where('u.id_tahun_ajaran', $id_tahun_ajaran_siswa);
            $this->datatables->where('u.id_jenjang_target', $id_jenjang_siswa);
            
            $result = $this->datatables->generate();
            
            // Validate JSON before returning
            $decoded = json_decode($result);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'JSON Error: ' . json_last_error_msg());
                throw new Exception('Invalid JSON generated');
            }
            
            return $result;
            
        } catch (Exception $e) {
            log_message('error', 'Error in get_list_ujian_for_siswa_dt: ' . $e->getMessage());
            throw $e;
        }
    }

    public function get_ujian_for_konfirmasi_siswa($id_ujian, $id_jenjang_siswa) {
        $this->db->select('u.id_ujian, u.nama_ujian, u.token, u.jumlah_soal, u.waktu, u.tgl_mulai, u.terlambat, u.aktif, m.nama_mapel, g.nama_guru as nama_pembuat_ujian');
        $this->db->from('m_ujian u');
        $this->db->join('mapel m', 'u.mapel_id = m.id_mapel', 'left');
        $this->db->join('guru g', 'u.guru_id = g.id_guru', 'left');
        $this->db->where('u.id_ujian', $id_ujian);
        $this->db->where('u.id_jenjang_target', $id_jenjang_siswa);
        return $this->db->get()->row();
    }

    public function get_hasil_ujian_by_ujian_and_siswa($id_ujian, $id_siswa) {
        $this->db->select('h.*, UNIX_TIMESTAMP(h.tgl_selesai) as waktu_habis_timestamp');
        $this->db->from('h_ujian h');
        $this->db->where('h.ujian_id', $id_ujian);
        $this->db->where('h.siswa_id', $id_siswa);
        return $this->db->get()->row();
    }
    
    public function update_status_hasil_ujian($id_h_ujian, $status_baru) {
        $this->db->where('id', $id_h_ujian);
        return $this->db->update('h_ujian', ['status' => $status_baru]);
    }

    public function get_soal_ids_for_ujian($id_ujian, $jumlah_soal_target, $acak_soal_y_n) {
        $this->db->select('id_soal');
        $this->db->from('d_ujian_soal');
        $this->db->where('id_ujian', $id_ujian);
        if ($acak_soal_y_n === 'Y') {
            $this->db->order_by('RAND()');
        } else {
            $this->db->order_by('nomor_urut', 'ASC');
        }
        $this->db->limit($jumlah_soal_target);
        $query = $this->db->get();
        return $query->result(); 
    }

    public function create_hasil_ujian_entry($data_h_ujian) {
        $this->db->insert('h_ujian', $data_h_ujian);
        return $this->db->insert_id() ? $this->db->insert_id() : false;
    }

    public function get_hasil_ujian_by_id_and_siswa($id_h_ujian, $id_siswa) {
        $this->db->select('h.*, UNIX_TIMESTAMP(h.tgl_selesai) as waktu_habis_timestamp');
        $this->db->from('h_ujian h');
        $this->db->where('h.id', $id_h_ujian);
        $this->db->where('h.siswa_id', $id_siswa);
        return $this->db->get()->row();
    }

    public function get_soal_details_for_lembar_ujian($array_list_id_soal, $acak_opsi_bool = false) {
        if (empty($array_list_id_soal) || !is_array($array_list_id_soal)) {
            return [];
        }
        $this->db->select('id_soal, soal, file, tipe_file, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, file_a, file_b, file_c, file_d, file_e, jawaban, bobot');
        $this->db->from('tb_soal');
        $this->db->where_in('id_soal', $array_list_id_soal);
        $result = $this->db->get()->result();

        if ($acak_opsi_bool) {
            foreach ($result as $key_soal => $soal_item) {
                $opsi_tersedia = [];
                if (isset($soal_item->opsi_a) && $soal_item->opsi_a !== null) $opsi_tersedia['A'] = ['teks' => $soal_item->opsi_a, 'file' => $soal_item->file_a];
                if (isset($soal_item->opsi_b) && $soal_item->opsi_b !== null) $opsi_tersedia['B'] = ['teks' => $soal_item->opsi_b, 'file' => $soal_item->file_b];
                if (isset($soal_item->opsi_c) && $soal_item->opsi_c !== null) $opsi_tersedia['C'] = ['teks' => $soal_item->opsi_c, 'file' => $soal_item->file_c];
                if (isset($soal_item->opsi_d) && $soal_item->opsi_d !== null) $opsi_tersedia['D'] = ['teks' => $soal_item->opsi_d, 'file' => $soal_item->file_d];
                if (isset($soal_item->opsi_e) && $soal_item->opsi_e !== null) $opsi_tersedia['E'] = ['teks' => $soal_item->opsi_e, 'file' => $soal_item->file_e];
                
                $kunci_opsi_asli = array_keys($opsi_tersedia);
                shuffle($kunci_opsi_asli);
                
                $opsi_render = [];
                $abjad_render = ['A', 'B', 'C', 'D', 'E'];
                $idx_render = 0;
                foreach($kunci_opsi_asli as $k_asli_acak){
                    if(isset($opsi_tersedia[$k_asli_acak]) && $idx_render < count($abjad_render)){
                        $opsi_render[$abjad_render[$idx_render]] = $opsi_tersedia[$k_asli_acak];
                        $opsi_render[$abjad_render[$idx_render]]['original_key'] = $k_asli_acak;
                        $idx_render++;
                    }
                }
                $result[$key_soal]->opsi_display = $opsi_render;
            }
        } else {
            foreach ($result as $key_soal => $soal_item) {
                $opsi_render = [];
                if (isset($soal_item->opsi_a) && $soal_item->opsi_a !== null) $opsi_render['A'] = ['teks' => $soal_item->opsi_a, 'file' => $soal_item->file_a, 'original_key' => 'A'];
                if (isset($soal_item->opsi_b) && $soal_item->opsi_b !== null) $opsi_render['B'] = ['teks' => $soal_item->opsi_b, 'file' => $soal_item->file_b, 'original_key' => 'B'];
                if (isset($soal_item->opsi_c) && $soal_item->opsi_c !== null) $opsi_render['C'] = ['teks' => $soal_item->opsi_c, 'file' => $soal_item->file_c, 'original_key' => 'C'];
                if (isset($soal_item->opsi_d) && $soal_item->opsi_d !== null) $opsi_render['D'] = ['teks' => $soal_item->opsi_d, 'file' => $soal_item->file_d, 'original_key' => 'D'];
                if (isset($soal_item->opsi_e) && $soal_item->opsi_e !== null) $opsi_render['E'] = ['teks' => $soal_item->opsi_e, 'file' => $soal_item->file_e, 'original_key' => 'E'];
                $result[$key_soal]->opsi_display = $opsi_render;
            }
        }
        return $result;
    }

    public function update_jawaban_siswa($id_h_ujian, $list_jawaban_json) {
        $this->db->where('id', $id_h_ujian);
        return $this->db->update('h_ujian', ['list_jawaban' => $list_jawaban_json]);
    }

    public function calculate_and_finalize_score($id_h_ujian) {
        $hasil_ujian = $this->db->get_where('h_ujian', ['id' => $id_h_ujian])->row();
        if (!$hasil_ujian) {
            log_message('error', "CalculateScore: Hasil ujian ID {$id_h_ujian} tidak ditemukan.");
            return false;
        }

        if ($hasil_ujian->status === 'completed') {
            log_message('info', "CalculateScore: Ujian ID {$id_h_ujian} sudah berstatus completed.");
            return [
                'jml_benar' => $hasil_ujian->jml_benar,
                'nilai' => $hasil_ujian->nilai,
                'nilai_bobot' => $hasil_ujian->nilai_bobot,
                'status' => $hasil_ujian->status
            ];
        }

        $list_jawaban_siswa = json_decode($hasil_ujian->list_jawaban, true);
        $list_id_soal_ujian = json_decode($hasil_ujian->list_soal, true);

        if (empty($list_id_soal_ujian) || !is_array($list_id_soal_ujian) || empty($list_jawaban_siswa) || !is_array($list_jawaban_siswa)) {
            log_message('error', "CalculateScore: list_soal atau list_jawaban tidak valid untuk h_ujian ID {$id_h_ujian}.");
            $this->update_status_hasil_ujian($id_h_ujian, 'expired'); // Atau status lain yang sesuai
            return false;
        }
        
        $this->db->select('id_soal, jawaban, bobot');
        $this->db->from('tb_soal');
        $this->db->where_in('id_soal', $list_id_soal_ujian);
        $kunci_jawaban_db_obj = $this->db->get()->result();
        
        $map_kunci_bobot = [];
        foreach($kunci_jawaban_db_obj as $k_obj){
            $map_kunci_bobot[$k_obj->id_soal] = ['jawaban' => $k_obj->jawaban, 'bobot' => (int)$k_obj->bobot];
        }

        $jumlah_benar = 0;
        $total_bobot_diperoleh = 0;
        $total_bobot_maksimal_dari_soal_dikerjakan = 0;

        foreach ($list_id_soal_ujian as $id_s) {
            if (isset($map_kunci_bobot[$id_s])) {
                $kunci_soal_ini = $map_kunci_bobot[$id_s]['jawaban'];
                $bobot_soal_ini = $map_kunci_bobot[$id_s]['bobot'];
                $total_bobot_maksimal_dari_soal_dikerjakan += $bobot_soal_ini;

                if (isset($list_jawaban_siswa[$id_s]) && !empty($list_jawaban_siswa[$id_s]['j'])) {
                    if (strtoupper($list_jawaban_siswa[$id_s]['j']) == strtoupper($kunci_soal_ini)) {
                        $jumlah_benar++;
                        $total_bobot_diperoleh += $bobot_soal_ini;
                    }
                }
            } else {
                log_message('warning', "CalculateScore: Soal ID {$id_s} dari h_ujian ID {$id_h_ujian} tidak ditemukan di tb_soal.");
            }
        }

        $nilai_akhir = 0;
        if ($total_bobot_maksimal_dari_soal_dikerjakan > 0) {
            $nilai_akhir = ($total_bobot_diperoleh / $total_bobot_maksimal_dari_soal_dikerjakan) * 100;
        } elseif (count($list_id_soal_ujian) > 0) { 
            $nilai_akhir = ($jumlah_benar / count($list_id_soal_ujian)) * 100;
        }

        $data_update_hasil = [
            'jml_benar'     => $jumlah_benar,
            'nilai'         => round($nilai_akhir, 2),
            'nilai_bobot'   => $total_bobot_diperoleh,
            'status'        => 'completed',
            'tgl_selesai'   => date('Y-m-d H:i:s')
        ];

        $this->db->where('id', $id_h_ujian);
        if ($this->db->update('h_ujian', $data_update_hasil)) {
            log_message('info', "CalculateScore: Skor berhasil dihitung dan disimpan untuk h_ujian ID {$id_h_ujian}. Nilai: " . $data_update_hasil['nilai']);
            return $data_update_hasil;
        }
        log_message('error', "CalculateScore: Gagal update h_ujian ID {$id_h_ujian}.");
        return false;
    }

    public function get_hasil_ujian_detail_for_siswa($id_h_ujian, $id_siswa) {
        $this->db->select('h.*, u.nama_ujian, u.jumlah_soal as jumlah_soal_di_m_ujian, m.nama_mapel, DATE_FORMAT(h.tgl_mulai, "%d %M %Y - %H:%i") as tgl_mulai_formatted, DATE_FORMAT(h.tgl_selesai, "%d %M %Y - %H:%i") as tgl_selesai_formatted');
        $this->db->from('h_ujian h');
        $this->db->join('m_ujian u', 'h.ujian_id = u.id_ujian');
        $this->db->join('mapel m', 'u.mapel_id = m.id_mapel');
        $this->db->where('h.id', $id_h_ujian);
        $this->db->where('h.siswa_id', $id_siswa);
        return $this->db->get()->row();
    }

    public function count_all()
    {
        return $this->db->count_all('m_ujian');
    }

    public function count_filtered()
    {
        $this->_get_datatables_query();
        return $this->db->get()->num_rows();
    }

    private function _get_datatables_query()
    {
        $this->db->from('m_ujian u');
        $this->db->join('mapel m', 'u.mapel_id = m.id_mapel');
        $this->db->join('guru g', 'u.guru_id = g.id_guru');
        $this->db->where('u.aktif', 'Y');
        
        if ($this->input->post('search')['value']) {
            $searchValue = $this->input->post('search')['value'];
            $this->db->group_start()
                ->like('u.nama_ujian', $searchValue)
                ->or_like('m.nama_mapel', $searchValue)
                ->or_like('g.nama_guru', $searchValue)
            ->group_end();
        }
    }

    public function mulai_ujian($id_ujian, $id_siswa, $token_input) 
    {
        try {
            // 1. Validasi token
            $ujian = $this->db->get_where('m_ujian', ['id_ujian' => $id_ujian])->row();
            if (!$ujian || strtoupper($token_input) !== $ujian->token) {
                return false;
            }

            // 2. Cek apakah sudah ada hasil ujian
            $hasil_ujian = $this->db->get_where('h_ujian', [
                'ujian_id' => $id_ujian,
                'siswa_id' => $id_siswa
            ])->row();

            if ($hasil_ujian) {
                // Jika sudah ada dan belum selesai, return ID yang sama
                if ($hasil_ujian->status !== 'completed') {
                    return $hasil_ujian->id;
                }
                // Jika sudah selesai, tidak boleh mengerjakan lagi
                return false;
            }

            // 3. Ambil soal untuk ujian ini
            $soal_ujian = $this->db->select('id_soal')
                                ->from('d_ujian_soal')
                                ->where('id_ujian', $id_ujian)
                                ->order_by('nomor_urut', 'ASC')
                                ->get()->result();

            // Jika ujian diset untuk acak soal
            if ($ujian->acak_soal == 'Y') {
                shuffle($soal_ujian);
            }

            // Buat array ID soal
            $list_soal = array_map(function($soal) {
                return $soal->id_soal;
            }, $soal_ujian);

            // Buat array jawaban kosong
            $list_jawaban = array();
            foreach ($list_soal as $id_soal) {
                $list_jawaban[$id_soal] = [
                    'j' => '', // jawaban
                    'r' => 'N', // ragu-ragu
                    'n' => 0   // nilai/point
                ];
            }

            // 4. Buat record baru di h_ujian
            $data_h_ujian = [
                'ujian_id' => $id_ujian,
                'siswa_id' => $id_siswa,
                'list_soal' => json_encode($list_soal),
                'list_jawaban' => json_encode($list_jawaban),
                'jml_benar' => 0,
                'nilai' => 0,
                'nilai_bobot' => 0,
                'tgl_mulai' => date('Y-m-d H:i:s'),
                'tgl_selesai' => date('Y-m-d H:i:s', strtotime("+{$ujian->waktu} minutes")),
                'status' => 'sedang_dikerjakan'
            ];

            $this->db->insert('h_ujian', $data_h_ujian);
            $id_h_ujian = $this->db->insert_id();

            if (!$id_h_ujian) {
                log_message('error', 'Gagal menyimpan h_ujian baru');
                return false;
            }

            return $id_h_ujian;

        } catch (Exception $e) {
            log_message('error', 'Error in mulai_ujian: ' . $e->getMessage());
            return false;
        }
    }
    
    public function get_lembar_ujian_siswa($id_h_ujian, $id_siswa)
    {
        try {
            // 1. Ambil data h_ujian dan validasi
            $h_ujian = $this->db->select('h.*, h.tgl_selesai as waktu_habis_timestamp, u.nama_ujian, u.waktu, u.mapel_id, m.nama_mapel')
                ->from('h_ujian h')
                ->join('m_ujian u', 'h.ujian_id = u.id_ujian')
                ->join('mapel m', 'u.mapel_id = m.id_mapel')
                ->where([
                    'h.id' => $id_h_ujian,
                    'h.siswa_id' => $id_siswa
                ])
                ->get()
                ->row();

            if (!$h_ujian) {
                throw new Exception('Data ujian tidak ditemukan.');
            }

            // 2. Decode list soal dan jawaban
            $list_soal = json_decode($h_ujian->list_soal);
            $list_jawaban = json_decode($h_ujian->list_jawaban, true);

            if (!$list_soal || !$list_jawaban) {
                throw new Exception('Format data soal/jawaban tidak valid.');
            }

            // 3. Ambil detail soal-soal
            $soal_collection = $this->db
                ->select('s.*, s.id_soal')
                ->from('tb_soal s')
                ->where_in('s.id_soal', $list_soal)
                ->get()
                ->result();

            // 4. Susun data untuk view
            $data = [
                'user' => $this->ion_auth->user()->row(),
                'judul' => 'Lembar Ujian',
                'subjudul' => $h_ujian->nama_ujian,
                'total_soal' => count($list_soal),
                'h_ujian' => $h_ujian,
                'hasil_ujian' => $h_ujian,
                'soal_collection' => $soal_collection,
                'jawaban_tersimpan' => $list_jawaban,
                'id_h_ujian_enc' => $this->encryption->encrypt($id_h_ujian),
                'waktu_selesai' => strtotime($h_ujian->tgl_selesai),
                'sisa_waktu' => max(0, strtotime($h_ujian->tgl_selesai) - time())
            ];

            return $data;

        } catch (Exception $e) {
            log_message('error', 'Error in get_lembar_ujian_siswa: ' . $e->getMessage());
            return false;
        }
    }

}