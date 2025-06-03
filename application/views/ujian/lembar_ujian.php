<?php
// File: application/views/ujian/lembar_ujian_siswa.php
// Menggunakan template _templates/topnav/
// Variabel yang diharapkan dari controller:
// $user, $siswa, $judul, $subjudul
// $ujian_master (objek detail m_ujian)
// $hasil_ujian (objek dari h_ujian, berisi list_soal, list_jawaban, tgl_selesai/waktu_habis_timestamp)
// $soal_collection (array objek soal lengkap yang sudah diurutkan/diacak)
// $jawaban_tersimpan (array jawaban yang sudah disimpan siswa)
// $id_h_ujian_enc (ID h_ujian yang dienkripsi)

$jumlah_soal_total = count($soal_collection);
?>

<?php if (strtotime($h_ujian->tgl_selesai) < time()): ?>
    <div class="alert alert-danger text-center">
        <h4>WAKTU HABIS!</h4>
        <p>Waktu pengerjaan ujian Anda telah berakhir.</p>
        <p>Jawaban terakhir Anda telah disimpan. Sistem akan segera memproses hasil ujian Anda.</p>
        <p><a href="<?= base_url('ujian/selesaikan_ujian_otomatis/' . rawurlencode($id_h_ujian_enc)) ?>" class="btn btn-primary">Lihat Hasil (jika tersedia) atau Kembali</a></p>
    </div>
    <?php return; ?>
<?php endif; ?>

<!-- Di bagian awal file, tambahkan variabel JavaScript -->
<script>
    var BASE_URL = "<?= base_url() ?>";
    var CSRF_TOKEN_NAME = "<?= $this->security->get_csrf_token_name() ?>";
    var CSRF_HASH = "<?= $this->security->get_csrf_hash() ?>";
    var WAKTU_SELESAI = "<?= date('Y-m-d H:i:s', strtotime($h_ujian->tgl_selesai)) ?>";
    var SISA_WAKTU = <?= max(0, strtotime($h_ujian->tgl_selesai) - time()) ?>;
</script>

