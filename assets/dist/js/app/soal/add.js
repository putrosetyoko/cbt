$(document).ready(function () {
  // Inisialisasi Select2
  if ($.fn.select2) {
    $('#mapel_id_form, #id_jenjang_form, #jawaban_kunci_form').select2({
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

  // When file input changes
  ['a', 'b', 'c', 'd', 'e'].forEach(function (option) {
    $('#file_' + option + '_form').change(function () {
      var hasFile = $(this).val() !== '';
      var textArea = $('#jawaban_' + option + '_form');
      var errorText = $('#error_jawaban_' + option);

      if (hasFile) {
        // If file exists, remove required from textarea
        textArea.removeAttr('required');
        textArea.closest('.form-group').find('label span.text-danger').hide();
        errorText.text('');
      } else {
        // If no file, make textarea required
        textArea.attr('required', 'required');
        textArea.closest('.form-group').find('label span.text-danger').show();
      }
    });
  });

  // Form validation before submit
  $('#formSoal').on('submit', function (e) {
    var isValid = true;

    ['a', 'b', 'c', 'd', 'e'].forEach(function (option) {
      var hasFile = $('#file_' + option + '_form').val() !== '';
      var hasText =
        $('#jawaban_' + option + '_form')
          .val()
          .trim() !== '';

      // Reset error message
      $('#error_jawaban_' + option).text('');

      // Check if at least one exists
      if (!hasFile && !hasText) {
        isValid = false;
        $('#error_jawaban_' + option).text(
          'Opsi ' + option.toUpperCase() + ' harus diisi dengan teks atau file'
        );
      }
    });

    if (!isValid) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Validasi Gagal',
        text: 'Setiap opsi harus diisi dengan teks atau file',
      });
    }
  });
});
