$(document).ready(function () {
  // Inisialisasi Select2 untuk field kelas
  if ($.fn.select2) {
    $('#kelas_id').select2({
      placeholder: '-- Pilih Kelas Baru --',
      allowClear: true,
    });
  }

  // Submit form Edit
  $('#formSiswaKelas').on('submit', function (e) {
    // Asumsi ID form adalah formSiswaKelas
    e.preventDefault();
    e.stopImmediatePropagation();

    var $form = $(this);
    var $submitBtn = $form.find('button[type="submit"]'); // Atau #submitBtn jika ID spesifik
    var btnText = $submitBtn.html();
    $submitBtn
      .attr('disabled', 'disabled')
      .html('<i class="fa fa-spinner fa-spin"></i> Processing...');

    $('.help-block.text-danger').text('');
    $('.form-group').removeClass('has-error');

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function (response) {
        $submitBtn.removeAttr('disabled').html(btnText);
        if (response.status) {
          Swal.fire(
            'Sukses!',
            response.message || 'Data penempatan Siswa berhasil diperbarui.',
            'success'
          ).then(() => {
            window.location.href = base_url + 'siswakelas';
          });
        } else {
          if (response.errors) {
            $.each(response.errors, function (key, value) {
              if (value) {
                var errorElement = $('#error_' + key);
                errorElement.text(value).addClass('text-danger');
                $('[name="' + key + '"], #' + key)
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
        console.error(
          'Form Edit SiswaKelas Error:',
          textStatus,
          errorThrown,
          jqXHR.responseText
        );
      },
    });
  });

  // Fokus ke input pertama yang bisa diedit saat halaman dimuat (opsional)
  $('#kelas_id').focus();
});
