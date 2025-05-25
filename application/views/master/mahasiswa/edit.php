<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Form <?=$judul?></h3>
        <div class="box-tools pull-right">
            <a href="<?=base_url('mahasiswa')?>" class="btn btn-sm btn-flat btn-warning">
                <i class="fa fa-arrow-left"></i> Batal
            </a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-4 col-sm-offset-4">
                <?=form_open('mahasiswa/save', array('id'=>'mahasiswa'), array('method'=>'edit', 'id_mahasiswa'=>$mahasiswa->id_mahasiswa))?>
                    <div class="form-group">
                        <label for="nim">NIM</label>
                        <input value="<?=$mahasiswa->nim?>" autofocus="autofocus" onfocus="this.select()" placeholder="NIM" type="text" name="nim" class="form-control">
                        <small class="help-block"></small>
                    </div>
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input value="<?=$mahasiswa->nama?>" placeholder="Nama" type="text" name="nama" class="form-control">
                        <small class="help-block"></small>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input value="<?=$mahasiswa->email?>" placeholder="Email" type="email" name="email" class="form-control">
                        <small class="help-block"></small>
                    </div>
                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="form-control select2">
                            <option value="">-- Pilih --</option>
                            <option <?=$mahasiswa->jenis_kelamin === "L" ? "selected" : "" ?> value="L">Laki-laki</option>
                            <option <?=$mahasiswa->jenis_kelamin === "P" ? "selected" : "" ?> value="P">Perempuan</option>
                        </select>
                        <small class="help-block"></small>
                    </div>
                    <div class="form-group">
                        <label for="jurusan">Jurusan</label>
                        <select name="jurusan" id="jurusan" class="form-control">
                            <option value="">Pilih Jurusan</option>
                            <?php foreach ($jurusan as $j) : // Loop untuk semua jurusan yang tersedia ?>
                                <option value="<?= $j->id_jurusan ?>"
                                    <?php if ($j->id_jurusan == $mahasiswa->jurusan_id) : // Bandingkan dengan jurusan_id dari mahasiswa yang diedit ?>
                                        selected
                                    <?php endif; ?>>
                                    <?= $j->nama_jurusan ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="help-block"></small>
                    </div>
                    <div class="form-group">
                        <label for="kelas">Kelas</label>
                        <select name="kelas" id="kelas" class="form-control">
                            <option value="">Pilih Kelas</option>
                            <?php foreach ($kelas as $k) : // Loop untuk semua kelas yang tersedia ?>
                                <option value="<?= $k->id_kelas ?>"
                                    <?php if ($k->id_kelas == $mahasiswa->kelas_id) : // Bandingkan dengan kelas_id dari mahasiswa yang diedit ?>
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

<script src="<?=base_url()?>assets/dist/js/app/master/mahasiswa/edit.js"></script>