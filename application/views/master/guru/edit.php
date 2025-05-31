<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Form <?=$subjudul?></h3>
        <div class="box-tools pull-right">
            <a href="<?=base_url()?>guru" class="btn btn-sm btn-flat btn-warning">
                <i class="fa fa-arrow-left"></i> Batal
            </a>
        </div>
    </div>
    <?=form_open('guru/save', array('id'=>'formguru'), array('method'=>'edit', 'id_guru'=>$data->id_guru));?>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-4 col-sm-offset-4">
                <div class="form-group">
                    <label for="nip">NIP/NIK/NUPTK</label>
                    <input value="<?=$data->nip?>" autofocus="autofocus" onfocus="this.select()" type="number" id="nip" class="form-control" name="nip" placeholder="NIP">
                    <small class="help-block" id="error_nip"></small>
                </div>
                <div class="form-group">
                    <label for="nama_guru">Nama Guru</label>
                    <input value="<?=$data->nama_guru?>" type="text" id="nama_guru" class="form-control" name="nama_guru" placeholder="Nama Guru">
                    <small class="help-block" id="error_nama_guru"></small>
                </div>
                <div class="form-group">
                    <label for="email">Email Guru</label>
                    <input value="<?=$data->email?>" type="email" id="email" class="form-control" name="email" placeholder="Email Guru (Opsional)">
                    <small class="help-block" id="error_email"></small>
                </div>
                <div class="form-group pull-right">
                    <button type="reset" class="btn btn-flat btn-default">
                        <i class="fa fa-rotate-left"></i> Reset
                    </button>
                    <button type="submit" id="submit" class="btn btn-flat bg-purple">
                        <i class="fa fa-pencil"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?=form_close();?>
</div>

<script>
$(document).ready(function() {
    // Menghapus error saat input berubah
    $('#formguru input').on('change keyup', function(){ // Hanya input, karena select mapel sudah tidak ada
        $(this).closest('.form-group').removeClass('has-error has-success');
        $('#error_' + $(this).attr('name')).text('');
    });
});
</script>
<script src="<?=base_url()?>assets/dist/js/app/master/guru/edit.js"></script>