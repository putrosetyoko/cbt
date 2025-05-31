<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Tambah Penugasan Guru'); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('penugasanguru') ?>" class="btn btn-sm btn-flat btn-warning"><i class="fa fa-arrow-left"></i> Batal</a>
        </div>
    </div>
    <?= form_open('penugasanguru/save', array('id' => 'formPenugasanGuru')); ?>
    <div class="box-body">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="form-group">
                    <label for="id_tahun_ajaran">Tahun Ajaran <span class="text-danger">*</span></label>
                    <select name="id_tahun_ajaran" id="id_tahun_ajaran" class="form-control select2" style="width: 100%;" required>
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        <?php if(isset($all_tahun_ajaran)): foreach ($all_tahun_ajaran as $ta) : ?>
                            <option value="<?= $ta->id_tahun_ajaran ?>"><?= htmlspecialchars($ta->nama_tahun_ajaran) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <small class="help-block text-danger" id="error_id_tahun_ajaran"></small>
                </div>

                <div class="form-group">
                    <label for="guru_id">Guru <span class="text-danger">*</span></label>
                    <select name="guru_id" id="guru_id" class="form-control select2" style="width: 100%;" required>
                        <option value="">-- Pilih Guru --</option>
                        <?php if(isset($all_guru)): foreach ($all_guru as $g) : ?>
                            <option value="<?= $g->id_guru ?>"><?= htmlspecialchars($g->nip . ' - ' . $g->nama_guru) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <small class="help-block text-danger" id="error_guru_id"></small>
                </div>

                <div class="form-group">
                    <label for="mapel_ids">Mata Pelajaran yang Diajar <span class="text-danger">*</span></label>
                    <select name="mapel_ids[]" id="mapel_ids" class="form-control select2" multiple="multiple" data-placeholder="Pilih satu atau beberapa mapel" style="width: 100%;" required>
                        <?php if(isset($all_mapel)): foreach ($all_mapel as $m) : ?>
                            <option value="<?= $m->id_mapel ?>"><?= htmlspecialchars($m->nama_mapel) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <small class="help-block text-danger" id="error_mapel_ids"></small>
                </div>

                <div class="form-group">
                    <label for="kelas_ids">Kelas yang Diajar <span class="text-danger">*</span></label>
                    <select name="kelas_ids[]" id="kelas_ids" class="form-control select2" multiple="multiple" data-placeholder="Pilih satu atau beberapa kelas" style="width: 100%;" required>
                        <?php if(isset($all_kelas)): foreach ($all_kelas as $k) : ?>
                            <option value="<?= $k->id_kelas ?>"><?= htmlspecialchars((isset($k->nama_jenjang) ? $k->nama_jenjang . ' - ' : '') . $k->nama_kelas) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                    <small class="help-block text-danger" id="error_kelas_ids"></small>
                </div>
                
                <div class="form-group pull-right">
                    <button type="reset" class="btn btn-flat btn-default"><i class="fa fa-rotate-left"></i> Reset</button>
                    <button type="submit" id="submitBtn" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<script src="<?= base_url() ?>assets/dist/js/app/relasi/penugasanguru/add.js"></script>
<script>
$(document).ready(function() {
    if($.fn.select2){ 
        $('.select2').select2({
            allowClear: true // Opsional
        }); 
        // Untuk placeholder yang berbeda pada multiple select
        $('#mapel_ids').select2({ placeholder: "Pilih mapel...", allowClear: true });
        $('#kelas_ids').select2({ placeholder: "Pilih kelas...", allowClear: true });
    }
    $('#id_tahun_ajaran').focus();
});
</script>
