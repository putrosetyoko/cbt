var tablePJSoal;
var csrf_name = $('input[name="csrf_name"]').val() || 'csrf_test_name';
var csrf_hash = $('input[name="csrf_hash"]').val();

$(document).ready(function () {
  if (typeof ajaxcsrf === 'function') {
    ajaxcsrf();
  }

  tablePJSoal = $('#table_pj_soal').DataTable({
    initComplete: function () {
      var api = this.api();
      $('#table_pj_soal_filter input')
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
      { extend: 'copy', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
      { extend: 'print', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
      { extend: 'excel', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
      { extend: 'pdf', exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6] } },
    ],
    oLanguage: {},
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'pjsoal/data',
      type: 'POST',
      data: function (d) {
        d.filter_tahun_ajaran = $('#filter_tahun_ajaran_pj').val();
        d.filter_mapel = $('#filter_mapel_pj').val();
        d.t = new Date().getTime();
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error(
          'DataTables AJAX error (pjsoal/data):',
          textStatus,
          errorThrown,
          jqXHR.responseText
        );
        Swal.fire(
          'Error DataTables',
          'Gagal memuat data PJ Soal: ' + textStatus,
          'error'
        );
      },
    },
    columns: [
      { data: 'id_pjsa', orderable: false, searchable: false, width: '3%' },
      { data: 'nama_tahun_ajaran' },
      { data: 'nama_mapel' },
      { data: 'nip_guru', defaultContent: '-' },
      { data: 'nama_guru' },
      { data: 'keterangan', defaultContent: '-' },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center',
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
        render: function (data, type, row, meta) {
          return `
                    <a href="${base_url}pjsoal/edit/${row.id_pjsa}" class="btn btn-xs btn-warning" title="Ubah PJ Guru">
                        <i class="fa fa-pencil"></i> Edit
                    </a>
                    <button type="button" class="btn btn-xs btn-danger btn-delete-pjsa" 
                        data-id="${row.id_pjsa}" 
                        data-mapel="${row.nama_mapel}" 
                        data-guru="${row.nama_guru}" 
                        title="Hapus Penugasan PJ">
                        <i class="fa fa-trash"></i> Delete
                    </button>
                `;
        },
      },
    ],
    order: [
      [1, 'desc'],
      [2, 'asc'],
    ],
    rowId: function (a) {
      return 'row_pjsa_' + a.id_pjsa;
    },
  });

  if (tablePJSoal && typeof tablePJSoal.buttons === 'function') {
    tablePJSoal
      .buttons()
      .container()
      .appendTo('#table_pj_soal_wrapper .col-md-6:eq(0)');
  }

  $('#filter_tahun_ajaran_pj, #filter_mapel_pj').on('change', function () {
    reload_ajax_pjsoal();
  });

  $('#table_pj_soal tbody').on('click', '.btn-delete-pjsa', function () {
    const id = $(this).data('id');
    const mapel = $(this).data('mapel');
    const guru = $(this).data('guru');

    console.log('Delete button clicked:', { id, mapel, guru }); // Debug info

    Swal.fire({
      title: 'Anda yakin?',
      html: `Akan menghapus penugasan PJ Soal untuk mapel <strong>${mapel}</strong> oleh guru <strong>${guru}</strong>?`,
      type: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal',
    }).then((result) => {
      if (result.value) {
        // Ambil ulang CSRF token untuk memastikan masih valid
        const currentCsrfHash = $('input[name="csrf_hash"]').val();

        $.ajax({
          url: base_url + 'pjsoal/delete',
          type: 'POST',
          data: {
            id_pjsa: id,
            csrf_test_name: currentCsrfHash, // Gunakan nama default CI CSRF
          },
          dataType: 'json',
          success: function (response) {
            if (response.status) {
              Swal.fire({
                type: 'success',
                title: 'Berhasil!',
                text: response.message,
                showConfirmButton: true,
                // timer: 1500,
              }).then(() => {
                tablePJSoal.ajax.reload(null, false);
              });
            } else {
              Swal.fire({
                type: 'error',
                title: 'Gagal!',
                text: response.message || 'Terjadi kesalahan',
              });
            }
          },
          error: function (xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            Swal.fire({
              type: 'error',
              title: 'Error!',
              text: 'Terjadi kesalahan pada server',
            });
          },
        });
      }
    });
  });
});

function reload_ajax_pjsoal() {
  if (tablePJSoal) tablePJSoal.ajax.reload(null, false);
}
