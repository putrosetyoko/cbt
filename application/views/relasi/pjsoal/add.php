<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Atur Penanggung Jawab Soal'); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('pjsoal') ?>" class="btn btn-sm btn-flat btn-warning"><i class="fa fa-arrow-left"></i> Batal</a>
        </div>
    </div>
    <?= form_open('pjsoal/save', array('id' => 'formPJSoal')); ?>
    <div class="box-body">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="form-group">
                    <label for="id_tahun_ajaran">Tahun Ajaran <span class="text-danger">*</span></label>
                    <select name="id_tahun_ajaran" id="id_tahun_ajaran_form" class="form-control select2" style="width: 100%;" required>
                        <option value="">-- Pilih Tahun Ajaran --</option>
                        <?php if(isset($all_tahun_ajaran)): foreach ($all_tahun_ajaran as $ta) : ?>
                            <option value="<?= $ta->id_tahun_ajaran ?>" <?= $ta->status == 'aktif' ? 'selected' : '' ?>><?= htmlspecialchars($ta->nama_tahun_ajaran) ?> </option>
                        <?php endforeach; endif; ?>
                    </select>
                    <small class="help-block text-danger" id="error_id_tahun_ajaran"></small>
                </div>

                <div class="form-group">
                    <label for="mapel_id">Mata Pelajaran <span class="text-danger">*</span></label>
                    <select name="mapel_id" id="mapel_id_form" class="form-control select2" data-placeholder="Pilih Mapel (setelah pilih TA)" style="width: 100%;" required disabled>
                        <option value="">-- Pilih Tahun Ajaran Dulu --</option>
                    </select>
                    <small class="help-block text-danger" id="error_mapel_id"></small>
                </div>

                <div class="form-group">
                    <label for="guru_id">Guru Penanggung Jawab <span class="text-danger">*</span></label>
                    <select name="guru_id" id="guru_id_form" class="form-control select2" data-placeholder="Pilih Guru (setelah pilih TA & Mapel)" style="width: 100%;" required disabled>
                        <option value="">-- Pilih Tahun Ajaran & Mapel Dulu --</option>
                    </select>
                    <small class="help-block text-danger" id="error_guru_id"></small>
                    <small class="help-block text-info" id="info_pj_sebelumnya"></small>
                </div>

                <div class="form-group">
                    <label for="keterangan">Keterangan (Opsional)</label>
                    <textarea name="keterangan" id="keterangan_form" class="form-control" rows="3" placeholder="Catatan tambahan..."></textarea>
                    <small class="help-block text-danger" id="error_keterangan"></small>
                </div>
                
                <div class="form-group pull-right">
                    <button type="reset" id="resetBtnPJ" class="btn btn-flat btn-default"><i class="fa fa-rotate-left"></i> Reset</button>
                    <button type="submit" id="submitBtnPJ" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<script src="<?= base_url() ?>assets/dist/js/app/relasi/pjsoal/add.js"></script>
<script>
$(document).ready(function() {
    if($.fn.select2){ 
        $('#id_tahun_ajaran_form').select2({ placeholder: "-- Pilih Tahun Ajaran --", allowClear: true });
        $('#mapel_id_form').select2({ placeholder: "-- Pilih TA dulu --", allowClear: true, disabled: true });
        $('#guru_id_form').select2({ placeholder: "-- Pilih TA & Mapel dulu --", allowClear: true, disabled: true });
    }
    // Trigger change pada tahun ajaran jika sudah ada yang terpilih (misal default aktif)
    if ($('#id_tahun_ajaran_form').val()) {
        $('#id_tahun_ajaran_form').trigger('change');
    }
});
</script>
