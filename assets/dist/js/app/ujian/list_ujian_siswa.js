// File: assets/dist/js/app/ujian/list_ujian_siswa.js

// Pastikan variabel global ini didefinisikan di view PHP sebelum skrip ini:
// const BASE_URL;
// const ID_SISWA_GLOBAL; // ID siswa yang sedang login
// const CSRF_TOKEN_NAME;
// const CSRF_HASH;

var tableListUjianSiswa;

$(document).ready(function () {
  // Fungsi ajaxcsrf() jika Anda menggunakannya secara global dari template
  // if (typeof ajaxcsrf === 'function') {
  //     ajaxcsrf(); // Panggil jika ada dan dibutuhkan untuk setup CSRF global
  // }

  tableListUjianSiswa = $('#table_ujian_siswa').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: BASE_URL + 'ujian/data_list_siswa', // Endpoint AJAX baru di controller Ujian
      type: 'POST',
      dataType: 'json',
      data: function (d) {
        // Kirim CSRF token jika tidak dihandle global oleh ajaxcsrf()
        // d[CSRF_TOKEN_NAME] = CSRF_HASH;
        // Anda bisa menambahkan filter tambahan dari UI di sini jika ada
        // Misalnya, filter berdasarkan status pengerjaan:
        // d.filter_status_pengerjaan = $('#filter_status_ujian_siswa').val();
        d[CSRF_TOKEN_NAME] = CSRF_HASH;
      },
      dataSrc: function (response) {
        // Log response for debugging
        console.log('Server Response:', response);

        // Check if response has data property
        if (!response.data) {
          console.error('Invalid response format:', response);
          return [];
        }
        return response.data;
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.error('DataTables AJAX Error:', {
          status: jqXHR.status,
          textStatus: textStatus,
          errorThrown: errorThrown,
          responseText: jqXHR.responseText,
        });

        Swal.fire({
          type: 'error',
          title: 'Error',
          text: 'Gagal memuat daftar ujian. Silakan refresh halaman.',
        });
      },
    },
    columns: [
      {
        data: null, // Untuk nomor urut
        orderable: false,
        searchable: false,
        className: 'text-center',
        render: function (data, type, row, meta) {
          return meta.row + meta.settings._iDisplayStart + 1;
        },
      },
      { data: 'nama_ujian' },
      { data: 'nama_mapel' },
      { data: 'nama_guru_pengajar' },
      {
        data: 'tgl_mulai_formatted',
        className: 'text-center', // Jadwal ujian (mulai - selesai/terlambat)
        render: function (data, type, row) {
          if (type === 'display') {
            // Split tanggal dan waktu
            const [datePart] = row.tgl_mulai_formatted.split(' ');
            // Split tanggal
            const [day, month, year] = datePart.split('-');

            // Buat objek Date dengan format yang benar (YYYY-MM-DD)
            const date = new Date(`${year}-${month}-${day}`);

            const options = {
              weekday: 'long',
              day: 'numeric',
              month: 'long',
              year: 'numeric',
              timeZone: 'Asia/Jakarta', // Pastikan timezone sesuai dengan WITA
            };
            return date.toLocaleDateString('id-ID', options);
          }
          return data;
        },
      },
      {
        data: null,
        className: 'text-center',
        render: function (data, type, row) {
          // Get time parts from both timestamps
          const [, mulaiTime] = row.tgl_mulai_formatted.split(' ');
          const [, terlambatTime] = row.terlambat_formatted.split(' ');
          return `${mulaiTime}-${terlambatTime} WIB`;
        },
      },
      {
        data: 'status_pengerjaan_siswa',
        className: 'text-center',
        render: function (data, type, row) {
          switch (data) {
            case 'completed':
              return '<span class="label label-success">Selesai</span>';
            case 'sedang_dikerjakan':
              return '<span class="label label-warning">Sedang Dikerjakan</span>';
            default:
              let now = new Date().getTime();
              let tglMulai = new Date(row.tgl_mulai_server_format).getTime();
              let tglTerlambat = new Date(
                row.terlambat_server_format
              ).getTime();

              if (now < tglMulai) {
                return '<span class="label label-default">Belum Mulai</span>';
              } else if (now > tglTerlambat) {
                return '<span class="label label-danger">Terlewat</span>';
              }
              return '<span class="label label-warning">Belum Dikerjakan</span>';
          }
        },
      },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: 'text-center',
        render: function (data, type, row, meta) {
          let now = new Date().getTime();
          let tglMulai = new Date(row.tgl_mulai_server_format).getTime();
          let tglTerlambat = new Date(row.terlambat_server_format).getTime();

          // Handle different status conditions
          switch (row.status_pengerjaan_siswa) {
            case 'completed':
              // Tidak menampilkan tombol lihat hasil
              return '';

            case 'sedang_dikerjakan':
              const safeUrlContinue = encodeURIComponent(row.id_ujian_encrypted)
                .replace(/\(/g, '%28')
                .replace(/\)/g, '%29')
                .replace(/!/g, '%21')
                .replace(/'/g, '%27')
                .replace(/\*/g, '%2A');

              return `<a class="btn btn-xs bg-blue" href="${BASE_URL}ujian/token/${safeUrlContinue}" title="Lanjutkan Ujian">
          <i class="fa fa-pencil"></i> Lanjutkan
        </a>`;
          }

          // Handle timing conditions for non-started exams
          if (now < tglMulai) {
            return `<button type="button" class="btn btn-xs bg-blue disabled" title="Ujian Belum Dimulai">
        <i class="fa fa-pencil"></i> Ikut Ujian
      </button>`;
          }

          if (now > tglTerlambat) {
            return `<button type="button" class="btn btn-xs bg-blue disabled" title="Waktu Sudah Habis">
        <i class="fa fa-pencil"></i> Ikuti Ujian
      </button>`;
          }

          // Exam is available to take
          const safeUrl = encodeURIComponent(row.id_ujian_encrypted)
            .replace(/\(/g, '%28')
            .replace(/\)/g, '%29')
            .replace(/!/g, '%21')
            .replace(/'/g, '%27')
            .replace(/\*/g, '%2A');

          return `<a class="btn btn-xs btn-primary" href="${BASE_URL}ujian/token/${safeUrl}" title="Ikuti Ujian">
      <i class="fa fa-pencil"></i> Ikut Ujian
    </a>`;
        },
      },
    ],
    order: [[4, 'desc']], // Sort by tanggal mulai descending
    language: {
      processing: 'Memuat data...',
      searchPlaceholder: 'Cari ujian...',
    },
  });

  $('#reload_ujian_siswa').on('click', function () {
    // Jika CSRF regenerate TRUE, Anda mungkin perlu mendapatkan hash baru sebelum reload
    // Untuk sekarang, coba reload biasa dulu
    tableListUjianSiswa.ajax.reload(null, false);
  });

  // Add error handler for failed table loads
  tableListUjianSiswa.on('error.dt', function (e, settings, techNote, message) {
    console.error('DataTables error:', message);
    Swal.fire({
      type: 'error',
      title: 'Error',
      text: 'Terjadi kesalahan saat memuat data. Silakan refresh halaman.',
    });
  });
});
