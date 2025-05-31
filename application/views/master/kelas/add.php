<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Form <?=$judul?></h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-offset-4 col-sm-4">
                <div class="my-2">
                    <div class="form-horizontal form-inline">
                        <a href="<?=base_url('kelas')?>" class="btn btn-flat btn-warning btn-sm">
                            <i class="fa fa-arrow-left"></i> Batal
                        </a>
                        <div class="pull-right">
                            <span> Jumlah : </span><label for=""><?=$banyak?></label>
                        </div>
                    </div>
                </div>
                <?=form_open('kelas/save', array('id'=>'kelas'), array('mode'=>'add'))?>
                <table id="form-table" class="table text-center table-condensed">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kelas</th>
                            <th>Jenjang</th> </tr>
                    </thead>
                    <tbody>
                        <?php for ($i=1; $i <= $banyak; $i++) : ?>
                        <tr>
                            <td><?=$i?></td>
                            <td>
                                <div class="form-group">
                                    <input autofocus="autofocus" onfocus="this.select()" required="required" autocomplete="off" type="text" name="nama_kelas[<?=$i?>]" class="form-control">
                                    <span class="d-none">DON'T DELETE THIS</span>
                                    <small class="help-block text-right" id="error_nama_kelas[<?=$i?>]"></small> </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <select name="id_jenjang[<?=$i?>]" class="form-control select2" style="width: 100%;" required>
                                        <option value="">-- Pilih Jenjang --</option>
                                        <?php foreach ($all_jenjang as $j) : ?>
                                            <option value="<?=$j->id_jenjang?>"><?=$j->nama_jenjang?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="help-block text-right" id="error_id_jenjang[<?=$i?>]"></small> </div>
                            </td>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <button id="submit" type="submit" class="mb-4 btn btn-block btn-flat bg-purple">
                    <i class="fa fa-save"></i> Simpan
                </button>
                <?=form_close()?>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    var inputs ='';
    var banyak = '<?=$banyak;?>';
</script>

<script src="<?=base_url()?>assets/dist/js/app/master/kelas/add.js"></script>