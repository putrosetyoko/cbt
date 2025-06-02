$(document).ready(function () {
  // Inisialisasi Select2
  if ($.fn.select2) {
    $('#mapel_id_form, #id_jenjang_form, #jawaban_kunci_form').select2({
      placeholder: '-- Pilih --',
      allowClear: true,
    });
  }

  // Inisialisasi Summernote
  if ($.fn.summernote) {
    $('#soal_text_form').summernote({
      height: 150,
      toolbar: [
        /* ... toolbar lengkap ... */
      ],
    });
    $('.summernote_opsi').summernote({
      height: 75,
      toolbar: [
        /* ... toolbar simpel untuk opsi ... */
      ],
    });
  }

  // AJAX Submit Form Tambah Soal
  $('#formSoal').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var formData = new FormData(this);
    var $submitBtn = $('#submitBtnSoal');
    var btnText = $submitBtn.html();
    $submitBtn
      .attr('disabled', 'disabled')
      .html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    $('.help-block.text-danger').text(''); // Clear previous errors
    $('.form-group').removeClass('has-error');

    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: formData,
      dataType: 'json',
      contentType: false, // Penting untuk FormData
      processData: false, // Penting untuk FormData
      success: function (response) {
        $submitBtn.removeAttr('disabled').html(btnText);
        if (response.status) {
          Swal.fire(
            'Sukses!',
            response.message || 'Soal berhasil disimpan.',
            'success'
          ).then(() => {
            if (response.redirect) {
              window.location.href = response.redirect;
            } else {
              // Reset form jika tidak redirect atau biarkan user di halaman
              // $('#formSoal')[0].reset();
              // $('.summernote').summernote('reset');
              // $('.summernote_opsi').summernote('reset');
              // $('.select2').val(null).trigger('change');
              window.location.href = base_url + 'soal'; // Default redirect
            }
          });
        } else {
          if (response.errors) {
            $.each(response.errors, function (key, value) {
              if (value) {
                $('#error_' + key).text(value);
                // Summernote error handling bisa tricky, biasanya di atas/bawah editornya
                if (key === 'soal')
                  $('#soal_text_form')
                    .closest('.form-group')
                    .addClass('has-error');
                else if (key.startsWith('jawaban_'))
                  $('#' + key + '_form')
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
            response.message ||
              'Terjadi kesalahan validasi. Periksa kembali inputan Anda.',
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
        console.error('Form Soal Add Error:', jqXHR.responseText);
      },
    });
  });
});
