var save_label;
var table;

$(document).ready(function () {
  ajaxcsrf();

  table = $('#mapel').DataTable({
    initComplete: function () {
      var api = this.api();
      $('#mapel_filter input')
        .off('.DT')
        .on('keyup.DT', function (e) {
          api.search(this.value).draw();
        });
    },
    dom:
      "<'row'<'col-sm-3'l><'col-sm-6 text-center'B><'col-sm-3'f>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    buttons: [
      {
        extend: 'copy',
        exportOptions: { columns: [1] },
      },
      {
        extend: 'print',
        exportOptions: { columns: [1] },
      },
      {
        extend: 'excel',
        exportOptions: { columns: [1] },
      },
      {
        extend: 'pdf',
        exportOptions: { columns: [1] },
      },
    ],
    oLanguage: {
      sProcessing: 'loading...',
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'mapel/data',
      type: 'POST',
      //data: csrf
    },
    columns: [
      {
        data: 'id_mapel',
        orderable: false,
        searchable: false,
        width: '3%',
      },
      { data: 'nama_mapel' },
    ],
    columnDefs: [
      {
        targets: 2,
        data: 'id_mapel',
        render: function (data, type, row, meta) {
          return `<div class="text-center">
									<input name="checked[]" class="check" value="${data}" type="checkbox">
								</div>`;
        },
      },
    ],
    order: [[1, 'asc']],
    rowId: function (a) {
      return a;
    },
    rowCallback: function (row, data, iDisplayIndex) {
      var info = this.fnPagingInfo();
      var page = info.iPage;
      var length = info.iLength;
      var index = page * length + (iDisplayIndex + 1);
      $('td:eq(0)', row).html(index);
    },
  });

  table.buttons().container().appendTo('#mapel_wrapper .col-md-6:eq(0)');

  $('#myModal').on('shown.modal.bs', function () {
    $(':input[name="banyak"]').select();
  });

  $('.select_all').on('click', function () {
    if (this.checked) {
      $('.check').each(function () {
        this.checked = true;
        $('.select_all').prop('checked', true);
      });
    } else {
      $('.check').each(function () {
        this.checked = false;
        $('.select_all').prop('checked', false);
      });
    }
  });

  $('#mapel tbody').on('click', 'tr .check', function () {
    var check = $('#mapel tbody tr .check').length;
    var checked = $('#mapel tbody tr .check:checked').length;
    if (check === checked) {
      $('.select_all').prop('checked', true);
    } else {
      $('.select_all').prop('checked', false);
    }
  });

  $('#bulk').on('submit', function (e) {
    if ($(this).attr('action') == base_url + 'mapel/delete') {
      e.preventDefault();
      e.stopImmediatePropagation();

      $.ajax({
        url: $(this).attr('action'),
        data: $(this).serialize(),
        type: 'POST',
        success: function (respon) {
          if (respon.status) {
            Swal({
              title: 'Berhasil',
              text: respon.total + ' data Mata Pelajaran berhasil dihapus',
              type: 'success',
            });
          } else {
            Swal({
              title: 'Gagal',
              text: 'Tidak ada data yang dipilih',
              type: 'error',
            });
          }
          reload_ajax();
        },
        error: function () {
          Swal({
            title: 'Gagal',
            text: 'Ada data yang sedang digunakan',
            type: 'error',
          });
        },
      });
    }
  });
});

function bulk_delete() {
  if ($('#mapel tbody tr .check:checked').length == 0) {
    Swal({
      title: 'Gagal',
      text: 'Tidak ada data Mata Pelajaran yang dipilih',
      type: 'error',
    });
  } else {
    $('#bulk').attr('action', base_url + 'mapel/delete');
    Swal({
      title: 'Anda yakin?',
      text: 'Data Mata Pelajaran akan dihapus!',
      type: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Hapus!',
    }).then((result) => {
      if (result.value) {
        $('#bulk').submit();
      }
    });
  }
}

function bulk_edit() {
  if ($('#mapel tbody tr .check:checked').length == 0) {
    Swal({
      title: 'Gagal',
      text: 'Tidak ada data Mata Pelajaran yang dipilih',
      type: 'error',
    });
  } else {
    $('#bulk').attr('action', base_url + 'mapel/edit');
    $('#bulk').submit();
  }
}
