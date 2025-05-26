var table;

$(document).ready(function () {
  ajaxcsrf();

  table = $('#siswa').DataTable({
    // Ubah #mahasiswa ke #siswa
    initComplete: function () {
      var api = this.api();
      $('#siswa_filter input') // Ubah #mahasiswa_filter ke #siswa_filter
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
        exportOptions: { columns: [1, 2, 3, 4] }, // Sesuaikan kolom: NISN, Nama, Jenis Kelamin, Kelas
      },
      {
        extend: 'print',
        exportOptions: { columns: [1, 2, 3, 4] }, // Sesuaikan kolom
      },
      {
        extend: 'excel',
        exportOptions: { columns: [1, 2, 3, 4] }, // Sesuaikan kolom
      },
      {
        extend: 'pdf',
        exportOptions: { columns: [1, 2, 3, 4] }, // Sesuaikan kolom
      },
    ],
    oLanguage: {
      sProcessing: 'loading...',
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'siswa/data', // Ubah mahasiswa/data ke siswa/data
      type: 'POST',
      //data: csrf
    },
    columns: [
      {
        data: 'id_siswa', // Ubah id_mahasiswa ke id_siswa
        orderable: false,
        searchable: false,
      },
      { data: 'nisn' }, // Ubah nim ke nisn
      { data: 'nama' },
      { data: 'jenis_kelamin' }, // Tambah kolom jenis_kelamin
      { data: 'nama_kelas' },
      // Kolom email dan nama_jurusan dihapus
    ],
    columnDefs: [
      {
        searchable: false,
        targets: 5, // Sesuaikan index target untuk kolom Aksi
        data: {
          id_siswa: 'id_siswa', // Ubah id_mahasiswa ke id_siswa
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
        targets: 6, // Sesuaikan index target untuk checkbox
        data: 'id_siswa', // Ubah id_mahasiswa ke id_siswa
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

  table.buttons().container().appendTo('#siswa_wrapper .col-md-6:eq(0)'); // Ubah #mahasiswa_wrapper ke #siswa_wrapper

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
    // Ubah #mahasiswa tbody ke #siswa tbody
    var check = $('#siswa tbody tr .check').length; // Ubah #mahasiswa tbody ke #siswa tbody
    var checked = $('#siswa tbody tr .check:checked').length; // Ubah #mahasiswa tbody ke #siswa tbody
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
      url: $(this).attr('action').replace('mahasiswa', 'siswa'), // Pastikan action URL berubah ke siswa/delete
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
          text: 'Ada data yang sedang digunakan',
          type: 'error',
        });
      },
    });
  });

  $('#siswa').on('click', '.btn-aktif', function () {
    // Ubah #mahasiswa ke #siswa
    let id = $(this).data('id');

    $.ajax({
      url: base_url + 'siswa/create_user', // Ubah mahasiswa/create_user ke siswa/create_user
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
    });
  });
});

function bulk_delete() {
  if ($('#siswa tbody tr .check:checked').length == 0) {
    // Ubah #mahasiswa tbody ke #siswa tbody
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
