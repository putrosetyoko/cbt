<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= $judul ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('jenjang') ?>" class="btn btn-sm btn-flat btn-warning">
                <i class="fa fa-arrow-left"></i> Batal
            </a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-4 col-sm-offset-4">
                <?= form_open($form_action, array('id' => 'formjenjang', 'class'=>'form')); ?>
                <div class="form-group">
                    <label for="nama_jenjang">Nama Jenjang</label>
                    <input type="text" name="nama_jenjang" id="nama_jenjang" class="form-control" placeholder="Contoh: VII / VIII / IX" required>
                    <small class="help-block" id="error_nama_jenjang"></small> </div>
                <div class="form-group">
                    <label for="deskripsi">Deskripsi (Opsional)</label>
                    <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3" placeholder="Deskripsi singkat mengenai jenjang"></textarea>
                    <small class="help-block" id="error_deskripsi"></small> </div>
                <input type="hidden" name="id_jenjang" value=""> <div class="form-group pull-right">
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
<script src="<?= base_url() ?>assets/dist/js/app/master/jenjang/data.js"></script>