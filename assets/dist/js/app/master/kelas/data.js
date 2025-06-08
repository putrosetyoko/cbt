var save_label; // Variabel ini tampaknya tidak digunakan, bisa dipertimbangkan untuk dihapus jika benar tidak dipakai.
var table;

$(document).ready(function () {
  // Pastikan ajaxcsrf() sudah terdefinisi dan berfungsi jika Anda menggunakannya
  if (typeof ajaxcsrf === 'function') {
    ajaxcsrf();
  }

  table = $('#kelas').DataTable({
    initComplete: function () {
      var api = this.api();
      $('#kelas_filter input')
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
        exportOptions: { columns: [1] }, // Kolom: Kelas (1), Jenjang (2)
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
      url: base_url + 'kelas/data', // Pastikan controller kelas/data mengembalikan nama_jenjang
      type: 'POST',
      // data: csrf // Jika Anda menggunakan variabel csrf untuk token
    },
    columns: [
      {
        data: 'id_kelas', // Atau null jika penomoran dari rowCallback
        orderable: false,
        searchable: false,
        width: '3%', // Atur lebar jika perlu
      },
      {
        data: null,
        render: function (data, type, row) {
          return row.nama_jenjang + ' ' + row.nama_kelas;
        },
        searchable: true,
      },
      {
        data: 'bulk_select', // Pastikan controller mengirimkan data untuk ini
        orderable: false,
        searchable: false,
        className: 'text-center', // Tengahkan checkbox jika perlu
        // width: '5%' // Atur lebar jika perlu
      },
    ],
    order: [[1, 'asc']], // Order default berdasarkan nama_kelas
    rowId: function (a) {
      // Fungsi ini sepertinya salah, seharusnya mengembalikan nilai unik seperti a.id_kelas
      // return a; // Ini akan error jika a bukan string/number. Seharusnya return a.id_kelas;
      return a.id_kelas; // Diasumsikan id_kelas ada di data source
    },
    rowCallback: function (row, data, iDisplayIndex) {
      var info = this.fnPagingInfo();
      var page = info.iPage;
      var length = info.iLength;
      var index = page * length + (iDisplayIndex + 1);
      $('td:eq(0)', row).html(index); // Menulis nomor urut
    },
  });

  table.buttons().container().appendTo('#kelas_wrapper .col-md-6:eq(0)');

  $('#myModal').on('shown.bs.modal', function () {
    $(':input[name="banyak"]').select();
  });

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

  $('#kelas tbody').on('click', 'tr .check', function () {
    var check = $('#kelas tbody tr .check').length;
    var checked = $('#kelas tbody tr .check:checked').length;
    if (check === checked) {
      $('#select_all').prop('checked', true);
    } else {
      $('#select_all').prop('checked', false);
    }
  });

  // Handler untuk bulk delete (form dengan id #bulk)
  $('#bulk').on('submit', function (e) {
    if ($(this).attr('action').endsWith('kelas/delete')) {
      // Hanya jika action adalah delete
      e.preventDefault();
      e.stopImmediatePropagation();

      $.ajax({
        url: $(this).attr('action'),
        data: $(this).serialize(),
        type: 'POST',
        dataType: 'json', // Pastikan controller mengembalikan JSON
        success: function (respon) {
          if (respon.status) {
            Swal({
              // Atau Swal.fire untuk versi SweetAlert2 yang lebih baru
              title: 'Berhasil',
              text:
                respon.message || respon.total + ' data Kelas berhasil dihapus',
              type: 'success', // 'icon: 'success'' untuk v9+
            });
          } else {
            Swal({
              title: 'Gagal',
              text:
                respon.message ||
                'Tidak ada data yang dipilih atau gagal dihapus',
              type: 'error', // 'icon: 'error'' untuk v9+
            });
          }
          reload_ajax(); // Muat ulang tabel
          $('#select_all').prop('checked', false); // Uncheck #select_all
          $('.check').prop('checked', false); // Uncheck semua checkbox individual
        },
        error: function (jqXHR, textStatus, errorThrown) {
          // Tambahkan error handling AJAX
          Swal({
            title: 'Error Server',
            text: 'Gagal menghubungi server: ' + textStatus,
            type: 'error',
          });
          console.error('AJAX Error: ', jqXHR.responseText);
        },
      });
    }
    // Jika action adalah edit, biarkan form submit secara normal (ke halaman edit)
  });
});

function bulk_delete() {
  if ($('#kelas tbody tr .check:checked').length == 0) {
    Swal({
      title: 'Gagal',
      text: 'Tidak ada data Kelas yang dipilih',
      type: 'error',
    });
  } else {
    $('#bulk').attr('action', base_url + 'kelas/delete'); // Set action form ke delete
    Swal({
      title: 'Anda yakin?',
      text: 'Data Kelas akan dihapus!',
      type: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Ya, Hapus!',
    }).then((result) => {
      if (result.value) {
        // 'isConfirmed' untuk v9+
        $('#bulk').submit(); // Submit form untuk delete
      }
    });
  }
}

function bulk_edit() {
  if ($('#kelas tbody tr .check:checked').length == 0) {
    Swal({
      title: 'Gagal',
      text: 'Tidak ada data Kelas yang dipilih',
      type: 'error',
    });
  } else {
    $('#bulk').attr('action', base_url + 'kelas/edit'); // Set action form ke edit
    $('#bulk').submit(); // Submit form untuk redirect ke halaman edit
  }
}

// Pastikan fungsi reload_ajax terdefinisi jika belum ada di scope global
function reload_ajax() {
  if (table) {
    // Cek apakah variabel table sudah diinisialisasi
    table.ajax.reload(null, false); // false agar tidak reset ke halaman pertama
  }
}
