var tableSiswaKelasAjaran;

console.log('base_url:', base_url);

// Pastikan base_url diakhiri dengan slash
if (!base_url.endsWith('/')) {
  base_url += '/';
  console.log('base_url updated:', base_url);
}

$.ajaxSetup({
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
  },
}); // Deklarasi di scope global sudah benar

$(document).ready(function () {
  if (typeof ajaxcsrf === 'function') {
    ajaxcsrf();
  }

  tableSiswaKelasAjaran = $('#table_siswa_kelas_ajaran').DataTable({
    // Inisialisasi menggunakan tableSiswaKelasAjaran
    initComplete: function () {
      var api = this.api();
      $('#table_siswa_kelas_ajaran_filter input') // Pastikan ID filter ini benar
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
      { extend: 'copy', exportOptions: { columns: [0, 1, 2, 3, 4] } },
      { extend: 'print', exportOptions: { columns: [0, 1, 2, 3, 4] } },
      { extend: 'excel', exportOptions: { columns: [0, 1, 2, 3, 4] } },
      { extend: 'pdf', exportOptions: { columns: [0, 1, 2, 3, 4] } },
    ],
    oLanguage: {
      sProcessing: 'Memuat...',
      sSearch: 'Cari:',
      sInfoFiltered: '(disaring dari _MAX_ total entri)',
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'siswakelas/data',
      type: 'POST',
      data: function (d) {
        d.filter_tahun_ajaran = $('#filter_tahun_ajaran').val();
        d.filter_kelas = $('#filter_kelas').val();
        d.t = new Date().getTime();
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          'DataTables AJAX error on load:',
          textStatus,
          errorThrown,
          jqXHR.responseText
        );
        Swal.fire(
          'Error DataTables',
          'Gagal memuat data: ' + textStatus,
          'error'
        );
      },
    },
    columns: [
      { data: 'id_ska', orderable: false, searchable: false, width: '3%' },
      { data: 'nama_tahun_ajaran' },
      {
        // Gabungkan jenjang dan kelas
        data: null,
        render: function (data, type, row) {
          return row.nama_jenjang + ' ' + row.nama_kelas;
        },
      },
      { data: 'nisn' },
      { data: 'nama_siswa' },
      {
        data: null,
        className: 'text-center',
        orderable: false,
        searchable: false,
      },
      {
        data: null,
        className: 'text-center',
        orderable: false,
        searchable: false,
      },
    ],
    columnDefs: [
      {
        targets: 0,
        render: function (data, type, row, meta) {
          return meta.row + meta.settings._iDisplayStart + 1;
        },
      },
      {
        targets: 5,
        render: function (data, type, row) {
          return `<div class="text-center">
                  <a href="${base_url}siswakelas/edit/${data.id_ska}" class="btn btn-xs btn-warning">
                      <i class="fa fa-pencil"></i> Edit
                  </a>
              </div>`;
        },
      },
      {
        targets: 6,
        render: function (data, type, row) {
          return `<div class="text-center">
                  <input type="checkbox" class="check" name="checked[]" value="${data.id_ska}">
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
      return 'row_ska_' + a.id_ska;
    },
  });

  // Perbaikan fungsi reload_ajax
  window.reload_ajax = function () {
    console.log('Reloading table data...');

    // Reset filter ke default
    $('#filter_tahun_ajaran').val('all').trigger('change');
    $('#filter_kelas').val('all').trigger('change');

    // Reload table dengan animasi loading
    Swal.fire({
      title: 'Memuat Ulang Data',
      text: 'Mohon tunggu sebentar...',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      didOpen: () => {
        Swal.showLoading();
        tableSiswaKelasAjaran.ajax.reload(function () {
          Swal.close();
        });
      },
    });

    // Uncheck semua checkbox
    $('.select_all').prop('checked', false);
    $('.check').prop('checked', false);
  };

  // Perbaikan pada baris di bawah ini: gunakan variabel yang benar
  if (
    tableSiswaKelasAjaran &&
    typeof tableSiswaKelasAjaran.buttons === 'function'
  ) {
    tableSiswaKelasAjaran
      .buttons()
      .container()
      .appendTo('#table_siswa_kelas_ajaran_wrapper .col-md-6:eq(0)');
  } else {
    console.error(
      "DataTables instance 'tableSiswaKelasAjaran' or its 'buttons' method is not available."
    );
  }

  $('#filter_tahun_ajaran, #filter_kelas').on('change', function () {
    console.log('Filter changed:', {
      tahun_ajaran: $('#filter_tahun_ajaran').val(),
      kelas: $('#filter_kelas').val(),
    });
    tableSiswaKelasAjaran.ajax.reload();
  });

  // Inisialisasi Select2
  if ($.fn.select2) {
    $('.select2').select2({
      width: '100%',
      placeholder: function () {
        return $(this).data('placeholder') || 'Pilih opsi';
      },
      allowClear: true,
    });
  }

  $(document).on('click', '.select_all', function () {
    let isChecked = this.checked;
    // Targetkan checkbox di dalam tabel DataTables yang aktif
    $('.check', tableSiswaKelasAjaran.rows({ search: 'applied' }).nodes()).prop(
      'checked',
      isChecked
    );
  });

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

    // Ambil semua checkbox yang diceklis
    var checkedVals = [];
    $('#table_siswa_kelas_ajaran .check:checked').each(function () {
      checkedVals.push($(this).val());
    });

    if (checkedVals.length === 0) {
      Swal.fire('Gagal', 'Tidak ada data yang dipilih untuk dihapus.', 'error');
      return;
    }

    // Pastikan action sudah benar
    if (
      !$(this).attr('action') ||
      !$(this).attr('action').endsWith('siswakelas/delete')
    ) {
      Swal.fire('Perhatian', 'Aksi form tidak diset untuk delete.', 'warning');
      return;
    }

    // Kirim data via AJAX
    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: { checked: checkedVals },
      dataType: 'json',
      success: function (respon) {
        if (respon && typeof respon.status !== 'undefined') {
          Swal.fire({
            title: respon.status ? 'Berhasil' : 'Gagal',
            text:
              respon.message ||
              (respon.status
                ? (respon.total || '') + ' data berhasil dihapus.'
                : 'Operasi gagal.'),
            type: respon.status ? 'success' : 'error',
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
}); // End $(document).ready

function reload_ajax() {
  console.log('reload_ajax() function called');

  // Validasi DataTable instance
  if (
    !tableSiswaKelasAjaran ||
    typeof tableSiswaKelasAjaran.ajax === 'undefined'
  ) {
    console.error('DataTables instance not initialized');
    Swal.fire('Error', 'Tabel tidak dapat dimuat ulang', 'error');
    return;
  }

  // Reset filter tanpa trigger reload
  $('#filter_tahun_ajaran, #filter_kelas').each(function () {
    $(this).val('all').trigger('change.select2', true);
  });

  // Tampilkan loading
  const loadingDialog = Swal.fire({
    title: 'Memuat Ulang Data',
    text: 'Mohon tunggu sebentar...',
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    willOpen: () => {
      Swal.showLoading();
    },
  });

  // Set timeout untuk force close
  const timeoutId = setTimeout(() => {
    if (Swal.isVisible()) {
      Swal.close();
      console.log('Force close loading dialog after timeout');
    }
  }, 1000); // 5 detik timeout

  // Reload DataTable
  try {
    tableSiswaKelasAjaran.ajax.reload(
      function (json) {
        // Clear timeout karena data sudah selesai dimuat
        clearTimeout(timeoutId);

        // Tutup dialog loading
        if (Swal.isVisible()) {
          Swal.close();
        }

        // Reset checkbox
        $('.select_all, .check').prop('checked', false);

        console.log('Data reload completed successfully');
      },
      false // Don't reset page
    );
  } catch (error) {
    clearTimeout(timeoutId);
    console.error('Error in reload_ajax:', error);
    Swal.close();
    Swal.fire('Error', 'Gagal memuat ulang data', 'error');
  }
}

function bulk_delete() {
  var $checkedBoxes = $('#table_siswa_kelas_ajaran .check:checked');
  console.log('Jumlah checkbox terpilih:', $checkedBoxes.length);

  if ($checkedBoxes.length === 0) {
    Swal.fire('Gagal', 'Tidak ada data yang dipilih untuk dihapus.', 'error');
    return;
  }

  var ids = [];
  $checkedBoxes.each(function () {
    ids.push($(this).val());
  });
  console.log('ID yang akan dihapus:', ids);

  Swal.fire({
    title: 'Anda yakin?',
    text:
      'Data penempatan Siswa yang dipilih (' +
      $checkedBoxes.length +
      ' data) akan dihapus.',
    type: 'warning', // Ganti 'type' menjadi 'icon'
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
  }).then((result) => {
    if (result.value) {
      // Ganti result.isConfirmed menjadi result.value
      console.log('User mengkonfirmasi penghapusan');
      console.log('Mengirim request ke:', base_url + 'siswakelas/delete');
      console.log('Data yang dikirim:', { checked: ids });

      $.ajax({
        url: base_url + 'siswakelas/delete',
        type: 'POST',
        data: { checked: ids },
        dataType: 'json',
        beforeSend: function () {
          console.log('Memulai request AJAX');
          Swal.fire({
            // title: 'Memproses...',
            // text: 'Mohon tunggu sebentar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
              Swal.showLoading();
            },
          });
        },
        success: function (response) {
          console.log('Response server:', response);
          if (response.status) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              type: 'success',
              text: response.message,
              showConfirmButton: true,
            }).then(() => {
              tableSiswaKelasAjaran.ajax.reload(null, false);
              $('.select_all').prop('checked', false);
            });
          } else {
            Swal.fire(
              'Gagal!',
              response.message || 'Terjadi kesalahan',
              'error'
            );
          }
        },
        error: function (xhr, status, error) {
          console.error('AJAX Error:', {
            xhr: xhr.responseText,
            status: status,
            error: error,
          });
          Swal.fire('Error!', 'Gagal menghapus data: ' + error, 'error');
        },
      });
    }
  });
}

// Tambahkan event handler untuk select all checkbox
$('.select_all').on('click', function () {
  $('.check').prop('checked', this.checked);
});
