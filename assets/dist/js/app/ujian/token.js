// File: assets/dist/js/app/ujian/token.js

// Pastikan variabel global ini didefinisikan di view PHP sebelum skrip ini:
// const BASE_URL;
// const CSRF_TOKEN_NAME;
// const CSRF_HASH;

// Variabel global yang Anda definisikan di token.php
var csrf_name = '<?=$this->security->get_csrf_token_name()?>';
var csrf_hash = '<?=$this->security->get_csrf_hash()?>';
var base_url = '<?=base_url()?>';

$(document).ready(function () {
  // Fungsi ajaxcsrf() jika Anda menggunakannya secara global dari template
  // if (typeof ajaxcsrf === 'function') {
  //    ajaxcsrf(); // Panggil jika ada dan dibutuhkan untuk setup CSRF global
  // }

  $('form#formtoken').submit(function (e) {
    // Pastikan ID form Anda adalah 'formtoken'
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
        [csrf_name]: csrf_hash, // Add CSRF token using global variables
        id_ujian_enc: id_ujian_enc,
        token: token,
      },
      success: function (response) {
        // PERBAIKAN DI SINI
        if (response.status) {
          Swal.fire({
            title: 'Berhasil!',
            text: response.message,
            icon: 'success',
            timer: 1500, // Durasi timer 1.5 detik
            showConfirmButton: false, // TIDAK menampilkan tombol konfirmasi
            allowOutsideClick: false, // Tidak bisa diklik di luar SweetAlert untuk menutupnya
          }).then(() => {
            // Callback ini akan dieksekusi setelah timer selesai
            if (response.redirect_url) {
              window.location.href = response.redirect_url;
            }
          });
        } else {
          Swal.fire({
            title: 'Gagal',
            text: response.message || 'Token tidak valid',
            icon: 'error',
            showCancelButton: false, // Tidak menampilkan tombol cancel
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK', // Hanya tombol OK untuk error
          }).then(() => {
            // Tambahkan .then() untuk mengembalikan tombol setelah diklik OK
            btn.attr('disabled', false).text('MULAI UJIAN');
          });
        }
        // Pastikan CSRF hash diperbarui setelah setiap respons AJAX
        if (response.csrf_hash_new) {
          csrf_hash = response.csrf_hash_new; // Perbarui hash global
          $('[name="' + csrf_name + '"]').val(csrf_hash); // Perbarui hidden input field jika ada di form
        }
      },
      error: function (xhr, status, error) {
        console.error('Ajax Error:', { xhr, status, error });
        Swal.fire({
          title: 'Error',
          text: 'Terjadi kesalahan saat memproses token',
          icon: 'error',
          showCancelButton: false,
          confirmButtonColor: '#3085d6',
          confirmButtonText: 'OK',
        }).then(() => {
          // Tambahkan .then() untuk mengembalikan tombol setelah diklik OK
          btn.attr('disabled', false).text('MULAI UJIAN');
        });
      },
    });
  });

  // Countdown timer jika ada di halaman (dari _footer.php atau template topnav)
  var timeCountdownElem = $('.countdown');
  if (timeCountdownElem.length) {
    if (typeof countdown === 'function') {
      countdown(timeCountdownElem.data('time'));
    } else {
      console.warn('Fungsi countdown(t) tidak ditemukan.');
    }
  }
});
