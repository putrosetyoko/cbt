$(document).ready(function () {
  // Inisialisasi Select2
  if ($.fn.select2) {
    $('#mapel_id, #id_jenjang_target, #id_tahun_ajaran').select2({
      placeholder: '-- Pilih --',
      // allowClear: true
    });
  }

  // Form submission AJAX
  $('#form-add-ujian').on('submit', function (e) {
    e.preventDefault();
    let form = $(this);
    let submitButton = form.find('button[type="submit"]');
    let originalButtonText = submitButton.html();
    submitButton
      .html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...')
      .prop('disabled', true);
    $('.help-block').text(''); // Clear previous errors

    $.ajax({
      url: form.attr('action'),
      type: 'POST',
      data:
        form.serialize() +
        '&<?= $this->security->get_csrf_token_name(); ?>=<?= $this->security->get_csrf_hash(); ?>', // CSRF
      dataType: 'json',
      success: function (response) {
        submitButton.html(originalButtonText).prop('disabled', false);
        if (response.status) {
          Swal.fire('Sukses!', response.message, 'success').then(function () {
            if (response.redirect_url) {
              window.location.href = response.redirect_url;
            } else {
              // Opsi: reset form atau kembali ke daftar
              // form[0].reset();
              // $('.select2').val(null).trigger('change');
              window.location.href = BASE_URL + 'ujian';
            }
          });
        } else {
          Swal.fire(
            'Gagal!',
            response.message || 'Terjadi kesalahan saat validasi.',
            'error'
          );
          if (response.errors) {
            $.each(response.errors, function (key, value) {
              $('#' + key)
                .closest('.form-group')
                .find('.help-block')
                .text(value);
            });
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        submitButton.html(originalButtonText).prop('disabled', false);
        Swal.fire(
          'Error AJAX!',
          'Tidak dapat menghubungi server: ' + errorThrown,
          'error'
        );
      },
    });
  });
});
