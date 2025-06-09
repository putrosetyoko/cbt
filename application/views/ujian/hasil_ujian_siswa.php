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
                        <option value="<?= $ta->id_tahun_ajaran ?>" <?= $ta->id_tahun_ajaran == $default_ta_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ta->nama_tahun_ajaran) ?>
                        </option>
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
        <hr>

        <div class="row" style="margin-bottom: 20px;">
            <div class="col-sm-6">
                <table class="table table-striped table-sm">
                    <tr>
                        <th width="30%">Nama Ujian</th>
                        <td id="summary_nama_ujian">-</td>
                    </tr>
                    <tr>
                        <th>Jumlah Soal</th>
                        <td id="summary_jumlah_soal">-</td>
                    </tr>
                    <tr>
                        <th>Waktu Ujian</th>
                        <td id="summary_waktu_ujian">-</td>
                    </tr>
                    <tr>
                        <th>Hari/Tanggal</th>
                        <td id="summary_hari_tanggal">-</td>
                    </tr>
                </table>
            </div>
            <div class="col-sm-6">
                <table class="table table-striped table-sm">
                    <tr>
                        <th width="30%">Mata Pelajaran</th>
                        <td id="summary_nama_mapel">-</td>
                    </tr>
                    <tr>
                        <th>Guru Pembuat Ujian</th>
                        <td id="summary_nama_guru_pembuat">-</td>
                    </tr>
                    <tr>
                        <th>Guru Mata Pelajaran</th>
                        <td id="summary_guru_mapel_mengajar">-</td>
                    </tr>
                    <tr>
                        <th>Nilai Terendah</th>
                        <td id="summary_nilai_terendah">
                            -
                        </td>
                    </tr>
                    <tr>
                        <th>Nilai Tertinggi</th>
                        <td id="summary_nilai_tertinggi">
                            -
                        </td>
                    </tr>
                    <tr>
                        <th>Rata-rata Nilai</th>
                        <td id="summary_rata_rata_nilai">-</td>
                    </tr>
                    <tr>
                        <th>Total Peserta Selesai</th>
                        <td id="summary_total_peserta_selesai">-</td>
                    </tr>
                </table>
            </div>
        </div>
        <hr>
        <div class="table-responsive mt-3">
            <form id="form-hapus-hasil-ujian">
                <div class="row dt-toolbar-top mb-2">
                    <div class="col-sm-12 text-right">
                        <div id="delete-button-placement" class="d-inline-block"></div>
                    </div>
                </div>
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
                            <th style="width: 5%">
                                <input type="checkbox" id="check-all">
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
</div>

