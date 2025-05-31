$(document).ready(function () {
  if ($.fn.select2) {
    $('#id_tahun_ajaran, #guru_id, #mapel_id, #kelas_id').select2({
      placeholder: '-- Pilih --',
      allowClear: true,
    });
  }
  $('#id_tahun_ajaran').focus();

  $('#formPenugasanGuru').on('submit', function (e) {
    // Asumsi ID form sama
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
      url: $form.attr('action'), // Ke penugasanguru/update
      type: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function (response) {
        $submitBtn.removeAttr('disabled').html(btnText);
        if (response.status) {
          Swal.fire(
            'Sukses!',
            response.message || 'Penugasan guru berhasil diperbarui.',
            'success'
          ).then(() => {
            window.location.href = base_url + 'penugasanguru';
          });
        } else {
          if (response.errors) {
            $.each(response.errors, function (key, value) {
              if (value) {
                $('#error_' + key)
                  .text(value)
                  .addClass('text-danger');
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
        Swal.fire(
          'Error Server!',
          'Tidak dapat terhubung ke server: ' + textStatus,
          'error'
        );
      },
    });
  });
});
