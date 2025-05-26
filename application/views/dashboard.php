<?php if( $this->ion_auth->is_admin() ) : ?>
<div class="row">
    <?php foreach($info_box as $info) : ?>
    <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-<?=$info->box?>">
        <div class="inner">
            <h3><?=$info->total;?></h3>
            <p><?=$info->title;?></p>
        </div>
        <div class="icon">
            <i class="fa fa-<?=$info->icon?>"></i>
        </div>
        <a href="<?=base_url().strtolower($info->title);?>" class="small-box-footer">
            More info <i class="fa fa-arrow-circle-right"></i>
        </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php elseif( $this->ion_auth->in_group('guru') ) : ?>

<div class="row">
    <div class="col-sm-4">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Informasi Akun</h3>
            </div>
            <table class="table table-hover">
                <tr>
                    <th>Nama</th>
                    <td><?=$guru->nama_guru?></td>
                </tr>
                <tr>
                    <th>NIP/NIK/NUPTK</th>
                    <td><?=$guru->nip?></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><?=$guru->email?></td>
                </tr>
                <tr>
                    <th>Mata Pelajaran</th>
                    <td><?=$guru->nama_mapel?></td>
                </tr>
                <tr>
                    <th>Daftar Kelas</th>
                    <td>
                        <ol class="pl-4">
                        <?php foreach ($kelas as $k) : ?>
                            <li><?=$k->nama_kelas?></li>
                        <?php endforeach;?>
                        </ol>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="box box-solid">
            <div class="box-header bg-purple">
                <h3 class="box-title">Pemberitahuan</h3>
            </div>
            <div class="box-body">
                <p>Sebelum membuat Ujian jangan lupa.</p>
                <ul class="pl-4">
                    <li>Wajib Buat Soal!</li>
                    <li>Hindari Membuat Soal Ambigu atau Membingungkan Siswa</li>
                    <li>Jumlah Soal Sesuaikan Dengan Waktu</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php else : ?>

<div class="row">
    <div class="col-sm-4">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Informasi Akun</h3>
            </div>
            <table class="table table-hover">
                <tr>
                    <th>NISN</th>
                    <td><?=$siswa->nisn?></td>
                </tr>
                <tr>
                    <th>Nama</th>
                    <td><?=$siswa->nama?></td>
                </tr>
                <tr>
                    <th>Jenis Kelamin</th>
                    <td><?=$siswa->jenis_kelamin === 'L' ? "Laki-laki" : "Perempuan" ;?></td>
                </tr>
                <!-- <tr>
                    <th>Email</th>
                    <td><?=$siswa->email?></td>
                </tr> -->
                <tr>
                    <th>Kelas</th>
                    <td><?=$siswa->nama_kelas?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="box box-solid">
            <div class="box-header bg-purple">
                <h3 class="box-title">Pemberitahuan</h3>
            </div>
            <div class="box-body">
                <p>Sebelum Ujian jangan lupa.</p>
                <ul class="pl-4">
                    <li>Cari tempat yang aman dan kondusif untuk mengerjakan</li>
                    <li>Siapkan alat yang dibutuhkan (bukan contekan)</li>
                    <li>Tenangkan pikiran</li>
                    <li>Berdoa</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>