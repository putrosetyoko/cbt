<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Master <?= $subjudul ?></h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <div class="mt-2 mb-3">
            <a href="<?= base_url('tahunajaran/add') ?>" class="btn btn-sm btn-flat bg-purple"><i class="fa fa-plus"></i> Tambah Data</a>
            <button type="button" onclick="reload_ajax()" class="btn btn-sm btn-flat btn-default"><i class="fa fa-refresh"></i> Reload</button>
            <div class="pull-right">
                <button onclick="bulk_delete()" class="btn btn-sm btn-flat btn-danger" type="button"><i class="fa fa-trash"></i> Delete</button>
            </div>
        </div>
        <?= form_open(base_url('tahunajaran/delete'), array('id' => 'bulk')) ?>
        <table id="tahunajaran_table" class="w-100 table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Tahun Ajaran</th>
                    <th>Semester</th>
                    <th>Tanggal Mulai</th>
                    <th>Tanggal Selesai</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                    <th class="text-center">
                        <input type="checkbox" id="select_all">
                    </th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        <?= form_close() ?>
    </div>
</div>

<script src="<?= base_url() ?>assets/dist/js/app/master/tahunajaran/data.js"></script>