<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('siswakelas') ?>" class="btn btn-sm btn-flat btn-warning"><i class="fa fa-arrow-left"></i> Batal</a>
        </div>
    </div>
    <?= form_open('siswakelas/update', array('id' => 'formSiswaKelas')); ?>
    <input type="hidden" name="id_ska" value="<?= $penempatan->id_ska; ?>">
    <div class="box-body">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="form-group">
                    <label>Tahun Ajaran</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($penempatan->nama_tahun_ajaran); ?>" readonly>
                    <input type="hidden" name="id_tahun_ajaran" value="<?= $penempatan->id_tahun_ajaran; ?>">
                </div>
                <div class="form-group">
                    <label>Siswa</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($penempatan->nama_siswa); ?> - <?= htmlspecialchars($penempatan->nisn ?? ''); ?>" readonly>
                    <input type="hidden" name="siswa_id" value="<?= $penempatan->siswa_id; ?>">
                </div>

                <div class="form-group">
                    <label for="kelas_id">Kelas <span class="text-danger">*</span></label>
                    <select name="kelas_id" id="kelas_id" class="form-control select2" style="width: 100%;" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php if (isset($all_kelas) && !empty($all_kelas)) : ?>
                            <?php foreach ($all_kelas as $k) : ?>
                                <option value="<?= $k->id_kelas ?>" <?= ($k->id_kelas == $penempatan->kelas_id) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($k->nama_jenjang . ' ' . $k->nama_kelas) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small class="help-block text-danger" id="error_kelas_id"></small>
                </div>
                
                <div class="form-group pull-right">
                    <button type="submit" id="submitBtn" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<script src="<?= base_url() ?>assets/dist/js/app/relasi/siswakelas/edit.js"></script>
<script>
$(document).ready(function() {
    if($.fn.select2){ $('.select2').select2(); }
});
</script>