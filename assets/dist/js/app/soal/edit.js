$(document).ready(function () {
  // Inisialisasi Select2
  if ($.fn.select2) {
    $(
      '#mapel_id_form_edit, #id_jenjang_form_edit, #jawaban_kunci_form_edit'
    ).select2({
      placeholder: '-- Pilih --',
      allowClear: true,
    });
  }

  // Inisialisasi Summernote
  if ($.fn.summernote) {
    $('#soal_text_form_edit').summernote({
      height: 150,
      toolbar: [
        /* ... toolbar lengkap ... */
      ],
    });
    $('.summernote_opsi').summernote({
      // Ini akan mentarget semua opsi
      height: 75,
      toolbar: [
        /* ... toolbar simpel untuk opsi ... */
      ],
    });
  }

  // AJAX Submit Form Edit Soal
  $('#formSoalEdit').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var formData = new FormData(this); // FormData akan menghandle file dan checkbox 'hapus_file'
    var $submitBtn = $('#submitBtnSoalEdit');
    var btnText = $submitBtn.html();
    $submitBtn
      .attr('disabled', 'disabled')
      .html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    $('.help-block.text-danger').text('');
    $('.form-group').removeClass('has-error');

    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: formData,
      dataType: 'json',
      contentType: false,
      processData: false,
      success: function (response) {
        $submitBtn.removeAttr('disabled').html(btnText);
        if (response.status) {
          Swal.fire(
            'Sukses!',
            response.message || 'Soal berhasil diperbarui.',
            'success'
          ).then(() => {
            if (response.redirect) {
              window.location.href = response.redirect;
            } else {
              window.location.href = base_url + 'soal'; // Default redirect
            }
          });
        } else {
          if (response.errors) {
            $.each(response.errors, function (key, value) {
              if (value) {
                $('#error_' + key).text(value);
                if (key === 'soal')
                  $('#soal_text_form_edit')
                    .closest('.form-group')
                    .addClass('has-error');
                else if (key.startsWith('jawaban_'))
                  $('#' + key + '_form_edit')
                    .closest('.form-group')
                    .addClass('has-error');
                else
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
        console.error('Form Soal Edit Error:', jqXHR.responseText);
      },
    });
  });
});
