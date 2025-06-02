// Disarankan disimpan sebagai: assets/dist/js/app/ujian/data_ujian.js
// Atau bisa diletakkan di dalam tag <script> di view ujian/data.php

// Pastikan variabel global ini sudah didefinisikan di view SEBELUM skrip ini dimuat:
// const BASE_URL; (contoh: 'http://localhost/cbt/')
// const IS_ADMIN; (boolean)
// const IS_GURU; (boolean)
// const GURU_ID; (integer atau null, ID guru yang login dari tabel guru)
// const PJ_MAPEL_ID; (integer atau null, ID mapel PJ jika guru adalah PJ)
// Dan CSRF token jika Anda menggunakannya:
// const CSRF_TOKEN_NAME = '<?= $this->security->get_csrf_token_name(); ?>';
// const CSRF_HASH = '<?= $this->security->get_csrf_hash(); ?>';

var tableUjian;

$(document).ready(function () {
  // Fungsi ajaxcsrf() jika Anda masih menggunakannya dari sistem lama
  // Jika tidak, pastikan CSRF token dikirim manual jika diperlukan.
  // if (typeof ajaxcsrf === 'function') {
  //     ajaxcsrf();
  // }

  // Inisialisasi Select2 untuk filter
  if ($.fn.select2) {
    $('#filter_tahun_ajaran, #filter_mapel, #filter_jenjang').select2({
      // placeholder: "Pilih...", // Sesuaikan jika perlu
      // allowClear: true // Aktifkan jika ingin ada tombol clear
    });
  }

  tableUjian = $('#table_ujian').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: BASE_URL + 'ujian/data',
      type: 'POST',
      data: function (d) {
        // Mengirim data filter tambahan
        d.filter_tahun_ajaran_ujian = $('#filter_tahun_ajaran').val();
        d.filter_mapel_ujian = $('#filter_mapel').val();
        d.filter_jenjang_ujian = $('#filter_jenjang').val();

        // Menambahkan CSRF Token ke data yang dikirim
        d[CSRF_TOKEN_NAME] = CSRF_HASH;
      },
      error: function (jqXHR, textStatus, errorThrown) {
        // ... (kode error handling Anda) ...
        // PENTING: Jika CSRF regenerate=TRUE, hash mungkin perlu diupdate setelah request pertama
        if (jqXHR.responseJSON && jqXHR.responseJSON.csrf_hash) {
          // Update CSRF_HASH untuk request berikutnya jika server mengirimkan hash baru
          // Ini diperlukan jika $config['csrf_regenerate'] = TRUE;
          // Namun, DataTables biasanya tidak mengirim ulang hash baru secara otomatis dalam konfigurasinya.
          // Cara yang lebih umum adalah mengirim hash yang ada di cookie setiap kali.
          // atau menonaktifkan regenerate untuk AJAX.
        }
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
      { data: 'nama_jenjang_target' },
      { data: 'pembuat_ujian' }, // Nama guru PJ pembuat
      { data: 'jumlah_soal', className: 'text-center' },
      {
        data: 'waktu',
        className: 'text-center',
        render: function (data) {
          return data + ' Menit';
        },
      },
      { data: 'tgl_mulai_formatted' }, // Sudah diformat dari server
      {
        data: 'token',
        className: 'text-center',
        render: function (data, type, row, meta) {
          // Tombol refresh token hanya untuk Admin atau PJ Soal dari mapel tersebut
          let can_refresh_token = false;
          if (
            IS_ADMIN ||
            (IS_GURU &&
              PJ_MAPEL_ID !== null &&
              row.id_mapel_ujian == PJ_MAPEL_ID)
          ) {
            // Atau cek row.id_pembuat_ujian == GURU_ID jika PJ hanya boleh refresh miliknya
            can_refresh_token = true;
          }
          let token_display = `<strong class="badge bg-purple" id="token-${
            row.id_ujian
          }">${data ? data : '-'}</strong>`;
          if (can_refresh_token && data) {
            // Hanya tampilkan tombol refresh jika token ada dan berhak
            token_display += ` <button type="button" data-id="${row.id_ujian}" class="btn btn-token-refresh btn-xs bg-blue" title="Refresh Token"><i class="fa fa-refresh"></i></button>`;
          }
          return token_display;
        },
      },
      {
        data: 'status_aktif',
        className: 'text-center',
        render: function (data, type, row) {
          if (data === 'Aktif') {
            return '<span class="badge bg-green">Aktif</span>';
          } else {
            return '<span class="badge bg-red">Tidak Aktif</span>';
          }
        },
      }, // Misal "Aktif" atau "Tidak Aktif"
      {
        data: 'id_ujian', // Digunakan untuk membuat link aksi
        orderable: false,
        searchable: false,
        width: '3%', // Atur lebar kolom aksi
        className: 'text-center',
        render: function (data, type, row, meta) {
          let buttons = '';
          // Tombol Edit: Admin atau PJ Soal (pembuat atau PJ mapel ujian)
          let can_edit = false;
          if (
            IS_ADMIN ||
            (IS_GURU &&
              PJ_MAPEL_ID !== null &&
              row.id_mapel_ujian == PJ_MAPEL_ID)
          ) {
            // Lebih detail: cek apakah row.id_pembuat_ujian == GURU_ID jika PJ hanya boleh edit miliknya
            can_edit = true;
          }

          buttons += `<a href="${BASE_URL}ujian/edit/${data}" class="btn btn-xs btn-warning ${
            !can_edit ? 'disabled' : ''
          }" title="Edit & Kelola Soal"><i class="fa fa-pencil"></i></a> `;

          // Guru Non-PJ mungkin hanya bisa lihat detail atau tidak ada aksi lain
          // Jika ada halaman detail terpisah untuk ujian:
          // buttons += `<a href="${BASE_URL}ujian/detail_info/${data}" class="btn btn-xs btn-info" title="Lihat Detail"><i class="fa fa-eye"></i></a>`;

          return buttons;
        },
      },
      {
        data: 'id_ujian', // Akan digunakan untuk value checkbox
        orderable: false,
        searchable: false,
        className: 'text-center',
        render: function (data, type, row, meta) {
          // Tombol checkbox hanya untuk Admin atau Guru PJ Soal
          if (
            IS_ADMIN ||
            (IS_GURU &&
              PJ_MAPEL_ID !== null &&
              row.id_mapel_ujian == PJ_MAPEL_ID)
          ) {
            // Atau cek row.id_pembuat_ujian == GURU_ID jika PJ hanya boleh hapus miliknya
            return `<input name="checked[]" class="check-item-ujian" value="${data}" type="checkbox">`;
          }
          return '';
        },
      },
    ],
    columnDefs: [
      {
        targets: 7, // Index kolom created_on_formatted
        render: function (data, type, row) {
          if (type === 'display') {
            // Split tanggal dan waktu
            const [datePart] = row.tgl_mulai_formatted.split(' ');
            // Split tanggal
            const [day, month, year] = datePart.split('-');

            // Buat objek Date dengan format yang benar (YYYY-MM-DD)
            const date = new Date(`${year}-${month}-${day}`);

            const options = {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
              timeZone: 'Asia/Jakarta',
            };
            return date.toLocaleDateString('id-ID', options);
          }
          return data;
        },
      },
    ],
    order: [[2, 'asc']], // Default order by nama_ujian ASC
    rowId: function (row) {
      return 'ujian_' + row.id_ujian;
    },
    // oLanguage: { /* ... Opsi bahasa Indonesia jika perlu ... */ }
  });

  // Handler untuk filter
  $('#btn-apply-filter-ujian').on('click', function () {
    tableUjian.ajax.reload();
  });

  $('#reload_table_ujian').on('click', function () {
    // Opsional: reset filter ke default sebelum reload
    // $('#filter_tahun_ajaran').val($('#filter_tahun_ajaran option[selected]').val()).trigger('change.select2');
    // $('#filter_mapel').val('all').trigger('change.select2');
    // $('#filter_jenjang').val('all').trigger('change.select2');
    tableUjian.ajax.reload(null, false); // false agar tidak reset paging
  });

  // Event handler untuk tombol delete
  $('#btn-delete-selected-ujian').on('click', function (e) {
    e.preventDefault();
    let checkedIds = [];

    // Kumpulkan semua ID dari checkbox yang dicentang
    $('.check-item-ujian:checked').each(function () {
      checkedIds.push($(this).val());
    });

    if (checkedIds.length === 0) {
      Swal.fire({
        title: 'Peringatan',
        text: 'Tidak ada ujian yang dipilih untuk dihapus.',
        type: 'warning',
      });
      return;
    }

    Swal.fire({
      title: 'Konfirmasi Hapus',
      text: `${checkedIds.length} ujian yang dipilih akan dihapus. Yakin?`,
      type: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal',
    }).then((result) => {
      if (result.value) {
        $.ajax({
          url: BASE_URL + 'ujian/delete',
          type: 'POST',
          data: {
            checked: checkedIds,
            [CSRF_TOKEN_NAME]: CSRF_HASH,
          },
          dataType: 'json',
          success: function (response) {
            if (response.status) {
              Swal.fire({
                title: 'Berhasil!',
                text: response.message,
                type: 'success',
                showConfirmButton: true,
                timer: 1500,
              });
              // Reload tabel dan reset checkbox
              tableUjian.ajax.reload(null, false);
              $('#check-all-ujian').prop('checked', false);
            } else {
              Swal.fire({
                title: 'Gagal!',
                text:
                  response.message || 'Terjadi kesalahan saat menghapus data.',
                type: 'error',
              });
            }
          },
          error: function (xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            Swal.fire({
              title: 'Error!',
              text: 'Terjadi kesalahan pada server',
              type: 'error',
            });
          },
        });
      }
    });
  });

  // Check all logic
  $('#check-all-ujian').on('click', function () {
    $('#table_ujian tbody .check-item-ujian').prop('checked', this.checked);
  });

  $('#table_ujian tbody').on('click', '.check-item-ujian', function () {
    if (
      $('#table_ujian tbody .check-item-ujian:checked').length ==
        $('#table_ujian tbody .check-item-ujian').length &&
      $('#table_ujian tbody .check-item-ujian').length > 0
    ) {
      $('#check-all-ujian').prop('checked', true);
    } else {
      $('#check-all-ujian').prop('checked', false);
    }
  });

  // Bulk delete handler
  $('#form-delete-selected-ujian').on('submit', function (e) {
    e.preventDefault();
    let form = this;
    let checkedIds = [];

    // Bulk delete
    $('#table_ujian tbody .check-item-ujian:checked').each(function () {
      checkedIds.push($(this).val());
    });

    if (checkedIds.length === 0) {
      Swal.fire(
        'Peringatan',
        'Tidak ada ujian yang dipilih untuk dihapus.',
        'warning'
      );
      return false;
    }
    Swal.fire({
      title: 'Anda yakin?',
      text:
        'Data ujian yang terpilih (' +
        checkedIds.length +
        ' item) akan dihapus!',
      type: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal',
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: BASE_URL + 'ujian/delete', // Controller akan menghandle berdasarkan 'checked'
          type: 'POST',
          data: {
            checked: checkedIds,
            [CSRF_TOKEN_NAME]: CSRF_HASH,
          },
          dataType: 'json',
          success: function (response) {
            Swal.fire(
              response.status ? 'Sukses!' : 'Gagal!',
              response.message,
              response.status ? 'success' : 'error'
            );
            if (response.status) {
              tableUjian.ajax.reload(null, false);
              $('#check-all-ujian').prop('checked', false);
            }
          },
          error: function (jqXHR, textStatus, errorThrown) {
            Swal.fire(
              'Error!',
              'Terjadi kesalahan AJAX saat menghapus data.',
              'error'
            );
          },
        });
      }
    });
  });

  // Handler untuk tombol refresh token
  $('#table_ujian').on('click', '.btn-token-refresh', function (e) {
    e.preventDefault();
    let button = $(this);
    let idUjian = button.data('id');
    let type = button.find('i');
    let originaltypeClass = type.attr('class');

    button.prop('disabled', true).addClass('disabled');
    type.removeClass(originaltypeClass).addClass('fa fa-spinner fa-spin');

    $.ajax({
      url: BASE_URL + 'ujian/refresh_token/' + idUjian,
      type: 'POST', // Atau GET, sesuaikan dengan method di controller
      dataType: 'json',
      data: { [CSRF_TOKEN_NAME]: CSRF_HASH }, // Kirim CSRF jika POST
      success: function (response) {
        if (response.status && response.new_token) {
          $('#token-' + idUjian).text(response.new_token);
          Swal.fire('Sukses', response.message, 'success');
        } else {
          Swal.fire(
            'Gagal',
            response.message || 'Gagal memperbarui token.',
            'error'
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        Swal.fire(
          'Error AJAX',
          'Gagal menghubungi server untuk refresh token.',
          'error'
        );
      },
      complete: function () {
        button.prop('disabled', false).removeClass('disabled');
        type.removeClass('fa-spinner fa-spin').addClass(originaltypeClass);
      },
    });
  });
});

// Fungsi reload global jika diperlukan oleh tombol di view utama
// function reload_ajax_ujian() {
//     if (tableUjian) {
//         tableUjian.ajax.reload(null, false);
//     }
// }
