<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($judul); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('guru') ?>" class="btn btn-sm btn-warning btn-flat"><i class="fa fa-arrow-left"></i> Batal</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-4 col-sm-offset-4">
                <?= form_open('guru/save', array('id' => 'formguru'), array('method' => 'add')); ?>
                    <div class="form-group">
                        <label for="nip">NIP</label>
                        <input type="text" name="nip" id="nip" class="form-control" placeholder="Nomor Induk Pegawai" required maxlength="20">
                        <small class="help-block text-danger" id="error_nip"></small>
                    </div>
                    <div class="form-group">
                        <label for="nama_guru">Nama Guru</label>
                        <input type="text" name="nama_guru" id="nama_guru" class="form-control" placeholder="Nama Lengkap Guru" required maxlength="100">
                        <small class="help-block text-danger" id="error_nama_guru"></small>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Alamat Email (Opsional)" maxlength="100">
                        <small class="help-block text-danger" id="error_email"></small>
                    </div>
                    
                    <div class="form-group pull-right">
                        <button type="reset" class="btn btn-flat btn-default"><i class="fa fa-rotate-left"></i> Reset</button>
                        <button type="submit" id="submit" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan</button>
                    </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    // Logika spesifik untuk add.php jika ada (misal fokus ke field pertama)
    $('#nip').focus();

    // Menghapus error saat input berubah
    $('#formguru input').on('change keyup', function(){
        $(this).closest('.form-group').removeClass('has-error');
        $('#error_' + $(this).attr('name')).text('');
    });
});
</script>
<script src="<?= base_url() ?>assets/dist/js/app/master/guru/add.js"></script>
