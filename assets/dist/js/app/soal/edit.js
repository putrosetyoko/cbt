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

  $('.summernote').summernote({
    height: 200,
    toolbar: [
      ['style', ['style']],
      ['font', ['bold', 'italic', 'underline', 'clear']],
      ['fontname', ['fontname']],
      ['fontsize', ['fontsize']],
      ['color', ['color']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['table', ['table']],
      ['insert', ['link']],
      ['view', ['fullscreen', 'codeview']],
    ],
    callbacks: {
      onInit: function () {
        console.log('Summernote initialized');
      },
      onError: function (e) {
        console.error('Summernote error:', e);
      },
      // === TAMBAHAN UNTUK FIX PASTE DARI WORD ===
      onPaste: function (e) {
        var bufferText = (
          (e.originalEvent || e).clipboardData || window.clipboardData
        ).getData('Text');
        e.preventDefault(); // Mencegah paste default

        // Masukkan teks mentah ke editor, tanpa format HTML
        // Summernote akan menerapkan gaya defaultnya
        setTimeout(function () {
          document.execCommand('insertText', false, bufferText);
        }, 10);
      },
      // === AKHIR TAMBAHAN ===
    },
    // Tambahan untuk mengatur default font dan ukuran (jika Summernote mendukungnya secara langsung)
    // Ini mungkin perlu konfigurasi CSS atau di set programmatically setelah paste.
    // fontNames: ['Helvetica', 'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Merriweather', 'Times New Roman'],
    // fontSizes: ['8', '9', '10', '11', '12', '14', '16', '18', '20', '24', '30', '36', '48'],
    // defaultFontName: 'Helvetica', // Tidak semua versi Summernote support defaultFontName
    // defaultFontSize: '14px' // Tidak semua versi Summernote support defaultFontSize
  });

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
