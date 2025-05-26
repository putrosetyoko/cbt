// Fungsi load_kelas dan load_jurusan dihapus karena Jurusan tidak lagi relevan untuk Siswa.
// Kelas akan diisi langsung dari data yang diterima controller.

$(document).ready(function () {
  ajaxcsrf();

  $('form#siswa input, form#siswa select').on('change', function () {
    // Ubah form#mahasiswa ke form#siswa
    $(this).closest('.form-group').removeClass('has-error has-success');
    $(this).nextAll('.help-block').eq(0).text('');
  });

  $('[name="jenis_kelamin"]').on('change', function () {
    $(this).parent().nextAll('.help-block').eq(0).text('');
  });

  $('form#siswa').on('submit', function (e) {
    // Ubah form#mahasiswa ke form#siswa
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
