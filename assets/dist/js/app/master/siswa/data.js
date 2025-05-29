var table;

$(document).ready(function () {
  ajaxcsrf();

  table = $('#siswa').DataTable({
    initComplete: function () {
      var api = this.api();
      $('#siswa_filter input')
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
        exportOptions: { columns: [1, 2, 3, 4, 5] },
      },
      {
        extend: 'print',
        exportOptions: { columns: [1, 2, 3, 4, 5] },
      },
      {
        extend: 'excel',
        exportOptions: { columns: [1, 2, 3, 4, 5] },
      },
      {
        extend: 'pdf',
        exportOptions: { columns: [1, 2, 3, 4, 5] },
      },
    ],
    oLanguage: {
      sProcessing: 'loading...',
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'siswa/data', // Pastikan ini mengarah ke controller Siswa/data
      type: 'POST',
      // data: csrf // Jika csrf diaktifkan, pastikan ini tidak dikomentari
    },
    columns: [
      {
        data: 'id_siswa',
        orderable: false,
        searchable: false,
      },
      { data: 'nisn' },
      { data: 'nama' },
      { data: 'jenis_kelamin' },
      { data: 'email' },
    ],
    columnDefs: [
      {
        targets: 5,
        data: {
          id_siswa: 'id_siswa',
          ada: 'ada',
        },
        render: function (data, type, row, meta) {
          let btn;
          if (data.ada > 0) {
            btn = '';
          } else {
            btn = `<button data-id="${data.id_siswa}" type="button" class="btn btn-xs btn-primary btn-aktif">
                            <i class="fa fa-user-plus"></i> Aktif
                        </button>`;
          }
          return `<div class="text-center">
                            <a class="btn btn-xs btn-warning" href="${base_url}siswa/edit/${data.id_siswa}">
                                <i class="fa fa-pencil"></i> Edit
                            </a>
                            ${btn}
                        </div>`;
        },
      },
      {
        targets: 6,
        data: 'id_siswa',
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

  table.buttons().container().appendTo('#siswa_wrapper .col-md-6:eq(0)');

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

  $('#siswa tbody').on('click', 'tr .check', function () {
    var check = $('#siswa tbody tr .check').length;
    var checked = $('#siswa tbody tr .check:checked').length;
    if (check === checked) {
      $('.select_all').prop('checked', true);
    } else {
      $('.select_all').prop('checked', false);
    }
  });

  $('#bulk').on('submit', function (e) {
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
            text: respon.total + ' data berhasil dihapus',
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
          text: 'Terjadi kesalahan saat menghapus data.',
          type: 'error',
        });
      },
    });
  });

  $('#siswa').on('click', '.btn-aktif', function () {
    let id = $(this).data('id');

    $.ajax({
      url: base_url + 'siswa/create_user',
      data: 'id=' + id,
      type: 'GET',
      success: function (response) {
        if (response.msg) {
          var title = response.status ? 'Berhasil' : 'Gagal';
          var type = response.status ? 'success' : 'error';
          Swal({
            title: title,
            text: response.msg,
            type: type,
          });
        }
        reload_ajax();
      },
      error: function () {
        Swal({
          title: 'Gagal',
          text: 'Terjadi kesalahan saat mengaktifkan user.',
          type: 'error',
        });
      },
    });
  });
});

function bulk_delete() {
  if ($('#siswa tbody tr .check:checked').length == 0) {
    Swal({
      title: 'Gagal',
      text: 'Tidak ada data yang dipilih',
      type: 'error',
    });
  } else {
    Swal({
      title: 'Anda yakin?',
      text: 'Data akan dihapus!',
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

function bulk_activate() {
  var checked_ids = [];
  $('#siswa tbody tr .check:checked').each(function () {
    checked_ids.push($(this).val());
  });

  if (checked_ids.length === 0) {
    Swal({
      title: 'Gagal',
      text: 'Tidak ada data siswa yang dipilih untuk diaktifkan!',
      type: 'error',
    });
    return;
  }

  Swal({
    title: 'Anda yakin?',
    text: 'Akun siswa yang dipilih akan diaktifkan!',
    type: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Aktifkan!',
  }).then((result) => {
    if (result.value) {
      $.ajax({
        url: base_url + 'siswa/bulk_create_user',
        type: 'POST',
        data: {
          ids: checked_ids,
          // csrf_token: csrf // Aktifkan jika CSRF diaktifkan
        },
        success: function (respon) {
          if (respon.status) {
            Swal({
              title: 'Berhasil',
              text:
                respon.total_success +
                ' dari ' +
                respon.total_processed +
                ' akun siswa berhasil diaktifkan.',
              type: 'success',
            });
          } else {
            Swal({
              title: 'Gagal',
              text:
                respon.msg || 'Terjadi kesalahan saat mengaktifkan akun siswa.',
              type: 'error',
            });
          }
          reload_ajax();
          $('.select_all').prop('checked', false);
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error('AJAX Error:', textStatus, errorThrown);
          console.error('Response Text:', jqXHR.responseText);
          Swal({
            title: 'Gagal',
            text: 'Terjadi kesalahan jaringan atau server saat mengaktifkan akun siswa. Cek console log.',
            type: 'error',
          });
        },
      });
    }
  });
}
