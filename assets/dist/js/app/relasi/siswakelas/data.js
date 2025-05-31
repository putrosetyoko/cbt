var tableSiswaKelasAjaran; // Ganti nama variabel tabel

$(document).ready(function () {
  if (typeof ajaxcsrf === 'function') {
    // Pastikan fungsi ini ada
    ajaxcsrf();
  }

  tableSiswaKelasAjaran = $('#table_siswa_kelas_ajaran').DataTable({
    // ID Tabel diubah
    initComplete: function () {
      var api = this.api();
      // Sesuaikan jika ada input filter global untuk tabel ini
      $('#table_siswa_kelas_ajaran_filter input')
        .off('.DT')
        .on('keyup.DT', function (e) {
          api.search(this.value).draw();
        });
    },
    dom:
      "<'row'<'col-sm-12 col-md-3'l><'col-sm-12 col-md-6 text-center'B><'col-sm-12 col-md-3'f>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    buttons: [
      // Sesuaikan kolom untuk export
      { extend: 'copy', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } }, // No, TA, Kelas, NISN, Nama
      { extend: 'print', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
      { extend: 'excel', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
      { extend: 'pdf', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
    ],
    oLanguage: {
      /* ... Teks bahasa Indonesia Anda ... */ sProcessing: 'Memuat...',
      sSearch: 'Cari:',
      sLengthMenu: 'Tampilkan _MENU_ entri',
      sInfo: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
      sInfoEmpty: 'Menampilkan 0 sampai 0 dari 0 entri',
      sInfoFiltered: '(disaring dari _MAX_ total entri)',
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'siswakelas/data', // URL untuk data Distribusi Kelas Siswa
      type: 'POST',
      data: function (d) {
        d.filter_tahun_ajaran = $('#filter_tahun_ajaran').val(); // Ambil filter
        d.filter_kelas = $('#filter_kelas').val(); // Ambil filter
        d.t = new Date().getTime(); // Cache busting
        // Tambahkan CSRF jika perlu
        // if(typeof csrf_name !== 'undefined' && typeof csrf_hash !== 'undefined'){
        //    d[csrf_name] = csrf_hash;
        // }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        /* ... error handling ... */
      },
    },
    columns: [
      { data: 'id_ska', orderable: false, searchable: false, width: '3%' }, // Untuk No.
      { data: 'nama_tahun_ajaran' },
      { data: 'nama_jenjang', defaultContent: '-' },
      { data: 'nama_kelas' },
      { data: 'nisn' },
      { data: 'nama_siswa' },
      // Kolom Aksi dan Checkbox akan dirender melalui columnDefs
      {
        data: null,
        className: 'text-center',
        orderable: false,
        searchable: false,
      }, // Aksi
      {
        data: null,
        className: 'text-center',
        orderable: false,
        searchable: false,
      }, // Checkbox
    ],
    columnDefs: [
      {
        targets: 0, // Kolom No.
        render: function (data, type, row, meta) {
          return meta.row + meta.settings._iDisplayStart + 1;
        },
      },
      {
        targets: 6, // Kolom Aksi (setelah Nama Siswa)
        data: 'id_ska', // Data yang dibutuhkan adalah id_ska untuk link edit
        render: function (data, type, row, meta) {
          // Tidak ada tombol "Aktifkan User" di sini, hanya Edit
          return `<div class="text-center">
                    <a class="btn btn-xs btn-warning" href="${base_url}siswakelas/edit/${data.id_ska}">
                        <i class="fa fa-pencil"></i> Edit
                    </a>
                  </div>`;
        },
      },
      {
        targets: 7, // Kolom Checkbox
        data: 'id_ska', // Value checkbox adalah id_ska
        render: function (data, type, row, meta) {
          return `<div class="text-center">
                    <input name="checked[]" class="check" value="${data}" type="checkbox">
                  </div>`;
        },
      },
    ],
    order: [
      [1, 'desc'],
      [2, 'asc'],
      [3, 'asc'],
      [5, 'asc'],
    ],
    rowId: function (a) {
      return 'row_ska_' + a.id_ska; // **PENTING**: Gunakan id_ska yang unik
    },
    // rowCallback untuk penomoran sudah dihandle columnDefs[0].render
  });

  tableSiswaKelasAjaran
    .buttons()
    .container()
    .appendTo('#table_siswa_kelas_ajaran_wrapper .col-md-6:eq(0)'); // Sesuaikan ID wrapper

  // Event listener untuk filter
  $('#filter_tahun_ajaran, #filter_kelas').on('change', function () {
    if (tableSiswaKelasAjaran) {
      tableSiswaKelasAjaran.ajax.reload();
    }
  });

  // Handler untuk checkbox "select_all" (gunakan class .select_all)
  $(document).on('click', '.select_all', function () {
    let isChecked = this.checked;
    $('.check', tableSiswaKelasAjaran.rows({ search: 'applied' }).nodes()).prop(
      'checked',
      isChecked
    );
  });

  // Handler untuk checkbox individual
  $('#table_siswa_kelas_ajaran tbody').on('click', 'tr .check', function () {
    var totalChecks = $(
      '.check',
      tableSiswaKelasAjaran.rows({ search: 'applied' }).nodes()
    ).length;
    var checkedChecks = $(
      '.check:checked',
      tableSiswaKelasAjaran.rows({ search: 'applied' }).nodes()
    ).length;
    $('.select_all').prop(
      'checked',
      totalChecks === checkedChecks && totalChecks > 0
    );
  });

  // Handler untuk form bulk delete (sesuaikan ID form jika berbeda dari '#bulkDeleteForm')
  // ID form di view data.php siswakelas adalah 'bulkDeleteForm'
  $('#bulkDeleteForm').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    console.log(
      'Form #bulkDeleteForm submitted. Action:',
      $(this).attr('action')
    ); // Debug

    // Pastikan action adalah untuk delete (ini seharusnya sudah di-set oleh fungsi bulk_delete())
    if (
      !$(this).attr('action') ||
      !$(this).attr('action').endsWith('siswakelas/delete')
    ) {
      console.warn(
        'Form action not set correctly for delete or not a delete action.'
      );
      Swal.fire('Perhatian', 'Aksi form tidak diset untuk delete.', 'warning');
      return;
    }

    var serializedData = $(this).serialize(); // Mengambil semua data form, termasuk checkbox 'checked[]'
    console.log('Data yang diserialisasi:', serializedData); // Debug

    // Cek apakah ada checkbox yang tercentang melalui data serialisasi atau hitung manual
    if (serializedData.indexOf('checked%5B%5D=') === -1) {
      // '%5B%5D' adalah '[]' yang di-URL encode
      Swal.fire('Gagal', 'Tidak ada data yang dipilih untuk dihapus.', 'error');
      return;
    }

    $.ajax({
      url: $(this).attr('action'),
      data: serializedData, // Kirim data yang sudah diserialisasi
      type: 'POST',
      dataType: 'json', // PENTING: harapkan JSON dari server
      success: function (respon) {
        console.log('Respon dari server (delete siswakelas):', respon); // Debug
        if (respon && typeof respon.status !== 'undefined') {
          Swal.fire({
            title: respon.status ? 'Berhasil' : 'Gagal',
            text:
              respon.message ||
              (respon.status
                ? (respon.total || '') + ' data berhasil dihapus.'
                : 'Operasi gagal.'),
            icon: respon.status ? 'success' : 'error',
          });
          if (respon.status) {
            reload_ajax();
            $('.select_all').prop('checked', false);
          }
        } else {
          Swal.fire(
            'Error Respons',
            'Format respons dari server tidak dikenali.',
            'error'
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        Swal.fire(
          'Error AJAX',
          'Gagal menghubungi server: ' + textStatus,
          'error'
        );
        console.error(
          'Delete SiswaKelas AJAX Error: ',
          jqXHR.responseText,
          textStatus,
          errorThrown
        );
      },
    });
  });

  // Tidak ada tombol .btn-aktif untuk Distribusi Kelas Siswa, jadi handler itu dihilangkan
}); // End $(document).ready

// Fungsi global untuk bulk delete (dipanggil dari tombol onclick)
function bulk_delete() {
  // Untuk Distribusi Kelas Siswa
  // Pastikan ada checkbox yang tercentang
  if ($('#table_siswa_kelas_ajaran tbody tr .check:checked').length == 0) {
    Swal.fire('Gagal', 'Tidak ada data penempatan yang dipilih.', 'error');
    return;
  }
  // Set action form ke URL delete yang benar
  $('#bulkDeleteForm').attr('action', base_url + 'siswakelas/delete');

  Swal.fire({
    title: 'Anda yakin?',
    text: 'Data penempatan siswa yang dipilih akan dihapus!',
    type: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
  }).then((result) => {
    if (result.isConfirmed) {
      $('#bulkDeleteForm').submit(); // Submit form yang akan ditangani oleh event handler di atas
    }
  });
}

// Tidak ada fungsi bulk_activate untuk Distribusi Kelas Siswa
// function bulk_activate() { /* ... */ }

function reload_ajax() {
  if (
    tableSiswaKelasAjaran &&
    typeof tableSiswaKelasAjaran.ajax !== 'undefined'
  ) {
    tableSiswaKelasAjaran.ajax.reload(null, false);
  }
}
