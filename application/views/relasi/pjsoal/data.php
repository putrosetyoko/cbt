<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Data Penanggung Jawab Soal'); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('pjsoal/add') ?>" class="btn btn-sm btn-flat bg-purple"><i class="fa fa-plus"></i> Atur PJ Soal</a>
            <!-- <a href="<?= base_url('pjsoal/import') ?>" class="btn btn-sm btn-flat btn-success"><i class="fa fa-upload"></i> Import PJ Soal</a> -->
            <button type="button" onclick="reload_ajax_pjsoal()" class="btn btn-sm btn-flat btn-default"><i class="fa fa-refresh"></i> Reload</button>
        </div>
    </div>
    <div class="box-body">
        <div class="row" style="margin-bottom: 10px;">
            <div class="col-sm-6">
                <label for="filter_tahun_ajaran_pj">Filter Tahun Ajaran:</label>
                <select id="filter_tahun_ajaran_pj" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Tahun Ajaran</option>
                    <?php if(isset($all_tahun_ajaran) && !empty($all_tahun_ajaran)): ?>
                        <?php foreach ($all_tahun_ajaran as $ta) : ?>
                            <option value="<?= $ta->id_tahun_ajaran ?>"><?= htmlspecialchars($ta->nama_tahun_ajaran) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-sm-6">
                <label for="filter_mapel_pj">Filter Mata Pelajaran:</label>
                <select id="filter_mapel_pj" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Mata Pelajaran</option>
                    <?php if(isset($all_mapel) && !empty($all_mapel)): ?>
                        <?php foreach ($all_mapel as $m) : ?>
                            <option value="<?= $m->id_mapel ?>"><?= htmlspecialchars($m->nama_mapel) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table id="table_pj_soal" class="table table-striped table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th width="3%" class="text-center">No.</th>
                        <th>Tahun Ajaran</th>
                        <th>Mata Pelajaran</th>
                        <th>NIP Guru</th>
                        <th>Nama Guru</th>
                        <th>Keterangan</th>
                        <th width="120" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<input type="hidden" name="csrf_name" value="<?= $this->security->get_csrf_token_name() ?>">
<input type="hidden" name="csrf_hash" value="<?= $this->security->get_csrf_hash() ?>">

<script src="<?= base_url() ?>assets/dist/js/app/relasi/pjsoal/data.js"></script>
<script>
$(document).ready(function() {
    if($.fn.select2){ 
        $('#filter_tahun_ajaran_pj, #filter_mapel_pj').select2(); 
    }
    // Trigger change untuk load data awal berdasarkan filter default (misal TA aktif)
    $('#filter_tahun_ajaran_pj').trigger('change'); 
});
</script>
