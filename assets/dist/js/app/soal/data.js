var tableSoal;

// Tambahkan variable global untuk CSRF
var csrf_token = $('input[name="csrf_test_name"]').val();

$(document).ready(function () {
  if (typeof ajaxcsrf === 'function') {
    ajaxcsrf();
  }

  tableSoal = $('#table_soal').DataTable({
    initComplete: function () {
      var api = this.api();
      $('#table_soal_filter input') // Ganti jika ID filter global Anda berbeda
        .off('.DT')
        .on('keyup.DT', function (e) {
          api.search(this.value).draw();
        });
    },
    // dom:
    //   "<'row'<'col-sm-12 col-md-3'l><'col-sm-12 col-md-6 text-center'B><'col-sm-12 col-md-3'f>>" +
    //   "<'row'<'col-sm-12'tr>>" +
    //   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    // buttons: [
    //   // No, Mapel, Jenjang, Cuplikan, Pembuat, Bobot, Kunci, Tgl Dibuat
    //   { extend: 'copy', exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8] } },
    //   { extend: 'print', exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8] } },
    //   { extend: 'excel', exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8] } },
    //   { extend: 'pdf', exportOptions: { columns: [1, 2, 3, 4, 5, 6, 7, 8] } },
    // ],
    // oLanguage: {
    //   /* ... bahasa Indonesia ... */
    // },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'soal/data',
      type: 'POST',
      data: function (d) {
        d.filter_mapel = $('#filter_mapel_soal').val();
        d.filter_jenjang = $('#filter_jenjang_soal').val();
        d.filter_guru_pembuat = $('#filter_guru_pembuat_soal').val(); // Untuk Admin
        d.t = new Date().getTime(); // Cache busting
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          'DataTables AJAX error (soal/data):',
          textStatus,
          errorThrown,
          jqXHR.responseText
        );
        Swal.fire(
          'Error DataTables',
          'Gagal memuat data soal: ' + textStatus,
          'error'
        );
      },
    },
    columns: [
      {
        // Kolom nomor urut
        data: null,
        orderable: false,
        searchable: false,
        width: '3%',
        className: 'text-center',
        render: function (data, type, row, meta) {
          return meta.row + meta.settings._iDisplayStart + 1;
        },
      },
      { data: 'nama_mapel', defaultContent: '-' },
      { data: 'nama_jenjang', defaultContent: '-' },
      {
        data: 'cuplikan_soal',
        orderable: false,
        searchable: true,
        width: '40%',
        render: function (data) {
          return data ? data + '...' : '';
        },
      },
      { data: 'pembuat_soal' },
      // { data: 'bobot', className: 'text-center' },
      // { data: 'jawaban', className: 'text-center' },
      // { data: 'created_on_formatted', className: 'text-center' },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center',
      },
      {
        // Checkbox di kolom terakhir
        data: 'id_soal',
        orderable: false,
        searchable: false,
        width: '3%',
        className: 'text-center',
        render: function (data, type, row) {
          if (
            typeof canDeleteSoal !== 'undefined' &&
            canDeleteSoal(row.guru_id, row.mapel_id)
          ) {
            return `<input type="checkbox" class="check" value="${data}" />`;
          }
          return '';
        },
      },
    ],
    columnDefs: [
      // {
      //   targets: 4, // Index kolom created_on_formatted
      //   render: function (data, type, row) {
      //     if (type === 'display') {
      //       // Split tanggal dan waktu
      //       const [datePart] = row.created_on_formatted.split(' ');
      //       // Split tanggal
      //       const [day, month, year] = datePart.split('-');

      //       // Buat objek Date dengan format yang benar (YYYY-MM-DD)
      //       const date = new Date(`${year}-${month}-${day}`);

      //       const options = {
      //         day: 'numeric',
      //         month: 'long',
      //         year: 'numeric',
      //         timeZone: 'Asia/Jakarta',
      //       };
      //       return date.toLocaleDateString('id-ID', options);
      //     }
      //     return data;
      //   },
      // },
      {
        targets: 5, // Kolom aksi
        data: 'id_soal',
        render: function (data, type, row, meta) {
          let detailBtn = `<a href="${base_url}soal/detail/${row.id_soal}" class="btn btn-xs bg-blue" title="Lihat Detail"><i class="fa fa-eye"></i> Detail</a>`;
          let editBtn = '';

          if (
            typeof canEditSoal !== 'undefined' &&
            canEditSoal(row.guru_id, row.mapel_id)
          ) {
            editBtn = ` <a href="${base_url}soal/edit/${row.id_soal}" class="btn btn-xs btn-warning" title="Edit Soal"><i class="fa fa-pencil"></i> Edit</a>`;
          }

          return `<div class="text-center">${detailBtn}${editBtn}</div>`;
        },
      },
    ],
    order: [[4, 'desc']], // Adjust order column index since we moved columns
    rowId: function (a) {
      return 'soal_' + a.id_soal;
    },
  });

  if (tableSoal && typeof tableSoal.buttons === 'function') {
    tableSoal
      .buttons()
      .container()
      .appendTo('#table_soal_wrapper .col-md-6:eq(0)');
  }

  // Event listener untuk filter
  $(
    'select#filter_mapel_soal, select#filter_jenjang_soal, select#filter_guru_pembuat_soal'
  ).on('change', function () {
    reload_ajax_soal();
  });

  // Handler untuk checkbox "select_all_soal"
  $(document).on('click', '.select_all_soal', function () {
    let isChecked = this.checked;
    $('#table_soal .check').prop('checked', isChecked);
    $(this).prop('checked', isChecked);
  });

  $('#table_soal tbody').on('click', 'tr .check', function () {
    var totalChecks = $('#table_soal .check').length;
    var checkedChecks = $('#table_soal .check:checked').length;
    $('.select_all_soal').prop(
      'checked',
      totalChecks === checkedChecks && totalChecks > 0
    );
  });

  // Handler untuk form bulk delete (ID Form: #bulkDeleteFormSoal)
  $('#bulkDeleteFormSoal').on('submit', function (e) {
    /* ... (Adaptasi dari bulk delete sebelumnya, target soal/delete) ... */
  });

  // Handler untuk select all checkbox
  $('#select_all_soal').on('click', function () {
    $('.check').prop('checked', this.checked);
  });

  // Handler untuk checkbox individual
  $('#table_soal tbody').on('click', '.check', function () {
    if (!this.checked) {
      $('#select_all_soal').prop('checked', false);
    } else {
      let allChecked = true;
      $('.check').each(function () {
        if (!this.checked) {
          allChecked = false;
          return false;
        }
      });
      $('#select_all_soal').prop('checked', allChecked);
    }
  });

  // Handler untuk bulk delete
  $('#bulk_delete').on('click', function () {
    bulk_delete();
  });
});

