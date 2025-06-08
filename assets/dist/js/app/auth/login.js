// File: assets/dist/js/app/auth/login.js

$(document).ready(function () {
  $('form#login input').on('change', function () {
    $(this).parent().removeClass('has-error');
    $(this).next().next().text('');
  });

  // Tombol Show/Hide Password
  // Tombol Show/Hide Password
  $('.toggle-password').on('click', function () {
    // Coba selector yang lebih spesifik atau alternatif
    // Pilihan 1: Jika input adalah elemen sebelumnya
    const passwordInput = $(this).prev('input[name="password"]');

    // Pilihan 2: Jika ada lebih dari satu input di form, cari spesifik
    // const passwordInput = $('input[name="password"]', $(this).closest('form'));

    // Pilihan 3: Jika Anda menambahkan ID ke input password (misal id="passwordInput")
    // const passwordInput = $('#passwordInput'); // Paling robust jika ID unik

    const icon = $(this);

    console.log('Toggle password clicked!'); // Debugging: pastikan ini muncul
    console.log(
      'Password Input Found:',
      passwordInput.length > 0 ? passwordInput : 'Not found'
    ); // Debugging: cek apakah input ditemukan

    if (passwordInput.length === 0) {
      console.error('Input password tidak ditemukan oleh selector!');
      return; // Hentikan eksekusi jika input tidak ditemukan
    }

    if (passwordInput.attr('type') === 'password') {
      passwordInput.attr('type', 'text');
      icon.removeClass('fa-eye-slash').addClass('fa-eye');
    } else {
      passwordInput.attr('type', 'password');
      icon.removeClass('fa-eye').addClass('fa-eye-slash');
    }
  });

  $('form#login').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var infobox = $('#infoMessage');
    infobox.addClass('callout callout-info text-center').text('Checking...');

    var btnsubmit = $('#submit');
    btnsubmit.attr('disabled', 'disabled').val('Wait...');

    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: $(this).serialize(),
      success: function (data) {
        infobox.removeAttr('class').text('');
        btnsubmit.removeAttr('disabled').val('Login');
        if (data.status) {
          infobox
            .addClass('callout callout-success text-center')
            .text('Successful Login');
          var go = base_url + data.url;
          window.location.href = go;
        } else {
          if (data.invalid) {
            $.each(data.invalid, function (key, val) {
              $('[name="' + key + '"')
                .parent()
                .addClass('has-error');
              $('[name="' + key + '"')
                .next()
                .next()
                .text(val);
              if (val == '') {
                $('[name="' + key + '"')
                  .parent()
                  .removeClass('has-error');
                $('[name="' + key + '"')
                  .next()
                  .next()
                  .text('');
              }
            });
          } else {
          }
          if (data.failed) {
            infobox
              .addClass('callout callout-danger text-center')
              .text(data.failed);
          }
        }
      },
    });
  });
});
