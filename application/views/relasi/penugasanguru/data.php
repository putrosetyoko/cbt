<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul ?? 'Data Penugasan Guru'); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('penugasanguru/add') ?>" class="btn btn-sm btn-flat bg-purple"><i class="fa fa-plus"></i> Tambah Penugasan</a>
            <!-- <a href="<?= base_url('penugasanguru/import') ?>" class="btn btn-sm btn-flat btn-success"><i class="fa fa-upload"></i> Import Data</a> -->
            <!-- <button type="button" onclick="reload_ajax()" class="btn btn-sm btn-flat btn-default"><i class="fa fa-refresh"></i> Reload</button> -->
        </div>
    </div>
    <div class="box-body">
        <div class="row" style="margin-bottom: 10px;">
            <div class="col-sm-3">
                <label>Filter Tahun Ajaran:</label>
                <select id="filter_tahun_ajaran" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Tahun Ajaran</option>
                    <?php if(isset($all_tahun_ajaran) && !empty($all_tahun_ajaran)): ?>
                        <?php foreach ($all_tahun_ajaran as $ta) : ?>
                            <option value="<?= $ta->id_tahun_ajaran ?>"><?= htmlspecialchars($ta->nama_tahun_ajaran) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label>Filter Guru:</label>
                <select id="filter_guru" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Guru</option>
                    <?php if(isset($all_guru) && !empty($all_guru)): ?>
                        <?php foreach ($all_guru as $g) : ?>
                            <option value="<?= $g->id_guru ?>"><?= htmlspecialchars($g->nama_guru) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label>Filter Mata Pelajaran:</label>
                <select id="filter_mapel" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Mapel</option>
                    <?php if(isset($all_mapel) && !empty($all_mapel)): ?>
                        <?php foreach ($all_mapel as $m) : ?>
                            <option value="<?= $m->id_mapel ?>"><?= htmlspecialchars($m->nama_mapel) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-sm-3">
                <label>Filter Kelas:</label>
                <select id="filter_kelas" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Kelas</option>
                    <?php if(isset($all_kelas) && !empty($all_kelas)): ?>
                        <?php foreach ($all_kelas as $k) : ?>
                            <option value="<?= $k->id_kelas ?>"><?= htmlspecialchars( (isset($k->nama_jenjang) ? $k->nama_jenjang . ' ' : '') . $k->nama_kelas) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <div class="row" style="margin-top: 20px;">
            <div class="col-sm-12 text-left">
            <div class="callout callout-info">
                    <p><strong>Keterangan Warna Kelas:</strong></p>
                    <div>
                        <span class="badge bg-green" style="font-size: 12px; padding: 4px 8px; margin-right: 4px;">Kelas VII</span>
                        <span class="badge bg-blue" style="font-size: 12px; padding: 4px 8px; margin-right: 4px;">Kelas VIII</span>
                        <span class="badge bg-maroon" style="font-size: 12px; padding: 4px 8px;">Kelas IX</span>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 text-right">
                <button onclick="bulk_delete()" class="btn btn-sm btn-flat btn-danger" type="button"><i class="fa fa-trash"></i> Delete</button>
            </div>
        </div>

        <?= form_open('penugasanguru/delete', array('id' => 'bulkDeleteFormPenugasanGuru')); ?>
        <div class="table-responsive mt-3">
            <table id="table_penugasan_guru" class="table table-striped table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th width="3%" class="text-center">No.</th>
                        <th>Tahun Ajaran</th>
                        <th>Nama Guru</th>
                        <th>Mata Pelajaran</th>
                        <th>Kelas Ajar</th>
                        <th width="80" class="text-center">Aksi</th>
                        <th width="30" class="text-center">
                            <input class="select_all" type="checkbox">
                        </th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <?= form_close(); ?>
    </div>
</div>

<script src="<?= base_url() ?>assets/dist/js/app/relasi/penugasanguru/data.js"></script>
<script>
$(document).ready(function() {
    if($.fn.select2){ 
        $('#filter_tahun_ajaran, #filter_guru, #filter_mapel, #filter_kelas').select2(); 
    }
});
</script>
