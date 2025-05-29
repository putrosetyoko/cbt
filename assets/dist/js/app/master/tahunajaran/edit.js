$(document).ready(function () {
  // Menyesuaikan selektor form ke '#formtahunajaran'
  $('form#formtahunajaran input, form#formtahunajaran select').on(
    'change',
    function () {
      $(this).closest('.form-group').removeClass('has-error');
      // Pesan error adalah elemen berikutnya (next sibling) dari input/select
      $(this).next('.help-block').text('');
    }
  );

  $('form#formtahunajaran').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var btn = $(this).find('button[type="submit"]'); // Menemukan tombol submit di dalam form
    var btnText = btn.html(); // Simpan teks asli tombol
    btn
      .attr('disabled', 'disabled')
      .html('<i class="fa fa-spinner fa-spin"></i> Wait...');

    $.ajax({
      url: $(this).attr('action'),
      data: $(this).serialize(),
      method: 'POST',
      dataType: 'json',
      success: function (data) {
        btn.removeAttr('disabled').html(btnText); // Kembalikan teks asli tombol

        if (data.status) {
          Swal({
            title: 'Sukses',
            text: 'Data Tahun Ajaran Berhasil diperbarui.',
            type: 'success',
          }).then((result) => {
            if (result.value) {
              window.location.href = base_url + 'tahunajaran';
            }
          });
        } else {
          if (data.errors) {
            // Loop melalui object errors (jika server mengembalikan object {nama_field: 'pesan'})
            // atau array errors (jika server mengembalikan array [{nama_field: 'pesan'}])
            if (Array.isArray(data.errors)) {
              for (let i = 0; i < data.errors.length; i++) {
                $.each(data.errors[i], function (key, val) {
                  var j = $('[name="' + key + '"]');
                  j.closest('.form-group').addClass('has-error');
                  // Menampilkan error pada elemen .help-block yang menjadi sibling berikutnya
                  j.next('.help-block').text(val);
                  if (val == '') {
                    j.closest('.form-group').removeClass('has-error');
                    j.next('.help-block').text('');
                  }
                });
              }
            } else {
              // Jika data.errors adalah objek tunggal (misal dari form_validation CodeIgniter)
              $.each(data.errors, function (key, val) {
                var j = $('[name="' + key + '"]');
                j.closest('.form-group').addClass('has-error');
                // Menampilkan error pada elemen .help-block yang menjadi sibling berikutnya
                j.next('.help-block').text(val);
                if (val == '') {
                  j.closest('.form-group').removeClass('has-error');
                  j.next('.help-block').text('');
                }
              });
            }
          } else if (data.message) {
            Swal({
              title: 'Gagal',
              text: data.message,
              type: 'error',
            });
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        btn.removeAttr('disabled').html(btnText); // Kembalikan teks asli tombol
        Swal({
          title: 'Error',
          text: 'Terjadi kesalahan saat mengirim data: ' + textStatus,
          type: 'error',
        });
        console.error('AJAX Error: ', textStatus, errorThrown);
        console.error('Response Text: ', jqXHR.responseText);
      },
    });
  });

  // Inisialisasi datepicker (jika belum ada di edit.php atau ingin dipusatkan di sini)
  // Jika sudah ada di edit.php, baris ini bisa dihilangkan dari edit_tahunajaran.js
  // $('.datepicker').datepicker({
  // format: 'yyyy-mm-dd',
  // autoclose: true,
  // todayHighlight: true
  // });
});
