$(document).ready(function () {
  if ($.fn.select2) {
    // Tahun Ajaran dan Mapel di form edit bersifat read-only, hanya Guru yang bisa diubah
    $('#guru_id_edit').select2({
      placeholder: '-- Pilih Guru --',
      allowClear: true,
    });
  }
  $('#guru_id_edit').focus();

  $('#formPJSoalEdit').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var $form = $(this);
    var $submitBtn = $form.find('button[type="submit"]');
    var btnText = $submitBtn.html();
    $submitBtn
      .attr('disabled', 'disabled')
      .html('<i class="fa fa-spinner fa-spin"></i> Processing...');
    $('.help-block.text-danger').text('');
    $('.form-group').removeClass('has-error');

    $.ajax({
      url: $form.attr('action'), // Ke pjsoal/update
      type: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function (response) {
        $submitBtn.removeAttr('disabled').html(btnText);
        if (response.status) {
          Swal.fire(
            'Sukses!',
            response.message || 'PJ Soal berhasil diperbarui.',
            'success'
          ).then(() => {
            window.location.href = base_url + 'pjsoal';
          });
        } else {
          if (response.errors) {
            $.each(response.errors, function (key, value) {
              if (value) {
                $('#error_' + key).text(value); // ID error harus ada di view edit
                $('[name="' + key + '"]')
                  .closest('.form-group')
                  .addClass('has-error');
              }
            });
          }
          Swal.fire(
            'Gagal!',
            response.message || 'Terjadi kesalahan validasi.',
            'error'
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        $submitBtn.removeAttr('disabled').html(btnText);
        Swal.fire('Error Server!', 'Tidak dapat terhubung ke server.', 'error');
        console.error('PJ Soal Edit Error:', jqXHR.responseText);
      },
    });
  });
});
