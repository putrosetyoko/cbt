$(document).ready(function () {
  // Fokus ke input NIP saat halaman dimuat (opsional)
  $('#nip').focus();

  // Menghapus kelas error/sukses dan teks help-block saat input berubah
  $('#formguru input, #formguru select').on('change keyup', function () {
    $(this).closest('.form-group').removeClass('has-error has-success');
    $(this).nextAll('.help-block').eq(0).text('');
  });

  $('#formguru').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var btn = $('#submit'); // Asumsi tombol submit memiliki ID 'submit'
    var btnText = btn.text(); // Simpan teks asli tombol
    btn
      .attr('disabled', 'disabled')
      .html('<i class="fa fa-spinner fa-spin"></i> Wait...'); // Gunakan .html() jika ada ikon

    // Bersihkan error sebelumnya
    $('.help-block').text('');
    $('.form-group').removeClass('has-error has-success');

    $.ajax({
      url: $(this).attr('action'),
      data: $(this).serialize(),
      type: 'POST',
      dataType: 'json', // Tambahkan ini untuk ekspektasi respons JSON
      success: function (response) {
        btn.removeAttr('disabled').text(btnText); // Kembalikan teks asli

        if (response.status) {
          Swal(
            'Sukses',
            response.message || 'Data Guru Berhasil disimpan',
            'success'
          ) // Gunakan response.message jika ada
            .then((result) => {
              if (result.value) {
                window.location.href = base_url + 'guru'; // Pastikan base_url terdefinisi global
              }
            });
        } else {
          if (response.errors) {
            $.each(response.errors, function (key, val) {
              var fieldElement = $('[name="' + key + '"]');
              var formGroup = fieldElement.closest('.form-group');
              var helpBlock = fieldElement.nextAll('.help-block').eq(0);

              if (val) {
                // Jika ada pesan error
                formGroup.removeClass('has-success').addClass('has-error');
                helpBlock.text(val);
              } else {
                // Jika tidak ada pesan error (misal string kosong dari server)
                formGroup.removeClass('has-error').addClass('has-success'); // Atau hanya removeClass('has-error')
                helpBlock.text('');
              }
            });
          } else if (response.message) {
            // Jika ada pesan error umum dari controller
            Swal('Gagal', response.message, 'error');
          } else {
            Swal('Gagal', 'Terjadi kesalahan yang tidak diketahui.', 'error');
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        // Tambahkan error handling untuk AJAX
        btn.removeAttr('disabled').text(btnText);
        Swal(
          'Error Server',
          'Tidak dapat terhubung ke server: ' + textStatus,
          'error'
        );
        console.error(
          'AJAX Error: ',
          textStatus,
          errorThrown,
          jqXHR.responseText
        );
      },
    });
  });
});
