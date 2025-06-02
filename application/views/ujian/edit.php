<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Edit Ujian'); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('ujian') ?>" class="btn btn-sm btn-flat bg-yellow"><i class="fa fa-arrow-left"></i> Batal</a>
        </div>
    </div>
    <div class="box-body">
        <hr>
        <h4><i class="fa fa-list-alt"></i> Soal dalam Ujian</h4>
        <p class="text-info">
            <i class="fa fa-info-circle"></i> Soal untuk ujian ini diambil secara otomatis dari Bank Soal 
            sesuai dengan Mata Pelajaran (<?= htmlspecialchars($ujian->nama_mapel ?? ''); ?>) 
            dan Jenjang (<?= htmlspecialchars($ujian->nama_jenjang_target ?? ''); ?>).
        </p>
        <p class="text-info">
            <i class="fa fa-info-circle"></i> Jumlah soal yang akan ditampilkan: 
            <strong><?= $ujian->jumlah_soal ?? 0 ?></strong> soal
        </p>
        <?php if(isset($total_available_soal)): ?>
            <p class="<?= $total_available_soal >= ($ujian->jumlah_soal ?? 0) ? 'text-success' : 'text-danger' ?>">
                <i class="fa fa-<?= $total_available_soal >= ($ujian->jumlah_soal ?? 0) ? 'check' : 'warning' ?>-circle"></i>
                Total soal tersedia di Bank Soal: <strong><?= $total_available_soal ?></strong> soal
                <?php if($total_available_soal < ($ujian->jumlah_soal ?? 0)): ?>
                    <br>
                    <small class="text-danger">
                        Peringatan: Jumlah soal yang tersedia kurang dari jumlah soal yang dibutuhkan untuk ujian ini.
                        Silakan tambahkan soal di menu Bank Soal terlebih dahulu.
                    </small>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <?= form_open(base_url('ujian/update/'.$ujian->id_ujian), ['id' => 'form-edit-ujian', 'class' => 'form-horizontal']) ?>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-8 col-sm-offset-2">
                <h4><i class="fa fa-cogs"></i> Detail Ujian</h4>
                <hr>
                <div class="form-group">
                    <label for="nama_ujian" class="col-sm-3 control-label">Nama Ujian</label>
                    <div class="col-sm-9">
                        <input type="text" name="nama_ujian" id="nama_ujian" class="form-control" value="<?= htmlspecialchars($ujian->nama_ujian ?? ''); ?>" required>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">Mata Pelajaran</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" value="<?= htmlspecialchars($ujian->nama_mapel ?? ''); ?>" readonly title="Mata pelajaran tidak dapat diubah setelah ujian dibuat.">
                        <?php if($is_admin): // Admin mungkin bisa mengubah mapel jika diperlukan, tapi umumnya tidak ?>
                        <?php endif; ?>
                        <input type="hidden" name="mapel_id" value="<?= $ujian->mapel_id; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="id_jenjang_target" class="col-sm-3 control-label">Jenjang Target</label>
                    <div class="col-sm-9">
                        <select name="id_jenjang_target" id="id_jenjang_target" class="form-control select2" style="width: 100%;" required>
                            <option value="">-- Pilih Jenjang Target --</option>
                            <?php if(isset($all_jenjang_options)): foreach ($all_jenjang_options as $j) : ?>
                                <option value="<?= $j->id_jenjang ?>" <?= ($ujian->id_jenjang_target == $j->id_jenjang) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($j->nama_jenjang) ?>
                                </option>
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
                            <?php if(isset($all_tahun_ajaran_options)): foreach ($all_tahun_ajaran_options as $ta) : ?>
                                <option value="<?= $ta->id_tahun_ajaran ?>" <?= ($ujian->id_tahun_ajaran == $ta->id_tahun_ajaran) ? 'selected' : '' ?>>
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
                        <input type="number" name="jumlah_soal" id="jumlah_soal" class="form-control" min="1" value="<?= htmlspecialchars($ujian->jumlah_soal ?? ''); ?>" required>
                        <small class="help-block">Pastikan jumlah soal yang ditambahkan ke ujian ini mencukupi.</small>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="waktu" class="col-sm-3 control-label">Waktu Ujian (Menit)</label>
                    <div class="col-sm-4">
                        <input type="number" name="waktu" id="waktu" class="form-control" min="1" value="<?= htmlspecialchars($ujian->waktu ?? ''); ?>" required>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>
                
                <?php
                    // Konversi format tanggal dari Y-m-d H:i:s (database) ke format input datetime-local (Y-m-d\TH:i)
                    $tgl_mulai_formatted = !empty($ujian->tgl_mulai) ? date('Y-m-d\TH:i', strtotime($ujian->tgl_mulai)) : '';
                    $terlambat_formatted = !empty($ujian->terlambat) ? date('Y-m-d\TH:i', strtotime($ujian->terlambat)) : '';
                ?>
                <div class="form-group">
                    <label for="tgl_mulai" class="col-sm-3 control-label">Tanggal Mulai Ujian</label>
                    <div class="col-sm-9">
                        <input type="datetime-local" name="tgl_mulai" id="tgl_mulai" class="form-control" value="<?= $tgl_mulai_formatted; ?>" required>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="terlambat" class="col-sm-3 control-label">Batas Akhir Masuk Ujian</label>
                    <div class="col-sm-9">
                        <input type="datetime-local" name="terlambat" id="terlambat" class="form-control" value="<?= $terlambat_formatted; ?>" required>
                        <span class="help-block text-danger"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">Acak Soal</label>
                    <div class="col-sm-9">
                        <label class="radio-inline">
                            <input type="radio" name="acak_soal" value="Y" <?= ($ujian->acak_soal ?? 'N') == 'Y' ? 'checked' : '' ?>> Ya
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="acak_soal" value="N" <?= ($ujian->acak_soal ?? 'N') == 'N' ? 'checked' : '' ?>> Tidak
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="col-sm-3 control-label">Acak Opsi Jawaban</label>
                    <div class="col-sm-9">
                        <label class="radio-inline">
                            <input type="radio" name="acak_opsi" value="Y" <?= ($ujian->acak_opsi ?? 'N') == 'Y' ? 'checked' : '' ?>> Ya
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="acak_opsi" value="N" <?= ($ujian->acak_opsi ?? 'N') == 'N' ? 'checked' : '' ?>> Tidak
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-sm-3 control-label">Status Ujian (Aktif)</label>
                    <div class="col-sm-9">
                        <label class="radio-inline">
                            <input type="radio" name="aktif" value="Y" <?= ($ujian->aktif ?? 'N') == 'Y' ? 'checked' : '' ?>> Ya (Bisa Dikerjakan)
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="aktif" value="N" <?= ($ujian->aktif ?? 'N') == 'N' ? 'checked' : '' ?>> Tidak (Draft)
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">Token Ujian</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" value="<?= htmlspecialchars($ujian->token ?? ''); ?>" readonly title="Token dibuat otomatis.">
                        <small class="help-block">Share token ini ke siswa agar bisa mengikuti ujian.</small>
                    </div>
                </div>


                <div class="form-group">
                    <div class="col-sm-offset-3 col-sm-9">
                        <button type="submit" class="btn btn-primary btn-flat"><i class="fa fa-save"></i> Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?= form_close() ?>
</div>

<script>
    const BASE_URL = '<?= base_url() ?>';
    const CSRF_TOKEN_NAME = '<?= $this->security->get_csrf_token_name() ?>';
    const CSRF_HASH = '<?= $this->security->get_csrf_hash() ?>';
</script>
<script src="<?= base_url('assets/dist/js/app/ujian/edit.js') ?>"></script>