$(document).ready(function () {
  ajaxcsrf();

  // Karena Jurusan dihapus dari data Siswa, fungsi load_jurusan dan ketergantungan kelas pada jurusan dihapus.
  // Asumsi: Dropdown kelas akan diisi langsung dari data yang dikirim controller.

  $('form#siswa input, form#siswa select').on('change', function () {
    $(this).closest('.form-group').removeClass('has-error has-success');
    $(this).nextAll('.help-block').eq(0).text('');
  });

  $('[name="jenis_kelamin"]').on('change', function () {
    $(this).parent().nextAll('.help-block').eq(0).text('');
  });

  $('form#siswa').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var btn = $('#submit');
    btn.attr('disabled', 'disabled').text('Wait...');

    $.ajax({
      url: $(this).attr('action'),
      data: $(this).serialize(),
      type: 'POST',
      success: function (data) {
        btn.removeAttr('disabled').text('Simpan');
        if (data.status) {
          Swal({
            title: 'Sukses',
            text: 'Data Berhasil disimpan',
            type: 'success',
          }).then((result) => {
            if (result.value) {
              window.location.href = base_url + 'siswa'; // Ubah mahasiswa ke siswa
            }
          });
        } else {
          console.log(data.errors);
          $.each(data.errors, function (key, value) {
            // Perbarui agar NISN yang divalidasi, bukan NIM. Email juga sudah dihapus.
            $('[name="' + key + '"]')
              .nextAll('.help-block')
              .eq(0)
              .text(value);
            $('[name="' + key + '"]')
              .closest('.form-group')
              .addClass('has-error');
            if (value == '') {
              $('[name="' + key + '"]')
                .nextAll('.help-block')
                .eq(0)
                .text('');
              $('[name="' + key + '"]')
                .closest('.form-group')
                .removeClass('has-error')
                .addClass('has-success');
            }
          });
        }
      },
    });
  });
});
