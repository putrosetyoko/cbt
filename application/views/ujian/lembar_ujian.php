<?php
// File: application/views/ujian/lembar_ujian.php
// Variabel yang diharapkan dari controller:
// $user, $siswa, $judul, $subjudul
// $ujian_master (objek detail m_ujian)
// $hasil_ujian (objek dari h_ujian, berisi list_soal, list_jawaban, tgl_selesai, waktu_habis_timestamp)
// $soal_collection (array objek soal lengkap yang sudah diurutkan/diacak)
// $jawaban_tersimpan (array jawaban yang sudah disimpan siswa)
// $id_h_ujian_enc (ID h_ujian yang dienkripsi dan url-safe base64)

// Calculate timestamps and prepare encrypted ID
$waktu_selesai = strtotime($hasil_ujian->tgl_selesai);
$waktu_selesai_format = date('Y-m-d H:i:s', $waktu_selesai);

// Ensure proper encryption
$id_h_ujian_enc = strtr(base64_encode($this->encryption->encrypt($h_ujian->id)), '+/=', '-_,'); ?>

<?php if ($waktu_habis_ujian_timestamp_php > 0 && $waktu_habis_ujian_timestamp_php < time() && isset($hasil_ujian) && $hasil_ujian->status !== 'completed'): ?>
    <div class="alert alert-danger text-center">
        <h4>WAKTU HABIS!</h4>
        <p>Waktu pengerjaan ujian Anda telah berakhir.</p>
        <p>Jawaban terakhir Anda akan otomatis diproses.</p>
        <!-- JS akan menghandle redirect atau submit otomatis -->
    </div>
<?php endif; ?>

<?php
// Di bagian atas file, setelah komentar variabel
$waktu_sekarang = time();
$waktu_terlambat = strtotime($h_ujian->batas_masuk);

// Pastikan waktu valid
if ($waktu_terlambat === false) {
    // Jika parsing gagal, gunakan waktu dari tabel m_ujian
    $waktu_terlambat = strtotime($ujian->terlambat);
}

// Hitung sisa waktu
$sisa_waktu = max(0, $waktu_terlambat - $waktu_sekarang);

// Debug waktu
log_message('debug', 'Debug waktu: ' . print_r([
    'waktu_sekarang' => date('Y-m-d H:i:s', $waktu_sekarang),
    'waktu_terlambat' => date('Y-m-d H:i:s', $waktu_terlambat),
    'sisa_waktu' => $sisa_waktu
], true));
?>

<!-- Tambahkan ini di bagian head atau sebelum closing </head> -->
<script type="text/javascript">
// Inisialisasi konfigurasi ujian
window.examConfig = {
    base_url: '<?= base_url() ?>',
    ID_H_UJIAN_ENC_GLOBAL: '<?= $id_h_ujian_enc ?>',
    JUMLAH_SOAL_TOTAL_GLOBAL: <?= count($soal_collection) ?>,
    JAWABAN_TERSIMPAN_GLOBAL: <?= json_encode($jawaban_tersimpan) ?>,
    WAKTU_SELESAI: '<?= date('Y-m-d H:i:s', $waktu_terlambat) ?>',
    WAKTU_HABIS_TIMESTAMP_GLOBAL: <?= intval($waktu_terlambat) ?>, // Pastikan integer valid
    CSRF_TOKEN_NAME_GLOBAL: '<?= $this->security->get_csrf_token_name() ?>',
    CSRF_HASH_GLOBAL: '<?= $this->security->get_csrf_hash() ?>'
};

// Debug log
console.log('Debug waktu:', {
    waktuSekarang: new Date().toISOString(),
    waktuTerlambat: new Date(<?= $waktu_terlambat * 1000 ?>).toISOString(),
    sisaWaktuDetik: <?= $sisa_waktu ?>,
    waktuTerlambatTimestamp: <?= intval($waktu_terlambat) ?>
});
</script>

