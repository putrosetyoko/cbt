// File: assets/dist/js/app/ujian/token_konfirmasi_siswa.js

// Pastikan variabel global ini didefinisikan di view PHP sebelum skrip ini:
// const BASE_URL;
// const CSRF_TOKEN_NAME;
// const CSRF_HASH;

$(document).ready(function () {
  // Fungsi ajaxcsrf() jika Anda menggunakannya secara global dari template
  // if (typeof ajaxcsrf === 'function') {
  //     ajaxcsrf();
  // }

  $('form#formtoken').submit(function (e) {
    e.preventDefault();

    let btn = $('#btncek');
    let id_ujian_enc = $('#id_ujian_enc').val();
    let token = $('#token').val();

    // Disable button and show loading
    btn.attr('disabled', true).text('Memproses...');

    $.ajax({
      url: base_url + 'ujian/proses_token',
      type: 'POST',
      dataType: 'json',
      data: {
        [csrf_name]: csrf_hash, // Add CSRF token
        id_ujian_enc: id_ujian_enc,
        token: token,
      },
      success: function (response) {
        if (response.status) {
          Swal.fire({
            title: 'Berhasil',
            text: response.message,
            type: 'success',
            showCancelButton: false,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK',
          }).then((result) => {
            if (response.redirect_url) {
              window.location.href = response.redirect_url;
            }
          });
        } else {
          Swal.fire({
            title: 'Gagal',
            text: response.message || 'Token tidak valid',
            type: 'error',
            showCancelButton: false,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK',
          });
          btn.attr('disabled', false).text('MULAI UJIAN');
        }
      },
      error: function (xhr, status, error) {
        console.error('Ajax Error:', { xhr, status, error });
        Swal.fire({
          title: 'Error',
          text: 'Terjadi kesalahan saat memproses token',
          type: 'error',
          showCancelButton: false,
          confirmButtonColor: '#3085d6',
          confirmButtonText: 'OK',
        });
        btn.attr('disabled', false).text('MULAI UJIAN');
      },
    });
  });

  // Countdown timer jika ada di halaman (dari _footer.php atau template topnav)
  var timeCountdownElem = $('.countdown');
  if (timeCountdownElem.length) {
    // Pastikan fungsi countdown(t) sudah tersedia global (misal dari _footer.php)
    if (typeof countdown === 'function') {
      countdown(timeCountdownElem.data('time'));
    } else {
      console.warn('Fungsi countdown(t) tidak ditemukan.');
    }
  }
});
