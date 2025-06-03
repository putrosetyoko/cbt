<?php
// File: application/views/ujian/hasil_siswa.php
// Menggunakan template _templates/dashboard/
// Variabel yang diharapkan dari controller:
// $user, $siswa, $judul, $subjudul
// $hasil (objek hasil ujian dari h_ujian, sudah di-join dengan m_ujian dan mapel)

// Atur locale untuk format tanggal Indonesia
if (strpos(setlocale(LC_TIME, 'id_ID.UTF-8'), 'id_ID') === false) {
    setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id', 'Indonesian_Indonesia.1252');
}
?>
<div class="row">
    <div class="col-sm-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Hasil Ujian: <?= htmlspecialchars($hasil->nama_ujian ?? 'Ujian'); ?></h3>
                <div class="box-tools pull-right">
                    <a href="<?= base_url('ujian/list_ujian_siswa') ?>" class="btn btn-sm btn-flat btn-default"><i class="fa fa-arrow-left"></i> Kembali ke Daftar Ujian</a>
                    </div>
            </div>
            <div class="box-body">
                <?php if ($hasil && $hasil->status === 'completed'): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Informasi Ujian</h4>
                            <table class="table table-condensed">
                                <tr>
                                    <th width="40%">Nama Ujian</th>
                                    <td>: <?= htmlspecialchars($hasil->nama_ujian ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th>Mata Pelajaran</th>
                                    <td>: <?= htmlspecialchars($hasil->nama_mapel ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <th>Jumlah Soal Dikerjakan</th>
                                    <td>: <?= count(json_decode($hasil->list_soal, true) ?? []); ?> dari <?= htmlspecialchars($hasil->jumlah_soal_di_m_ujian ?? '0'); ?> soal</td>
                                </tr>
                                <tr>
                                    <th>Waktu Mulai</th>
                                    <td>: <?= !empty($hasil->tgl_mulai) ? strftime('%A, %d %B %Y - %H:%M:%S', strtotime($hasil->tgl_mulai)) . " ".strtoupper(date('T')) : 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <th>Waktu Selesai</th>
                                    <td>: <?= !empty($hasil->tgl_selesai) ? strftime('%A, %d %B %Y - %H:%M:%S', strtotime($hasil->tgl_selesai)) . " ".strtoupper(date('T')) : 'N/A'; ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h4>Hasil Pengerjaan</h4>
                            <table class="table table-condensed">
                                <tr>
                                    <th width="40%">Jumlah Jawaban Benar</th>
                                    <td>: <?= htmlspecialchars($hasil->jml_benar ?? '0'); ?></td>
                                </tr>
                                <tr>
                                    <th>Total Skor Bobot Diperoleh</th>
                                    <td>: <?= htmlspecialchars($hasil->nilai_bobot ?? '0'); ?></td>
                                </tr>
                                <tr>
                                    <th>Nilai Akhir</th>
                                    <td style="font-size: 1.5em; font-weight: bold; color: green;">: <?= htmlspecialchars(number_format($hasil->nilai ?? 0, 2)); ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>: <span class="label label-success">SELESAI</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <p>Terima kasih telah mengikuti ujian ini.</p>
                        </div>
                <?php elseif ($hasil): ?>
                    <div class="alert alert-warning">
                        <h4>Informasi</h4>
                        <p>Status ujian Anda saat ini adalah: <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $hasil->status))); ?></strong>.</p>
                        <p>Jika Anda merasa ini adalah kesalahan, silakan hubungi pengawas.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <p>Hasil ujian tidak dapat ditampilkan atau tidak ditemukan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="<?=base_url()?>assets/dist/js/app/ujian/hasil_siswa.js"></script>
