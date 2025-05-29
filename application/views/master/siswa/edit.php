<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Form <?=$subjudul?></h3>
        <div class="box-tools pull-right">
            <a href="<?=base_url('siswa')?>" class="btn btn-sm btn-flat btn-warning"><i class="fa fa-arrow-left"></i> Batal</a>
        </div>
    </div>
    <?=form_open('siswa/save', array('id'=>'formsiswa'), array('method'=>'add'));?>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-4 col-sm-offset-4">
                <div class="form-group">
                    <label for="nisn">NISN</label>
                    <input autofocus="autofocus" onfocus="this.select()" type="text" name="nisn" id="nisn" class="form-control" placeholder="Nomor Induk Siswa Nasional" required>
                    <small class="help-block text-danger" id="error_nisn"></small>
                </div>
                <div class="form-group">
                    <label for="nama">Nama Siswa</label>
                    <input type="text" name="nama" id="nama" class="form-control" placeholder="Nama Lengkap Siswa" required>
                    <small class="help-block text-danger" id="error_nama"></small>
                </div>
                <div class="form-group">
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="jenis_kelamin" class="form-control select2" required>
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                    <small class="help-block text-danger" id="error_jenis_kelamin"></small>
                </div>

                <div class="form-group pull-right">
                    <button type="reset" class="btn btn-flat btn-default"><i class="fa fa-rotate-left"></i> Reset</button>
                    <button type="submit" id="submit" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>
    <?=form_close();?>
</div>
<script src="<?=base_url()?>assets/dist/js/app/master/siswa/edit.js"></script>