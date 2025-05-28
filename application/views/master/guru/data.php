<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Master <?= $subjudul ?></h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-sm-4">
                <a href="<?= base_url('guru/add') ?>" class="btn btn-sm btn-flat bg-purple"><i class="fa fa-plus"></i> Tambah Data</a>
                <button type="button" onclick="reload_ajax()" class="btn btn-sm btn-flat btn-default"><i class="fa fa-refresh"></i> Reload</button>
            </div>
            <div class="form-group col-sm-4 text-center">
                <select id="mapel_filter" class="form-control select2" style="width:100% !important">
                    <option value="all">Semua Mata Pelajaran</option>
                    <?php foreach ($mapel as $m) : ?>
                        <option value="<?= $m->id_mapel ?>"><?= $m->nama_mapel ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4">
                <div class="pull-right">
                    <!-- <button onclick="bulk_activate()" class="btn btn-sm btn-flat btn-primary" type="button"><i class="fa fa-user-plus"></i> Aktif Semua</button> -->
                    <button onclick="bulk_delete()" class="btn btn-sm btn-flat btn-danger" type="button"><i class="fa fa-trash"></i> Delete</button>
                </div>
            </div>
        </div>
        <div class="mt-2 mb-3">
        </div>
        <?= form_open('guru/delete', array('id' => 'bulk')); ?>
        <div class="table-responsive">
            <table id="guru" class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>NIP</th>
                        <th>Nama Guru</th>
                        <th>Email</th>
                        <th>Mata Pelajaran</th>
                        <th width="100" class="text-center">Aksi</th>
                        <th width="100" class="text-center">
                            <input class="select_all" type="checkbox">
                        </th>
                    </tr>
                </thead>
            </table>
        </div>
        <?= form_close() ?>
    </div>
</div>

<script src="<?= base_url() ?>assets/dist/js/app/master/guru/data.js"></script>

<script type="text/javascript">
$(document).ready(function(){
    // Inisialisasi Select2 untuk dropdown mata pelajaran
    $('#mapel_filter').select2();

    $('#mapel_filter').on('change', function(){
        let id_mapel = $(this).val();
        let src = '<?= base_url() ?>guru/data'; // Base URL untuk DataTables AJAX

        // Perbarui URL AJAX DataTables berdasarkan filter mata pelajaran
        let url = src;
        if(id_mapel !== 'all'){
            url = src + '/' + id_mapel;
        }
        
        table.ajax.url(url).load(); // Muat ulang DataTables
    });
});
</script>