<div class="row">
    <!-- Kolom Navigasi Soal -->
    <div class="col-md-3">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Navigasi Soal</h3>
            </div>
            <div class="box-body" id="panel-navigasi-soal" style="max-height: 450px; overflow-y: auto;">
                <?php if (!empty($soal_collection)): ?>
                    <?php foreach ($soal_collection as $index => $soal_nav): ?>
                        <?php
                            $no_display = $index + 1;
                            // Inisialisasi default
                            $status_jawaban_nav = 'default'; 
                            $id_soal_nav_item = $soal_nav->id_soal ?? null;

                            if ($id_soal_nav_item && is_array($jawaban_tersimpan_php) && isset($jawaban_tersimpan_php[$id_soal_nav_item])) {
                                $jwb_item_nav = $jawaban_tersimpan_php[$id_soal_nav_item];
                                if (!empty($jwb_item_nav['j'])) {
                                    $status_jawaban_nav = ($jwb_item_nav['r'] ?? 'N') == 'Y' ? 'warning' : 'success';
                                } elseif (($jwb_item_nav['r'] ?? 'N') == 'Y') {
                                    $status_jawaban_nav = 'warning';
                                }
                            }
                        ?>
                        <button type="button" class="btn btn-<?= $status_jawaban_nav ?> btn-soal-nav" data-nomor="<?= $no_display ?>" data-id-soal="<?= $id_soal_nav_item ?>" style="margin:2px; width: 40px; height: 40px;">
                            <?= $no_display ?>
                        </button>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Tidak ada soal.</p>
                <?php endif; ?>
            </div>
            <div class="box-footer">
                <small><span class="bg-success" style="padding: 2px 5px; border-radius:3px;">&nbsp;</span> Sudah Dijawab</small><br>
                <small><span class="bg-warning" style="padding: 2px 5px; border-radius:3px;">&nbsp;</span> Ragu-ragu</small><br>
                <small><span class="bg-gray" style="padding: 2px 5px; border-radius:3px;">&nbsp;</span> Belum Dijawab</small>
            </div>
        </div>
        <div class="text-center">
            <button type="button" id="btn-selesai-ujian" class="btn btn-danger btn-lg btn-flat" style="width:100%;"><i class="fa fa-check-circle"></i> SELESAIKAN UJIAN</button>
        </div>
    </div>

    <!-- Kolom Soal Ujian -->
    <div class="col-md-9">
        <?=form_open('', ['id'=>'form-lembar-ujian'], ['id_h_ujian_enc' => ($id_h_ujian_enc_php ?? ''), 'jumlah_soal_total' => ($jumlah_soal_total_php ?? 0)]);?>
        <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Soal #<span id="display-nomor-soal">1</span> dari <span><?= $jumlah_soal_total_php ?></span></h3>
            <div class="box-tools pull-right">
                <span class="badge bg-red">Sisa Waktu: 
                    <span id="timer-ujian">
                        <?= gmdate('H:i:s', max(0, strtotime($hasil_ujian->tgl_selesai) - time())) ?>
                    </span>
                </span>
            </div>
        </div>
            
            <div id="area-soal-ujian">
            <?php if (!empty($soal_collection)): ?>
                <?php foreach ($soal_collection as $index => $soal_item): ?>
                    <?php
                        $no_soal_aktual = $index + 1;
                        $id_soal_item_current = $soal_item->id_soal ?? null;
                        $jawaban_tersimpan_untuk_soal_ini = '';
                        $status_ragu_soal_ini = false;
                        if ($id_soal_item_current && is_array($jawaban_tersimpan_php) && isset($jawaban_tersimpan_php[$id_soal_item_current])) {
                            $jawaban_tersimpan_untuk_soal_ini = $jawaban_tersimpan_php[$id_soal_item_current]['j'] ?? '';
                            $status_ragu_soal_ini = ($jawaban_tersimpan_php[$id_soal_item_current]['r'] ?? 'N') === 'Y';
                        }
                    ?>
                    <div class="panel-soal" id="soal-<?= $no_soal_aktual ?>" data-id-soal="<?= $id_soal_item_current ?>" style="display: <?= $no_soal_aktual == 1 ? 'block' : 'none'; ?>;">
                        <div class="box-body">
                            <div class="soal-content" style="margin-bottom: 20px;">
                                <?php if (!empty($soal_item->file) && !empty($soal_item->tipe_file)): ?>
                                    <div class="text-center" style="margin-bottom:15px;">
                                        <?= tampil_media(base_url('uploads/bank_soal/'.$soal_item->file), $soal_item->tipe_file); ?>
                                    </div>
                                <?php endif; ?>
                                <?= $soal_item->soal ?? 'Soal tidak tersedia'; ?>
                            </div>
                            
                            <div class="opsi-jawaban">
                                <?php 
                                $opsi_untuk_render = $soal_item->opsi_display ?? [];
                                if(empty($opsi_untuk_render) && isset($soal_item)){
                                    if (isset($soal_item->opsi_a) && $soal_item->opsi_a !== null) $opsi_untuk_render['A'] = ['teks' => $soal_item->opsi_a, 'file' => $soal_item->file_a, 'original_key' => 'A'];
                                    if (isset($soal_item->opsi_b) && $soal_item->opsi_b !== null) $opsi_untuk_render['B'] = ['teks' => $soal_item->opsi_b, 'file' => $soal_item->file_b, 'original_key' => 'B'];
                                    if (isset($soal_item->opsi_c) && $soal_item->opsi_c !== null) $opsi_untuk_render['C'] = ['teks' => $soal_item->opsi_c, 'file' => $soal_item->file_c, 'original_key' => 'C'];
                                    if (isset($soal_item->opsi_d) && $soal_item->opsi_d !== null) $opsi_untuk_render['D'] = ['teks' => $soal_item->opsi_d, 'file' => $soal_item->file_d, 'original_key' => 'D'];
                                    if (isset($soal_item->opsi_e) && $soal_item->opsi_e !== null) $opsi_untuk_render['E'] = ['teks' => $soal_item->opsi_e, 'file' => $soal_item->file_e, 'original_key' => 'E'];
                                }
                                ?>
                                <?php foreach ($opsi_untuk_render as $key_opsi_render => $opsi_data): ?>
                                    <?php
                                        $id_radio = 'opsi_' . strtolower($key_opsi_render) . '_' . ($id_soal_item_current ?? 'unknown');
                                        $checked = ($id_soal_item_current && $jawaban_tersimpan_untuk_soal_ini === $opsi_data['original_key']) ? 'checked' : '';
                                    ?>
                                    <div class="funkyradio">
                                        <div class="funkyradio-success">
                                            <input type="radio" name="jawaban_soal_<?= $id_soal_item_current ?>" 
                                                id="<?= $id_radio ?>" 
                                                value="<?= $opsi_data['original_key'] ?? '' ?>" 
                                                <?= $checked ?>
                                                data-nomor-soal-display="<?= $no_soal_aktual ?>">
                                            <label for="<?= $id_radio ?>">
                                                <div class="huruf_opsi"><?= $key_opsi_render ?></div> 
                                                <div class="opsi-konten">
                                                    <?= $opsi_data['teks'] ?? '' ?>
                                                    <?php if (!empty($opsi_data['file'])): ?>
                                                        <div class="opsi-file-attachment text-center" style="margin-top:5px;">
                                                            <?= tampil_media(base_url('uploads/bank_soal/'.$opsi_data['file'])); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="box-body"><p class="text-center text-danger">Tidak ada soal yang dimuat untuk ujian ini.</p></div>
            <?php endif; ?>
            </div> 
            <div class="box-footer text-center">
                <button type="button" class="btn btn-default btn-flat btn-nav-soal" id="btn-prev-soal" data-navigasi="prev" style="display:none;"><i class="fa fa-chevron-left"></i> Soal Sebelumnya</button>
                <button type="button" class="btn btn-warning btn-flat" id="btn-ragu-ragu">
                    <i class="fa fa-question-circle"></i> <span>Ragu-ragu</span>
                </button>
                <button type="button" class="btn btn-primary btn-flat btn-nav-soal" id="btn-next-soal" data-navigasi="next"><i class="fa fa-chevron-right"></i> Soal Berikutnya</button>
            </div>
        </div>
        <?=form_close();?>
    </div>
</div>
<!-- Memuat file JS di akhir setelah semua elemen HTML ada -->
<script src="<?= base_url('assets/dist/js/app/ujian/lembar_ujian.js') ?>"></script>
