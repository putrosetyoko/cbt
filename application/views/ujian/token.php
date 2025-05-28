<div class="callout callout-info">
    <h4>INFORMASI UJIAN</h4>
    <ul>
        <li>Peserta ujian diwajibkan mengerjakan soal secara jujur dan mandiri. Segala bentuk kecurangan akan dikenakan <b>SANKSI TEGAS</b>.</li>
        <li>Waktu pengerjaan ujian terbatas sesuai dengan durasi yang telah ditentukan. Pastikan Anda menyelesaikan ujian sebelum waktu berakhir.</li>
        <li>Peserta ujian disarankan menggunakan device/perangkat Laptop.</li>
        <li>Tombol <b>"MULAI"</b> akan muncul jika ujian sudah dapat dimulai.</li>
        <li>Pastikan koneksi internet Anda stabil selama ujian berlangsung untuk menghindari gangguan teknis.</li>
        <li>Selama ujian berlangsung, peserta dilarang untuk keluar dari halaman ujian atau beralih ke aplikasi/browser lain. Pelanggaran dapat menyebabkan ujian otomatis berakhir atau dianggap <b>TIDAK LULUS</b>.</li>
        <li>Sebelum menyelesaikan ujian, pastikan Anda telah memeriksa kembali semua jawaban Anda.</li>
        <li>Setelah selesai mengerjakan, pastikan Anda menekan tombol <b>"Selesai"</b>.</li>
        <li>Jika terjadi kendala teknis atau hal lain yang tidak terduga, segera hubungi pengawas.</li>
    </ul>
</div>
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Konfirmasi Data</h3>
    </div>
    <div class="box-body">
        <span id="id_ujian" data-key="<?=$encrypted_id?>"></span>
        <div class="row">
            <div class="col-sm-6">
                <table class="table table-bordered">
                    <tr>
                        <th>Nama Siswa / NISN</th>
                        <td><?=$siswa->nama?> / <?=$siswa->nisn?></td>
                    </tr>
                    <tr>
                        <th>Nama Guru</th>
                        <td><?=$ujian->nama_guru?></td>
                    </tr>
                    <tr>
                        <th>Kelas</th>
                        <td><?=$siswa->nama_kelas?></td>
                    </tr>
                    <tr>
                        <th>Nama Ujian</th>
                        <td><?=$ujian->nama_ujian?></td>
                    </tr>
                    <tr>
                        <th>Jumlah Soal</th>
                        <td><?=$ujian->jumlah_soal?></td>
                    </tr>
                    <tr>
                        <th>Mulai</th>
                        <td>
                            <?php
                                // Atur locale jika belum diatur secara global (bisa di awal script atau di config)
                                // setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id'); 
                                $timestamp_mulai = strtotime($ujian->tgl_mulai);
                                echo strftime('%d %B %Y', $timestamp_mulai) . date(' H:i', $timestamp_mulai) . ' WITA';
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Batas Akhir</th>
                        <td>
                            <?php
                                // Mengubah format tanggal dari YYYY-MM-DD HH:MM:SS menjadi DD Month YYYY HH:MM
                                $timestamp_terlambat = strtotime($ujian->terlambat); // Menggunakan kolom 'terlambat' sebagai 'tgl_selesai'
                                echo strftime('%d %B %Y', $timestamp_terlambat) . date(' H:i', $timestamp_terlambat) . ' WITA'; // Tambahkan WITA secara manual
                                ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Durasi</th>
                        <td><?=$ujian->waktu?> Menit</td>
                    </tr>
                    <tr>
                        <th style="vertical-align:middle">Token</th>
                        <td>
                            <input autocomplete="off" id="token" placeholder="Token" type="text" class="input-sm form-control">
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-sm-6">
                <div class="box box-solid">
                    <div class="box-body pb-0">
                        <div class="callout callout-info">
                            <p>
                                <b>Token</b> ujian diberikan oleh Guru.
                            </p>
                        </div>
                        <div class="callout callout-warning">
                            <p>
                                Waktu boleh mengerjakan ujian adalah saat tombol <b>"MULAI"</b> muncul.
                            </p>
                        </div>
                        <?php
                        $mulai = strtotime($ujian->tgl_mulai);
                        $terlambat = strtotime($ujian->terlambat);
                        $now = time();
                        if($mulai > $now) : 
                        ?>
                        <div class="callout callout-success">
                            <strong><i class="fa fa-clock-o"></i> Ujian akan dimulai pada</strong>
                            <br>
                            <span class="countdown" data-time="<?=date('Y-m-d H:i:s', strtotime($ujian->tgl_mulai))?>">00 Hari, 00 Jam, 00 Menit, 00 Detik</strong><br/>
                        </div>
                        <?php elseif( $terlambat > $now ) : ?>
                        <div class="callout callout-danger">
                            Batas waktu klik tombol mulai.<br/>
                            <i class="fa fa-clock-o"></i> <strong class="countdown" data-time="<?=date('Y-m-d H:i:s', strtotime($ujian->terlambat))?>">00 Hari, 00 Jam, 00 Menit, 00 Detik</strong>
                        </div>
                        <button id="btncek" data-id="<?=$ujian->id_ujian?>" class="btn btn-success btn-lg mb-4 ml-auto d-block">
                            <i class="fa fa-pencil"></i> Mulai
                        </button>
                        <?php else : ?>
                        <div class="callout callout-danger">
                            Waktu ujian sudah habis.<br/>
                            Silakan hubungi guru anda untuk bisa mengikuti ujian pengganti.
                        </div>
                        
                        <?php endif;?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?=base_url()?>assets/dist/js/app/ujian/token.js"></script>