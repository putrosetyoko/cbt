<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Buat Ujian Baru'); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('ujian') ?>" class="btn btn-sm btn-flat bg-yellow"><i class="fa fa-arrow-left"></i> Batal</a>
        </div>
    </div>
    <?= form_open(base_url('ujian/save'), ['id' => 'form-add-ujian', 'class' => 'form-horizontal']) ?>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-8 col-sm-offset-2">
                <div class="form-group">
                    <label for="nama_ujian" class="col-sm-3 control-label">Nama Ujian</label>
                    <div class="col-sm-9">
                        <input type="text" name="nama_ujian" id="nama_ujian" class="form-control" required>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>

                <?php if ($is_admin && isset($all_mapel_options_for_admin)) : ?>
                <div class="form-group">
                    <label for="mapel_id" class="col-sm-3 control-label">Mata Pelajaran</label>
                    <div class="col-sm-9">
                        <select name="mapel_id" id="mapel_id" class="form-control select2" style="width: 100%;" required>
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            <?php foreach ($all_mapel_options_for_admin as $mapel) : ?>
                                <option value="<?= $mapel->id_mapel ?>"><?= htmlspecialchars($mapel->nama_mapel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>
                <?php elseif ($is_guru && $pj_mapel_data) : ?>
                <div class="form-group">
                    <label class="col-sm-3 control-label">Mata Pelajaran (PJ)</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" value="<?= htmlspecialchars($pj_mapel_data->nama_mapel); ?>" readonly>
                        <input type="hidden" name="mapel_id" value="<?= $pj_mapel_data->id_mapel; ?>">
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="id_jenjang_target" class="col-sm-3 control-label">Jenjang Target</label>
                    <div class="col-sm-9">
                        <select name="id_jenjang_target" id="id_jenjang_target" class="form-control select2" style="width: 100%;" required>
                            <option value="">-- Pilih Jenjang Target --</option>
                            <?php if(isset($all_jenjang)): foreach ($all_jenjang as $j) : ?>
                                <option value="<?= $j->id_jenjang ?>"><?= htmlspecialchars($j->nama_jenjang) ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_tahun_ajaran" class="col-sm-3 control-label">Tahun Ajaran</label>
                    <div class="col-sm-9">
                        <select name="id_tahun_ajaran" id="id_tahun_ajaran" class="form-control select2" style="width: 100%;" required>
                            <option value="">-- Pilih Tahun Ajaran --</option>
                            <?php if(isset($all_tahun_ajaran)): foreach ($all_tahun_ajaran as $ta) : ?>
                                <option value="<?= $ta->id_tahun_ajaran ?>" <?= $ta->status == 'aktif' ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ta->nama_tahun_ajaran) ?> <?= $ta->status == 'aktif' ? '(Aktif)' : '' ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="jumlah_soal" class="col-sm-3 control-label">Jumlah Soal Ditampilkan</label>
                    <div class="col-sm-4">
                        <input type="number" name="jumlah_soal" id="jumlah_soal" class="form-control" min="1" required>
                        <small class="help-block">Jumlah soal yang akan tampil di siswa. Pastikan jumlah soal yang ditambahkan ke ujian ini mencukupi.</small>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="waktu" class="col-sm-3 control-label">Waktu Ujian (Menit)</label>
                    <div class="col-sm-4">
                        <input type="number" name="waktu" id="waktu" class="form-control" min="1" required>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tgl_mulai" class="col-sm-3 control-label">Tanggal Mulai Ujian</label>
                    <div class="col-sm-9">
                        <input type="datetime-local" name="tgl_mulai" id="tgl_mulai" class="form-control" required>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="terlambat" class="col-sm-3 control-label">Batas Akhir Masuk Ujian</label>
                    <div class="col-sm-9">
                        <input type="datetime-local" name="terlambat" id="terlambat" class="form-control" required>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">Acak Soal</label>
                    <div class="col-sm-9">
                        <label class="radio-inline">
                            <input type="radio" name="acak_soal" value="Y"> Ya
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="acak_soal" value="N" checked> Tidak
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Acak Opsi Jawaban</label>
                    <div class="col-sm-9">
                        <label class="radio-inline">
                            <input type="radio" name="acak_opsi" value="Y"> Ya
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="acak_opsi" value="N" checked> Tidak
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">Status Ujian (Aktif)</label>
                    <div class="col-sm-9">
                        <label class="radio-inline">
                            <input type="radio" name="aktif" value="Y" checked> Ya (Bisa Dikerjakan)
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="aktif" value="N"> Tidak (Draft)
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">
                        <button type="submit" class="btn bg-purple btn-flat"><i class="fa fa-save"></i> Simpan & Lanjut Tambah Soal</button>
                        <button type="reset" class="btn btn-default btn-flat"><i class="fa fa-refresh"></i> Reset</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?= form_close() ?>
</div>

<script src="<?= base_url('assets/dist/js/app/ujian/add.js') ?>"></script>