<?php
// File: application/views/ujian/lembar_ujian.php
// Variabel yang diharapkan dari controller:
// $user, $siswa, $judul, $subjudul
// $ujian (objek detail m_ujian, dari Ujian_model->get_ujian_by_id_with_guru)
// $h_ujian (objek dari h_ujian, berisi list_soal, list_jawaban, tgl_selesai, waktu_habis_timestamp)
// $soal_collection (array objek soal lengkap yang sudah diurutkan/diacak)
// $jawaban_tersimpan_php (array jawaban yang sudah disimpan siswa, di-decode dari h_ujian->list_jawaban)

// Calculate timestamps and prepare encrypted ID
$waktu_selesai_ujian = strtotime($h_ujian->tgl_selesai); // Waktu selesai dari h_ujian
$waktu_selesai_format_js = date('Y-m-d H:i:s', $waktu_selesai_ujian);

// Ensure proper encryption for JS
$id_h_ujian_enc_for_js = strtr(base64_encode($this->encryption->encrypt($h_ujian->id)), '+/=', '-_,');

// Hitung sisa waktu untuk tampilan awal timer
$sisa_waktu_awal = max(0, $waktu_selesai_ujian - time());
?>

<?php if (isset($h_ujian) && $h_ujian->status !== 'completed' && $sisa_waktu_awal <= 0): ?>
    <div class="alert alert-danger text-center">
        <h4>WAKTU HABIS!</h4>
        <p>Waktu pengerjaan ujian Anda telah berakhir.</p>
        <p>Jawaban terakhir Anda akan otomatis diproses.</p>
    </div>
<?php endif; ?>

<script type="text/javascript">
// Inisialisasi konfigurasi ujian
window.examConfig = {
    base_url: '<?= base_url() ?>',
    ID_H_UJIAN_ENC_GLOBAL: '<?= $id_h_ujian_enc_for_js ?>',
    JUMLAH_SOAL_TOTAL_GLOBAL: <?= count($soal_collection) ?>,
    // PENTING: encode $jawaban_tersimpan_php sebagai objek JSON
    JAWABAN_TERSIMPAN_GLOBAL: <?= json_encode($jawaban_tersimpan_php) ?>,
    WAKTU_SELESAI: '<?= $waktu_selesai_format_js ?>', // Waktu selesai untuk ditampilkan
    WAKTU_HABIS_TIMESTAMP_GLOBAL: <?= intval($waktu_selesai_ujian) ?>, // Unix timestamp untuk JS timer
    CSRF_TOKEN_NAME_GLOBAL: '<?= $this->security->get_csrf_token_name() ?>',
    CSRF_HASH_GLOBAL: '<?= $this->security->get_csrf_hash() ?>',

    siswa: {
        nisn: '<?= htmlspecialchars($siswa->nisn ?? ''); ?>',
        nama: '<?= htmlspecialchars($siswa->nama_siswa ?? ''); ?>'
    },
    ujian: {
        nama_ujian: '<?= htmlspecialchars($ujian->nama_ujian ?? ''); ?>'
    },

    // ANTI-CHEATING SETTINGS (NEW)
    enableAntiCheating: true, // Set to false to disable these features
    maxCheatAttempts: 3,
    redirectUrlOnCheat: '<?= base_url('auth/logout') ?>',
    confirmFinishMessage: 'Setelah ujian diselesaikan, Anda tidak dapat mengubah jawaban Anda lagi.',
    unansweredWarning: 'Perhatian: Ada {count} soal yang belum Anda jawab.',
    doubtfulWarning: 'Ada {count} soal yang masih ditandai ragu-ragu.',
    exitFullscreenWarning: 'Anda keluar dari mode layar penuh. Ini akan dihitung sebagai pelanggaran!',
    tabChangeWarning: 'Anda beralih tab atau keluar dari jendela browser. Ini akan dihitung sebagai pelanggaran!',
    cheatAttemptExceeded: 'Anda telah melewati batas maksimal pelanggaran. Ujian akan diakhiri.',
    
    startTime: Date.now() // Timestamp saat halaman dimuat, untuk perhitungan waktu yang lebih akurat
};
</script>

<style>
body {
    -webkit-user-select: none; /* Safari */
    -moz-user-select: none; /* Firefox */
    -ms-user-select: none; /* IE 10+ */
    user-select: none; /* Standard syntax */
}
/* Opsional: Sembunyikan scrollbar jika fullscreen */
/* html.fullscreen-mode, body.fullscreen-mode { overflow: hidden; } */
</style>

