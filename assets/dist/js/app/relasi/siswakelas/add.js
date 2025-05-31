$(document).ready(function () {
  // Inisialisasi Select2
  if ($.fn.select2) {
    $('#id_tahun_ajaran, #kelas_id').select2({
      placeholder: '-- Pilih --',
      allowClear: true,
    });
    $('#siswa_ids').select2({
      placeholder: 'Pilih Tahun Ajaran dulu.',
      allowClear: true,
      disabled: true, // Awalnya disable sampai tahun ajaran dipilih
    });
  }

  // Load siswa ketika Tahun Ajaran di form Add dipilih
  $('#id_tahun_ajaran').on('change', function () {
    var id_ta = $(this).val();
    var $selectSiswa = $('#siswa_ids');

    // Reset dan disable pilihan siswa
    $selectSiswa
      .empty()
      .append('<option value=""></option>')
      .trigger('change')
      .prop('disabled', true);
    // Perbarui placeholder saat loading atau jika tidak ada pilihan
    $selectSiswa.select2({
      placeholder: 'Pilih Tahun Ajaran dulu',
      allowClear: true,
      disabled: true,
    });

    if (id_ta) {
      $selectSiswa.select2({
        placeholder: 'Memuat siswa...',
        allowClear: true,
        disabled: true,
      });
      $.ajax({
        url: base_url + 'siswakelas/get_siswa_available/' + id_ta, // Pastikan base_url terdefinisi
        type: 'GET',
        dataType: 'json',
        success: function (response) {
          if (
            response.status &&
            response.data_siswa &&
            response.data_siswa.length > 0
          ) {
            $selectSiswa.prop('disabled', false);
            $.each(response.data_siswa, function (key, siswa) {
              var option = new Option(
                siswa.nisn + ' - ' + siswa.nama,
                siswa.id_siswa,
                false,
                false
              );
              $selectSiswa.append(option);
            });
            $selectSiswa.trigger('change'); // Update Select2
            $selectSiswa.select2({
              placeholder: 'Pilih satu atau beberapa Siswa',
              allowClear: true,
              disabled: false,
            });
          } else {
            $selectSiswa.select2({
              placeholder:
                response.data_siswa && response.data_siswa.length === 0
                  ? 'Tidak ada Siswa tersedia'
                  : 'Error memuat siswa',
              allowClear: true,
              disabled: true,
            });
            if (response.message)
              console.error('Error get_siswa_available:', response.message);
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          $selectSiswa.select2({
            placeholder: 'Error memuat siswa',
            allowClear: true,
            disabled: true,
          });
          console.error(
            'AJAX Error get_siswa_available:',
            textStatus,
            errorThrown,
            jqXHR.responseText
          );
        },
      });
    }
  });

  // Submit form Add
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

    $('.help-block.text-danger').text(''); // Bersihkan pesan error spesifik
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
            response.message || 'Data penempatan Siswa berhasil disimpan.',
            'success'
          ) // Ganti 'type' dengan 'icon' jika SweetAlert2 v9+
            .then(() => {
              window.location.href = base_url + 'siswakelas'; // Redirect ke halaman daftar
            });
        } else {
          if (response.errors) {
            $.each(response.errors, function (key, value) {
              if (value) {
                var errorElement = $('#error_' + key);
                errorElement.text(value).addClass('text-danger'); // Pastikan ada class .text-danger
                // Target input atau select berdasarkan name atau id
                var fieldElement = $(
                  '[name="' + key + '"], [name="' + key + '[]"], #' + key
                );
                fieldElement.closest('.form-group').addClass('has-error');
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
        console.error(
          'Form Add SiswaKelas Error:',
          textStatus,
          errorThrown,
          jqXHR.responseText
        );
      },
    });
  });
  // Fokus ke input pertama saat halaman dimuat (opsional)
  $('#id_tahun_ajaran').focus();
});
