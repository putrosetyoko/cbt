<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Daftar Ujian'); ?></h3>
        <div class="box-tools pull-right">
            <?php if ($is_admin || ($is_guru && $pj_mapel_data)) : ?>
                <a href="<?= base_url('ujian/add') ?>" class="btn btn-sm btn-flat bg-purple"><i class="fa fa-plus"></i> Buat Ujian Baru</a>
            <?php endif; ?>
            <button type="button" id="reload_table_ujian" class="btn btn-sm btn-flat btn-default"><i class="fa fa-refresh"></i> Reload</button>
        </div>
    </div>
    <div class="box-body">
        <div class="row" style="margin-bottom: 20px;">
            <div class="col-sm-3">
                <label for="filter_tahun_ajaran">Tahun Ajaran:</label>
                <select id="filter_tahun_ajaran" name="filter_tahun_ajaran" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua TA</option>
                    <?php if(isset($filter_tahun_ajaran_options)): foreach ($filter_tahun_ajaran_options as $ta) : ?>
                        <option value="<?= $ta->id_tahun_ajaran ?>" <?= ($ta->status == 'aktif' ? 'selected' : '') ?> >
                            <?= htmlspecialchars($ta->nama_tahun_ajaran) ?> <?= ($ta->status == 'aktif' ? '(Aktif)' : '') ?>
                        </option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <div class="col-sm-3">
                <label for="filter_mapel">Mata Pelajaran:</label>
                <?php if ($is_guru && $pj_mapel_data && !$is_admin) : // PJ Soal non-admin, mapelnya sudah pasti ?>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($pj_mapel_data->nama_mapel); ?>" readonly>
                    <input type="hidden" id="filter_mapel" name="filter_mapel" value="<?= $pj_mapel_data->id_mapel; ?>">
                <?php else: // Admin atau Guru Non-PJ (yang mapelnya bisa banyak) ?>
                    <select id="filter_mapel" name="filter_mapel" class="form-control select2" style="width: 100%;">
                        <option value="all">Semua Mapel</option>
                        <?php if(isset($filter_mapel_options)): foreach ($filter_mapel_options as $m) : ?>
                            <option value="<?= $m->id_mapel ?>"><?= htmlspecialchars($m->nama_mapel) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="col-sm-3">
                <label for="filter_jenjang">Jenjang:</label>
                <select id="filter_jenjang" name="filter_jenjang" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Jenjang</option>
                    <?php if(isset($filter_jenjang_options)): foreach ($filter_jenjang_options as $j) : ?>
                        <option value="<?= $j->id_jenjang ?>"><?= htmlspecialchars($j->nama_jenjang) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label>&nbsp;</label><br>
                <button type="button" id="btn-apply-filter-ujian" class="btn btn-primary btn-flat">Terapkan Filter</button>
            </div>
        </div>
        
        <div class="row" style="margin-bottom: 10px;">
            <div class="col-sm-12 text-right">
                <?php if ($is_admin || ($is_guru && $pj_mapel_data)) : ?>
                    <button type="button" id="btn-delete-selected-ujian" class="btn btn-danger btn-sm btn-flat">
                            <i class="fa fa-trash"></i> Delete
                            </button>
                        <?php endif; ?>            
            </div>
            <!-- <div class="col-sm-6">
                <div class="pull-right">
                    Show
                    <select name="table_ujian_length" aria-controls="table_ujian" class="form-control input-sm">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    entries
                </div>
            </div> -->
        </div>

        <?= form_open(base_url('ujian/delete'), ['id' => 'form-delete-selected-ujian']) ?>
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <div class="table-responsive">
            <table id="table_ujian" class="table table-striped table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th width="3%" class="text-center">No.</th>
                        <th>Nama Ujian</th>
                        <th>Mapel</th>
                        <th>Jenjang</th>
                        <th>PJ/Pembuat</th>
                        <th>Jumlah Soal</th>
                        <th>Hari/Tanggal</th>
                        <th>Waktu</th>
                        <th>Token</th>
                        <th>Status</th>
                        <th width="15%" class="text-center">Aksi</th>
                        <th width="3%" class="text-center">
                            <?php if ($is_admin || ($is_guru && $pj_mapel_data)) : // Admin atau PJ Soal bisa bulk delete ?>
                                <input type="checkbox" id="check-all-ujian">
                            <?php endif; ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <?= form_close() ?>
    </div>
</div>

<script type="text/javascript">
    // Variabel global dari PHP untuk JS (jika diperlukan)
    const BASE_URL = '<?= base_url() ?>';
    const IS_ADMIN = <?= json_encode($is_admin); ?>;
    const IS_GURU = <?= json_encode($is_guru); ?>;
    const GURU_ID = <?= json_encode($is_guru && isset($guru_data->id_guru) ? $guru_data->id_guru : null); ?>;
    const PJ_MAPEL_ID = <?= json_encode($is_guru && isset($pj_mapel_data->id_mapel) ? $pj_mapel_data->id_mapel : null); ?>;
    const CSRF_TOKEN_NAME = '<?= $this->security->get_csrf_token_name(); ?>';
    const CSRF_HASH = '<?= $this->security->get_csrf_hash(); ?>';
</script>
<script src="<?= base_url('assets/dist/js/app/ujian/data.js') ?>"></script>