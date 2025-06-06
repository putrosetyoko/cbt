<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title"><?= htmlspecialchars($subjudul); ?></h3>
        <div class="box-tools pull-right">
            <button type="button" onclick="reload_ajax()" class="btn btn-sm btn-flat btn-default"><i class="fa fa-refresh"></i> Reload</button>
        </div>
    </div>
    <div class="box-body">
        <div class="row" style="margin-bottom: 10px;">
            <div class="col-sm-4">
                <label for="filter_tahun_ajaran">Filter Tahun Ajaran</label>
                <select id="filter_tahun_ajaran" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Tahun Ajaran</option>
                    <?php foreach ($filter_tahun_ajaran_options as $ta) : ?>
                        <option value="<?= $ta->id_tahun_ajaran ?>"><?= htmlspecialchars($ta->nama_tahun_ajaran) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4">
                <label for="filter_mapel">Filter Mata Pelajaran</label>
                <select id="filter_mapel" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Mata Pelajaran</option>
                    <?php foreach ($filter_mapel_options as $mapel) : ?>
                        <option value="<?= $mapel->id_mapel ?>"><?= htmlspecialchars($mapel->nama_mapel) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4">
                <label for="filter_kelas">Filter Kelas</label>
                <select id="filter_kelas" class="form-control select2" style="width: 100%;">
                    <option value="all">Semua Kelas</option>
                    <?php foreach ($filter_kelas_options as $kelas) : ?>
                        <option value="<?= $kelas->id_kelas ?>"><?= htmlspecialchars($kelas->nama_jenjang) ?> <?= htmlspecialchars($kelas->nama_kelas) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="table-responsive mt-3">
            <table class="table table-bordered table-striped table-hover" id="hasilUjianTable" style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 5%">No.</th>
                        <th>NISN</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Nama Ujian</th>
                        <th>Benar</th>
                        <th>Nilai Akhir</th>
                        <th>Status</th>
                        <th style="width: 10%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Variabel base_url sudah dideklarasikan di _header.php
    // Fungsi ajaxcsrf() sudah didefinisikan di _footer.php
    // Fungsi reload_ajax() sudah didefinisikan di _footer.php

    $(document).ready(function() {
        // Panggil ajaxcsrf() untuk mengatur token global pada setiap request AJAX
        ajaxcsrf();

        // Inisialisasi DataTables
        table = $('#hasilUjianTable').DataTable({
            "initComplete": function() {
                var api = this.api();
                $('#hasilUjianTable_filter input') 
                    .off('.DT')
                    .on('keyup.DT', function(e) {
                        api.search(this.value).draw();
                    });
            },
            "dom": "<'row'<'col-sm-3'l><'col-sm-6 text-center'B><'col-sm-3'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            "buttons": [
                {
                    extend: "copy",
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                },
                {
                    extend: "print",
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                },
                {
                    extend: "excel",
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                },
                {
                    extend: "pdf",
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
                }
            ],
            "oLanguage": {
                "sProcessing": "loading..."
            },
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": base_url + "ujian/data_hasil_ujian_siswa", // Menggunakan base_url global
                "type": "POST",
                "data": function(d) {
                    d.filter_tahun_ajaran = $('#filter_tahun_ajaran').val();
                    d.filter_mapel = $('#filter_mapel').val();
                    d.filter_kelas = $('#filter_kelas').val();
                    // CSRF token sudah dihandle oleh ajaxcsrf() global di _footer.php
                }
            },
            "columns": [
                {
                    "data": null,
                    "orderable": false,
                    "searchable": false
                },
                { "data": "nisn" },
                { "data": "nama_siswa" },
                { "data": "kelas_lengkap" }, 
                { "data": "nama_mapel" },
                { "data": "nama_ujian" },
                { "data": "jml_benar" },
                { "data": "nilai" },
                { "data": "status" },
                {
                    "data": "id_hasil_ujian_encrypted",
                    "orderable": false,
                    "searchable": false,
                    "render": function(data, type, row) {
                        var disabled = (row.status !== 'completed') ? 'disabled' : '';
                        return `<div class="text-center">
                                    <a class="btn btn-info btn-xs ${disabled}" href="${base_url}ujian/detail_hasil_ujian/${data}">
                                        <i class="fa fa-eye"></i> Lihat Hasil
                                    </a>
                                </div>`;
                    }
                }
            ],
            "order": [[1, "asc"]],
            "rowId": function(a) {
                return a.id; 
            },
            "rowCallback": function(row, data, iDisplayIndex) {
                var info = this.fnPagingInfo(); // fnPagingInfo sudah didefinisikan di _footer.php
                var page = info.iPage;
                var length = info.iLength;
                var index = page * length + (iDisplayIndex + 1);
                $('td:eq(0)', row).html(index);
            }
        });

        table.buttons().container().appendTo('#hasilUjianTable_wrapper .col-sm-6:eq(0)');

        // Inisialisasi select2
        $('.select2').select2({
            placeholder: "Pilih Filter",
            allowClear: true
        });

        // Event listener untuk perubahan filter
        $('#filter_tahun_ajaran, #filter_mapel, #filter_kelas').on('change', function() {
            // reload_ajax() adalah fungsi global dari _footer.php
            reload_ajax(); 
        });
    });
</script>