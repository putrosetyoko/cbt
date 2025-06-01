$(document).ready(function () {
  // Inisialisasi Select2
  $('.select2').select2({
    width: '100%',
    theme: 'bootstrap',
  });

  $('#formPenugasanGuru').on('submit', function (e) {
    e.preventDefault();

    // Debug form data
    const formEl = $(this)[0];
    const formData = new FormData(formEl);
    console.log('Form elements:', formEl.elements);
    console.log('id_gmka value:', formData.get('id_gmka'));

    $.ajax({
      url: $(this).attr('action'),
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      beforeSend: function () {
        $('#submitBtn')
          .attr('disabled', true)
          .html('<i class="fa fa-spin fa-spinner"></i> Processing...');
      },
      success: function (response) {
        if (response.status) {
          Swal.fire({
            title: 'Berhasil',
            text: response.message,
            type: 'success',
            showCancelButton: false,
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK',
          }).then((result) => {
            if (result.value) {
              window.location.href = base_url + 'penugasanguru';
            }
          });
        } else {
          Swal.fire({
            title: 'Gagal',
            text: response.message || 'Terjadi kesalahan',
            icon: 'error',
          });
        }
      },
      error: function (xhr, status, error) {
        console.error('AJAX Error:', xhr.responseText);
        Swal.fire('Error', 'Terjadi kesalahan pada server', 'error');
      },
      complete: function () {
        $('#submitBtn')
          .attr('disabled', false)
          .html('<i class="fa fa-save"></i> Simpan');
      },
    });
  });
});
