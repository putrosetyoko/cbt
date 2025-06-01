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
      { extend: 'copy', exportOptions: { columns: [0, 1, 2, 3, 4] } }, // No, TA, Guru, Mapel, Jenjang, Kelas
      { extend: 'print', exportOptions: { columns: [0, 1, 2, 3, 4] } },
      { extend: 'excel', exportOptions: { columns: [0, 1, 2, 3, 4] } },
      { extend: 'pdf', exportOptions: { columns: [0, 1, 2, 3, 4] } },
    ],
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
      {
        data: 'kelas_info',
      },
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
        targets: 4,
        searchable: false,
        orderable: false,
        data: 'kelas_info',
        render: function (data, type, row, meta) {
          if (type === 'display' && data) {
            // Pisahkan string kelas menjadi array dan bersihkan spasi
            const kelasArray = data.split(',').map((k) => k.trim());
            let badges = '';

            // Buat badge untuk setiap kelas
            kelasArray.forEach((kelas) => {
              // Ambil jenjang dari kelas (VII, VIII, atau IX)
              const jenjang = kelas.split(' ')[0];
              let badgeClass = '';

              // Tentukan warna badge berdasarkan jenjang
              switch (jenjang) {
                case 'VII':
                  badgeClass = 'bg-green';
                  break;
                case 'VIII':
                  badgeClass = 'bg-blue';
                  break;
                case 'IX':
                  badgeClass = 'bg-maroon';
                  break;
                default:
                  badgeClass = 'bg-gray';
              }

              // Tambahkan badge dengan margin
              badges += `<span class="badge ${badgeClass}" style="margin: 2px 4px; display: inline-block;">${kelas}</span>`;
            });

            return badges;
          }
          return data;
        },
      },
      {
        targets: 5, // Kolom Aksi
        render: function (data, type, row, meta) {
          return `<a href="${base_url}penugasanguru/edit/${data.id_gmka}" class="btn btn-xs btn-warning""><i class="fa fa-pencil"></i> Edit</a>`;
        },
      },
      {
        targets: 6, // Kolom Checkbox
        render: function (data, type, row, meta) {
          // Debug: log row data
          console.log('Row data for checkbox:', row);
          return `<input name="checked[]" class="check" value="${row.id_gmka}" type="checkbox">`;
        },
      },
    ],
    order: [
      [1, 'desc'],
      [2, 'asc'],
      [3, 'asc'],
      [4, 'asc'],
    ],
    rowId: function (a) {
      return 'row_gmka_' + a.id_gmka;
    },
  });

  $('#filter_tahun_ajaran, #filter_guru, #filter_mapel, #filter_kelas').on(
    'change',
    function () {
      console.log('Filter changed:', {
        tahun_ajaran: $('#filter_tahun_ajaran').val(),
        guru: $('#filter_guru').val(),
        mapel: $('#filter_mapel').val(),
        kelas: $('#filter_kelas').val(),
      });
      tablePenugasanGuru.ajax.reload();
    }
  );

  if (tablePenugasanGuru && typeof tablePenugasanGuru.buttons === 'function') {
    tablePenugasanGuru
      .buttons()
      .container()
      .appendTo('#table_penugasan_guru_wrapper .col-md-6:eq(0)');
  }

  $('.select_all').on('click', function () {
    $('.check').prop('checked', this.checked);
  });

  $('#table_penugasan_guru tbody').on('click', 'tr .check', function () {
    if (!this.checked) {
      $('.select_all').prop('checked', false);
    }
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
  var $checkedBoxes = $('#table_penugasan_guru .check:checked');

  if ($checkedBoxes.length === 0) {
    Swal.fire('Gagal', 'Tidak ada data yang dipilih untuk dihapus.', 'error');
    return;
  }

  // Kumpulkan data yang akan dihapus
  let uniqueData = new Set();
  let dataToDelete = [];

  $checkedBoxes.each(function () {
    const row = tablePenugasanGuru.row($(this).closest('tr')).data();
    // Buat key unik untuk kombinasi guru, mapel, dan tahun ajaran
    const key = `${row.guru_id}_${row.mapel_id}_${row.id_tahun_ajaran}`;

    if (!uniqueData.has(key)) {
      uniqueData.add(key);
      dataToDelete.push({
        guru_id: row.guru_id,
        mapel_id: row.mapel_id,
        tahun_ajaran_id: row.id_tahun_ajaran,
      });
    }
  });

  Swal.fire({
    title: 'Anda yakin?',
    text: `Data yang dipilih akan dihapus beserta seluruh kelasnya!`,
    type: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, Hapus!',
    cancelButtonText: 'Batal',
  }).then((result) => {
    if (result.value) {
      $.ajax({
        url: base_url + 'penugasanguru/delete',
        type: 'POST',
        data: {
          data_delete: JSON.stringify(dataToDelete),
        },
        success: function (response) {
          if (response.status) {
            Swal.fire({
              title: 'Berhasil',
              text: response.message,
              type: 'success',
            }).then(() => {
              tablePenugasanGuru.ajax.reload();
              $('.select_all').prop('checked', false);
            });
          } else {
            Swal.fire({
              title: 'Gagal',
              text: response.message,
              type: 'error',
            });
          }
        },
        error: function (xhr, status, error) {
          console.error('AJAX Error:', error);
          Swal.fire({
            title: 'Error',
            text: 'Terjadi kesalahan saat menghapus data',
            type: 'error',
          });
        },
      });
    }
  });
}

function reload_ajax() {
  if (tablePenugasanGuru) {
    tablePenugasanGuru.ajax.reload(null, false);
  }
}
