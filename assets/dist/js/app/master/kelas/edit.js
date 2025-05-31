$(document).ready(function () {
  // Menargetkan input dan select di dalam form
  $('form#kelas input, form#kelas select').on('change', function () {
    $(this).closest('.form-group').removeClass('has-error');
    $(this).next().next().text('');
  });

  $('form#kelas').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var btn = $('#submit'); // Tombol submit Anda memiliki ID 'submit'
    var btnText = btn.text();
    btn.attr('disabled', 'disabled').text('Wait...');

    $('.help-block').text('');
    $('.form-group').removeClass('has-error');

    $.ajax({
      url: $(this).attr('action'),
      data: $(this).serialize(),
      method: 'POST',
      dataType: 'json',
      success: function (data) {
        btn.removeAttr('disabled').text(btnText);
        if (data.status) {
          Swal({
            title: 'Sukses',
            text: data.message || 'Data Kelas Berhasil diperbarui.',
            type: 'success',
          }).then((result) => {
            if (result.value) {
              window.location.href = base_url + 'kelas';
            }
          });
        } else {
          if (data.errors && Array.isArray(data.errors)) {
            for (let i = 0; i < data.errors.length; i++) {
              // Perbaiki: gunakan < bukan <=
              $.each(data.errors[i], function (key, val) {
                if (val) {
                  var fieldElement = $('[name="' + key + '"]');
                  fieldElement.closest('.form-group').addClass('has-error');
                  fieldElement.next().next().text(val).addClass('text-danger');
                }
              });
            }
            if (data.message && data.errors.length === 0) {
              Swal({ title: 'Gagal', text: data.message, type: 'error' });
            } else if (!data.message && data.errors.length > 0) {
              // Tidak perlu swal jika sudah ada error per field
            } else {
              Swal({
                title: 'Gagal',
                text: data.message || 'Terjadi kesalahan validasi.',
                type: 'error',
              });
            }
          } else if (data.message) {
            Swal({ title: 'Gagal', text: data.message, type: 'error' });
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
        btn.removeAttr('disabled').text(btnText);
        Swal({
          title: 'Error Server',
          text: 'Terjadi kesalahan pada server: ' + textStatus,
          type: 'error',
        });
        console.error('AJAX Error: ', jqXHR.responseText);
      },
    });
  });
});
