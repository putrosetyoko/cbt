$(document).ready(function () {
  // Menyesuaikan selektor form ke '#formjenjang'
  $(
    'form#formjenjang input, form#formjenjang textarea, form#formjenjang select'
  ).on('change keyup', function () {
    $(this).closest('.form-group').removeClass('has-error');
    $('#error_' + $(this).attr('name')).text('');
  });

  $('form#formjenjang').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var btn = $(this).find('button[type="submit"]');
    var btnText = btn.html();
    btn
      .attr('disabled', 'disabled')
      .html('<i class="fa fa-spinner fa-spin"></i> Wait...');

    $('.help-block').text('');
    $('.form-group').removeClass('has-error');

    $.ajax({
      url: $(this).attr('action'),
      data: $(this).serialize(),
      method: 'POST',
      dataType: 'json',
      success: function (data) {
        btn.removeAttr('disabled').html(btnText);

        if (data.status) {
          Swal({
            title: 'Sukses',
            text: 'Data Jenjang Berhasil diperbarui.', // Pesan disesuaikan
            type: 'success',
          }).then((result) => {
            if (result.value) {
              window.location.href = base_url + 'jenjang'; // URL disesuaikan
            }
          });
        } else {
          if (data.errors) {
            $.each(data.errors, function (key, val) {
              if (val) {
                var errorHelpBlock = $('#error_' + key);
                errorHelpBlock.text(val).addClass('text-danger');
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
