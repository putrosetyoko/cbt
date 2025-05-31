<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul); // Misal: "Import Data Penempatan Siswa" ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('siswakelas') ?>" class="btn btn-sm btn-warning btn-flat"><i class="fa fa-arrow-left"></i> Batal</a>
            <!-- <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button> -->
        </div>
    </div>
    <div class="box-body">
        <?php if($this->session->flashdata('error_message')): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h4><i class="icon fa fa-ban"></i> Error!</h4>
                <?= $this->session->flashdata('error_message'); ?>
            </div>
        <?php endif; ?>
        <?php if($this->session->flashdata('success_message')): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h4><i class="icon fa fa-check"></i> Sukses!</h4>
                <?= $this->session->flashdata('success_message'); ?>
            </div>
        <?php endif; ?>
        <?php if($this->session->flashdata('warning_message')): ?>
            <div class="alert alert-warning alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h4><i class="icon fa fa-warning"></i> Perhatian!</h4>
                <?= $this->session->flashdata('warning_message'); ?>
            </div>
        <?php endif; ?>


        <ul class="alert alert-info" style="padding-left: 40px">
            <li>Silahkan import data Penempatan Siswa dari Excel, <b>WAJIB</b> menggunakan format yang sudah disediakan.</li>
            <li>Data tidak boleh ada yang kosong, harus terisi semua.</li>
            <li>Pastikan semua ID Kelas dan ID Tahun Ajaran berisi angka valid dan terdaftar di sistem.</li>
            <li>Klik untuk melihat daftar ID Kelas dan ID Tahun Ajaran yang valid:
                <button data-toggle="modal" href="#tahunAjaranIdModal" class="btn btn-xs btn-primary">Lihat ID Tahun Ajaran</button>
                <button data-toggle="modal" href="#kelasIdModal" class="btn btn-xs btn-primary">Lihat ID Kelas</button>
                <!-- <a data-toggle="modal" href="#siswaIdModal" class="btn btn-xs btn-primary">Lihat Data Siswa (NISN)</a> -->
            </li>
        </ul>

        <div class="text-center">
            <a href="<?= base_url('uploads/import/format/penempatan_siswa.xlsx') // GANTI DENGAN PATH TEMPLATE ANDA ?>" class="btn-default btn"><i class="fa fa-download"></i> Download Format</a>
        </div>
        <br>

        <div class="row">
            <?= form_open_multipart('siswakelas/preview', ['class' => 'form-horizontal']); ?>
            <div class="form-group">
                <label for="upload_file" class="col-sm-3 col-sm-offset-1 control-label">Pilih File Excel</label>
                <div class="col-sm-5">
                    <input type="file" name="upload_file" id="upload_file" class="form-control" required>
                </div>
                <div class="col-sm-2">
                    <button name="preview" type="submit" class="btn btn-sm btn-success"><i class="fa fa-eye"></i> Preview</button>
                </div>
            </div>
            <?= form_close(); ?>
        </div>
        <hr>

        <?php if (isset($show_preview) && $show_preview === true && isset($import) && !empty($import)) : ?>
            <h4><i class="fa fa-list-alt"></i> Preview Data</h4>
            <!-- <p>Periksa data di bawah. Baris yang valid (semua ID dan NISN ditemukan, dan siswa belum ditempatkan di tahun ajaran tersebut) akan berwarna hijau dan dapat diimpor.</p> -->
            
            <?= form_open('siswakelas/do_import', array('id'=>'formImportSiswaKelas')); ?>
            <input type="hidden" name="data_import_json" value="<?= htmlspecialchars(json_encode($import)); ?>">
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-condensed">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Tahun Ajaran</th>
                            <th>Jenjang </th>
                            <th>Kelas</th>
                            <th>NISN Siswa</th>
                            <th>Nama Siswa</th>
                            <th>Status Validasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $jumlah_data_valid_untuk_import = 0;
                        foreach ($import as $data_row) :
                            // Asumsikan controller preview() menambahkan key berikut ke setiap $data_row:
                            // $data_row['nisn_excel'], $data_row['id_kelas_excel'], $data_row['id_tahun_ajaran_excel']
                            // $data_row['nama_siswa_resolved'], $data_row['nama_kelas_resolved'], 
                            // $data_row['nama_jenjang_resolved'], $data_row['nama_tahun_ajaran_resolved']
                            // $data_row['siswa_id_resolved'], $data_row['kelas_id_resolved'], $data_row['id_tahun_ajaran_resolved']
                            // $data_row['is_valid_nisn'], $data_row['is_valid_kelas'], $data_row['is_valid_tahun_ajaran']
                            // $data_row['is_already_assigned'] (true jika siswa sudah ada di TA itu)
                            // $data_row['is_importable'] (true jika semua valid dan belum assigned)

                            $row_class = 'default';
                            $status_messages = [];
                            if (isset($data_row['is_importable']) && $data_row['is_importable']) {
                                $row_class = 'success'; // Siap diimpor
                                $status_messages[] = '<span class="label label-success">OK</span>';
                                $jumlah_data_valid_untuk_import++;
                            } else {
                                $row_class = 'danger'; // Tidak bisa diimpor
                                if (isset($data_row['is_valid_nisn']) && !$data_row['is_valid_nisn']) $status_messages[] = '<span class="label label-danger">NISN Tidak Ditemukan</span>';
                                if (isset($data_row['is_valid_kelas']) && !$data_row['is_valid_kelas']) $status_messages[] = '<span class="label label-danger">ID Kelas Tidak Ditemukan</span>';
                                if (isset($data_row['is_valid_tahun_ajaran']) && !$data_row['is_valid_tahun_ajaran']) $status_messages[] = '<span class="label label-danger">ID TA Tidak Ditemukan</span>';
                                if (isset($data_row['is_already_assigned']) && $data_row['is_already_assigned']) $status_messages[] = '<span class="label label-warning">Siswa sudah di kelas lain pada TA ini</span>';
                                if (empty($status_messages)) $status_messages[] = '<span class="label label-danger">Data Tidak Valid</span>';
                            }
                        ?>
                            <tr class="<?= $row_class; ?>">
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($data_row['nama_tahun_ajaran_resolved'] ?? ($data_row['id_tahun_ajaran_excel'] ?? 'N/A')); ?></td>
                                <td><?= htmlspecialchars($data_row['nama_jenjang_resolved'] ?? '-'); ?></td>
                                <td><?= htmlspecialchars($data_row['nama_kelas_resolved'] ?? ($data_row['id_kelas_excel'] ?? 'N/A')); ?></td>
                                <td><?= htmlspecialchars($data_row['nisn_excel'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($data_row['nama_siswa_resolved'] ?? ($data_row['nama_siswa_excel'] ?? 'N/A')); ?></td>
                                <td><?= implode('<br>', $status_messages); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($jumlah_data_valid_untuk_import > 0) : ?>
                <p class="text-info">Terdapat <strong><?= $jumlah_data_valid_untuk_import ?></strong> baris data yang valid dan siap untuk diimpor.</p>
                <button type='submit' class='btn btn-block btn-flat bg-purple'><i class="fa fa-upload"></i> Import <?= $jumlah_data_valid_untuk_import ?> Data Valid</button>
            <?php else: ?>
                <div class="alert alert-danger">
                    <strong>Import Ditunda!</strong> Tidak ada data yang valid untuk diimpor. Harap perbaiki file Excel Anda (cek NISN, ID Kelas, ID Tahun Ajaran, dan pastikan siswa belum ditempatkan di tahun ajaran yang sama) dan coba lagi.
                </div>
            <?php endif; ?>
            <?= form_close(); ?>
            <br>
        <?php elseif (isset($show_preview) && $show_preview === true && empty($import)) : ?>
            <div class="alert alert-warning text-center">
                Tidak ada data yang dapat di-preview dari file yang Anda unggah. <br>
                Pastikan file Excel memiliki kolom: **NISN Siswa**, **ID Kelas**, **ID Tahun Ajaran**.
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="tahunAjaranIdModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title">Data Tahun Ajaran</h4>
            </div>
            <div class="modal-body table-responsive">
                <table id="tableTahunAjaranRef" class="table table-bordered table-striped table-condensed" style="width:100%">
                    <thead><tr><th>ID Tahun Ajaran</th><th>Nama Tahun Ajaran</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php if(isset($all_tahun_ajaran_ref) && !empty($all_tahun_ajaran_ref)): ?>
                            <?php foreach ($all_tahun_ajaran_ref as $ta_ref) : ?>
                                <tr><td><?= $ta_ref->id_tahun_ajaran; ?></td><td><?= htmlspecialchars($ta_ref->nama_tahun_ajaran); ?></td><td><?= $ta_ref->status == 'aktif' ? '<span class="label label-success">Aktif</span>' : '<span class="label label-danger">Tidak Aktif</span>'; ?></td></tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">Data tahun ajaran tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="kelasIdModal">
    <div class="modal-dialog"> 
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title">Data Kelas</h4>
            </div>
            <div class="modal-body table-responsive">
                <table id="tableKelasRef" class="table table-bordered table-striped table-condensed" style="width:100%">
                    <thead><tr><th>ID Kelas</th><th>Nama Jenjang</th><th>Nama Kelas</th></tr></thead>
                    <tbody>
                        <?php if(isset($all_kelas_ref) && !empty($all_kelas_ref)): ?>
                            <?php foreach ($all_kelas_ref as $k_ref) : ?>
                                <tr><td><?= $k_ref->id_kelas; ?></td><td><?= htmlspecialchars($k_ref->nama_jenjang ?? '-'); ?></td><td><?= htmlspecialchars($k_ref->nama_kelas); ?></td></tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">Data kelas tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="siswaIdModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                <h4 class="modal-title">Referensi Data Siswa</h4>
            </div>
            <div class="modal-body table-responsive">
                <table id="tableSiswaRef" class="table table-bordered table-striped table-condensed" style="width:100%">
                    <thead><tr><th>NISN</th><th>Nama Siswa</th><th>Jenis Kelamin</th></tr></thead>
                    <tbody>
                        <?php if(isset($all_siswa_ref) && !empty($all_siswa_ref)): ?>
                            <?php foreach ($all_siswa_ref as $s_ref) : ?>
                                <tr><td><?= htmlspecialchars($s_ref->nisn); ?></td><td><?= htmlspecialchars($s_ref->nama); ?></td><td><?= htmlspecialchars($s_ref->jenis_kelamin); ?></td></tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center">Data siswa tidak ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) { 
        $('#tableTahunAjaranRef, #tableKelasRef, #tableSiswaRef').DataTable({
            "lengthMenu": [[10, 20, 25, 100, -1], [10, 20, 50, 100, "All"]],
            "pageLength": 10            // Anda bisa menambahkan order default jika perlu
        });
    }
});
</script>