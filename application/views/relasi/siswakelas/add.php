<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('siswakelas') ?>" class="btn btn-sm btn-flat btn-warning"><i class="fa fa-arrow-left"></i> Batal</a>
        </div>
    </div>
    <?= form_open('siswakelas/save', array('id' => 'formSiswaKelas')); ?>
    <div class="box-body">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="form-group">
                    <label for="id_tahun_ajaran">Tahun Ajaran <span class="text-danger">*</span></label>
                    <select name="id_tahun_ajaran" id="id_tahun_ajaran" class="form-control select2" style="width: 100%;" required>
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        <?php foreach ($all_tahun_ajaran as $ta) : ?>
                            <option value="<?= $ta->id_tahun_ajaran ?>"><?= htmlspecialchars($ta->nama_tahun_ajaran) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="help-block text-danger" id="error_id_tahun_ajaran"></small>
                </div>

                <div class="form-group">
                    <label for="kelas_id">Jenjang - Kelas <span class="text-danger">*</span></label> 
                    <select name="kelas_id" id="kelas_id" class="form-control select2" style="width: 100%;" required>
                        <option value="">-- Pilih Jenjang & Kelas --</option>
                        <?php if (isset($all_kelas) && !empty($all_kelas)) : ?>
                            <?php foreach ($all_kelas as $k) : ?>
                                <option value="<?= $k->id_kelas ?>">
                                    <?= htmlspecialchars($k->nama_jenjang . ' - ' . $k->nama_kelas) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small class="help-block text-danger" id="error_kelas_id"></small>
                </div>

                <div class="form-group">
                    <label for="siswa_ids">Pilih Siswa <span class="text-danger">*</span></label>
                    <!-- <small class="help-block text-danger" id="error_siswa_ids">Pilih Tahun Ajaran terlebih dahulu untuk memuat daftar Siswa yang belum ditempatkan.</small> -->
                    <select name="siswa_ids[]" id="siswa_ids" class="form-control select2" multiple="multiple" data-placeholder="Pilih satu atau beberapa siswa" style="width: 100%;" required disabled>
                    </select>
                    <small class="help-block text-danger" id="error_siswa_ids"></small>
                </div>
                
                <div class="form-group pull-right">
                    <button type="reset" class="btn btn-flat btn-default"><i class="fa fa-rotate-left"></i> Reset</button>
                    <button type="submit" id="submitBtn" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan Penempatan</button>
                </div>
            </div>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<script src="<?= base_url() ?>assets/dist/js/app/relasi/siswakelas/add.js"></script>
<script>
$(document).ready(function() {
    if($.fn.select2){ 
        $('.select2').select2(); 
        $('#siswa_ids').select2({
            placeholder: "Pilih tahun ajaran dulu",
            allowClear: true
        });
    }
});
</script>