<?php 
// Ambil variabel tahun ajaran aktif dari controller untuk kemudahan akses
$ta_aktif = $tahun_ajaran_aktif_info ?? null; 
?>

<?php if( $this->ion_auth->is_admin() && isset($info_box) && !empty($info_box) ) : ?>
    <!-- Welcome Message -->
    <div class="row">
        <div class="col-lg-12">
            <div class="callout callout-info">
                <h4><i class="fa fa-user-circle"></i> Selamat Datang, Super Admin!</h4>
                <p>
                    Tahun Ajaran Aktif: 
                    <strong>
                        <?php 
                        if ($ta_aktif && isset($ta_aktif->nama_tahun_ajaran)) {
                            echo htmlspecialchars($ta_aktif->nama_tahun_ajaran);
                        } else {
                            echo '<span class="text-danger">Belum Ditetapkan</span>';
                        }
                        ?>
                    </strong>
                </p>
            </div>
        </div>
    </div>

    <!-- Info Boxes -->
    <div class="row">
        <?php foreach($info_box as $info) : ?>
        <div class="col-lg-3 col-xs-6">
            <div class="small-box <?= htmlspecialchars($info->box ?? 'bg-aqua'); ?>" style="box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                <div class="inner">
                    <h3><?= htmlspecialchars($info->total ?? 0);?></h3>
                    <p><?= htmlspecialchars($info->title ?? '');?></p>
                </div>
                <div class="icon" style="top: 5px;">
                    <i class="fa fa-<?= htmlspecialchars($info->icon ?? 'info-circle');?>"></i>
                </div>
                <a href="<?= base_url(strtolower(str_replace(' ', '', $info->url ?? '#'))); ?>" 
                class="small-box-footer" style="padding: 8px;">
                    Kelola Data <i class="fa fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-bolt"></i> Aksi Cepat</h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-xs-6">
                            <a href="<?= base_url('guru/add')?>" class="btn bg-yellow btn-block btn-flat">
                                <i class="fa fa-user-plus"></i> Tambah Guru
                            </a>
                        </div>
                        <div class="col-xs-6">
                            <a href="<?= base_url('siswa/add')?>" class="btn bg-red btn-block btn-flat">
                                <i class="fa fa-user-plus"></i> Tambah Siswa
                            </a>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-xs-6">
                            <a href="<?= base_url('kelas')?>" class="btn bg-green btn-block btn-flat">
                                <i class="fa fa-university"></i> Kelola Kelas
                            </a>
                        </div>
                        <div class="col-xs-6">
                            <a href="<?= base_url('mapel')?>" class="btn bg-blue btn-block btn-flat">
                                <i class="fa fa-book"></i> Kelola Mapel
                            </a>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-xs-6">
                            <a href="<?= base_url('mapel')?>" class="btn bg-purple btn-block btn-flat">
                                <i class="fa fa-book"></i> Kelas Siswa
                            </a>
                        </div>
                        <div class="col-xs-6">
                            <a href="<?= base_url('mapel')?>" class="btn bg-navy btn-block btn-flat">
                                <i class="fa fa-book"></i> Penugasan Guru
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-info-circle"></i> Informasi Sistem</h3>
                </div>
                <div class="box-body">
                    <ul class="list-group">
                        <li class="list-group-item">
                            <i class="fa fa-calendar text-primary"></i> Tahun Ajaran Aktif: 
                            <strong><?= htmlspecialchars($ta_aktif->nama_tahun_ajaran ?? 'Belum ditetapkan') ?></strong>
                        </li>
                        <li class="list-group-item">
                            <i class="fa fa-database text-success"></i> Total Data: 
                            <strong><?= array_sum(array_column(json_decode(json_encode($info_box), true), 'total')) ?> record</strong>
                        </li>
                        <li class="list-group-item">
                            <i class="fa fa-clock-o text-warning"></i> Server Time: 
                            <strong><?= date('d M Y H:i:s') ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if( $this->ion_auth->in_group('guru') ) : ?>
    <?php if(isset($guru_info_dashboard) && $guru_info_dashboard): // Pastikan data guru ada ?>
        <div class="row">
            <div class="col-lg-12">
                <div class="callout callout-info">
                    <h4>Selamat Datang, <?= htmlspecialchars($guru_info_dashboard->nama_guru ?? $user->username); ?>👋</h4>
                    <p>Tahun Ajaran Aktif Saat Ini: 
                        <strong>
                        <?php 
                        if ($ta_aktif && isset($ta_aktif->nama_tahun_ajaran)) {
                            echo htmlspecialchars($ta_aktif->nama_tahun_ajaran);
                            // echo " (".htmlspecialchars($ta_aktif->status).")"; // Opsional tampilkan status
                        } else {
                            echo '<span class="text-red">Belum Ada Tahun Ajaran Aktif Ditetapkan.</span>';
                        }
                        ?>
                        </strong>
                    </p> 
                </div>
            </div>
        </div>

        <?php if(isset($info_box_guru) && !empty($info_box_guru)): ?>
        <div class="row">
            <?php foreach($info_box_guru as $info) : ?>
            <div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
                <div class="small-box <?= htmlspecialchars($info->boxClass ?? 'bg-gray'); ?>">
                <div class="inner">
                    <h3><?= htmlspecialchars($info->value ?? 0);?></h3>
                    <p><?= htmlspecialchars($info->title ?? '');?></p>
                </div>
                <div class="icon">
                    <i class="fa fa-<?= htmlspecialchars($info->icon ?? 'info-circle');?>"></i>
                </div>
                <?php $url_info = isset($info->url) ? base_url($info->url) : '#'; ?>
                <!-- <a href="<?= $url_info; ?>" class="small-box-footer">
                    Lihat Detail <i class="fa fa-arrow-circle-right"></i>
                </a> -->
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <hr>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-5">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-user-circle-o"></i> Informasi Akun Anda</h3>
                    </div>
                    <table class="table table-hover">
                        <tr>
                            <th width="35%">Nama</th>
                            <td>: <?= htmlspecialchars($guru_info_dashboard->nama_guru ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>NIP/NIK/NUPTK</th>
                            <td>: <?= htmlspecialchars($guru_info_dashboard->nip ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>: <?= htmlspecialchars($guru_info_dashboard->email ?? '-'); ?></td>
                        </tr>
                        <?php if(isset($mapel_pj_info_dashboard) && $mapel_pj_info_dashboard): ?>
                        <tr>
                            <th>PJ Soal Mapel (TA Aktif)</th>
                            <td>: <?= htmlspecialchars($mapel_pj_info_dashboard->nama_mapel); ?></strong></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
            <div class="col-md-7">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calendar"></i> Tugas Mengajar Anda (<?= htmlspecialchars($ta_aktif->nama_tahun_ajaran ?? 'TA Belum Aktif'); ?>)</h3>
                    </div>
                    <div class="box-body" style="max-height: 350px; overflow-y: auto;">
                        <?php if(isset($penugasan_guru_dashboard) && !empty($penugasan_guru_dashboard)): ?>
                            <?php foreach($penugasan_guru_dashboard as $nama_mapel_diajar => $kelas_diajar_list): ?>
                                <h5><strong><i class="fa fa-book text-blue"></i> <?= htmlspecialchars($nama_mapel_diajar); ?></strong></h5>
                                <?php if(!empty($kelas_diajar_list)): ?>
                                    <ul style="padding-left: 25px; margin-bottom: 15px; list-style-type: none;">
                                        <?php foreach($kelas_diajar_list as $kelas_info): ?>
                                            <li><i class="fa fa-university text-green"></i> <?= htmlspecialchars($kelas_info); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted" style="padding-left: 25px;">- Tidak ada kelas yang diajar untuk mapel ini pada tahun ajaran aktif -</p>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php elseif(isset($ta_aktif)): // Jika TA aktif tapi tidak ada penugasan ?>
                            <p class="text-muted">Belum ada data penugasan mengajar untuk tahun ajaran aktif.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif(isset($dashboard_message_guru)): // Pesan jika data guru atau TA aktif tidak ada ?>
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h4><i class="icon fa fa-warning"></i> Perhatian!</h4>
            <?= htmlspecialchars($dashboard_message_guru); ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Informasi dashboard guru tidak tersedia.</div>
    <?php endif; ?>

<?php elseif( $this->ion_auth->in_group('siswa') ) : ?>
    <div class="row">
        <div class="col-lg-12" style="margin-bottom:15px;">
            <div class="callout callout-info">
                <h3>Selamat Datang, <?= htmlspecialchars($user->first_name ?? $user->username); ?>!</h3>
                <p>Tahun Ajaran Aktif Saat Ini: 
                    <strong>
                    <?php 
                    if ($ta_aktif && isset($ta_aktif->nama_tahun_ajaran)) {
                        echo htmlspecialchars($ta_aktif->nama_tahun_ajaran);
                    } else {
                        echo '<span class="text-red">Belum Ditetapkan</span>';
                    }
                    ?>
                    </strong>
                </p> 
            </div>
        </div>
    </div>
    <?php if(isset($siswa_dashboard_info) && $siswa_dashboard_info): ?>
    <div class="row">
        <div class="col-md-5">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-user-circle-o"></i> Informasi Akun Anda</h3></div>
                <table class="table table-hover">
                    <tr><th width="40%">NISN</th><td>: <?= htmlspecialchars($siswa_dashboard_info->nisn ?? '-'); ?></td></tr>
                    <tr><th>Nama</th><td>: <?= htmlspecialchars($siswa_dashboard_info->nama_siswa ?? '-'); ?></td></tr>
                    <tr><th>Jenis Kelamin</th><td>: <?= ($siswa_dashboard_info->jenis_kelamin ?? '') === 'L' ? "Laki-laki" : (($siswa_dashboard_info->jenis_kelamin ?? '') === 'P' ? "Perempuan" : "-") ;?></td></tr>
                    <tr><th>Tahun Ajaran Saat Ini</th><td>: <?= htmlspecialchars($siswa_dashboard_info->nama_tahun_ajaran ?? '-'); ?></td></tr>
                    <tr><th>Kelas Saat Ini</th><td>: <?= htmlspecialchars( (isset($siswa_dashboard_info->nama_jenjang) ? $siswa_dashboard_info->nama_jenjang . ' - ' : '') . ($siswa_dashboard_info->nama_kelas ?? '-')) ?></td></tr>
                </table>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box box-success">
                <div class="box-header with-border"> <h3 class="box-title"><i class="fa fa-bullhorn"></i> Pemberitahuan</h3> </div>
                <div class="box-body">
                    <p>Selamat datang di Ujian Online CBT!</p>
                    <ul style="padding-left: 20px;">
                        <li>Periksa menu <a href="<?= base_url('ujian/list')?>"><b>Ujian Saya</b></a> untuk melihat daftar ujian yang tersedia.</li>
                        <li>Pastikan Anda memeriksa jadwal ujian dengan seksama.</li>
                        <li>Siapkan diri Anda dengan baik sebelum memulai ujian.</li>
                        <li>Periksa koneksi internet Anda sebelum memulai.</li>
                        <li>Kerjakan ujian dengan jujur dan teliti. Semoga berhasil!</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php elseif(isset($dashboard_message_siswa)): ?>
        <div class="alert alert-warning alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h4><i class="icon fa fa-warning"></i> Perhatian!</h4>
            <?= htmlspecialchars($dashboard_message_siswa); ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Selamat datang! Informasi detail akun siswa Anda belum tersedia atau belum ada penempatan kelas untuk tahun ajaran aktif.</div>
    <?php endif; ?>
<?php endif; ?>
