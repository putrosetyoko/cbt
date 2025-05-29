<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Form <?=$judul?></h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
             <div class="col-sm-offset-2 col-sm-10 col-md-offset-2 col-md-8">
                <div class="my-2">
                    <div class="form-horizontal form-inline">
                        <a href="<?=base_url('kelas')?>" class="btn btn-default btn-xs">
                            <i class="fa fa-arrow-left"></i> Batal
                        </a>
                        <div class="pull-right">
                            <span> Jumlah : </span><label for=""><?=count($kelas)?></label>
                        </div>
                    </div>
                </div>
                <?=form_open('kelas/save', array('id'=>'kelas'), array('mode'=>'edit'))?>
                <table id="form-table" class="table text-center table-condensed">
                    <thead>
                        <tr>
                            <th># No</th>
                            <th>Nama Kelas</th>
                            <th>Jenjang</th> </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach($kelas as $row) : ?>
                        <tr>
                            <td><?=$i?></td>
                            <td>
                                <div class="form-group">
                                    <?=form_hidden('id_kelas['.$i.']', $row->id_kelas);?>
                                    <input required="required" autofocus="autofocus" onfocus="this.select()" value="<?=$row->nama_kelas?>" type="text" name="nama_kelas[<?=$i?>]" class="form-control">
                                    <span class="d-none">DON'T DELETE THIS</span>
                                    <small class="help-block text-right" id="error_nama_kelas[<?=$i?>]"></small>
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <select name="id_jenjang[<?=$i?>]" class="form-control select2" style="width: 100%;" required>
                                        <option value="">-- Pilih Jenjang --</option>
                                        <?php foreach ($all_jenjang as $j) : ?>
                                            <option value="<?=$j->id_jenjang?>" <?=$row->id_jenjang == $j->id_jenjang ? 'selected' : ''?>><?=$j->nama_jenjang?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="help-block text-right" id="error_id_jenjang[<?=$i?>]"></small>
                                </div>
                            </td>
                        </tr>
                        <?php $i++;endforeach; ?>
                    </tbody>
                </table>
                <button id="submit" type="submit" class="mb-4 btn btn-block btn-flat bg-purple">
                    <i class="fa fa-edit"></i> Simpan Perubahan
                </button>
                <?=form_close()?>
            </div>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('.select2').select2();
});
</script>
<script src="<?=base_url()?>assets/dist/js/app/master/kelas/edit.js"></script>