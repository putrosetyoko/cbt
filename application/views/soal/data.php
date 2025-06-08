<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Bank Soal'); ?></h3>
        <div class="box-tools pull-right">
            <?php if ($is_admin || ($is_guru && $pj_mapel_data)) : // Hanya Admin atau PJ Soal yang bisa tambah soal ?>
                <a href="<?= base_url('soal/add') ?>" class="btn btn-sm btn-flat bg-purple"><i class="fa fa-plus"></i> Buat Soal Baru</a>
            <?php endif; ?>
            <button type="button" onclick="reload_ajax_soal()" class="btn btn-sm btn-flat btn-default"><i class="fa fa-refresh"></i> Reload</button>
        </div>
    </div>
    <div class="box-body">
        <div class="row" style="margin-bottom: 10px;">
            <?php if ($is_admin) : ?>
                <div class="col-sm-3">
                    <label for="filter_mapel_soal">Filter Mata Pelajaran:</label>
                    <select id="filter_mapel_soal" class="form-control select2" style="width: 100%;">
                        <option value="all">Semua Mapel</option>
                        <?php if(isset($all_mapel)): foreach ($all_mapel as $m) : ?>
                            <option value="<?= $m->id_mapel ?>"><?= htmlspecialchars($m->nama_mapel) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
            <?php elseif ($is_guru && !$pj_mapel_data && isset($mapel_filter_options)) : ?>
                <div class="col-sm-3">
                    <label for="filter_mapel_soal">Filter Mata Pelajaran (yang Anda Ajar):</label>
                    <select id="filter_mapel_soal" class="form-control select2" style="width: 100%;">
                        <option value="all">Semua Mapel Diajar</option>
                        <?php foreach ($mapel_filter_options as $m) : ?>
                            <option value="<?= $m->id_mapel ?>"><?= htmlspecialchars($m->nama_mapel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($is_guru && $pj_mapel_data) : ?>
                <div class="col-sm-3">
                    <label>Mata Pelajaran (PJ):</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($pj_mapel_data->nama_mapel); ?>" readonly>
                    <input type="hidden" id="filter_mapel_soal" value="<?= $pj_mapel_data->id_mapel; ?>">
                </div>
            <?php endif; ?>

            <div class="col-sm-3">
                <label for="filter_jenjang_soal">Filter Jenjang:</label>
                <select id="filter_jenjang_soal" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Jenjang</option>
                    <?php if(isset($all_jenjang)): foreach ($all_jenjang as $j) : ?>
                        <option value="<?= $j->id_jenjang ?>"><?= htmlspecialchars($j->nama_jenjang) ?></option>
                    <?php endforeach; endif; ?>
                </select>
            </div>

            <?php
            // Kondisi ini sudah benar: Filter Guru Pembuat hanya muncul untuk admin.
            // Jika $is_admin bernilai false (misalnya untuk user guru), blok ini tidak akan ditampilkan.
            ?>
            <?php if ($is_admin) : ?>
                <div class="col-sm-3">
                    <label for="filter_guru_pembuat_soal">Filter Guru Pembuat:</label>
                    <select id="filter_guru_pembuat_soal" class="form-control select2" style="width: 100%;">
                        <option value="all">Semua Guru</option>
                        <?php if(isset($all_guru_pembuat)): foreach ($all_guru_pembuat as $gp) : ?>
                            <option value="<?= $gp->id_guru ?>"><?= htmlspecialchars($gp->nama_guru) ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-sm-12 text-right" style="padding-top:25px;">
                 <?php if ($is_admin || ($is_guru && $pj_mapel_data)): // Hanya admin atau PJ yang bisa bulk delete ?>
                    <button onclick="bulk_delete()" class="btn btn-sm btn-flat btn-danger" type="button"><i class="fa fa-trash"></i> Delete</button>
                <?php endif; ?>
            </div>
        </div>

        <?= form_open('soal/delete', array('id' => 'bulkDeleteFormSoal')); ?>
        <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" />
        <div class="table-responsive mt-3">
            <table id="table_soal" class="table table-striped table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Mapel</th>
                        <th>Jenjang</th>
                        <th>Soal</th>
                        <th>Pembuat</th>
                        <!-- <th>Bobot</th>
                        <th>Kunci</th>
                        <th>Soal Dibuat</th> -->
                        <th>Aksi</th>
                        <th>
                            <input type="checkbox" id="select_all_soal">
                        </th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <?= form_close(); ?>
    </div>
</div>

<script>
    // Pastikan variabel PHP $is_admin, $is_guru, $guru_data, dan $pj_mapel_data ada dari controller
    const IS_ADMIN_JS = <?= isset($is_admin) ? json_encode((bool)$is_admin) : 'false'; ?>;
    const IS_GURU_JS = <?= isset($is_guru) ? json_encode((bool)$is_guru) : 'false'; ?>;

    // Ambil id_guru dari objek $guru_data jika ada
    const GURU_ID_LOGIN_JS = <?= (isset($guru_data) && is_object($guru_data) && property_exists($guru_data, 'id_guru')) ? json_encode((int)$guru_data->id_guru) : 'null'; ?>;

    // Ambil id_mapel dari objek $pj_mapel_data jika ada dan $is_guru true
    const PJ_MAPEL_ID_JS = <?= (isset($is_guru) && $is_guru && isset($pj_mapel_data) && is_object($pj_mapel_data) && property_exists($pj_mapel_data, 'id_mapel')) ? json_encode((int)$pj_mapel_data->id_mapel) : 'null'; ?>;

    // Anda bisa tambahkan console.log di sini untuk debugging saat development
    console.log('JS Globals:', { IS_ADMIN_JS, IS_GURU_JS, GURU_ID_LOGIN_JS, PJ_MAPEL_ID_JS });
</script>

<script src="<?= base_url() ?>assets/dist/js/app/soal/data.js"></s>
<script>
$(document).ready(function() {
    if($.fn.select2){
        // Modifikasi di sini: tambahkan 'select' sebelum ID untuk lebih spesifik
        $('select#filter_mapel_soal, select#filter_jenjang_soal, select#filter_guru_pembuat_soal').select2({
            allowClear: true,
            placeholder: $(this).data('placeholder') || "Semua" // Placeholder akan tetap berfungsi untuk elemen yg cocok
        });
    }
    // Trigger change untuk load data awal berdasarkan filter default (jika ada)
    // Misalnya, jika guru PJ, mapel sudah terfilter
    if ($('#filter_mapel_soal').length > 0 || $('#filter_jenjang_soal').length > 0) {
        reload_ajax_soal(); // Panggil reload untuk memuat data awal dengan filter
    }
});
</script>