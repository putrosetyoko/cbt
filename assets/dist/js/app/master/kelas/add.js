// Variabel banyak sudah didefinisikan di view add.php
// banyak = Number(banyak); // Baris ini bisa dihapus jika sudah ada di view

$(document).ready(function () {
  if (typeof banyak !== 'undefined') {
    // Cek jika variabel banyak ada
    banyak = Number(banyak);
    if (banyak < 1 || banyak > 50) {
      alert('Maksimum input 50');
      window.location = base_url + 'kelas'; // Pastikan base_url terdefinisi
    }
  }

  // Menargetkan input dan select di dalam form
  $('form#kelas input, form#kelas select').on('change', function () {
    $(this).closest('.form-group').removeClass('has-error');
    // Menargetkan elemen small.help-block yang merupakan sibling ketiga (setelah input dan span.d-none)
    $(this).next().next().text('');
  });

  $('form#kelas').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();

    var btn = $('#submit'); // Tombol submit Anda memiliki ID 'submit'
    var btnText = btn.text(); // Simpan teks asli tombol
    btn.attr('disabled', 'disabled').text('Wait...');

    $('.help-block').text(''); // Bersihkan error sebelumnya
    $('.form-group').removeClass('has-error');

    $.ajax({
      url: $(this).attr('action'),
      data: $(this).serialize(),
      method: 'POST',
      dataType: 'json', // Pastikan controller mengembalikan JSON
      success: function (data) {
        btn.removeAttr('disabled').text(btnText); // Kembalikan teks asli
        if (data.status) {
          Swal({
            title: 'Sukses',
            text: data.message || 'Data Kelas Berhasil disimpan', // Ambil pesan dari controller
            type: 'success',
          }).then((result) => {
            if (result.value) {
              window.location.href = base_url + 'kelas';
            }
          });
        } else {
          if (data.errors && Array.isArray(data.errors)) {
            // data.errors adalah array of objects [{ field_name: "error" }, ...]
            for (let i = 0; i < data.errors.length; i++) {
              // Perbaiki: gunakan < bukan <=
              $.each(data.errors[i], function (key, val) {
                if (val) {
                  // Hanya jika ada pesan error
                  var fieldElement = $('[name="' + key + '"]');
                  fieldElement.closest('.form-group').addClass('has-error');
                  // Menampilkan error pada <small> yang sesuai (sibling ketiga)
                  fieldElement.next().next().text(val).addClass('text-danger');
                }
              });
            }
            if (data.message && data.errors.length === 0) {
              // Pesan umum jika tidak ada error field spesifik
              Swal({ title: 'Gagal', text: data.message, type: 'error' });
            } else if (!data.message && data.errors.length > 0) {
              // Tidak perlu swal jika sudah ada error per field
            } else {
              Swal({
                title: 'Gagal',
                text: data.message || 'Terjadi kesalahan validasi.',
                type: 'error',
              });
            }
          } else if (data.message) {
            // Pesan error umum dari controller
            Swal({ title: 'Gagal', text: data.message, type: 'error' });
          } else {
            Swal({
              title: 'Gagal',
              text: 'Terjadi kesalahan yang tidak diketahui.',
              type: 'error',
            });
          }
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        btn.removeAttr('disabled').text(btnText);
        Swal({
          title: 'Error Server',
          text: 'Terjadi kesalahan pada server: ' + textStatus,
          type: 'error',
        });
        console.error('AJAX Error: ', jqXHR.responseText);
      },
    });
  });
});