<div class="row">
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
                            $status_jawaban = 'default'; // Belum dijawab
                            $jawaban_saat_ini = '';
                            if (isset($jawaban_tersimpan[$soal_nav->id_soal])) {
                                $jwb_item = $jawaban_tersimpan[$soal_nav->id_soal];
                                $jawaban_saat_ini = $jwb_item['j'];
                                if (!empty($jawaban_saat_ini)) {
                                    $status_jawaban = ($jwb_item['r'] == 'Y') ? 'warning' : 'success'; // Ragu-ragu atau sudah dijawab
                                }
                            }
                        ?>
                        <button type="button" class="btn btn-<?= $status_jawaban ?> btn-soal-nav" data-nomor="<?= $no_display ?>" data-id-soal="<?= $soal_nav->id_soal ?>" style="margin:2px; width: 40px; height: 40px;">
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

    <div class="col-md-9">
        <?=form_open('', ['id'=>'form-lembar-ujian'], ['id_h_ujian_enc' => $id_h_ujian_enc, 'jumlah_soal_total' => $jumlah_soal_total]);?>
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Soal #<span id="display-nomor-soal">1</span></h3>
                <div class="box-tools pull-right">
                    <span class="badge bg-red">
                        Sisa Waktu: <span id="timer-ujian" 
                            data-waktu-selesai="<?= date('Y-m-d H:i:s', strtotime($h_ujian->tgl_selesai)) ?>"
                            data-sisa-waktu="<?= max(0, strtotime($h_ujian->tgl_selesai) - time()) ?>">
                            <?= gmdate('H:i:s', max(0, strtotime($h_ujian->tgl_selesai) - time())) ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <div id="area-soal-ujian">
            <?php if (!empty($soal_collection)): ?>
                <?php foreach ($soal_collection as $index => $soal_item): ?>
                    <?php
                        $no_soal_aktual = $index + 1;
                        $jawaban_tersimpan_untuk_soal_ini = $jawaban_tersimpan[$soal_item->id_soal]['j'] ?? '';
                        $status_ragu_soal_ini = ($jawaban_tersimpan[$soal_item->id_soal]['r'] ?? 'N') === 'Y';
                    ?>
                    <div class="panel-soal" id="soal-<?= $no_soal_aktual ?>" data-id-soal="<?= $soal_item->id_soal ?>" style="display: <?= $no_soal_aktual == 1 ? 'block' : 'none'; ?>;">
                        <div class="box-body">
                            <input type="hidden" name="current_id_soal_<?= $no_soal_aktual ?>" value="<?= $soal_item->id_soal ?>">
                            <input type="hidden" name="current_ragu_<?= $no_soal_aktual ?>" id="current_ragu_status_<?= $no_soal_aktual ?>" value="<?= $status_ragu_soal_ini ? 'Y' : 'N' ?>">

                            <div class="soal-content" style="margin-bottom: 20px;">
                                <?php if (!empty($soal_item->file) && !empty($soal_item->tipe_file)): ?>
                                    <div class="text-center" style="margin-bottom:15px;">
                                        <?= tampil_media(base_url('uploads/bank_soal/'.$soal_item->file), $soal_item->tipe_file); ?>
                                    </div>
                                <?php endif; ?>
                                <?= $soal_item->soal; // Isi soal (HTML) ?>
                            </div>
                            
                            <div class="opsi-jawaban">
                                <?php 
                                $opsi_untuk_render = $soal_item->opsi_display ?? []; // opsi_display dari model jika ada acak opsi
                                if(empty($opsi_untuk_render)){ // Fallback jika opsi_display tidak ada (tidak diacak)
                                    if (isset($soal_item->opsi_a) && $soal_item->opsi_a !== null) $opsi_untuk_render['A'] = ['teks' => $soal_item->opsi_a, 'file' => $soal_item->file_a, 'original_key' => 'A'];
                                    if (isset($soal_item->opsi_b) && $soal_item->opsi_b !== null) $opsi_untuk_render['B'] = ['teks' => $soal_item->opsi_b, 'file' => $soal_item->file_b, 'original_key' => 'B'];
                                    if (isset($soal_item->opsi_c) && $soal_item->opsi_c !== null) $opsi_untuk_render['C'] = ['teks' => $soal_item->opsi_c, 'file' => $soal_item->file_c, 'original_key' => 'C'];
                                    if (isset($soal_item->opsi_d) && $soal_item->opsi_d !== null) $opsi_untuk_render['D'] = ['teks' => $soal_item->opsi_d, 'file' => $soal_item->file_d, 'original_key' => 'D'];
                                    if (isset($soal_item->opsi_e) && $soal_item->opsi_e !== null) $opsi_untuk_render['E'] = ['teks' => $soal_item->opsi_e, 'file' => $soal_item->file_e, 'original_key' => 'E'];
                                }
                                ?>
                                <?php foreach ($opsi_untuk_render as $key_opsi_render => $opsi_data): // $key_opsi_render A,B,C,D,E ?>
                                    <?php
                                        $id_radio = 'opsi_' . strtolower($key_opsi_render) . '_' . $soal_item->id_soal;
                                        // Jawaban yang dipilih siswa adalah kunci asli (A,B,C,D,E)
                                        $checked = ($jawaban_tersimpan_untuk_soal_ini === $opsi_data['original_key']) ? 'checked' : '';
                                    ?>
                                    <div class="funkyradio">
                                        <div class="funkyradio-success">
                                            <input type="radio" name="jawaban_soal_<?= $soal_item->id_soal ?>" 
                                                id="<?= $id_radio ?>" 
                                                   value="<?= $opsi_data['original_key'] // Simpan kunci asli sebagai value ?>" 
                                                <?= $checked ?>
                                                data-nomor-soal-display="<?= $no_soal_aktual ?>">
                                            <label for="<?= $id_radio ?>">
                                                <div class="huruf_opsi"><?= $key_opsi_render ?></div> 
                                                <div class="opsi-konten">
                                                    <?= $opsi_data['teks'] ?>
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
            </div> <div class="box-footer text-center">
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
<script src="<?= base_url('assets/dist/js/app/ujian/lembar_ujian.js') ?>"></script>
