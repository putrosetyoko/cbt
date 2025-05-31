<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul); ?></h3>
        <div class="box-tools pull-right">
            <a href="<?= base_url('siswakelas/add') ?>" class="btn btn-sm btn-flat bg-purple"><i class="fa fa-plus"></i> Tambah Penempatan</a>
            <a href="<?= base_url('siswakelas/import') ?>" class="btn btn-sm btn-flat btn-success"><i class="fa fa-upload"></i> Import Data</a> 
            <!-- <button type="button" onclick="reload_ajax()" class="btn btn-sm btn-flat btn-default"><i class="fa fa-refresh"></i> Reload</button> -->
        </div>
    </div>
    <div class="box-body">
        
        <div class="row" style="margin-bottom: 10px;">
            <div class="col-sm-4">
                <label>Filter Tahun Ajaran:</label>
                <select id="filter_tahun_ajaran" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Tahun Ajaran</option>
                    <?php foreach ($all_tahun_ajaran as $ta) : ?>
                        <option value="<?= $ta->id_tahun_ajaran ?>"><?= htmlspecialchars($ta->nama_tahun_ajaran) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4">
                <label>Filter Kelas:</label>
                <select id="filter_kelas" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Kelas</option>
                    <?php if (isset($all_kelas) && !empty($all_kelas)) : ?>
                            <?php foreach ($all_kelas as $k) : ?>
                                <option value="<?= $k->id_kelas ?>">
                                    <?= htmlspecialchars($k->nama_jenjang . ' ' . $k->nama_kelas) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                </select>
            </div>
            <div class="col-sm-4 text-right" style="padding-top: 25px;">
                <button onclick="bulk_delete()" class="btn btn-sm btn-flat btn-danger" type="button"><i class="fa fa-trash"></i> Delete</button>
            </div>
        </div>

        <?= form_open('siswakelas/delete', array(
                'id' => 'bulkDeleteForm'
            )); ?>
        <div class="table-responsive">
            <table id="table_siswa_kelas_ajaran" class="table table-striped table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th width="3%" class="text-center">No.</th>
                        <th>Tahun Ajaran</th>
                        <th>Kelas</th>
                        <th>NISN Siswa</th>
                        <th>Nama Siswa</th>
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

<script src="<?= base_url() ?>assets/dist/js/app/relasi/siswakelas/data.js"></script>
<script>
$(document).ready(function() {
    if($.fn.select2){ $('.select2').select2(); }
});
</script>