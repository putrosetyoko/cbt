var table;

$(document).ready(function () {
  ajaxcsrf();

  table = $('#hasil').DataTable({
    initComplete: function () {
      var api = this.api();
      $('#hasil_filter input')
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
        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] },
      },
      {
        extend: 'print',
        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] },
      },
      {
        extend: 'excel',
        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] },
      },
      {
        extend: 'pdf',
        exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] },
      },
    ],
    oLanguage: {
      sProcessing: 'loading...',
    },
    processing: true,
    serverSide: true,
    ajax: {
      url: base_url + 'hasilujian/data',
      type: 'POST',
    },
    columns: [
      {
        data: 'id_ujian',
        orderable: false,
        searchable: false,
      },
      { data: 'nama_ujian' },
      { data: 'nama_mapel' },
      { data: 'nama_guru' },
      { data: 'jumlah_soal' },
      { data: 'waktu' },
      {
        data: 'tgl_mulai',
        render: function (data, type, row) {
          if (data) {
            var date = new Date(data);
            var options = {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              hour: '2-digit', // Tampilkan jam dalam 2 digit
              minute: '2-digit', // Tampilkan menit dalam 2 digit
              hour12: false, // Gunakan format 24 jam
            };
            // Ini hanya format tanggal saja, sesuai permintaan sebelumnya
            return date.toLocaleDateString('id-ID', options) + ' WITA';
          }
          return '';
        },
      },
      {
        // KOLOM UNTUK TANGGAL SELESAI DENGAN JAM DAN MENIT
        data: 'terlambat',
        render: function (data, type, row) {
          if (data) {
            var date = new Date(data);
            var options = {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              hour: '2-digit', // Tampilkan jam dalam 2 digit
              minute: '2-digit', // Tampilkan menit dalam 2 digit
              hour12: false, // Gunakan format 24 jam
            };
            // Asumsi zona waktu adalah WITA, Anda bisa mengubahnya jika diperlukan
            // Atau Anda bisa mendapatkan zona waktu dari server jika tersedia di data JSON
            return date.toLocaleDateString('id-ID', options) + ' WITA';
          }
          return '';
        },
      },
      {
        orderable: false,
        searchable: false,
      },
    ],
    columnDefs: [
      {
        targets: 8, // Indeks target kolom aksi masih 8
        data: 'id_ujian',
        render: function (data, type, row, meta) {
          return `
            <div class="text-center">
              <a class="btn btn-xs bg-maroon" href="${base_url}hasilujian/detail/${data}" >
                <i class="fa fa-search"></i> Lihat Hasil
              </a>
            </div>
            `;
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
});

table.buttons().container().appendTo('#hasil_wrapper .col-md-6:eq(0)');
