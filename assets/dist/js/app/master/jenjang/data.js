var table;

$(document).ready(function () {
  // Pastikan fungsi ajaxcsrf() sudah terdefinisi dan berfungsi jika Anda menggunakannya
  // Jika tidak, Anda bisa menghapus pemanggilan ajaxcsrf()
  if (typeof ajaxcsrf === 'function') {
    ajaxcsrf();
  }

  table = $('#jenjang_table').DataTable({
    initComplete: function () {
      var api = this.api();
      $('#jenjang_table_filter input')
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
      { extend: 'copy', exportOptions: { columns: [1, 2] } }, // Sesuaikan kolom
      { extend: 'print', exportOptions: { columns: [1, 2] } },
      { extend: 'excel', exportOptions: { columns: [1, 2] } },
      { extend: 'pdf', exportOptions: { columns: [1, 2] } },
    ],
    oLanguage: {
      sProcessing: 'loading...',
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'jenjang/data', // URL ke controller Jenjang method data
      type: 'POST',
      // Jika menggunakan ajaxcsrf(), tambahkan data CSRF
      // data: function(d) {
      //     return $.extend({}, d, {
      //         "<?= $this->security->get_csrf_token_name(); ?>": "<?= $this->security->get_csrf_hash(); ?>"
      //     });
      // }
    },
    columns: [
      {
        data: 'id_jenjang', // Atau null jika nomor urut dari rowCallback
        orderable: false,
        searchable: false,
        width: '3%',
      },
      { data: 'nama_jenjang' },
      { data: 'deskripsi' },
      {
        data: 'action',
        orderable: false,
        searchable: false,
        className: 'text-center',
      },
      {
        data: 'bulk_select',
        orderable: false,
        searchable: false,
        className: 'text-center',
        width: '3%',
      },
    ],
    order: [[1, 'asc']], // Order by nama_jenjang
    rowId: function (a) {
      return a.id_jenjang; // Pastikan ada id_jenjang di data JSON
    },
    rowCallback: function (row, data, iDisplayIndex) {
      var info = this.fnPagingInfo();
      var page = info.iPage;
      var length = info.iLength;
      var index = page * length + (iDisplayIndex + 1);
      $('td:eq(0)', row).html(index);
    },
  });

  table
    .buttons()
    .container()
    .appendTo('#jenjang_table_wrapper .col-md-6:eq(0)');

  // Handle form submission untuk Add dan Edit Jenjang
  // Pastikan form di add.php dan edit.php memiliki id="formjenjang"
  $('#formjenjang').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var btn = $(this).find('button[type="submit"]');
    var oldText = btn.html();
    btn
      .html('<i class="fa fa-spin fa-spinner"></i> Menyimpan...')
      .attr('disabled', true);
    $('.help-block').empty().removeClass('text-danger'); // Clear previous errors

    var form = this;
    $.ajax({
      url: $(form).attr('action'),
      type: 'POST',
      data: new FormData(form), // Menggunakan FormData untuk menghandle file jika ada
      processData: false,
      contentType: false,
      cache: false,
      dataType: 'json',
      success: function (data) {
        btn.html(oldText).attr('disabled', false);
        if (data.status) {
          Swal.fire({
            title: 'Berhasil',
            text: data.message,
            type: 'success', // 'icon' di SweetAlert2 versi baru
            timer: 2000,
            showConfirmButton: false,
          }).then(() => {
            // Cek jika kita di halaman add/edit, lalu redirect
            // Jika tidak, reload tabel (misal jika form ada di modal)
            if (
              window.location.pathname.includes('/add') ||
              window.location.pathname.includes('/edit')
            ) {
              window.location.href = base_url + 'jenjang';
            } else {
              reload_ajax();
            }
          });
        } else {
          if (data.errors) {
            $.each(data.errors, function (key, val) {
              if (val) {
                // Hanya tampilkan jika ada pesan error
                $('#error_' + key)
                  .html(val)
                  .addClass('text-danger');
                $('#' + key)
                  .closest('.form-group')
                  .addClass('has-error'); // Bootstrap class
              } else {
                $('#error_' + key)
                  .empty()
                  .removeClass('text-danger');
                $('#' + key)
                  .closest('.form-group')
                  .removeClass('has-error');
              }
            });
          } else {
            Swal.fire({
              title: 'Gagal',
              text: data.message || 'Terjadi kesalahan, periksa inputan Anda.',
              type: 'error', // 'icon'
            });
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        btn.html(oldText).attr('disabled', false);
        Swal.fire({
          title: 'Error',
          text: 'Terjadi kesalahan pada server: ' + textStatus,
          type: 'error', // 'icon'
        });
      },
    });
  });

  // Handle Select All Checkbox
  $('#select_all').on('click', function () {
    if (this.checked) {
      $('.check').each(function () {
        this.checked = true;
      });
    } else {
      $('.check').each(function () {
        this.checked = false;
      });
    }
  });

  // Handle individual checkbox click
  $('#jenjang_table tbody').on('click', 'tr .check', function () {
    var check = $('#jenjang_table tbody tr .check').length;
    var checked = $('#jenjang_table tbody tr .check:checked').length;
    if (check === checked) {
      $('#select_all').prop('checked', true);
    } else {
      $('#select_all').prop('checked', false);
    }
  });

  // Handle Bulk Delete (dipanggil oleh fungsi global bulk_delete())
  // Fungsi bulk_delete() ada di bawah
  $('#bulk').on('submit', function (e) {
    // Form bulk delete
    e.preventDefault();
    e.stopImmediatePropagation();

    $.ajax({
      url: $(this).attr('action'),
      data: $(this).serialize(),
      type: 'POST',
      dataType: 'json',
      success: function (data) {
        if (data.status) {
          Swal.fire({ title: 'Berhasil', text: data.message, type: 'success' }); // 'icon'
        } else {
          Swal.fire({ title: 'Gagal', text: data.message, type: 'error' }); // 'icon'
        }
        reload_ajax();
        $('#select_all').prop('checked', false);
        $('.check').prop('checked', false); // Uncheck all after operation
      },
      error: function (jqXHR, textStatus, errorThrown) {
        Swal.fire({
          title: 'Error',
          text: 'Terjadi kesalahan pada server.',
          type: 'error',
        }); // 'icon'
      },
    });
  });
}); // End document.ready

// Fungsi bulk_delete() global
function bulk_delete() {
  if ($('#jenjang_table tbody tr .check:checked').length == 0) {
    Swal.fire({
      title: 'Gagal',
      text: 'Tidak ada data jenjang yang dipilih',
      type: 'error',
    }); // 'icon'
  } else {
    $('#bulk').attr('action', base_url + 'jenjang/delete'); // Target ke controller Jenjang
    Swal.fire({
      title: 'Anda yakin?',
      text: 'Data jenjang yang dipilih akan dihapus!',
      type: 'warning', // 'icon'
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, Hapus!',
    }).then((result) => {
      if (result.value) {
        // 'isConfirmed' di SweetAlert2 v9+
        $('#bulk').submit();
      }
    });
  }
}

// Fungsi reload_ajax() global
function reload_ajax() {
  if (table) {
    table.ajax.reload(null, false); // false untuk tidak reset pagination
  }
}
