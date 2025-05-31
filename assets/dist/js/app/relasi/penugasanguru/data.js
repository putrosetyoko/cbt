var tablePenugasanGuru;

$(document).ready(function () {
  if (typeof ajaxcsrf === 'function') {
    ajaxcsrf();
  }

  tablePenugasanGuru = $('#table_penugasan_guru').DataTable({
    initComplete: function () {
      var api = this.api();
      $('#table_penugasan_guru_filter input')
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
      { extend: 'copy', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } }, // No, TA, Guru, Mapel, Jenjang, Kelas
      { extend: 'print', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
      { extend: 'excel', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
      { extend: 'pdf', exportOptions: { columns: [0, 1, 2, 3, 4, 5] } },
    ],
    oLanguage: {
      sProcessing: 'Memuat...',
      sSearch: 'Cari:',
      sLengthMenu: 'Tampilkan _MENU_ entri',
      sInfo: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
      sInfoEmpty: 'Menampilkan 0 dari 0 entri',
      sInfoFiltered: '(disaring dari _MAX_ total entri)',
      oPaginate: { sFirst: 'Awal', sLast: 'Akhir', sNext: '>', sPrevious: '<' },
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'penugasanguru/data',
      type: 'POST',
      data: function (d) {
        d.filter_tahun_ajaran = $('#filter_tahun_ajaran').val();
        d.filter_guru = $('#filter_guru').val();
        d.filter_mapel = $('#filter_mapel').val();
        d.filter_kelas = $('#filter_kelas').val();
        d.t = new Date().getTime();
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          'DataTables AJAX error (penugasanguru/data):',
          textStatus,
          errorThrown,
          jqXHR.responseText
        );
        Swal.fire(
          'Error DataTables',
          'Gagal memuat data penugasan guru: ' + textStatus,
          'error'
        );
      },
    },
    columns: [
      { data: 'id_gmka', orderable: false, searchable: false, width: '3%' },
      { data: 'nama_tahun_ajaran' },
      { data: 'nama_guru' },
      { data: 'nama_mapel' },
      { data: 'nama_jenjang', defaultContent: '-' },
      { data: 'nama_kelas' },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center',
        width: '80px',
      },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center',
        width: '30px',
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
        targets: 6, // Kolom Aksi
        data: 'id_gmka',
        render: function (data, type, row, meta) {
          return `<a href="${base_url}penugasanguru/edit/${data}" class="btn btn-xs btn-warning" title="Edit Penugasan"><i class="fa fa-pencil"></i> Edit</a>`;
        },
      },
      {
        targets: 7, // Kolom Checkbox
        data: 'id_gmka',
        render: function (data, type, row, meta) {
          return `<input name="checked[]" class="check" value="${data}" type="checkbox">`;
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
      return 'row_gmka_' + a.id_gmka;
    },
  });

  if (tablePenugasanGuru && typeof tablePenugasanGuru.buttons === 'function') {
    tablePenugasanGuru
      .buttons()
      .container()
      .appendTo('#table_penugasan_guru_wrapper .col-md-6:eq(0)');
  }

  $('#filter_tahun_ajaran, #filter_guru, #filter_mapel, #filter_kelas').on(
    'change',
    function () {
      reload_ajax();
    }
  );

  $(document).on('click', '.select_all', function () {
    /* ... logika select all ... */
  });
  $('#table_penugasan_guru tbody').on('click', 'tr .check', function () {
    /* ... logika individual check ... */
  });

  // Handler untuk form bulk delete (ID Form: #bulkDeleteFormPenugasanGuru)
  $('#bulkDeleteFormPenugasanGuru').on('submit', function (e) {
    /* ... (adaptasi dari siswakelas/data.js, ganti URL dan pesan) ... */
  });
});

function reload_ajax() {
  if (tablePenugasanGuru && typeof tablePenugasanGuru.ajax !== 'undefined') {
    tablePenugasanGuru.ajax.reload(null, false);
  } else {
    console.error("Instance DataTables 'tablePenugasanGuru' tidak valid.");
  }
}

function bulk_delete() {
  var $form = $('#bulkDeleteFormPenugasanGuru');
  var $checkedBoxes = $('#table_penugasan_guru .check:checked');
  if ($checkedBoxes.length === 0) {
    /* ... Swal error ... */ return;
  }

  $form.attr('action', base_url + 'penugasanguru/delete'); // Pastikan action benar
  Swal.fire({
    /* ... konfirmasi ... */
  }).then((result) => {
    if (result.isConfirmed) {
      // Langsung AJAX, bukan $form.submit() jika handler di atas sudah ada
      var ids_to_delete = $checkedBoxes
        .map(function () {
          return $(this).val();
        })
        .get();
      var postData = { checked: ids_to_delete };
      $.ajax({
        url: $form.attr('action'),
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function (response) {
          /* ... Swal notifikasi & reload ... */
        },
        error: function (jqXHR, textStatus, errorThrown) {
          /* ... Swal error ... */
        },
      });
    }
  });
}