function reload_ajax_soal() {
  if (tableSoal) tableSoal.ajax.reload(null, false);
}

function bulk_delete() {
  const $checkedBoxes = $('#table_soal .check:checked');

  if ($checkedBoxes.length === 0) {
    Swal.fire({
      title: 'Perhatian',
      text: 'Tidak ada soal yang dipilih untuk dihapus',
      type: 'warning',
    });
    return;
  }

  Swal.fire({
    title: 'Konfirmasi',
    text: `Akan menghapus ${$checkedBoxes.length} soal yang dipilih. Yakin?`,
    type: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
  }).then((result) => {
    if (result.value) {
      // Kumpulkan ID soal yang akan dihapus
      let selected = [];
      $checkedBoxes.each(function () {
        selected.push($(this).val());
      });

      // Kirim request AJAX untuk delete
      $.ajax({
        url: base_url + 'soal/delete',
        type: 'POST',
        data: {
          checked: selected,
          csrf_test_name: csrf_token,
        },
        dataType: 'json',
        success: function (response) {
          if (response.status) {
            Swal.fire({
              title: 'Berhasil!',
              text: response.message,
              type: 'success',
              showConfirmButton: false,
              timer: 1500,
            }).then(() => {
              // Reload table dan reset checkbox
              tableSoal.ajax.reload(null, false);
              $('#select_all_soal').prop('checked', false);
            });
          } else {
            Swal.fire({
              title: 'Gagal!',
              text: response.message,
              type: 'error',
            });
          }
        },
        error: function (xhr, status, error) {
          console.error('AJAX Error:', xhr.responseText);
          Swal.fire({
            title: 'Error!',
            text: 'Terjadi kesalahan pada server',
            type: 'error',
          });
        },
      });
    }
  });
}

// Anda perlu mendefinisikan fungsi JS global ini, dan mengisinya dengan data dari PHP
// var USER_ID_LOGIN = <?= $this->session->userdata('user_id') ?>;
// var IS_ADMIN_JS = <?= $this->ion_auth->is_admin() ? 'true' : 'false' ?>;
// var PJ_MAPEL_ID_JS = <?= isset($pj_mapel_data->id_mapel) ? $pj_mapel_data->id_mapel : 'null' ?>;
// var GURU_ID_LOGIN_JS = <?= isset($guru_data->id_guru) ? $guru_data->id_guru : 'null' ?>;

function canEditSoal(id_guru_soal, mapel_id_soal) {
  if (IS_ADMIN_JS) return true;
  if (GURU_ID_LOGIN_JS && PJ_MAPEL_ID_JS) {
    return id_guru_soal == GURU_ID_LOGIN_JS && mapel_id_soal == PJ_MAPEL_ID_JS;
  }
  return false;
}
function canDeleteSoal(id_guru_soal, mapel_id_soal) {
  // Sama dengan canEditSoal untuk saat ini
  if (IS_ADMIN_JS) return true;
  if (GURU_ID_LOGIN_JS && PJ_MAPEL_ID_JS) {
    return id_guru_soal == GURU_ID_LOGIN_JS && mapel_id_soal == PJ_MAPEL_ID_JS;
  }
  return false;
}
