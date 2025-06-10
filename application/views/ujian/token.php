<?php
// File: application/views/ujian/konfirmasi_siswa.php
// Menggunakan template _templates/topnav/
// Variabel yang diharapkan dari controller:
// $user (objek user Ion Auth)
// $siswa (objek detail siswa)
// $ujian (objek detail ujian dari m_ujian)
// $judul
// $subjudul
// $id_ujian_enc (ID ujian yang dienkripsi)

// Atur locale untuk format tanggal Indonesia
// Sebaiknya dilakukan di config atau helper global
if (strpos(setlocale(LC_TIME, 'id_ID.UTF-8'), 'id_ID') === false) {
    setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id', 'Indonesian_Indonesia.1252');
}
?>

<div class="row">
    <div class="col-md-12">
        <div class="callout callout-info">
            <h4><i class="fa fa-info-circle"></i> INFORMASI PENTING UJIAN</h4>
            <p>Harap baca semua instruksi dengan seksama sebelum memulai ujian.</p>
            <ul>
                <li>Peserta ujian diwajibkan mengerjakan soal secara jujur dan mandiri.</li>
                <li>Waktu pengerjaan ujian terbatas sesuai dengan durasi yang telah ditentukan.</li>
                <li>Tombol <strong>"MULAI UJIAN"</strong> akan aktif jika token benar dan waktu ujian telah tiba.</li>
                <li>Pastikan koneksi internet Anda stabil selama ujian berlangsung.</li>
                <li>Dilarang keluar dari halaman ujian atau beralih ke aplikasi/browser lain selama ujian.</li>
                <li>Periksa kembali semua jawaban Anda sebelum menekan tombol "Selesai".</li>
                <li>Jika terjadi kendala teknis, segera hubungi pengawas ujian.</li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <!-- Data Peserta Section -->
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-user"></i> Data Peserta</h3>
            </div>
            <div class="box-body">
                <table class="table table-striped table-condensed">
                    <tr>
                        <th>Nama Siswa</th>
                        <td width="80%">: <?= $siswa->nama_siswa ?></td>
                    </tr>
                    <tr>
                        <th>NISN</th>
                        <td>: <?= $siswa->nisn ?></td>
                    </tr>
                    <tr>
                        <th>Kelas</th>
                        <td>: <?= $ujian->nama_jenjang ?> <?= $siswa->nama_kelas ?></td>
                    </tr>
                </table>
            </div>

            <!-- Divider -->
            <hr style="margin: 0; border-top: 1px solid #f4f4f4;">

            <!-- Detail Ujian Section -->
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-file-text"></i> Detail Ujian</h3>
            </div>
            <div class="box-body">
                <table class="table table-striped table-condensed">
                    <tr>
                        <th>Nama Ujian</th>
                        <td>: <?= $ujian->nama_ujian ?></td>
                    </tr>
                    <tr>
                        <th>Mata Pelajaran</th>
                        <td width="80%">: <?= $ujian->nama_mapel ?> - Kelas <?= $ujian->nama_jenjang ?></td>
                    </tr> 
                    <tr>
                        <th>Guru Mata Pelajaran</th>
                        <td>: <?= $ujian->nama_guru_pengajar ?? 'Belum ditentukan' ?></td>
                    </tr> 
                    <tr>
                        <th>Hari/Tanggal</th>
                        <td>: <?= strftime('%A, %d %B %Y', strtotime($ujian->tgl_mulai)) ?></td>
                    </tr>
                    <tr>
                        <th>Waktu</th>
                        <td>: <?= date('H.i', strtotime($ujian->tgl_mulai)) ?>-<?= date('H.i', strtotime($ujian->terlambat)) ?></td>
                    </tr>
                    <!-- <tr>
                        <th>Jumlah Soal</th>
                        <td>: <?= $ujian->jumlah_soal ?> soal</td>
                    </tr> -->
                </table>
            </div>

            <!-- Divider -->
            <hr style="margin: 0; border-top: 1px solid #f4f4f4;">

            <!-- Token Input Section -->
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-key"></i> Masukkan Token Ujian</h3>
            </div>
            <div class="box-body">
                <form id="formToken" method="POST">
                    <input type="hidden" name="<?=$this->security->get_csrf_token_name()?>" value="<?=$this->security->get_csrf_hash()?>">
                    <input type="hidden" name="id_ujian_enc" id="id_ujian_enc" value="<?=$encrypted_id_ujian?>">
                    
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                            <input type="text" id="token" name="token" 
                                class="form-control input-lg text-center" 
                                style="font-size: 24px; letter-spacing: 10px;"
                                maxlength="5"
                                placeholder="•••••" required autocomplete="off">
                        </div>
                        <small class="help-block text-center text-muted">Token diberikan oleh Pengawas Ujian.</small>
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" id="btncek" class="btn bg-green btn-md btn-block">
                            <i class="fa fa-play"></i> MULAI UJIAN
                        </button>
                        <a href="<?=base_url()?>ujian/list_ujian_siswa" class="btn btn-warning btn-block" style="margin-top: 10px;">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Keep existing scripts -->
<script>
    var csrf_name = '<?=$this->security->get_csrf_token_name()?>';
    var csrf_hash = '<?=$this->security->get_csrf_hash()?>';
    var base_url = '<?=base_url()?>';
</script>

<script>
$(document).ready(function() {
    $('#formToken').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?=base_url()?>ujian/proses_token',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.status) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: response.message,
                        type: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                    }).then(function() {
                        // Force redirect to lembar_ujian
                        window.location.href = response.redirect_url;
                    });
                } else {
                    Swal.fire({
                        title: 'Gagal!',
                        text: response.message,
                        type: 'error'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat memproses token',
                    type: 'error'
                });
            }
        });
    });
});
</script>

<script src="<?= base_url('assets/dist/js/app/ujian/token.js') ?>"></script>

