<?php
// File: application/views/ujian/list_siswa.php
// Menggunakan template _templates/dashboard/
// Variabel yang diharapkan dari controller:
// $user (objek user Ion Auth)
// $siswa (objek detail siswa dari model, termasuk nama_kelas, nama_jenjang)
// $judul
// $subjudul
// When generating the "Ikut Ujian" button URL:
$encrypted_id = $this->ujian_m->encrypt_exam_id($ujian->id_h_ujian);
$url = base_url('ujian/lembar_ujian/' . urlencode($encrypted_id));
?>
<div class="row">
    <div class="col-sm-4">
        <div class="alert bg-green">
            <h4>Kelas<i class="pull-right fa fa-building-o"></i></h4>
            <span class="d-block"><?= htmlspecialchars($siswa->nama_jenjang ?? 'N/A'); ?> <?= htmlspecialchars($siswa->nama_kelas ?? 'N/A'); ?></span>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="alert bg-blue">
            <h4>Tahun Ajaran<i class="pull-right fa fa-calendar-check-o"></i></h4>
            <?php 
                if (isset($tahun_ajaran_aktif_display) && !empty($tahun_ajaran_aktif_display->nama_tahun_ajaran)) {
                    echo htmlspecialchars($tahun_ajaran_aktif_display->nama_tahun_ajaran);
                } else {
                    echo 'TA Tidak Aktif'; // Fallback jika tidak ada TA aktif
                }
            ?>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="alert bg-purple">
            <h4>Siswa<i class="pull-right fa fa-user"></i></h4>
            <span class="d-block"> <?= htmlspecialchars($siswa->nama_siswa ?? 'N/A'); ?></span>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Daftar Ujian Tersedia'); ?></h3>
                <div class="box-tools pull-right">
                    <button type="button" id="reload_ujian_siswa" class="btn btn-sm btn-flat btn-default"><i class="fa fa-refresh"></i> Reload</button>
                </div>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table id="table_ujian_siswa" class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th class="text-center" width="3%">No.</th>
                                <th>Nama Ujian</th>
                                <th>Mata Pelajaran</th>
                                <th>Guru Mapel</th>
                                <th class="text-center">Tanggal</th>
                                <th class="text-center">Waktu</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    const BASE_URL = '<?= base_url() ?>';
    const ID_SISWA_GLOBAL = <?= json_encode($siswa->id_siswa ?? null); ?>;
    // Variabel CSRF
    const CSRF_TOKEN_NAME = '<?= $this->security->get_csrf_token_name(); ?>';
    const CSRF_HASH = '<?= $this->security->get_csrf_hash(); ?>';
</script>
<script src="<?= base_url('assets/dist/js/app/ujian/list_ujian_siswa.js') ?>"></script>
