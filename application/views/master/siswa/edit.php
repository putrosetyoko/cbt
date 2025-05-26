<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Form <?=$judul?></h3>
        <div class="box-tools pull-right">
            <a href="<?=base_url('siswa')?>" class="btn btn-sm btn-flat btn-warning">
                <i class="fa fa-arrow-left"></i> Batal
            </a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-4 col-sm-offset-4">
                <?=form_open('siswa/save', array('id'=>'siswa'), array('method'=>'edit', 'id_siswa'=>$siswa->id_siswa))?>
                    <div class="form-group">
                        <label for="nisn">NISN</label>
                        <input value="<?=$siswa->nisn?>" autofocus="autofocus" onfocus="this.select()" placeholder="NISN" type="text" name="nisn" class="form-control">
                        <small class="help-block"></small>
                    </div>
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input value="<?=$siswa->nama?>" placeholder="Nama" type="text" name="nama" class="form-control">
                        <small class="help-block"></small>
                    </div>
                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="form-control select2">
                            <option value="">-- Pilih --</option>
                            <option <?=$siswa->jenis_kelamin === "L" ? "selected" : "" ?> value="L">Laki-laki</option>
                            <option <?=$siswa->jenis_kelamin === "P" ? "selected" : "" ?> value="P">Perempuan</option>
                        </select>
                        <small class="help-block"></small>
                    </div>
                    <div class="form-group">
                        <label for="kelas">Kelas</label>
                        <select name="kelas" id="kelas" class="form-control">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($kelas as $k) : ?>
                                <option value="<?= $k->id_kelas ?>"
                                    <?php if ($k->id_kelas == $siswa->kelas_id) : ?>
                                        selected
                                    <?php endif; ?>>
                                    <?= $k->nama_kelas ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="help-block"></small>
                    </div>
                    <div class="form-group pull-right">
                        <button type="reset" class="btn btn-flat btn-default"><i class="fa fa-rotate-left"></i> Reset</button>
                        <button type="submit" id="submit" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan</button>
                    </div>
                <?=form_close()?>
            </div>
        </div>
    </div>
</div>

<script src="<?=base_url()?>assets/dist/js/app/master/siswa/edit.js"></script>