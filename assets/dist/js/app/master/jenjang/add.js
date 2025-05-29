$(document).ready(function () {
  // Inisialisasi datetimepicker dihapus karena tidak ada di form Jenjang

  // Menyesuaikan selektor form ke '#formjenjang'
  $(
    'form#formjenjang input, form#formjenjang textarea, form#formjenjang select'
  ).on(
    'change keyup', // Tambahkan keyup untuk validasi lebih responsif jika diinginkan
    function () {
      $(this).closest('.form-group').removeClass('has-error');
      // Menghapus pesan error pada elemen <small> dengan ID spesifik
      $('#error_' + $(this).attr('name')).text(''); // Menggunakan atribut name untuk mencocokkan ID error
    }
  );

  $('form#formjenjang').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var btn = $(this).find('button[type="submit"]');
    var btnText = btn.html();
    btn
      .attr('disabled', 'disabled')
      .html('<i class="fa fa-spinner fa-spin"></i> Wait...');

    // Kosongkan pesan error sebelumnya
    $('.help-block').text(''); // Kosongkan semua help-block
    $('.form-group').removeClass('has-error'); // Hapus kelas error dari semua form-group

    $.ajax({
      url: $(this).attr('action'),
      data: $(this).serialize(), // Cukup untuk form tanpa file upload
      method: 'POST',
      dataType: 'json',
      success: function (data) {
        btn.removeAttr('disabled').html(btnText);

        if (data.status) {
          Swal({
            // Atau Swal.fire jika menggunakan SweetAlert2 versi baru
            title: 'Sukses',
            text: 'Data Jenjang Berhasil disimpan.', // Pesan disesuaikan
            type: 'success', // 'icon: 'success'' di SweetAlert2 v9+
          }).then((result) => {
            if (result.value) {
              // 'isConfirmed' di SweetAlert2 v9+
              window.location.href = base_url + 'jenjang'; // URL disesuaikan
            }
          });
        } else {
          if (data.errors) {
            // data.errors diharapkan sebagai objek: { field_name: "error message", ... }
            // seperti yang dihasilkan oleh controller Anda
            $.each(data.errors, function (key, val) {
              if (val) {
                // Hanya jika ada pesan error
                var errorHelpBlock = $('#error_' + key);
                errorHelpBlock.text(val).addClass('text-danger'); // Menampilkan error pada elemen <small id="error_NAMAFORM">
                // Menambahkan class has-error ke .form-group parent dari input terkait
                $('[name="' + key + '"]')
                  .closest('.form-group')
                  .addClass('has-error');
              }
            });
          } else if (data.message) {
            Swal({
              title: 'Gagal',
              text: data.message,
              type: 'error',
            });
          } else {
            Swal({
              title: 'Gagal',
              text: 'Terjadi kesalahan yang tidak diketahui.',
              type: 'error',
            });
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        btn.removeAttr('disabled').html(btnText);
        Swal({
          title: 'Error',
          text: 'Terjadi kesalahan saat mengirim data: ' + textStatus,
          type: 'error',
        });
        console.error('AJAX Error: ', textStatus, errorThrown);
        console.error('Response Text: ', jqXHR.responseText);
      },
    });
  });
});
