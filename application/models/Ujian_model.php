<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ujian_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // $this->load->database(); // Biasanya sudah autoload
        $this->load->model('Master_model', 'master');
    }

    public function get_ujian_by_id_with_guru($id_ujian, $id_kelas_siswa = null) // Tambahkan parameter $id_kelas_siswa
    {
        $this->db->select('m_ujian.*, mapel.nama_mapel,
                            g1.nama_guru as pembuat_soal,
                            jenjang.nama_jenjang'); // Hapus g2.nama_guru dari SELECT utama dulu

        // Subquery untuk mendapatkan guru pengajar
        // Menggunakan subquery agar bisa mendapatkan satu string guru saja,
        // meskipun ada banyak guru mengajar mapel tersebut di kelas yang sama
        $subquery_guru_pengajar = '(
            SELECT GROUP_CONCAT(DISTINCT g_pengajar.nama_guru ORDER BY g_pengajar.nama_guru ASC)
            FROM guru_mapel_kelas_ajaran gmka_pengajar
            JOIN guru g_pengajar ON gmka_pengajar.guru_id = g_pengajar.id_guru
            WHERE gmka_pengajar.mapel_id = m_ujian.mapel_id
            AND gmka_pengajar.id_tahun_ajaran = m_ujian.id_tahun_ajaran';

        if ($id_kelas_siswa !== null) {
            $subquery_guru_pengajar .= ' AND gmka_pengajar.kelas_id = ' . $this->db->escape($id_kelas_siswa);
        }
        $subquery_guru_pengajar .= ') AS nama_guru_pengajar';
        $this->db->select($subquery_guru_pengajar, FALSE); // FALSE agar tidak di-escape CodeIgniter

        $this->db->from('m_ujian')
            ->join('mapel', 'mapel.id_mapel = m_ujian.mapel_id')
            ->join('guru g1', 'g1.id_guru = m_ujian.guru_id') // Guru pembuat ujian
            ->join('jenjang', 'jenjang.id_jenjang = m_ujian.id_jenjang_target', 'left')
            ->where('m_ujian.id_ujian', $id_ujian);

        // For debugging
        $result = $this->db->get()->row();
        log_message('debug', 'SQL Query for get_ujian_by_id_with_guru: ' . $this->db->last_query());

        return $result;
    }

    public function getUjianDatatables($filters = [], $guru_context = []) {
        $this->datatables->select(
            'u.id_ujian, u.nama_ujian, m.nama_mapel, j.nama_jenjang AS nama_jenjang_target, '.
            'g.nama_guru AS pembuat_ujian, u.jumlah_soal, '.
            // Modifikasi di sini: Tambahkan u.tgl_mulai dan u.terlambat sebagai raw data
            // Lalu tambahkan kolom terformat untuk waktu
            'u.tgl_mulai, u.terlambat, '. // Ambil raw tgl_mulai dan terlambat
            'CONCAT(DATE_FORMAT(u.tgl_mulai, "%H:%i"), "-", DATE_FORMAT(u.terlambat, "%H:%i"), " WIB") as waktu_mulai_terlambat, '.
            // Modifikasi di sini: Format Hari/Tanggal sepenuhnya di server
            // Gunakan setlocale dan strftime untuk format bahasa Indonesia yang lebih baik
            // Ini akan menghasilkan string seperti 'Rabu, 11 Juni 2025'
            'DATE_FORMAT(u.tgl_mulai, "%Y-%m-%d") as tgl_mulai_for_date_format, '. // Ambil tanggal saja untuk formatting tanggal di PHP
            'u.token, '.
            'CASE u.aktif WHEN "Y" THEN "Aktif" ELSE "Tidak Aktif" END as status_aktif, '.
            'ta.nama_tahun_ajaran, u.guru_id AS id_pembuat_ujian, u.mapel_id AS id_mapel_ujian'
        , FALSE); // FALSE agar tidak meng-escape custom SELECT
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
        
        $result_json = $this->datatables->generate();
        $result_array = json_decode($result_json, true);

        // Tambahkan formatting tanggal di PHP setelah DataTables generate JSON
        if (isset($result_array['data'])) {
            setlocale(LC_TIME, 'id_ID', 'Indonesian', 'id'); // Set locale untuk Bahasa Indonesia

            foreach ($result_array['data'] as $key => $row) {
                // Format Hari/Tanggal (contoh: Rabu, 11 Juni 2025)
                if (!empty($row['tgl_mulai_for_date_format'])) {
                    $timestamp = strtotime($row['tgl_mulai_for_date_format']);
                    $result_array['data'][$key]['hari_tanggal_ujian'] = strftime('%A, %d %B %Y', $timestamp);
                } else {
                    $result_array['data'][$key]['hari_tanggal_ujian'] = '-';
                }
            }
        }
        
        return json_encode($result_array);
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

            $this->datatables->select(
                'u.id_ujian, u.nama_ujian, m.nama_mapel, '.
                'u.jumlah_soal, u.waktu, '.
                'u.tgl_mulai AS tgl_mulai_server_format, '.
                'u.terlambat AS terlambat_server_format, '.
                'DATE_FORMAT(u.tgl_mulai, "%H:%i") as waktu_mulai, '. // Tambahkan waktu mulai
                'DATE_FORMAT(u.terlambat, "%H:%i") as waktu_terlambat, '. // Tambahkan waktu terlambat
                'DATE_FORMAT(u.tgl_mulai, "%d-%m-%Y %H:%i") as tgl_mulai_formatted, '.
                'DATE_FORMAT(u.terlambat, "%d-%m-%Y %H:%i") as terlambat_formatted, '.
                '(SELECT hu.status FROM h_ujian hu WHERE hu.ujian_id = u.id_ujian AND hu.siswa_id = '.$this->db->escape($id_siswa).') AS status_pengerjaan_siswa, '.
                'HEX(AES_ENCRYPT(CAST(u.id_ujian AS CHAR), "'.$CI->config->item('encryption_key').'")) as id_ujian_encrypted, '.
                '(SELECT HEX(AES_ENCRYPT(CAST(hu.id AS CHAR), "'.$CI->config->item('encryption_key').'")) FROM h_ujian hu WHERE hu.ujian_id = u.id_ujian AND hu.siswa_id = '.$this->db->escape($id_siswa).') AS id_hasil_ujian_encrypted, '.
                '(SELECT UNIX_TIMESTAMP(hu.tgl_selesai) FROM h_ujian hu WHERE hu.ujian_id = u.id_ujian AND hu.siswa_id = '.$this->db->escape($id_siswa).') AS tgl_selesai_pengerjaan_timestamp, '.

                '(SELECT GROUP_CONCAT(g_pengajar.nama_guru ORDER BY g_pengajar.nama_guru ASC)
                FROM guru_mapel_kelas_ajaran gmka_pengajar
                JOIN guru g_pengajar ON gmka_pengajar.guru_id = g_pengajar.id_guru
                WHERE gmka_pengajar.mapel_id = u.mapel_id
                AND gmka_pengajar.kelas_id = '.$this->db->escape($id_kelas_siswa).'
                AND gmka_pengajar.id_tahun_ajaran = u.id_tahun_ajaran
                ) AS nama_guru_pengajar'.
            '', FALSE); 

            $this->datatables->from('m_ujian u');
            $this->datatables->join('mapel m', 'u.mapel_id = m.id_mapel');
            
            $this->datatables->where('u.aktif', 'Y');
            $this->datatables->where('u.id_tahun_ajaran', $id_tahun_ajaran_siswa);
            $this->datatables->where('u.id_jenjang_target', $id_jenjang_siswa);
            
            $result = $this->datatables->generate();
            
            $decoded = json_decode($result, true); // Decode to array for modification
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'JSON Error: ' . json_last_error_msg());
                throw new Exception('Invalid JSON generated');
            }

            // Tambahkan formatting Hari/Tanggal di PHP
            if (isset($decoded['data'])) {
                setlocale(LC_TIME, 'id_ID', 'Indonesian', 'id'); // Set locale untuk Bahasa Indonesia

                foreach ($decoded['data'] as $key => $row) {
                    if (!empty($row['tgl_mulai_server_format'])) {
                        $timestamp = strtotime($row['tgl_mulai_server_format']);
                        $decoded['data'][$key]['hari_tanggal_ujian'] = strftime('%A, %d %B %Y', $timestamp);
                    } else {
                        $decoded['data'][$key]['hari_tanggal_ujian'] = '-';
                    }
                    // Waktu sudah diformat di query SQL (`waktu_mulai` dan `waktu_terlambat`)
                    $decoded['data'][$key]['waktu_ujian_formatted'] = $row['waktu_mulai'] . '-' . $row['waktu_terlambat'] . ' WIB';
                }
            }
            
            return json_encode($decoded); // Encode back to JSON
            
        } catch (Exception $e) {
            log_message('error', 'Error in get_list_ujian_for_siswa_dt: ' . $e->getMessage());
            throw $e;
        }
    }

    public function get_ujian_for_konfirmasi_siswa($id_ujian, $id_jenjang_siswa) {
        $this->db->select('u.id_ujian, u.nama_ujian, u.token, u.jumlah_soal, u.waktu, u.tgl_mulai, u.terlambat, u.aktif, m.nama_mapel, g.nama_guru as nama_pembuat_ujian');
        // Tambahkan jenjang juga di sini untuk tampilan
        $this->db->select('j.nama_jenjang');
        $this->db->from('m_ujian u');
        $this->db->join('mapel m', 'u.mapel_id = m.id_mapel', 'left');
        $this->db->join('guru g', 'u.guru_id = g.id_guru', 'left');
        $this->db->join('jenjang j', 'u.id_jenjang_target = j.id_jenjang', 'left'); // JOIN ke tabel jenjang
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
        $this->db->select('h.*, UNIX_TIMESTAMP(h.tgl_selesai) as waktu_habis_timestamp'); // Ini sudah benar
        $this->db->from('h_ujian h');
        $this->db->where('h.id', $id_h_ujian);
        $this->db->where('h.siswa_id', $id_siswa);
        return $this->db->get()->row();
    }

    public function get_soal_details_for_lembar_ujian($array_list_id_soal, $acak_opsi_bool = false) {
        if (empty($array_list_id_soal) || !is_array($array_list_id_soal)) {
            log_message('debug', 'Ujian_model: $array_list_id_soal is empty or not an array. Returning empty.');
            return [];
        }
        
        log_message('debug', 'Ujian_model: Searching for soal IDs: ' . implode(', ', $array_list_id_soal));

        $this->db->select('id_soal, soal, file, tipe_file, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, file_a, file_b, file_c, file_d, file_e, jawaban, bobot');
        $this->db->from('tb_soal');
        $this->db->where_in('id_soal', $array_list_id_soal);

        $query = $this->db->get();
        $result = $query->result(); // Ini akan mengembalikan array of stdClass Objects

        // === DEBUG KRUSIAL: Raw $result from DB query ===
        // log_message('debug', 'Ujian_model: Raw $result from DB query: ' . print_r($result, true));
        
        // if (!empty($result)) {
        //     foreach ($result as $idx => $item) {
        //         log_message('debug', 'Ujian_model: Soal ID ' . ($item->id_soal ?? 'N/A') . ' - file_a property: ' . (isset($item->file_a) ? ($item->file_a ?? 'NULL_VALUE') : 'NOT_SET'));
        //         log_message('debug', 'Ujian_model: Soal ID ' . ($item->id_soal ?? 'N/A') . ' - opsi_a property: ' . (isset($item->opsi_a) ? ($item->opsi_a ?? 'NULL_VALUE') : 'NOT_SET'));
        //         log_message('debug', 'Ujian_model: Soal ID ' . ($item->id_soal ?? 'N/A') . ' - file_b property: ' . (isset($item->file_b) ? ($item->file_b ?? 'NULL_VALUE') : 'NOT_SET'));
        //         log_message('debug', 'Ujian_model: Soal ID ' . ($item->id_soal ?? 'N/A') . ' - opsi_b property: ' . (isset($item->opsi_b) ? ($item->opsi_b ?? 'NULL_VALUE') : 'NOT_SET'));
        //         log_message('debug', 'Ujian_model: Soal ID ' . ($item->id_soal ?? 'N/A') . ' - file_c property: ' . (isset($item->file_c) ? ($item->file_c ?? 'NULL_VALUE') : 'NOT_SET'));
        //         log_message('debug', 'Ujian_model: Soal ID ' . ($item->id_soal ?? 'N/A') . ' - opsi_c property: ' . (isset($item->opsi_c) ? ($item->opsi_c ?? 'NULL_VALUE') : 'NOT_SET'));
        //         log_message('debug', 'Ujian_model: Soal ID ' . ($item->id_soal ?? 'N/A') . ' - file_d property: ' . (isset($item->file_d) ? ($item->file_d ?? 'NULL_VALUE') : 'NOT_SET'));
        //         log_message('debug', 'Ujian_model: Soal ID ' . ($item->id_soal ?? 'N/A') . ' - opsi_d property: ' . (isset($item->opsi_d) ? ($item->opsi_d ?? 'NULL_VALUE') : 'NOT_SET'));
        //         log_message('debug', 'Ujian_model: Soal ID ' . ($item->id_soal ?? 'N/A') . ' - file_e property: ' . (isset($item->file_e) ? ($item->file_e ?? 'NULL_VALUE') : 'NOT_SET'));
        //         log_message('debug', 'Ujian_model: Soal ID ' . ($item->id_soal ?? 'N/A') . ' - opsi_e property: ' . (isset($item->opsi_e) ? ($item->opsi_e ?? 'NULL_VALUE') : 'NOT_SET'));
        //     }
        // } else {
        //     log_message('debug', 'Ujian_model: Raw query result is EMPTY!');
        // }

        if ($acak_opsi_bool) {
            foreach ($result as $key_soal => $soal_item) {
                $opsi_tersedia = [];
                $opsi_tersedia['A'] = ['teks' => ($soal_item->opsi_a ?? null), 'file' => trim($soal_item->file_a ?? '')];
                $opsi_tersedia['B'] = ['teks' => ($soal_item->opsi_b ?? null), 'file' => trim($soal_item->file_b ?? '')];
                $opsi_tersedia['C'] = ['teks' => ($soal_item->opsi_c ?? null), 'file' => trim($soal_item->file_c ?? '')];
                $opsi_tersedia['D'] = ['teks' => ($soal_item->opsi_d ?? null), 'file' => trim($soal_item->file_d ?? '')];
                $opsi_tersedia['E'] = ['teks' => ($soal_item->opsi_e ?? null), 'file' => trim($soal_item->file_e ?? '')];
                
                $kunci_opsi_asli = array_keys(array_filter($opsi_tersedia, function($v) { return !empty($v['teks']) || !empty($v['file']); }));
                shuffle($kunci_opsi_asli);
                
                $opsi_render = []; // Pastikan ini array asosiatif
                $abjad_render = ['A', 'B', 'C', 'D', 'E'];
                $idx_render = 0;
                foreach($kunci_opsi_asli as $k_asli_acak){
                    if(isset($opsi_tersedia[$k_asli_acak]) && $idx_render < count($abjad_render)){
                        $opsi_render[$abjad_render[$idx_render]] = $opsi_tersedia[$k_asli_acak]; // Simpan sebagai array
                        $opsi_render[$abjad_render[$idx_render]]['original_key'] = $k_asli_acak;
                        $idx_render++;
                    }
                }
                $result[$key_soal]->opsi_display = $opsi_render; // Simpan array di properti objek
                
                // Unset properti asli setelah dipindahkan ke opsi_display
                unset($result[$key_soal]->file_a);
                unset($result[$key_soal]->file_b);
                unset($result[$key_soal]->file_c);
                unset($result[$key_soal]->file_d);
                unset($result[$key_soal]->file_e);
                unset($result[$key_soal]->opsi_a);
                unset($result[$key_soal]->opsi_b);
                unset($result[$key_soal]->opsi_c);
                unset($result[$key_soal]->opsi_d);
                unset($result[$key_soal]->opsi_e);
            }
        } else {
            // Jika acak_opsi_bool FALSE, isi opsi_display secara manual
            foreach ($result as $key_soal => $soal_item) {
                $opsi_render = []; // Pastikan ini array asosiatif
                $opsi_render['A'] = ['teks' => ($soal_item->opsi_a ?? null), 'file' => trim($soal_item->file_a ?? ''), 'original_key' => 'A'];
                $opsi_render['B'] = ['teks' => ($soal_item->opsi_b ?? null), 'file' => trim($soal_item->file_b ?? ''), 'original_key' => 'B'];
                $opsi_render['C'] = ['teks' => ($soal_item->opsi_c ?? null), 'file' => trim($soal_item->file_c ?? ''), 'original_key' => 'C'];
                $opsi_render['D'] = ['teks' => ($soal_item->opsi_d ?? null), 'file' => trim($soal_item->file_d ?? ''), 'original_key' => 'D'];
                $opsi_render['E'] = ['teks' => ($soal_item->opsi_e ?? null), 'file' => trim($soal_item->file_e ?? ''), 'original_key' => 'E'];
                $result[$key_soal]->opsi_display = $opsi_render; // Simpan array di properti objek
                
                // Unset properti asli
                unset($result[$key_soal]->file_a);
                unset($result[$key_soal]->file_b);
                unset($result[$key_soal]->file_c);
                unset($result[$key_soal]->file_d);
                unset($result[$key_soal]->file_e);
                unset($result[$key_soal]->opsi_a);
                unset($result[$key_soal]->opsi_b);
                unset($result[$key_soal]->opsi_c);
                unset($result[$key_soal]->opsi_d);
                unset($result[$key_soal]->opsi_e);
            }
        }
        log_message('debug', 'Ujian_model: Final processed soal details: ' . print_r($result, true));
        return $result;
    }

    public function update_jawaban_siswa($id_h_ujian, $list_jawaban_json) {
        if(empty($id_h_ujian) || $list_jawaban_json === null) { // Periksa juga jika $list_jawaban_json null
            log_message('error', 'UjianModel (update_jawaban_siswa): id_h_ujian atau list_jawaban_json kosong.');
            return false;
        }
        $this->db->where('id', $id_h_ujian);
        $update_status = $this->db->update('h_ujian', ['list_jawaban' => $list_jawaban_json]);
        
        if(!$update_status){
            log_message('error', 'UjianModel (update_jawaban_siswa): Gagal update DB. Error: ' . $this->db->error()['message'] . ' Query: ' . $this->db->last_query());
        } else {
            log_message('debug', 'UjianModel (update_jawaban_siswa): Berhasil update list_jawaban untuk id_h_ujian: ' . $id_h_ujian);
        }
        return $update_status;
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

    /**
     * Mengambil soal-soal untuk ujian berdasarkan ID h_ujian
     * * @param int $id_h_ujian ID dari h_ujian
     * @return array Array berisi data soal-soal untuk ujian
     */
    public function get_soal_by_id_ujian($id_h_ujian) 
    {
        // Ambil data h_ujian untuk mendapatkan list_soal
        $h_ujian = $this->db->get_where('h_ujian', ['id' => $id_h_ujian])->row();
        if (!$h_ujian) {
            return [];
        }

        // Decode list_soal JSON menjadi array
        $list_soal = json_decode($h_ujian->list_soal);
        if (empty($list_soal)) {
            return [];
        }

        // Ambil master ujian untuk cek pengacakan opsi
        $m_ujian = $this->db->get_where('m_ujian', ['id_ujian' => $h_ujian->ujian_id])->row();
        $acak_opsi = ($m_ujian && $m_ujian->acak_opsi === 'Y');

        // Ambil detail soal dari bank soal
        $soal_collection = $this->db
            ->select('id_soal, soal, file, tipe_file, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e')
            ->from('tb_soal')
            ->where_in('id_soal', $list_soal)
            ->get()
            ->result();

        // Jika perlu acak opsi
        if ($acak_opsi) {
            foreach ($soal_collection as &$soal) {
                $opsi = [
                    'A' => $soal->opsi_a,
                    'B' => $soal->opsi_b,
                    'C' => $soal->opsi_c,
                    'D' => $soal->opsi_d,
                    'E' => $soal->opsi_e
                ];
                
                // Acak opsi
                uksort($opsi, function($a, $b) {
                    return rand(-1, 1);
                });

                // Assign kembali opsi yang sudah diacak
                $i = 0;
                foreach ($opsi as $key => $value) {
                    $prop = 'opsi_' . strtolower($key);
                    $soal->$prop = $value;
                }
            }
        }

        // Urutkan soal sesuai urutan di list_soal
        $sorted_soal = [];
        foreach ($list_soal as $id_soal) {
            foreach ($soal_collection as $soal) {
                if ($soal->id_soal == $id_soal) {
                    $sorted_soal[] = $soal;
                    break;
                }
            }
        }

        return $sorted_soal;
    }
    
    public function get_lembar_ujian_siswa($id_h_ujian, $id_siswa)
    {
        try {
            // Get exam data
            $h_ujian = $this->db->select('h.*, h.tgl_selesai as waktu_habis_timestamp, u.nama_ujian, u.waktu, u.mapel_id, m.nama_mapel, u.acak_opsi')
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
    
            // Calculate timestamps
            $waktu_selesai = strtotime($h_ujian->tgl_selesai);
            $sisa_waktu = max(0, $waktu_selesai - time());
    
            // Get list soal from JSON
            $list_soal = json_decode($h_ujian->list_soal, true);
            
            // Get soal details using existing function
            $soal_collection = $this->get_soal_details_for_lembar_ujian(
                $list_soal, 
                $h_ujian->acak_opsi === 'Y'
            );
    
            // Prepare data for view
            $data = [
                'user' => $this->ion_auth->user()->row(),
                'judul' => 'Lembar Ujian',
                'subjudul' => $h_ujian->nama_ujian,
                'h_ujian' => $h_ujian,
                'hasil_ujian' => $h_ujian,
                'soal_collection' => $soal_collection,
                'jawaban_tersimpan' => json_decode($h_ujian->list_jawaban, true),
                'id_h_ujian_enc' => $this->encrypt_exam_id($id_h_ujian),
                'waktu_selesai' => $waktu_selesai,
                'sisa_waktu' => $sisa_waktu,
                'jumlah_soal_total_php' => count($list_soal)
            ];
    
            return $data;
    
        } catch (Exception $e) {
            log_message('error', 'Error in get_lembar_ujian_siswa: ' . $e->getMessage());
            return false;
        }
    }

    public function encrypt_exam_id($id)
    {
        $CI =& get_instance();
        try {
            // Always use the same encryption method
            $encrypted = $CI->encryption->encrypt($id);
            return strtr(base64_encode($encrypted), '+/=', '-_,');
        } catch (Exception $e) {
            log_message('error', 'Error encrypting exam ID: ' . $e->getMessage());
            return false;
        }
    }

    public function decrypt_exam_id($encrypted_id)
    {
        $CI =& get_instance();
        try {
            // Reverse the process
            $encrypted_id = urldecode($encrypted_id);
            $encrypted = strtr($encrypted_id, '-_,', '+/=');
            $binary = base64_decode($encrypted);
            
            if ($binary === false) {
                throw new Exception('Invalid encrypted ID format');
            }

            $id = $CI->encryption->decrypt($binary);
            
            if (!$id || !is_numeric($id)) {
                throw new Exception('Failed to decrypt ID');
            }

            return (int)$id;
        } catch (Exception $e) {
            log_message('error', 'Error decrypting exam ID: ' . $e->getMessage());
            return false;
        }
    }

    public function decrypt_examm_id($encrypted_hex_id)
    {
        $CI =& get_instance();
        try {
            if (empty($encrypted_hex_id) || !preg_match('/^[a-f0-9]+$/i', $encrypted_hex_id)) {
                throw new Exception('Invalid encrypted ID format (not hex).');
            }

            // Gunakan AES_DECRYPT dari database
            $decrypted_id_result = $this->db->query(
                "SELECT CAST(AES_DECRYPT(UNHEX(?), ?) AS UNSIGNED) as decrypted_id",
                [$encrypted_hex_id, $CI->config->item('encryption_key')]
            )->row();
            
            if ($decrypted_id_result && isset($decrypted_id_result->decrypted_id) && is_numeric($decrypted_id_result->decrypted_id)) {
                return (int)$decrypted_id_result->decrypted_id;
            } else {
                // Ini akan menghasilkan log "Failed to decrypt ID"
                throw new Exception('Failed to decrypt ID (DB method returned null/invalid).');
            }
        } catch (Exception $e) {
            log_message('error', 'Error decrypting exam ID (DB method): ' . $e->getMessage());
            return false;
        }
    }

    // ========================================================================
    // PENAMBAHAN METHOD UNTUK FITUR HASIL UJIAN SISWA (GURU/ADMIN)
    // ========================================================================

    public function getHasilUjianDatatables($filters = [], $context = [])
    {
        $CI =& get_instance();
        $CI->load->config('config');

        $this->datatables->select('
            DISTINCT h.id,
            s.nisn,
            s.nama as nama_siswa,
            k.nama_kelas,
            j.nama_jenjang,
            m.nama_mapel,
            u.nama_ujian,
            h.jml_benar,
            h.nilai,
            h.status AS status_pengerjaan_raw, /* Raw status from h_ujian */
            h.ujian_id, /* Menggunakan h.ujian_id karena FROM utamanya h_ujian */
            h.siswa_id, /* Menggunakan h.siswa_id */
            ta.id_tahun_ajaran as id_tahun_ajaran_ujian,
            sk.kelas_id as kelas_id_siswa,
            u.mapel_id,
            u.tgl_mulai, /* Needed for dynamic status */
            u.terlambat, /* Needed for dynamic status */
            HEX(AES_ENCRYPT(CAST(h.id AS CHAR), "'.$CI->config->item('encryption_key').'")) as id_hasil_ujian_encrypted
        ', FALSE); // <-- Pastikan FALSE di sini.
        $this->datatables->from('h_ujian h');
        $this->datatables->join('m_ujian u', 'h.ujian_id = u.id_ujian');
        $this->datatables->join('mapel m', 'u.mapel_id = m.id_mapel');
        $this->datatables->join('siswa s', 'h.siswa_id = s.id_siswa');
        $this->datatables->join('siswa_kelas_ajaran sk', 's.id_siswa = sk.siswa_id AND u.id_tahun_ajaran = sk.id_tahun_ajaran');
        $this->datatables->join('kelas k', 'sk.kelas_id = k.id_kelas');
        $this->datatables->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
        $this->datatables->join('tahun_ajaran ta', 'u.id_tahun_ajaran = ta.id_tahun_ajaran', 'left');

        // Komentar ini (sekarang di luar string SELECT) tidak akan menyebabkan masalah SQL.
        // Anda sudah menghapus WHERE h.status = 'completed', jadi ini sudah benar untuk menampilkan semua status.

        if (!empty($filters['id_tahun_ajaran']) && $filters['id_tahun_ajaran'] !== 'all') {
            $this->datatables->where('u.id_tahun_ajaran', $filters['id_tahun_ajaran']);
        }
        if (!empty($filters['kelas_id']) && $filters['kelas_id'] !== 'all') {
            $this->datatables->where('sk.kelas_id', $filters['kelas_id']);
        }
        if (!empty($filters['mapel_id']) && $filters['mapel_id'] !== 'all') {
            $this->datatables->where('u.mapel_id', $filters['mapel_id']);
        }

        if (!$context['is_admin'] && $context['is_guru']) {
            if ($context['id_guru'] && $context['tahun_ajaran_aktif_id']) {
                $this->datatables->join(
                    'guru_mapel_kelas_ajaran gmka_filter',
                    'gmka_filter.mapel_id = u.mapel_id AND gmka_filter.kelas_id = sk.kelas_id AND gmka_filter.id_tahun_ajaran = u.id_tahun_ajaran',
                    'inner'
                );
                $this->datatables->where('gmka_filter.guru_id', $context['id_guru']);
            } else {
                $this->datatables->where('1', '0');
            }
        }

        $this->datatables->add_column('kelas_lengkap', '$1 $2', 'nama_jenjang, nama_kelas');

        $this->datatables->add_column('aksi', '
            <a href="'.base_url('ujian/detail_hasil_ujian/$1').'" class="btn btn-info btn-xs">Lihat Hasil</a>
        ', 'id_hasil_ujian_encrypted');

        return $this->datatables->generate();
    }

    public function get_hasil_ujian_detail_for_guru_admin($id_h_ujian) {
        $this->db->select('
            h.id, h.ujian_id, h.siswa_id, h.list_soal, h.list_jawaban, h.jml_benar, h.nilai_bobot, h.nilai, h.status,
            DATE_FORMAT(h.tgl_mulai, "%d %M %Y %H:%i") as tgl_mulai_formatted,
            DATE_FORMAT(h.tgl_selesai, "%d %M %Y %H:%i") as tgl_selesai_formatted,
            s.nisn, s.nama as nama_siswa,
            k.nama_kelas, j.nama_jenjang, sk.kelas_id as kelas_id_siswa,
            u.nama_ujian, u.jumlah_soal as jumlah_soal_ujian, u.mapel_id, u.guru_id as guru_pembuat_ujian_id,
            m.nama_mapel,
            g.nama_guru as nama_guru_pembuat_ujian,
            ta.nama_tahun_ajaran
        ');
        $this->db->from('h_ujian h');
        $this->db->join('m_ujian u', 'h.ujian_id = u.id_ujian');
        $this->db->join('mapel m', 'u.mapel_id = m.id_mapel');
        $this->db->join('siswa s', 'h.siswa_id = s.id_siswa');
        $this->db->join('siswa_kelas_ajaran sk', 's.id_siswa = sk.siswa_id AND u.id_tahun_ajaran = sk.id_tahun_ajaran');
        $this->db->join('kelas k', 'sk.kelas_id = k.id_kelas');
        $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
        $this->db->join('guru g', 'u.guru_id = g.id_guru', 'left'); // Guru pembuat ujian
        $this->db->join('tahun_ajaran ta', 'u.id_tahun_ajaran = ta.id_tahun_ajaran', 'left');
        $this->db->where('h.id', $id_h_ujian);
        return $this->db->get()->row();
    }

    public function get_soal_details_with_kunci_and_bobot($list_soal_ids) {
        if (empty($list_soal_ids)) {
            return [];
        }
        $this->db->select('id_soal, soal, file, tipe_file, opsi_a, opsi_b, opsi_c, opsi_d, opsi_e, file_a, file_b, file_c, file_d, file_e, jawaban, bobot');
        $this->db->from('tb_soal');
        $this->db->where_in('id_soal', $list_soal_ids);
        return $this->db->get()->result();
    }

    /**
     * Mengambil data ringkasan ujian berdasarkan filter.
     * Digunakan untuk display di atas tabel hasil ujian.
     *
     * @param array $filters Filter: id_tahun_ajaran, mapel_id, kelas_id
     * @param array $context Konteks user: is_admin, is_guru, id_guru, tahun_ajaran_aktif_id
     * @return object|null Data ringkasan (nama_ujian, nama_mapel, nama_guru_pembuat, dll.)
     */
    public function getSummaryUjianData($filters = [], $context = [])
    {
        // Hapus semua komentar dari dalam string SELECT
        $this->db->select('
            u.id_ujian,
            u.nama_ujian,
            m.nama_mapel,
            g.nama_guru AS nama_guru_pembuat,
            u.jumlah_soal,
            u.waktu,
            u.tgl_mulai,
            u.terlambat,
            u.mapel_id AS mapel_id_raw,
            u.id_tahun_ajaran AS id_tahun_ajaran_raw,
            AVG(h.nilai) AS rata_rata_nilai,
            MIN(h.nilai) AS nilai_terendah,
            MAX(h.nilai) AS nilai_tertinggi,
            COUNT(h.id) AS total_peserta_selesai
        ');

        $this->db->from('m_ujian u');
        $this->db->join('mapel m', 'u.mapel_id = m.id_mapel');
        $this->db->join('guru g', 'u.guru_id = g.id_guru');
        $this->db->join('tahun_ajaran ta', 'u.id_tahun_ajaran = ta.id_tahun_ajaran');
        
        $this->db->join('h_ujian h', 'h.ujian_id = u.id_ujian AND h.status = "completed"', 'left');
        $this->db->join('siswa s', 'h.siswa_id = s.id_siswa', 'left');
        $this->db->join('siswa_kelas_ajaran sk', 's.id_siswa = sk.siswa_id AND u.id_tahun_ajaran = sk.id_tahun_ajaran', 'left');
        $this->db->join('kelas k', 'sk.kelas_id = k.id_kelas', 'left');

        // Apply filters
        if (!empty($filters['id_tahun_ajaran']) && $filters['id_tahun_ajaran'] !== 'all') {
            $this->db->where('u.id_tahun_ajaran', $filters['id_tahun_ajaran']);
        }
        if (!empty($filters['mapel_id']) && $filters['mapel_id'] !== 'all') {
            $this->db->where('u.mapel_id', $filters['mapel_id']);
        }
        if (!empty($filters['kelas_id']) && $filters['kelas_id'] !== 'all') {
            $this->db->where('sk.kelas_id', $filters['kelas_id']);
        }

        // Apply guru-specific filters (same as Datatables)
        if (!$context['is_admin'] && $context['is_guru']) {
            if ($context['id_guru'] && $context['tahun_ajaran_aktif_id']) {
                $this->db->join(
                    'guru_mapel_kelas_ajaran gmka_filter',
                    'gmka_filter.mapel_id = u.mapel_id AND gmka_filter.kelas_id = sk.kelas_id AND gmka_filter.id_tahun_ajaran = u.id_tahun_ajaran',
                    'inner'
                );
                $this->db->where('gmka_filter.guru_id', $context['id_guru']);
            } else {
                return null;
            }
        }
        
        // Group by semua kolom non-agregat untuk mendapatkan satu baris ringkasan per ujian unik.
        $this->db->group_by('u.id_ujian, u.nama_ujian, m.nama_mapel, g.nama_guru, u.jumlah_soal, u.waktu, u.tgl_mulai, u.terlambat, u.mapel_id, u.id_tahun_ajaran');
        
        $query = $this->db->get();
        
        return $query->row();
    }

    // Revisi get_nilai_terendah
    public function get_nilai_terendah($filters = [], $context = []) {
        $this->db->select_min('h.nilai', 'nilai_terendah');
        $this->_apply_summary_filters_and_joins($filters, $context); // Gunakan helper join baru
        $this->db->where('h.status', 'completed'); // Hanya hitung dari yang selesai
        $query = $this->db->get('h_ujian h');
        log_message('debug', 'get_nilai_terendah Query: ' . $this->db->last_query());
        return $query->row();
    }

    // Revisi get_nilai_tertinggi
    public function get_nilai_tertinggi($filters = [], $context = []) {
        $this->db->select_max('h.nilai', 'nilai_tertinggi');
        $this->_apply_summary_filters_and_joins($filters, $context); // Gunakan helper join baru
        $this->db->where('h.status', 'completed'); // Hanya hitung dari yang selesai
        $query = $this->db->get('h_ujian h');
        log_message('debug', 'get_nilai_tertinggi Query: ' . $this->db->last_query());
        return $query->row();
    }

    // Revisi get_siswa_dengan_nilai
    public function get_siswa_dengan_nilai($nilai, $filters = [], $context = []) {
        if ($nilai === null || $nilai === '-') { // Jika nilai tidak valid, kembalikan kosong
            return [];
        }
        $this->db->select('s.nama as nama_siswa');
        $this->_apply_summary_filters_and_joins($filters, $context); // Gunakan helper join baru
        $this->db->where('h.nilai', $nilai);
        $this->db->where('h.status', 'completed'); // Hanya dari yang selesai
        $this->db->group_by('s.id_siswa, s.nama'); // Group by untuk nama unik
        $query = $this->db->get('h_ujian h'); // Dari h_ujian
        
        log_message('debug', 'get_siswa_dengan_nilai Query: ' . $this->db->last_query());
        $result_names = [];
        foreach ($query->result() as $row) {
            $result_names[] = $row->nama_siswa;
        }
        return $result_names;
    }

    // New helper method to apply common joins and filters for summary-related queries
    private function _apply_summary_filters_and_joins($filters = [], $context = []) {
        $this->db->join('m_ujian u', 'h.ujian_id = u.id_ujian');
        $this->db->join('mapel m', 'u.mapel_id = m.id_mapel');
        $this->db->join('siswa s', 'h.siswa_id = s.id_siswa'); // Join ke siswa
        $this->db->join('siswa_kelas_ajaran sk', 's.id_siswa = sk.siswa_id AND u.id_tahun_ajaran = sk.id_tahun_ajaran');
        $this->db->join('kelas k', 'sk.kelas_id = k.id_kelas');
        $this->db->join('jenjang j', 'k.id_jenjang = j.id_jenjang', 'left');
        $this->db->join('tahun_ajaran ta', 'u.id_tahun_ajaran = ta.id_tahun_ajaran', 'left');

        // Apply filters
        if (!empty($filters['id_tahun_ajaran']) && $filters['id_tahun_ajaran'] !== 'all') {
            $this->db->where('u.id_tahun_ajaran', $filters['id_tahun_ajaran']);
        }
        if (!empty($filters['mapel_id']) && $filters['mapel_id'] !== 'all') {
            $this->db->where('u.mapel_id', $filters['mapel_id']);
        }
        if (!empty($filters['kelas_id']) && $filters['kelas_id'] !== 'all') {
            $this->db->where('sk.kelas_id', $filters['kelas_id']);
        }

        // Apply guru-specific filters
        if (!$context['is_admin'] && $context['is_guru']) {
            if ($context['id_guru'] && $context['tahun_ajaran_aktif_id']) {
                $this->db->join(
                    'guru_mapel_kelas_ajaran gmka_filter',
                    'gmka_filter.mapel_id = u.mapel_id AND gmka_filter.kelas_id = sk.kelas_id AND gmka_filter.id_tahun_ajaran = u.id_tahun_ajaran',
                    'inner'
                );
                $this->db->where('gmka_filter.guru_id', $context['id_guru']);
            } else {
                // If guru context is invalid, ensure no results
                $this->db->where('1', '0');
            }
        }
    }

    /**
     * Menghapus satu atau beberapa hasil ujian berdasarkan ID.
     * @param array $ids Array berisi ID hasil ujian (id dari tabel h_ujian)
     * @return int Jumlah baris yang berhasil dihapus
     */
    public function delete_hasil_ujian($ids) {
        if (empty($ids) || !is_array($ids)) {
            return 0;
        }
        $this->db->where_in('id', $ids); // Pastikan 'id' adalah nama kolom PRIMARY KEY di h_ujian
        $this->db->delete('h_ujian');
        return $this->db->affected_rows();
    }
}