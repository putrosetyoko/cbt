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
                        <a href="<?=base_url('mapel')?>" class="btn btn-flat btn-warning btn-sm">
                            <i class="fa fa-arrow-left"></i> Batal
                        </a>
                        <div class="pull-right">
                            <span> Jumlah : </span><label for=""><?=count($mapel)?></label>
                        </div>
                    </div>
                </div>
                <?=form_open('mapel/save', array('id'=>'mapel'), array('mode'=>'edit'))?>
                <table id="form-table" class="table text-center table-condensed">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Mata Pelajaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($mapel as $row) : ?>
                        <tr>
                            <td><?=$no?></td>
                            <td>
                                <div class="form-group">
                                    <?=form_hidden('id_mapel['.$no.']', $row->id_mapel)?>
                                    <input autofocus="autofocus" onfocus="this.select()" autocomplete="off" value="<?=$row->nama_mapel?>" type="text" name="nama_mapel[<?=$no?>]" class="input-sm form-control">
                                    <small class="help-block text-right"></small>
                                </div>
                            </td>
                        </tr>
                        <?php 
                        $no++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
                <button type="submit" class="mb-4 btn btn-block btn-flat bg-purple">
                    <i class="fa fa-save"></i> Simpan
                </button>
                <?=form_close()?>
            </div>
        </div>
    </div>
</div>

<script src="<?=base_url()?>assets/dist/js/app/master/mapel/edit.js"></script>