<link rel="stylesheet" href="<?= base_url('assets/dist/css/ujian.css') ?>">
<link href="https://cdnjs.cloudflare.com/ajax/libs/medium-zoom/1.0.6/medium-zoom.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/medium-zoom/1.0.6/medium-zoom.min.js"></script>

<div id="fullscreen-prompt" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    z-index: 9999;
    text-align: center;
    font-size: 1.5em;
    padding: 20px;
">
    <h3>Ujian Siap Dimulai!</h3>
    <p>Untuk menghindari kecurangan, ujian ini akan berjalan dalam mode layar penuh.</p>
    <p>Pastikan Anda <b>TIDAK BERALIH APLIKASI/WEB</b> atau <b>KELUAR DARI MODE LAYAR PENUH</b> selama ujian. Pelanggaran akan DICATAT!</p>
    <button id="start-fullscreen-exam" class="btn btn-lg btn-success" style="margin-top: 20px;">
        <i class="fa fa-play-circle"></i> Mulai Ujian dalam Layar Penuh
    </button>
</div>

<div class="row">
    <div class="col-sm-3">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Navigasi Soal</h3>
                <div class="box-tools pull-right">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse">
                        <i class="fa fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="box-body" id="panel-navigasi-soal" style="max-height: 450px; overflow-y: auto;">
                <?php if (!empty($soal_collection)): ?>
                    <?php foreach ($soal_collection as $index => $soal_nav): ?>
                        <?php
                            $no_display = $index + 1;
                            $id_soal_nav_item = $soal_nav['id_soal'] ?? null;
                            
                            // Logika PHP untuk menentukan warna awal tombol navigasi
                            $status_jawaban_nav_class = 'btn-default'; // Default: abu-abu (belum dijawab)
                            if ($id_soal_nav_item && is_array($jawaban_tersimpan_php) && isset($jawaban_tersimpan_php[$id_soal_nav_item])) {
                                $jwb_item_nav = $jawaban_tersimpan_php[$id_soal_nav_item];
                                if (!empty($jwb_item_nav['j'])) {
                                    $status_jawaban_nav_class = (($jwb_item_nav['r'] ?? 'N') == 'Y') ? 'btn-warning' : 'btn-success';
                                } elseif (($jwb_item_nav['r'] ?? 'N') == 'Y') {
                                    $status_jawaban_nav_class = 'btn-warning';
                                }
                            }
                        ?>
                        <button type="button" class="btn <?= $status_jawaban_nav_class ?> btn-soal-nav" 
                            data-nomor="<?= $no_display ?>" 
                            data-id-soal="<?= $id_soal_nav_item ?>" 
                            style="margin:2px; width: 40px; height: 40px;">
                            <?= $no_display ?>
                        </button>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">Tidak ada soal.</p>
                <?php endif; ?>
            </div>
            <div class="box-footer">
                <div class="legend-container">
                    <div class="legend-item">
                        <h5>Keterangan:</h5>
                        <span class="badge bg-green">&nbsp;</span> Sudah Dijawab
                    </div>
                    <div class="legend-item">
                        <span class="badge bg-yellow">&nbsp;</span> Ragu-ragu
                    </div>
                    <div class="legend-item">
                        <span class="badge bg-gray">&nbsp;</span> Belum Dijawab
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <span class="badge bg-blue">
                        No. <span id="display-nomor-soal">1</span> dari <?= count($soal_collection) ?> Soal
                    </span>
                </h3>
                <div class="box-tools pull-right">
                    <span class="badge bg-red">
                        <i class="fa fa-clock-o"></i>   
                        <span id="timer-ujian">
                            <?= gmdate('H:i:s', $sisa_waktu_awal) ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <div class="box-body">
                <div id="area-soal-ujian">
                    <?php if (!empty($soal_collection)): ?>
                        <?php foreach ($soal_collection as $index => $soal_item): ?>
                            <?php
                                $no_soal_aktual = $index + 1;
                                $id_soal_item_current = $soal_item['id_soal'] ?? null; 
                            ?>
                            <div class="panel-soal" id="soal-<?= $no_soal_aktual ?>" data-id-soal="<?= $id_soal_item_current ?>" style="display: <?= $no_soal_aktual == 1 ? 'block' : 'none'; ?>;">
                                <div class="box-body">
                                <div class="soal-content">
                                    <?php 
                                    $cleaned_soal_file = trim($soal_item['file'] ?? ''); 
                                    if (!empty($cleaned_soal_file)): 
                                    ?>
                                        <div class="soal-media">
                                            <?php $relative_path_soal_file = 'uploads/bank_soal/' . $cleaned_soal_file; ?>
                                            <?= tampil_media($relative_path_soal_file) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="soal-text mb-3">
                                    <?= $soal_item['soal'] ?>
                                </div>

                                <div class="opsi-jawaban">
                                    <?php 
                                    $opsi_untuk_render = $soal_item['opsi_display'] ?? []; 
                                    ?>
                                    <?php foreach ($opsi_untuk_render as $key_opsi_render => $opsi_data): 
                                        $id_radio = 'opsi_' . strtolower($key_opsi_render) . '_' . ($id_soal_item_current ?? 'unknown');
                                        $original_key = $opsi_data['original_key'] ?? ''; 
                                    ?>
                                        <div class="funkyradio">
                                            <div class="funkyradio-success">
                                                <input type="radio" 
                                                    name="jawaban_soal_<?= $id_soal_item_current ?>" 
                                                    id="<?= $id_radio ?>" 
                                                    value="<?= $original_key ?>" 
                                                    data-nomor-soal-display="<?= $no_soal_aktual ?>">
                                                <label for="<?= $id_radio ?>">
                                                    <div class="huruf_opsi"><?= $key_opsi_render ?></div> 
                                                    <div class="opsi-konten">
                                                        <div class="opsi-text"><?= ($opsi_data['teks'] ?? '') ?></div>
                                                        <?php
                                                        $file_opsi = $opsi_data['file'] ?? null;
                                                        if (!empty($file_opsi)): 
                                                            $final_file_name = $file_opsi; 
                                                        ?>
                                                            <div class="opsi-media mt-2">
                                                                <?php $relative_path_opsi_file = 'uploads/bank_soal/' . $final_file_name; ?>
                                                                <?= tampil_media($relative_path_opsi_file) ?>
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
                    <button type="button" class="action back btn btn-default" id="btn-prev-soal" data-navigasi="prev">
                        <i class="glyphicon glyphicon-chevron-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-warning" id="btn-ragu-ragu">
                        <i class="fa fa-question-circle"></i> Ragu-ragu
                    </button>
                    <?php $total_soal = count($soal_collection); ?>
                    <span id="selesai-ujian-wrapper" style="display: none;">
                        <button type="button" class="btn btn-success" id="btn-selesai-ujian">
                            <i class="glyphicon glyphicon-stop"></i> Selesai
                        </button>
                    </span>
                    <button type="button" class="action next btn bg-blue" id="btn-next-soal" data-navigasi="next">
                        Next <i class="glyphicon glyphicon-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/dist/js/app/ujian/lembar_ujian.js') ?>"></script>
