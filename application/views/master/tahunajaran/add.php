<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= $subjudul ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('tahunajaran') ?>" class="btn btn-sm btn-flat btn-warning">
                <i class="fa fa-arrow-left"></i> Batal
            </a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-4 col-sm-offset-4">
                <?= form_open($form_action, array('id' => 'formtahunajaran'), array('id_tahun_ajaran' => '')); ?>
                <div class="form-group">
                    <label for="nama_tahun_ajaran">Nama Tahun Ajaran</label>
                    <input type="text" name="nama_tahun_ajaran" class="form-control" placeholder="Contoh: 2025/2026 Ganjil" required>
                    <small class="help-block text-danger" id="error_nama_tahun_ajaran"></small>
                </div>
                <div class="form-group">
                    <label for="semester">Semester</label>
                    <select name="semester" class="form-control select2" required>
                        <option value="">-- Pilih Semester --</option>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                    <small class="help-block text-danger" id="error_semester"></small>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="tgl_mulai">Tanggal Mulai</label>
                            <input type="text" name="tgl_mulai" class="form-control datetimepicker" placeholder="YYYY - MM - DD" required autocomplete="off">
                            <small class="help-block text-danger" id="error_tgl_mulai"></small>
                            <small class="help-block"><b>Tahun - Bulan - Tanggal</b></small>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="tgl_selesai">Tanggal Selesai</label>
                            <input type="text" name="tgl_selesai" class="form-control datetimepicker" placeholder="YYYY - MM - DD" required autocomplete="off">
                            <small class="help-block text-danger" id="error_tgl_selesai"></small>
                            <small class="help-block"><b>Tahun - Bulan - Tanggal</b></small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" class="form-control select2" required>
                        <option value="tidak_aktif">Tidak Aktif</option>
                        <option value="aktif">Aktif</option>
                    </select>
                    <small class="help-block text-danger" id="error_status"></small>
                </div>
                <div class="form-group pull-right">
                    <button type="reset" class="btn btn-flat btn-default">
                        <i class="fa fa-rotate-left"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-flat bg-purple">
                        <i class="fa fa-save"></i> Simpan
                    </button>
                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url() ?>assets/dist/js/app/master/tahunajaran/add.js"></script>