<script>
    var table; // Deklarasi global

    // Fungsi global untuk mendapatkan CSRF token terbaru
    function getCsrfToken() {
        const csrfName = $('meta[name="csrf-name"]').attr('content') || '<?= $this->security->get_csrf_token_name(); ?>';
        const csrfHash = $('meta[name="csrf-hash"]').attr('content') || '<?= $this->security->get_csrf_hash(); ?>';
        return { name: csrfName, hash: csrfHash };
    }

    function showStudentNamesPopup(studentNames, title) {
        let content = '<ul>';
        studentNames.forEach(name => {
            content += `<li>${name}</li>`;
        });
        content += '</ul>';

        Swal.fire({
            title: title,
            html: content,
            confirmButtonText: 'OK'
        });
    }

    function updateSummaryPanel(summary) {
        $('#summary_nama_ujian').text(summary.nama_ujian);
        $('#summary_jumlah_soal').text(summary.jumlah_soal);
        $('#summary_waktu_ujian').text(summary.waktu_ujian_formatted);
        $('#summary_hari_tanggal').text(summary.hari_tanggal_formatted);
        $('#summary_nama_mapel').text(summary.nama_mapel);
        $('#summary_nama_guru_pembuat').text(summary.nama_guru_pembuat);
        $('#summary_guru_mapel_mengajar').text(summary.guru_mapel_mengajar);

        let nilaiTerendahText = summary.nilai_terendah + ' ';
        if (summary.siswa_nilai_terendah && summary.siswa_nilai_terendah.length > 0) {
            const siswaTerendahString = summary.siswa_nilai_terendah.map(name => `'${name.replace(/'/g, "\\'")}'`).join(",");
            nilaiTerendahText += `<i class="fa fa-search text-blue" style="cursor: pointer;" onclick="showStudentNamesPopup([${siswaTerendahString}], 'Siswa dengan Nilai Terendah')"></i>`;
        }
        $('#summary_nilai_terendah').html(nilaiTerendahText);

        let nilaiTertinggiText = summary.nilai_tertinggi + ' ';
        if (summary.siswa_nilai_tertinggi && summary.siswa_nilai_tertinggi.length > 0) {
            const siswaTertinggiString = summary.siswa_nilai_tertinggi.map(name => `'${name.replace(/'/g, "\\'")}'`).join(",");
            nilaiTertinggiText += `<i class="fa fa-search text-blue" style="cursor: pointer;" onclick="showStudentNamesPopup([${siswaTertinggiString}], 'Siswa dengan Nilai Tertinggi')"></i>`;
        }
        $('#summary_nilai_tertinggi').html(nilaiTertinggiText);

        $('#summary_rata_rata_nilai').text(summary.rata_rata_nilai);
        $('#summary_total_peserta_selesai').text(summary.total_peserta_selesai);
    }

    function loadSummaryData() {
        const csrfName = '<?= $this->security->get_csrf_token_name(); ?>';
        const csrfHash = '<?= $this->security->get_csrf_hash(); ?>';

        $.ajax({
            url: base_url + 'ujian/get_summary_hasil_ujian',
            type: 'POST',
            dataType: 'json',
            data: {
                filter_tahun_ajaran: $('#filter_tahun_ajaran').val(),
                filter_mapel: $('#filter_mapel').val(),
                filter_kelas: $('#filter_kelas').val(),
                [csrfName]: csrfHash
            },
            success: function(response) {
                if (response.status) {
                    updateSummaryPanel(response.summary);
                    if (response.csrf_hash_new) {
                        window[csrfName] = response.csrf_hash_new;
                    }
                } else {
                    console.error('Gagal memuat ringkasan:', response.message);
                    updateSummaryPanel({
                        nama_ujian: '-', jumlah_soal: '-', waktu_ujian_formatted: '-', hari_tanggal_formatted: '-',
                        nama_mapel: '-', nama_guru_pembuat: '-', guru_mapel_mengajar: '-',
                        nilai_terendah: '-', nilai_tertinggi: '-', rata_rata_nilai: '-', total_peserta_selesai: '-',
                        siswa_nilai_terendah: [], siswa_nilai_tertinggi: []
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error loading summary:', error);
                updateSummaryPanel({
                        nama_ujian: '-', jumlah_soal: '-', waktu_ujian_formatted: '-', hari_tanggal_formatted: '-',
                        nama_mapel: '-', nama_guru_pembuat: '-', guru_mapel_mengajar: '-',
                        nilai_terendah: '-', nilai_tertinggi: '-', rata_rata_nilai: '-', total_peserta_selesai: '-',
                        siswa_nilai_terendah: [], siswa_nilai_tertinggi: []
                    });
            }
        });
    }

    // Fungsi untuk mereset hasil ujian
    function deleteHasilUjian(ids) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: `Anda akan reset ${ids.length} hasil ujian ini. Ini akan mereset ujian bagi siswa tersebut. Tindakan ini tidak dapat dibatalkan!`,
            type: 'warning', // Gunakan 'type' untuk SweetAlert2 modern
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            // --- DEBUGGING KRUSIAL DI SINI ---
            console.log("SweetAlert result:", result); // Log seluruh objek result
            // Menggunakan OR (||) untuk mencakup kedua kemungkinan jika SweetAlert2 mengembalikan result.value atau result.isConfirmed
            // Yang paling tepat adalah memastikan hanya result.isConfirmed yang dicek
            // karena log Anda menunjukkan {value: true}, kita bisa cek itu juga.
            if (result.isConfirmed) { // Ini adalah cara yang direkomendasikan untuk SweetAlert2
                console.log("User mengklik 'Ya, Reset!' (isConfirmed)");
            } else if (result.value) { // Fallback untuk versi SweetAlert2 yang mengembalikan 'value'
                console.log("User mengklik 'Ya, Reset!' (value)");
            } else {
                console.log("User mengklik 'Batal' atau menutup SweetAlert.");
            }
            // --- AKHIR DEBUGGING KRUSIAL ---

            // Perubahan pada kondisi if (result.isConfirmed)
            if (result.isConfirmed || result.value) { // <--- KRUSIAL: UBAH KONDISI INI
                const csrfToken = getCsrfToken(); // Ambil token terbaru di sini

                console.log("Mengirim AJAX delete dengan CSRF Token:", csrfToken.name, csrfToken.hash);
                console.log("IDs yang akan di reset:", ids);

                $.ajax({
                    url: base_url + 'ujian/delete_hasil_ujian',
                    type: 'POST',
                    data: {
                        ids: ids,
                        [csrfToken.name]: csrfToken.hash
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Perbarui token setelah sukses
                        if (response.csrf_hash_new) {
                            window[csrfToken.name] = response.csrf_hash_new;
                            $('meta[name="csrf-hash"]').attr('content', response.csrf_hash_new);
                        }

                        if (response.status) {
                            Swal.fire('Di reset!', response.message, 'success');
                            reload_ajax();
                            loadSummaryData();
                            $('#check-all').prop('checked', false);
                        } else {
                            Swal.fire('Gagal!', response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error, xhr.responseText);
                        Swal.fire('Error!', 'Terjadi kesalahan saat berkomunikasi dengan server. ' + xhr.status + ': ' + error, 'error');
                        // Perbarui token jika ada error
                        if (xhr.responseJSON && xhr.responseJSON.csrf_hash_new) {
                            window[csrfToken.name] = xhr.responseJSON.csrf_hash_new;
                            $('meta[name="csrf-hash"]').attr('content', xhr.responseJSON.csrf_hash_new);
                        }
                    }
                });
            }
        });
    }


    $(document).ready(function() {
        ajaxcsrf();

        $('#filter_tahun_ajaran').val('<?= $default_ta_id ?>').trigger('change.select2');

        table = $('#hasilUjianTable').DataTable({
            "initComplete": function() {
                var api = this.api();
                // Tidak perlu lagi memindahkan input search secara manual di sini
                // Karena 'f' (Search) akan dipertahankan di dom
                $('#hasilUjianTable_filter input')
                    .off('.DT')
                    .on('keyup.DT', function(e) {
                        api.search(this.value).draw();
                    });
            },
            // DOM disesuaikan:
            // - r: Processing display element (hidden by default)
            // - t: The table
            // - <'row'>... : Baris atas dengan length menu (l), buttons (B), dan Search (f)
            // - <'row'<'col-sm-12'tr>>: Baris tabel
            // - <'row'<'col-sm-5'i><'col-sm-7'p>>: Baris bawah dengan info (i) dan pagination (p)
            // Tombol delete akan diletakkan di div khusus di atas baris utama
            "dom": "<'row'<'col-sm-3'l><'col-sm-5'B><'col-sm-4'f>>" + // l, B, dan f tetap sejajar
                "<'row'<'col-sm-12'tr>>" +
                "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            "buttons": [
                { extend: "copy", exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] } },
                { extend: "print", exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] } },
                { extend: "excel", exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] } },
                { extend: "pdf", exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9] } },
            ],
            "oLanguage": {
                "sProcessing": "loading..."
            },
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": base_url + "ujian/data_hasil_ujian_siswa",
                "type": "POST",
                "data": function(d) {
                    d.filter_tahun_ajaran = $('#filter_tahun_ajaran').val();
                    d.filter_mapel = $('#filter_mapel').val();
                    d.filter_kelas = $('#filter_kelas').val();
                    d['<?= $this->security->get_csrf_token_name(); ?>'] = '<?= $this->security->get_csrf_hash(); ?>';
                },
                "dataSrc": function (json) {
                    if (json.csrf_hash_new) {
                        window['<?= $this->security->get_csrf_token_name(); ?>'] = json.csrf_hash_new;
                    }
                    const nilaiTertinggi = json.nilai_tertinggi;
                    const nilaiTerendah = json.nilai_terendah;
                    json.data.forEach(row => {
                        row.nilai_tertinggi = nilaiTertinggi;
                        row.nilai_terendah = nilaiTerendah;
                    });
                    return json.data;
                }
            },
            "columns": [
                {
                    "data": null, // Nomor urut
                    "orderable": false,
                    "searchable": false,
                    "className": "text-center",
                },
                { "data": "nisn" },
                { "data": "nama_siswa" },
                { "data": "kelas_lengkap" },
                { "data": "nama_mapel" },
                { "data": "nama_ujian" },
                { "data": "jml_benar" },
                { "data": "nilai" },
                {
                    "data": "status_pengerjaan_raw",
                    "orderable": false,
                    "searchable": false,
                    "className": "text-center",
                    "render": function(data, type, row) {
                        const now = new Date().getTime();
                        const tglMulai = new Date(row.tgl_mulai).getTime();
                        const tglTerlambat = new Date(row.terlambat).getTime();

                        let statusText = '';
                        let statusClass = '';

                        if (data === 'completed') {
                            statusText = 'Selesai';
                            statusClass = 'label-success';
                        } else if (data === 'sedang_dikerjakan') {
                            statusText = 'Sedang Dikerjakan';
                            statusClass = 'label-warning';
                        } else {
                            if (now < tglMulai) {
                                statusText = 'Belum Mulai';
                                statusClass = 'label-default';
                            } else if (now > tglTerlambat) {
                                statusText = 'Terlewat';
                                statusClass = 'label-danger';
                            } else {
                                statusText = 'Belum Dikerjakan';
                                statusClass = 'label-info';
                            }
                        }
                        return `<span class="label ${statusClass}">${statusText}</span>`;
                    }
                },
                {
                    "data": "aksi", // Kolom Aksi
                    "orderable": false,
                    "searchable": false,
                    "className": "text-center",
                    "render": function(data, type, row) {
                        let actionButton = '';
                        actionButton = `<a class="btn bg-blue btn-xs" href="${base_url}ujian/detail_hasil_ujian/${row.id_hasil_ujian_encrypted}">
                                            <i class="fa fa-eye"></i> Lihat Hasil
                                        </a>`;
                        return `<div class="btn-group">${actionButton}</div>`;
                    }
                },
                {
                    "data": "id", // CHECKBOX DIPINDAHKAN KE SINI (PALING KANAN)
                    "orderable": false,
                    "searchable": false,
                    "className": "text-center",
                    "render": function(data, type, row) {
                        return `<input type="checkbox" name="checked[]" value="${row.id}">`;
                    }
                }
            ],
            "order": [[1, "asc"]], // Urutkan berdasarkan NISN
            "rowId": function(a) {
                return a.id;
            },
            "rowCallback": function(row, data, iDisplayIndex) {
                var info = this.fnPagingInfo();
                var page = info.iPage;
                var length = info.iLength;
                var index = page * length + (iDisplayIndex + 1);
                $('td:eq(0)', row).html(index); // Nomor urut sekarang di kolom pertama (indeks 0)

                const nilaiSiswa = parseFloat(data.nilai);
                const nilaiTertinggi = parseFloat(data.nilai_tertinggi);
                const nilaiTerendah = parseFloat(data.nilai_terendah);

                $(row).css('background-color', ''); // Reset warna

                if (nilaiSiswa === nilaiTertinggi && nilaiSiswa !== null && !isNaN(nilaiSiswa) && data.status_pengerjaan_raw === 'completed') {
                    $(row).css('background-color', '#d4edda'); // Hijau pudar
                } else if (nilaiSiswa === nilaiTerendah && nilaiSiswa !== null && !isNaN(nilaiSiswa) && data.status_pengerjaan_raw === 'completed') {
                    $(row).css('background-color', '#f8d7da'); // Merah pudar
                }
            }
        });

        // Tombol-tombol standar (Copy, Print, Excel, PDF) dipindahkan ke container defaultnya
        // Mereka akan otomatis ditempatkan di 'B' di dom
        table.buttons().container().appendTo($('#hasilUjianTable_wrapper .dt-buttons'));

        // Buat tombol Delete Selected secara manual dan tempatkan di container khusus
        $('<button>')
            .text(' Reset')
            .prepend('<i class="fa fa-refresh"></i>')
            .addClass('btn btn-danger  btn-flat btn-sm')
            .attr('id', 'btn-delete-selected')
            .on('click', function(e) {
                e.preventDefault();
                const selectedIds = [];
                $('input[name="checked[]"]:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    Swal.fire('Peringatan!', 'Tidak ada data yang dipilih untuk di reset.', 'warning');
                    return;
                }
                deleteHasilUjian(selectedIds);
            })
            .appendTo('#delete-button-placement'); // Masukkan ke container yang telah dibuat

        $('.select2').select2({
            placeholder: "Pilih Filter",
            allowClear: true
        });

        $('#filter_tahun_ajaran, #filter_mapel, #filter_kelas').on('change', function() {
            reload_ajax();
            loadSummaryData();
        });

        loadSummaryData(); 
        
        // Logika Checkbox Massal
        $('#check-all').on('click', function() {
            $('input[name="checked[]"]').prop('checked', $(this).prop('checked'));
        });

        $('#hasilUjianTable tbody').on('change', 'input[name="checked[]"]', function() {
            if (!$(this).prop('checked')) {
                $('#check-all').prop('checked', false);
            }
        });
    });

    function reload_ajax() {
        table.ajax.reload(null, false);
    }
</script>