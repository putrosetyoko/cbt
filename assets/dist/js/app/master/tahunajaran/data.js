var table;

$(document).ready(function () {
  if (typeof ajaxcsrf === 'function') {
    ajaxcsrf();
  }

  table = $('#tahunajaran_table').DataTable({
    initComplete: function () {
      var api = this.api();
      $('#tahunajaran_table_filter input')
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
      { extend: 'copy', exportOptions: { columns: [1, 2, 3, 4, 5] } }, // Nama, Sem, Mulai, Selesai, Status
      { extend: 'print', exportOptions: { columns: [1, 2, 3, 4, 5] } },
      { extend: 'excel', exportOptions: { columns: [1, 2, 3, 4, 5] } },
      { extend: 'pdf', exportOptions: { columns: [1, 2, 3, 4, 5] } },
    ],
    oLanguage: {
      sProcessing: 'loading...',
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'tahunajaran/data',
      type: 'POST',
      // data: function(d) { d.t = new Date().getTime(); } // Opsional: Cache buster
    },
    columns: [
      {
        // 0: Kolom No.
        data: 'id_tahun_ajaran', // Bisa null jika diisi rowCallback
        orderable: false,
        searchable: false,
        width: '3%',
      },
      { data: 'nama_tahun_ajaran' }, // 1
      { data: 'semester' }, // 2
      { data: 'tgl_mulai' }, // 3
      { data: 'tgl_selesai' }, // 4
      {
        // 5: Kolom Status
        data: 'status',
        className: 'text-center',
        render: function (data, type, row) {
          if (data === 'aktif') {
            return '<span class="badge bg-green">Aktif</span>';
          } else {
            return '<span class="badge bg-red">Tidak Aktif</span>';
          }
        },
      },
      {
        // 6: Kolom Aksi (akan dirender oleh columnDefs)
        data: null, // Tidak mengambil data langsung dari field 'action' lagi
        orderable: false,
        searchable: false,
        className: 'text-center',
      },
      {
        // 7: Kolom bulk_select
        data: 'bulk_select', // Jika server masih mengirim ini, atau render via columnDefs
        orderable: false,
        searchable: false,
        className: 'text-center',
        width: '3%',
        // Jika 'bulk_select' tidak lagi dikirim server, render di columnDefs:
        // render: function(data, type, row){
        //    return '<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="'+row.id_tahun_ajaran+'"/></div>';
        // }
      },
    ],
    columnDefs: [
      {
        targets: 6, // Target kolom Aksi (indeks ke-6)
        data: null, // Tidak mengambil data dari satu field spesifik
        render: function (data, type, row, meta) {
          // `row` berisi seluruh data untuk baris saat ini, termasuk `row.status` dan `row.id_tahun_ajaran`
          let tombolEdit = `<a href="${base_url}tahunajaran/edit/${row.id_tahun_ajaran}" class="btn btn-xs btn-warning" title="Edit"><i class="fa fa-pencil"></i> Edit</a>`;
          let tombolSetAktif = '';

          // Hanya tampilkan tombol "Set Aktif" jika status BUKAN 'aktif'
          if (row.status !== 'aktif') {
            tombolSetAktif = ` <button type="button" class="btn btn-xs btn-success btn-set-aktif" data-id="${row.id_tahun_ajaran}" data-status="${row.status}" title="Set Aktif"><i class="fa fa-check-circle"></i> Aktif</button>`;
          }

          return `<div class="text-center">${tombolSetAktif} ${tombolEdit}</div>`;
        },
      },
      {
        // Jika 'bulk_select' tidak lagi dikirim server secara pre-rendered
        targets: 6,
        data: 'id_tahun_ajaran',
        render: function (data, type, row, meta) {
          return `<div class="text-center"><input type="checkbox" class="check" name="checked[]" value="${data}</div>`;
        },
      },
    ],
    order: [[1, 'asc']],
    rowId: function (a) {
      // return a; // Ini salah, harusnya ID unik
      return a.id_tahun_ajaran; // Pastikan id_tahun_ajaran ada di data JSON dari server
    },
    rowCallback: function (row, data, iDisplayIndex) {
      var info = this.fnPagingInfo();
      if (info) {
        // Pastikan info ada
        var page = info.iPage;
        var length = info.iLength;
        var index = page * length + (iDisplayIndex + 1);
        $('td:eq(0)', row).html(index);
      } else {
        $('td:eq(0)', row).html(iDisplayIndex + 1); // Fallback
      }
    },
  });

  table
    .buttons()
    .container()
    .appendTo('#tahunajaran_table_wrapper .col-md-6:eq(0)');

  // Handle form submission untuk Add dan Edit (AJAX)
  $('#formtahunajaran').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var btn = $(this).find('button[type="submit"]');
    var oldText = btn.html();
    btn
      .html('<i class="fa fa-spin fa-spinner"></i> Menyimpan...')
      .attr('disabled', true);
    $('.help-block').empty(); // Clear previous errors

    var form = this;
    $.ajax({
      url: $(form).attr('action'),
      type: 'POST',
      data: new FormData(form),
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
            type: 'success',
            timer: 2000,
            showConfirmButton: false,
          }).then((result) => {
            window.location.href = base_url + 'tahunajaran';
          });
        } else {
          if (data.errors) {
            $.each(data.errors, function (key, val) {
              $('#error_' + key)
                .html(val)
                .addClass('text-danger');
              if (val === '') {
                $('#error_' + key).removeClass('text-danger');
              }
            });
          } else {
            Swal.fire({
              title: 'Gagal',
              text: data.message || 'Terjadi kesalahan, periksa inputan Anda.',
              type: 'error',
            });
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        btn.html(oldText).attr('disabled', false);
        Swal.fire({
          title: 'Error',
          text: 'Terjadi kesalahan pada server: ' + textStatus,
          type: 'error',
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
  $('#tahunajaran_table tbody').on('click', 'tr .check', function () {
    var check = $('#tahunajaran_table tbody tr .check').length;
    var checked = $('#tahunajaran_table tbody tr .check:checked').length;
    if (check === checked) {
      $('#select_all').prop('checked', true);
    } else {
      $('#select_all').prop('checked', false);
    }
  });

  // Handle Bulk Delete
  $('#bulk').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    $.ajax({
      url: $(this).attr('action'),
      data: $(this).serialize(),
      type: 'POST',
      dataType: 'json',
      success: function (data) {
        if (data.status) {
          Swal.fire({
            title: 'Berhasil',
            text: data.message,
            type: 'success',
          });
        } else {
          Swal.fire({
            title: 'Gagal',
            text: data.message,
            type: 'error',
          });
        }
        reload_ajax();
        $('#select_all').prop('checked', false);
      },
      error: function (jqXHR, textStatus, errorThrown) {
        Swal.fire({
          title: 'Error',
          text: 'Terjadi kesalahan pada server.',
          type: 'error',
        });
      },
    });
  });

  // Handle Set Status Aktif
  $('#tahunajaran_table').on('click', '.btn-set-aktif', function () {
    let id = $(this).data('id');
    let current_status = $(this).data('status');

    if (current_status === 'aktif') {
      Swal.fire('', 'Tahun ajaran ini sudah aktif.', 'info');
      return;
    }

    Swal.fire({
      title: 'Anda yakin?',
      text: 'Hanya satu tahun ajaran yang bisa aktif dalam satu waktu. Mengaktifkan ini akan menonaktifkan yang lain (jika ada).',
      type: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, Aktifkan!',
    }).then((result) => {
      if (result.value) {
        $.ajax({
          url: base_url + 'tahunajaran/set_status_aktif/' + id,
          type: 'POST', // atau GET, sesuaikan dengan route
          dataType: 'json',
          success: function (data) {
            if (data.status) {
              Swal.fire(
                'Berhasil Aktifkan Tahun Ajaran!',
                data.message,
                'success'
              );
              reload_ajax();
            } else {
              Swal.fire('Gagal!', data.message, 'error');
            }
          },
          error: function (jqXHR, textStatus, errorThrown) {
            Swal.fire(
              'Error!',
              'Gagal mengubah status. Kesalahan server.',
              'error'
            );
          },
        });
      }
    });
  });
}); // End document.ready

function bulk_delete() {
  if ($('#tahunajaran_table tbody tr .check:checked').length == 0) {
    Swal.fire({
      title: 'Gagal',
      text: 'Tidak ada data Tahun Ajaran yang dipilih',
      type: 'error',
    });
  } else {
    $('#bulk').attr('action', base_url + 'tahunajaran/delete');
    Swal.fire({
      title: 'Anda yakin?',
      text: 'Data Tahun Ajaran akan dihapus!',
      type: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, Hapus!',
    }).then((result) => {
      if (result.value) {
        $('#bulk').submit();
      }
    });
  }
}
// Fungsi reload_ajax() jika belum ada di file js global Anda
function reload_ajax() {
  if (table) {
    // Cek apakah table sudah diinisialisasi
    table.ajax.reload(null, false);
  }
}
