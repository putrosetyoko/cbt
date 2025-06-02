<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Detail Soal'); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('soal') ?>" class="btn btn-sm btn-warning btn-flat"><i class="fa fa-arrow-left"></i> Kembali ke Bank Soal</a>
            <?php
            // Variabel $is_admin_view dan $logged_in_guru_id sekarang seharusnya dikirim dari controller
            // $soal adalah objek soal yang harus memiliki properti $soal->guru_id

            $show_edit_button = false; // Default

            // Cek apakah pengguna adalah admin
            if (isset($is_admin_view) && $is_admin_view === true) {
                $show_edit_button = true;
            } else {
                // Jika bukan admin, cek apakah pengguna adalah guru pembuat soal
                if (isset($logged_in_guru_id) && $logged_in_guru_id !== null &&
                    isset($soal) && is_object($soal) && property_exists($soal, 'guru_id') && $soal->guru_id !== null) {
                    
                    // Pastikan perbandingan tipe data konsisten (misalnya, keduanya integer)
                    if ((int)$soal->guru_id == (int)$logged_in_guru_id) {
                        $show_edit_button = true;
                    }
                }
            }

            if ($show_edit_button):
            ?>
            <a href="<?= base_url('soal/edit/'.$soal->id_soal) ?>" class="btn btn-sm bg-purple btn-flat"><i class="fa fa-edit"></i> Edit Soal Ini</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-10 col-sm-offset-1">
                <table class="table table-bordered">
                    <tr>
                        <th width="20%">Mata Pelajaran</th>
                        <td><?= htmlspecialchars($soal->nama_mapel ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Jenjang</th>
                        <td><?= htmlspecialchars($soal->nama_jenjang ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Guru Pembuat</th>
                        <td><?= htmlspecialchars($soal->nama_pembuat ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Bobot Soal</th>
                        <td><?= htmlspecialchars($soal->bobot ?? '1'); ?></td>
                    </tr>
                    <tr>
                        <th>Kunci Jawaban</th>
                        <td><b><?= htmlspecialchars(strtoupper($soal->jawaban ?? '')); ?></b></td>
                    </tr>
                </table>
                <hr>
                <h4><b>Pertanyaan:</b></h4>
                <div><?= $soal->soal; // Tampilkan HTML dari summernote ?></div>
                <?php if (!empty($soal->file)) : ?>
                    <p><strong>File Pendukung Soal:</strong></p>
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
                <?php $abjad_detail = ['a', 'b', 'c', 'd', 'e']; ?>
                <?php foreach ($abjad_detail as $abj) : $ABJ = strtoupper($abj); ?>
                    <?php $opsi_field = 'opsi_'.$abj; $file_opsi_field = 'file_'.$abj; ?>
                    <div style="margin-bottom: 15px; padding: 10px; border: 1px solid #eee; <?= $soal->jawaban == $ABJ ? 'background-color: #d4edda; border-left: 5px solid #28a745;' : '' ?>">
                        <b>Opsi <?= $ABJ; ?>:</b>
                        <div><?= $soal->$opsi_field; ?></div>
                        <?php if (!empty($soal->$file_opsi_field)) : ?>
                            <p style="margin-top:5px;"><strong>File Pendukung Opsi <?= $ABJ; ?>:</strong></p>
                            <?php
                            $file_path_opsi = FCPATH . 'uploads/bank_soal/' . $soal->$file_opsi_field;
                            $file_url_opsi = base_url('uploads/bank_soal/' . $soal->$file_opsi_field);
                            if (file_exists($file_path_opsi)) {
                                $file_type_opsi = mime_content_type($file_path_opsi);
                                if (strpos($file_type_opsi, 'image/') === 0) {
                                    echo '<img src="' . $file_url_opsi . '" class="img-responsive" style="max-height: 200px; margin-bottom:5px;" alt="File Opsi '.$ABJ.'">';
                                } elseif (strpos($file_type_opsi, 'audio/') === 0) {
                                    echo '<audio controls><source src="' . $file_url_opsi . '" type="' . $file_type_opsi . '"></audio>';
                                } elseif (strpos($file_type_opsi, 'video/') === 0) {
                                    echo '<video controls width="300"><source src="' . $file_url_opsi . '" type="' . $file_type_opsi . '"></video>';
                                } else {
                                    echo '<a href="' . $file_url_opsi . '" target="_blank">Lihat File Opsi '.$ABJ.'</a>';
                                }
                            } else {
                                echo '<p class="text-muted">File opsi '.$ABJ.' tidak ditemukan.</p>';
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
