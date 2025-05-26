<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ujian_model extends CI_Model {
    
    public function getDataUjian($id)
    {
        $this->datatables->select('a.id_ujian, a.token, a.nama_ujian, b.nama_mapel, a.jumlah_soal, CONCAT(a.tgl_mulai, " <br/> (", a.waktu, " Menit)") as waktu, a.jenis'); // Changed matkul to mapel
        $this->datatables->from('m_ujian a');
        $this->datatables->join('mapel b', 'a.mapel_id = b.id_mapel'); // Changed matkul to mapel
        if($id!==null){
            $this->datatables->where('guru_id', $id); // Changed dosen_id to guru_id
        }
        return $this->datatables->generate();
    }
    
    public function getListUjian($id, $kelas)
    {
        $this->datatables->select("a.id_ujian, e.nama_guru, d.nama_kelas, a.nama_ujian, b.nama_mapel, a.jumlah_soal, CONCAT(a.tgl_mulai, ' <br/> (', a.waktu, ' Menit)') as waktu,  (SELECT COUNT(id) FROM h_ujian h WHERE h.siswa_id = {$id} AND h.ujian_id = a.id_ujian) AS ada"); // Changed nama_dosen to nama_guru, nama_matkul to nama_mapel, mahasiswa_id to siswa_id
        $this->datatables->from('m_ujian a');
        $this->datatables->join('mapel b', 'a.mapel_id = b.id_mapel'); // Changed matkul to mapel
        $this->datatables->join('kelas_guru c', "a.guru_id = c.guru_id"); // Changed kelas_dosen to kelas_guru, dosen_id to guru_id
        $this->datatables->join('kelas d', 'c.kelas_id = d.id_kelas');
        $this->datatables->join('guru e', 'e.id_guru = c.guru_id'); // Changed dosen to guru, id_dosen to id_guru, dosen_id to guru_id
        $this->datatables->where('d.id_kelas', $kelas);
        return $this->datatables->generate();
    }

    public function getUjianById($id)
    {
        $this->db->select('*');
        $this->db->from('m_ujian a');
        $this->db->join('guru b', 'a.guru_id=b.id_guru'); // Changed dosen to guru, dosen_id to guru_id
        $this->db->join('mapel c', 'a.mapel_id=c.id_mapel'); // Changed matkul to mapel
        $this->db->where('id_ujian', $id);
        return $this->db->get()->row();
    }

    public function getIdGuru($nip) // Renamed function from getIdDosen
    {
        $this->db->select('id_guru, nama_guru')->from('guru')->where('nip', $nip); // Changed id_dosen to id_guru, nama_dosen to nama_guru, dosen to guru
        return $this->db->get()->row();
    }

    public function getJumlahSoal($guru) // Changed dosen to guru
    {
        $this->db->select('COUNT(id_soal) as jml_soal');
        $this->db->from('tb_soal');
        $this->db->where('guru_id', $guru); // Changed dosen_id to guru_id
        return $this->db->get()->row();
    }

    public function getIdSiswa($nisn) // Renamed function from getIdMahasiswa
    {
        $this->db->select('*');
        $this->db->from('siswa a'); // Changed mahasiswa to siswa
        $this->db->join('kelas b', 'a.kelas_id=b.id_kelas');
        // Removed join to jurusan as per database schema
        // $this->db->join('jurusan c', 'b.jurusan_id=c.id_jurusan'); 
        $this->db->where('nisn', $nisn); // Changed nim to nisn
        return $this->db->get()->row();
    }

    public function HslUjian($id, $siswa_id) // Changed mhs to siswa_id
    {
        $this->db->select('*, UNIX_TIMESTAMP(tgl_selesai) as waktu_habis');
        $this->db->from('h_ujian');
        $this->db->where('ujian_id', $id);
        $this->db->where('siswa_id', $siswa_id); // Changed mahasiswa_id to siswa_id
        return $this->db->get();
    }

    public function getSoal($id)
    {
        $ujian = $this->getUjianById($id);
        $order = $ujian->jenis==="acak" ? 'rand()' : 'id_soal';

        $this->db->select('
        id_soal, soal, file, tipe_file, opsi_a, opsi_b, opsi_c, 
        opsi_d, opsi_e,
        file_a, file_b, file_c, file_d, file_e, 
        jawaban');
        $this->db->from('tb_soal');
        $this->db->where('guru_id', $ujian->guru_id); // Changed dosen_id to guru_id
        $this->db->where('mapel_id', $ujian->mapel_id); // Changed matkul_id to mapel_id
        $this->db->order_by($order);
        $this->db->limit($ujian->jumlah_soal);
        return $this->db->get()->result();
    }

    public function ambilSoal($pc_urut_soal1, $pc_urut_soal_arr)
    {
        $this->db->select("*, {$pc_urut_soal1} AS jawaban");
        $this->db->from('tb_soal');
        $this->db->where('id_soal', $pc_urut_soal_arr);
        return $this->db->get()->row();
    }

    public function getJawaban($id_tes)
    {
        $this->db->select('list_jawaban');
        $this->db->from('h_ujian');
        $this->db->where('id', $id_tes);
        return $this->db->get()->row()->list_jawaban;
    }

    public function getHasilUjian($nip = null)
    {
        $this->datatables->select('b.id_ujian, b.nama_ujian, b.jumlah_soal, CONCAT(b.waktu, " Menit") as waktu, b.tgl_mulai');
        $this->datatables->select('c.nama_mapel, d.nama_guru'); // Changed nama_matkul to nama_mapel, nama_dosen to nama_guru
        $this->datatables->from('h_ujian a');
        $this->datatables->join('m_ujian b', 'a.ujian_id = b.id_ujian');
        $this->datatables->join('mapel c', 'b.mapel_id = c.id_mapel'); // Changed matkul to mapel
        $this->datatables->join('guru d', 'b.guru_id = d.id_guru'); // Changed dosen to guru, id_dosen to id_guru
        $this->datatables->group_by('b.id_ujian');
        if($nip !== null){
            $this->datatables->where('d.nip', $nip);
        }
        return $this->datatables->generate();
    }

    public function HslUjianById($id, $dt=false)
    {
        if($dt===false){
            $db = "db";
            $get = "get";
        }else{
            $db = "datatables";
            $get = "generate";
        }
        
        $this->$db->select('d.id, a.nama, b.nama_kelas, d.jml_benar, d.nilai'); // Removed c.nama_jurusan
        $this->$db->from('siswa a'); // Changed mahasiswa to siswa
        $this->$db->join('kelas b', 'a.kelas_id=b.id_kelas');
        // Removed join to jurusan as per database schema
        // $this->$db->join('jurusan c', 'b.jurusan_id=c.id_jurusan');
        $this->$db->join('h_ujian d', 'a.id_siswa=d.siswa_id'); // Changed id_mahasiswa to id_siswa, mahasiswa_id to siswa_id
        $this->$db->where(['d.ujian_id' => $id]);
        return $this->$db->$get();
    }

    public function bandingNilai($id)
    {
        $this->db->select_min('nilai', 'min_nilai');
        $this->db->select_max('nilai', 'max_nilai');
        $this->db->select_avg('FORMAT(FLOOR(nilai),0)', 'avg_nilai');
        $this->db->where('ujian_id', $id);
        return $this->db->get('h_ujian')->row();
    }

    // New functions added for student login logic
    public function getUjianByToken($token)
    {
        $this->db->select('*');
        $this->db->from('m_ujian');
        $this->db->where('token', $token);
        // You might want to add more conditions here, e.g., 'aktif' status or time range
        // $this->db->where('status_ujian', 'aktif');
        // $this->db->where('tgl_mulai <= NOW()');
        // $this->db->where('tgl_selesai >= NOW()'); // Assuming tgl_selesai exists in m_ujian or calculate based on waktu
        return $this->db->get()->row();
    }

    public function getAllowedClassesForUjian($ujian_id)
    {
        // This function needs to determine which classes are allowed for a given exam.
        // This is based on the assumption that m_ujian is linked to guru_id, and guru_id is linked to kelas_guru.
        $this->db->select('DISTINCT d.id_kelas, d.nama_kelas');
        $this->db->from('m_ujian a');
        $this->db->join('guru b', 'a.guru_id = b.id_guru'); // Join to guru table
        $this->db->join('kelas_guru c', 'b.id_guru = c.guru_id'); // Join to kelas_guru to find associated classes
        $this->db->join('kelas d', 'c.kelas_id = d.id_kelas'); // Join to kelas to get class details
        $this->db->where('a.id_ujian', $ujian_id);
        return $this->db->get()->result();
    }

}