<script>
$(document).ready(function() {
    // Show/hide SELESAI button based on current question
    function toggleSelesaiButton(currentQuestionNumber) {
        var totalQuestions = <?= $total_soal ?>;
        $('#selesai-ujian-wrapper').toggle(currentQuestionNumber === totalQuestions);
    }

    // Call this when navigating questions
    // This is already handled by initializeNavigation in lembar_ujian.js
    // and showSoalPanel
    /*
    $('.btn-soal-nav, #btn-prev-soal, #btn-next-soal').on('click', function() {
        var currentNumber = parseInt($('#display-nomor-soal').text());
        toggleSelesaiButton(currentNumber);
    });
    */

    // Initial check (this will be called by initializeNavigation now)
    // toggleSelesaiButton(1);

    // Make sure both SELESAI buttons do the same thing
    // If you have an inline "Selesai" button, ensure its ID is unique and target it correctly
    // or remove it if not used. If #btn-selesai-ujian-inline exists, keep this:
    $('#btn-selesai-ujian-inline').on('click', function() {
        $('#btn-selesai-ujian').trigger('click');
    });
});

document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi zoom untuk gambar dengan data-zoomable
    const zoomable = mediumZoom('[data-zoomable]', {
        margin: 20,
        background: '#000000e6',
        scrollOffset: 0,
    });

    zoomable.on('closed', () => {
        console.log('MediumZoom: Zoom overlay closed.');
    });

    // Handle navigation events
    const navigationButtons = document.querySelectorAll('.btn-soal-nav, #btn-prev-soal, #btn-next-soal');
    navigationButtons.forEach(button => {
        button.addEventListener('click', () => {
            zoomable.close();
            console.log('MediumZoom: Navigation button clicked, closing zoom.');
        });
    });

    console.log('Zoom initialized for', document.querySelectorAll('[data-zoomable]').length, 'images');
});
</script>