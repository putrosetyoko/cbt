<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Detail Hasil Ujian'); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('ujian/hasil_ujian_siswa') ?>" class="btn btn-sm btn-warning btn-flat"><i class="fa fa-arrow-left"></i> Batal</a>
            <?php
            // Logika untuk tombol edit soal di contoh ini tidak relevan untuk halaman detail hasil ujian siswa.
            // Halaman ini hanya menampilkan hasil, bukan untuk mengedit soal itu sendiri.
            // Jadi, tombol "Edit Soal Ini" tidak akan ditampilkan di sini.
            ?>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-10 col-sm-offset-1">
                <table class="table table-bordered">
                    <tr>
                        <th width="20%">Nama Ujian</th>
                        <td><?= htmlspecialchars($hasil_ujian->nama_ujian ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Mata Pelajaran</th>
                        <td><?= htmlspecialchars($hasil_ujian->nama_mapel ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Guru Pembuat Ujian</th>
                        <td><?= htmlspecialchars($hasil_ujian->nama_guru_pembuat_ujian ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Jumlah Soal</th>
                        <td><?= htmlspecialchars($hasil_ujian->jumlah_soal_ujian ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Waktu Mulai</th>
                        <td><?= htmlspecialchars($hasil_ujian->tgl_mulai_formatted ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Waktu Selesai</th>
                        <td><?= htmlspecialchars($hasil_ujian->tgl_selesai_formatted ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th colspan="2" class="text-center bg-gray">INFORMASI SISWA</th>
                    </tr>
                    <tr>
                        <th>NISN</th>
                        <td><?= htmlspecialchars($hasil_ujian->nisn ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Nama Siswa</th>
                        <td><?= htmlspecialchars($hasil_ujian->nama_siswa ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Kelas</th>
                        <td><?= htmlspecialchars($hasil_ujian->nama_jenjang ?? 'N/A'); ?> <?= htmlspecialchars($hasil_ujian->nama_kelas ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Tahun Ajaran</th>
                        <td><?= htmlspecialchars($hasil_ujian->nama_tahun_ajaran ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Jumlah Jawaban Benar</th>
                        <td><span class="badge bg-green"><?= htmlspecialchars($hasil_ujian->jml_benar ?? '0'); ?></span></td>
                    </tr>
                    <tr>
                        <th>Total Poin Diperoleh</th>
                        <td><span class="badge bg-aqua"><?= htmlspecialchars($hasil_ujian->nilai_bobot ?? '0'); ?></span></td>
                    </tr>
                    <tr>
                        <th>Nilai Akhir (0-100)</th>
                        <td><span class="badge bg-purple" style="font-size: 1.2em;"><?= htmlspecialchars($hasil_ujian->nilai ?? '0'); ?></span></td>
                    </tr>
                </table>
                <hr>

                <h4><b>Detail Jawaban Siswa per Soal:</b></h4>
                <?php $no_soal = 1; ?>
                <?php foreach ($soal_data as $soal) : ?>
                    <div class="callout callout-default" style="margin-bottom: 20px;">
                        <p><strong>Soal No. <?= $no_soal++ ?></strong></p>
                        <div><?= $soal->soal; // Tampilkan HTML dari Summernote ?></div>
                        <?php if (!empty($soal->file)) : ?>
                            <p><strong>File Soal:</strong></p>
                            <?php
                            $file_path_soal = FCPATH . 'uploads/bank_soal/' . $soal->file;
                            $file_url_soal = base_url('uploads/bank_soal/' . $soal->file);
                            if (file_exists($file_path_soal)) {
                                $file_type_soal = mime_content_type($file_path_soal);
                                if (strpos($file_type_soal, 'image/') === 0) {
                                    echo '<img src="' . $file_url_soal . '" class="img-responsive" style="max-height: 300px; margin-bottom:10px;" alt="File Soal">';
                                } elseif (strpos($file_type_soal, 'audio/') === 0) {
                                    echo '<audio controls><source src="' . $file_url_soal . '" type="' . $file_type_soal . '">Browser Anda tidak mendukung elemen audio.</audio>';
                                } elseif (strpos($file_type_soal, 'video/') === 0) {
                                    echo '<video controls width="400"><source src="' . $file_url_soal . '" type="' . $file_type_soal . '">Browser Anda tidak mendukung tag video.</video>';
                                } else {
                                    echo '<a href="' . $file_url_soal . '" target="_blank">Lihat File Soal</a>';
                                }
                            } else {
                                echo '<p class="text-muted">File soal tidak ditemukan.</p>';
                            }
                            ?>
                        <?php endif; ?>
                        <hr>
                        <h4><b>Opsi Jawaban:</b></h4>
                        <?php
                        $opsi_labels = ['A', 'B', 'C', 'D', 'E'];
                        foreach ($opsi_labels as $label) :
                            // Pastikan properti opsi_display ada dari model, yang sudah di-generate di get_soal_details_for_lembar_ujian
                            $opsi_display_item = $soal->opsi_display[$label] ?? null;

                            // Jika opsi tidak ada, lewati
                            if (empty($opsi_display_item['teks']) && empty($opsi_display_item['file'])) continue;

                            $is_siswa_answer = (strtoupper($soal->jawaban_siswa) === $label);
                            $is_correct_answer_key = (strtoupper($soal->jawaban) === $label); // Kunci jawaban asli

                            $box_style = '';
                            $icon_status = '';
                            $text_status = '';

                            if ($is_siswa_answer) {
                                if ($soal->is_correct) {
                                    $box_style = 'background-color: #d4edda; border-left: 5px solid #28a745;'; // Hijau untuk benar
                                    $icon_status = '<i class="fa fa-check-circle text-success"></i>';
                                    $text_status = ' (Jawaban Siswa)';
                                } else {
                                    $box_style = 'background-color: #f8d7da; border-left: 5px solid #dc3545;'; // Merah untuk salah
                                    $icon_status = '<i class="fa fa-times-circle text-danger"></i>';
                                    $text_status = ' (Jawaban Siswa)';
                                }
                            } elseif ($is_correct_answer_key) {
                                $box_style = 'background-color: #cce5ff; border-left: 5px solid #007bff;'; // Biru untuk kunci jawaban jika siswa tidak memilihnya
                                $icon_status = '<i class="fa fa-key text-info"></i>';
                                $text_status = ' (Kunci Jawaban)';
                            }
                        ?>
                            <div style="margin-bottom: 10px; padding: 10px; border: 1px solid #eee; <?= $box_style ?>">
                                <b>Opsi <?= $label ?>:</b>
                                <div><?= $opsi_display_item['teks'] ?? ''; ?></div>
                                <?php if (!empty($opsi_display_item['file'])) : ?>
                                    <p style="margin-top:5px;"><strong>File Pendukung Opsi <?= $label ?>:</strong></p>
                                    <?php
                                    $file_path_opsi = FCPATH . 'uploads/bank_soal/' . $opsi_display_item['file'];
                                    $file_url_opsi = base_url('uploads/bank_soal/' . $opsi_display_item['file']);
                                    if (file_exists($file_path_opsi)) {
                                        $file_type_opsi = mime_content_type($file_path_opsi);
                                        if (strpos($file_type_opsi, 'image/') === 0) {
                                            echo '<img src="' . $file_url_opsi . '" class="img-responsive" style="max-height: 150px; margin-bottom:5px;" alt="File Opsi ' . $label . '">';
                                        } elseif (strpos($file_type_opsi, 'audio/') === 0) {
                                            echo '<audio controls><source src="' . $file_url_opsi . '" type="' . $file_type_opsi . '"></audio>';
                                        } elseif (strpos($file_type_opsi, 'video/') === 0) {
                                            echo '<video controls width="250"><source src="' . $file_url_opsi . '" type="' . $file_type_opsi . '"></video>';
                                        } else {
                                            echo '<a href="' . $file_url_opsi . '" target="_blank">Lihat File Opsi ' . $label . '</a>';
                                        }
                                    } else {
                                        echo '<p class="text-muted">File opsi ' . $label . ' tidak ditemukan.</p>';
                                    }
                                    ?>
                                <?php endif; ?>
                                <?= $icon_status ?> <small><?= $text_status ?></small>
                            </div>
                        <?php endforeach; ?>

                        <p class="mt-3">
                            <strong>Jawaban Siswa:</strong>
                            <span class="<?= $soal->is_correct ? 'text-success' : 'text-danger' ?>">
                                <b><?= !empty($soal->jawaban_siswa) ? htmlspecialchars($soal->jawaban_siswa) : 'Tidak dijawab' ?></b>
                            </span>
                            <br>
                            <strong>Kunci Jawaban:</strong>
                            <span class="text-info">
                                <b><?= htmlspecialchars($soal->jawaban) ?></b>
                            </span>
                            <br>
                            <strong>Bobot Soal:</strong> <?= $soal->bobot ?> |
                            <strong>Poin Diperoleh:</strong> <span class="badge bg-green"><?= $soal->poin_diperoleh ?></span>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Untuk memanggil mime_content_type, pastikan ekstensi fileinfo di PHP aktif.
// Jika tidak, Anda bisa menggunakan pendekatan lain seperti pathinfo() dan mengecek ekstensi.
// Atau, jika Anda yakin tipe_file sudah disimpan di DB:
// $file_type_soal = $soal->tipe_file;
?>