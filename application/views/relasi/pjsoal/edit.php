<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Edit Penanggung Jawab Soal'); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('pjsoal') ?>" class="btn btn-sm btn-flat btn-warning"><i class="fa fa-arrow-left"></i> Batal</a>
        </div>
    </div>
    <?= form_open('pjsoal/update', array('id' => 'formPJSoalEdit')); ?>
    <input type="hidden" name="id_pjsa" value="<?= $pjsa->id_pjsa; ?>">
    <div class="box-body">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="form-group">
                    <label>Tahun Ajaran</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($pjsa->nama_tahun_ajaran); ?>" readonly>
                </div>

                <div class="form-group">
                    <label>Mata Pelajaran</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($pjsa->nama_mapel); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="guru_id_edit">Guru Penanggung Jawab <span class="text-danger">*</span></label>
                    <select name="guru_id" id="guru_id_edit" class="form-control select2" style="width: 100%;" required>
                        <option value="">-- Pilih Guru --</option>
                        <?php 
                        // Menambahkan guru PJ saat ini ke daftar jika belum ada di $all_guru (yang sudah difilter)
                        $guru_pj_saat_ini_ada_di_list = false;
                        if(isset($all_guru)): 
                            foreach ($all_guru as $g) : 
                                if ($g->id_guru == $pjsa->guru_id) $guru_pj_saat_ini_ada_di_list = true;
                        ?>
                            <option value="<?= $g->id_guru ?>" <?= ($g->id_guru == $pjsa->guru_id) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($g->nip . ' - ' . $g->nama_guru) ?>
                            </option>
                        <?php 
                            endforeach; 
                        endif; 
                        // Jika guru PJ saat ini tidak ada di list (karena dia sudah jadi PJ mapel lain), tambahkan secara manual
                        if (!$guru_pj_saat_ini_ada_di_list && isset($pjsa->guru_id) && isset($pjsa->nip) && isset($pjsa->nama_guru)):
                        ?>
                            <option value="<?= $pjsa->guru_id ?>" selected>
                                <?= htmlspecialchars($pjsa->nip . ' - ' . $pjsa->nama_guru) ?> (PJ Saat Ini)
                            </option>
                        <?php endif; ?>
                    </select>
                    <small class="help-block text-danger" id="error_guru_id"></small>
                </div>

                <div class="form-group">
                    <label for="keterangan_edit">Keterangan (Opsional)</label>
                    <textarea name="keterangan" id="keterangan_edit" class="form-control" rows="3" placeholder="Catatan tambahan..."><?= htmlspecialchars($pjsa->keterangan); ?></textarea>
                    <small class="help-block text-danger" id="error_keterangan"></small>
                </div>
                
                <div class="form-group pull-right">
                    <button type="submit" id="submitBtnEditPJ" class="btn btn-flat bg-purple"><i class="fa fa-save"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>
    <?= form_close(); ?>
</div>

<script src="<?= base_url() ?>assets/dist/js/app/relasi/pjsoal/edit.js"></script>
<script>
$(document).ready(function() {
    if($.fn.select2){ 
        $('#guru_id_edit').select2({ placeholder: "-- Pilih Guru --", allowClear: true });
    }
    $('#guru_id_edit').focus();
});
</script>
