<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Edit Penugasan Guru</h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('penugasanguru') ?>" class="btn btn-sm btn-flat btn-warning">
                <i class="fa fa-arrow-left"></i> Batal
            </a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <form action="<?= base_url('penugasanguru/update') ?>" method="POST" id="formPenugasanGuru">
                    <!-- Hidden inputs -->
                    <input type="hidden" name="id_gmka" value="<?= $id_gmka ?? $penugasan->id_gmka ?>">
                    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                    
                    <div class="form-group">
                        <label for="id_tahun_ajaran">Tahun Ajaran <span class="text-danger">*</span></label>
                        <select name="id_tahun_ajaran" id="id_tahun_ajaran" class="form-control select2" required>
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            <?php foreach ($all_tahun_ajaran as $ta) : ?>
                                <option value="<?= $ta->id_tahun_ajaran ?>" <?= ($ta->id_tahun_ajaran == $penugasan->id_tahun_ajaran) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($ta->nama_tahun_ajaran) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="help-block text-danger" id="error_id_tahun_ajaran"></small>
                    </div> 

                    <div class="form-group">
                        <label for="guru_id">Guru <span class="text-danger">*</span></label>
                        <select name="guru_id" id="guru_id" class="form-control select2" style="width: 100%;" required>
                            <option value="">-- Pilih Guru --</option>
                            <?php if(isset($all_guru)): foreach ($all_guru as $g) : ?>
                                <option value="<?= $g->id_guru ?>" <?= ($g->id_guru == $penugasan->guru_id) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($g->nip . ' - ' . $g->nama_guru) ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                        <small class="help-block text-danger" id="error_guru_id"></small>
                    </div>

                    <div class="form-group">
                        <label for="mapel_id">Mata Pelajaran yang Diajar <span class="text-danger">*</span></label>
                        <select name="mapel_id" id="mapel_id" class="form-control select2" style="width: 100%;" required>
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            <?php if(isset($all_mapel) && !empty($all_mapel)): 
                                foreach ($all_mapel as $m) : ?>
                                    <option value="<?= $m->id_mapel ?>" <?= ($m->id_mapel == $penugasan->mapel_id) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($m->nama_mapel) ?>
                                    </option>
                            <?php endforeach; 
                            endif; ?>
                        </select>
                        <small class="help-block text-danger" id="error_mapel_id"></small>
                    </div>

                    <div class="form-group">
                        <label for="kelas_ids">Kelas yang Diajar <span class="text-danger">*</span></label>
                        <select name="kelas_ids[]" id="kelas_ids" class="form-control select2" multiple="multiple" 
                                data-placeholder="Pilih satu atau beberapa kelas" style="width: 100%;" required>
                            <?php if(isset($all_kelas)): foreach ($all_kelas as $k) : ?>
                                <?php 
                                // Convert kelas_id string to array if it's not already
                                $selected_kelas = is_array($penugasan->kelas_id) ? 
                                                $penugasan->kelas_id : 
                                                explode(',', $penugasan->kelas_id);
                                ?>
                                <option value="<?= $k->id_kelas ?>" 
                                    <?= in_array($k->id_kelas, $selected_kelas) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($k->nama_jenjang . ' ' . $k->nama_kelas) ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                        <small class="help-block text-danger" id="error_kelas_ids"></small>
                    </div>

                    <div class="form-group pull-right">
                        <button type="submit" id="submitBtn" class="btn btn-flat bg-purple">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url() ?>assets/dist/js/app/relasi/penugasanguru/edit.js"></script>



