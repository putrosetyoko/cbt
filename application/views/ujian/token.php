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

<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?=$subjudul?></h3>
        <div class="box-tools pull-right">
            <a href="<?=base_url()?>ujian/list_ujian_siswa" class="btn btn-sm btn-flat btn-warning">
                <i class="fa fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-12">
                <form id="formtoken" method="POST">
                    <input type="hidden" name="<?=$this->security->get_csrf_token_name()?>" value="<?=$this->security->get_csrf_hash()?>">
                    <input type="hidden" name="id_ujian_enc" id="id_ujian_enc" value="<?=$encrypted_id_ujian?>">
                    
                    <div class="form-group">
                        <label for="token">Token Ujian</label>
                        <input type="text" id="token" name="token" class="form-control" required>
                        <small class="help-block text-muted">Tanyakan token kepada pengawas</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" id="btncek" class="btn btn-primary btn-flat btn-block">MULAI UJIAN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
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
                        icon: 'success',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(function() {
                        // Force redirect to lembar_ujian
                        window.location.href = response.redirect_url;
                    });
                } else {
                    Swal.fire({
                        title: 'Gagal!',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat memproses token',
                    icon: 'error'
                });
            }
        });
    });
});
</script>

<script src="<?= base_url('assets/dist/js/app/ujian/token.js') ?>"></sc>

