$(document).ready(function () {
  // Inisialisasi Select2
  if ($.fn.select2) {
    $('#mapel_id, #id_jenjang_target, #id_tahun_ajaran').select2({
      placeholder: '-- Pilih --',
    });
  }

  // Form submission AJAX untuk detail ujian
  $('#form-edit-ujian').on('submit', function (e) {
    e.preventDefault();
    let form = $(this);
    let submitButton = form.find('button[type="submit"]');
    let originalButtonText = submitButton.html();
    submitButton
      .html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...')
      .prop('disabled', true);
    $('.help-block').text('');

    $.ajax({
      url: form.attr('action'),
      type: 'POST',
      data:
        form.serialize() +
        '&<?= $this->security->get_csrf_token_name(); ?>=<?= $this->security->get_csrf_hash(); ?>',
      dataType: 'json',
      success: function (response) {
        submitButton.html(originalButtonText).prop('disabled', false);
        Swal.fire(
          response.status ? 'Sukses!' : 'Gagal!',
          response.message,
          response.status ? 'success' : 'error'
        );
        if (response.errors) {
          $.each(response.errors, function (key, value) {
            $('#' + key)
              .closest('.form-group')
              .find('.help-block')
              .text(value);
          });
        }
        if (response.status && response.redirect_url) {
          // Tidak redirect agar tetap di halaman edit soal
          // window.location.href = response.redirect_url;
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        submitButton.html(originalButtonText).prop('disabled', false);
        Swal.fire(
          'Error AJAX!',
          'Tidak dapat menghubungi server: ' + errorThrown,
          'error'
        );
      },
    });
  });

  // --- Logika JavaScript untuk manajemen soal ---
  const idUjian = $('#form-edit-ujian').data('id-ujian');

  // Fungsi untuk memuat soal yang tersedia di bank soal
  function loadBankSoalTersedia() {
    $.ajax({
      url: BASE_URL + 'ujian/get_soal_bank_for_ujian/' + idUjian,
      type: 'GET',
      success: function (response) {
        // Debug info yang lebih detail
        console.log('Response:', response);
        console.log('Debug Info:', response.debug_info);
        console.log('Status:', response.status);
        console.log('Message:', response.message);

        if (response.status) {
          if (response.data && response.data.length > 0) {
            let html =
              '<div class="table-responsive"><table class="table table-bordered table-striped table-hover">';
            html +=
              '<thead><tr><th width="5%"><input type="checkbox" id="check-all-bank-soal"></th>';
            html += '<th width="5%">No</th><th>Soal</th></tr></thead><tbody>';

            response.data.forEach((soal, index) => {
              html += `<tr>
                            <td><input type="checkbox" class="check-soal-bank" value="${
                              soal.id_soal
                            }"></td>
                            <td>${index + 1}</td>
                            <td>${soal.soal}</td>
                        </tr>`;
            });

            html += '</tbody></table></div>';
            $('#bank-soal-tersedia-container').html(html);
          } else {
            $('#bank-soal-tersedia-container').html(
              '<p>Tidak ada soal tersedia.</p>'
            );
          }
        } else {
          $('#bank-soal-tersedia-container').html(
            '<p class="text-danger">Error: ' +
              response.message +
              '</p>' +
              '<p>Debug Info: ' +
              JSON.stringify(response.debug_info) +
              '</p>'
          );
        }
      },
      error: function (xhr, status, error) {
        console.error('AJAX Error:', error);
        console.log('XHR Status:', xhr.status);
        console.log('XHR Response:', xhr.responseText);
        $('#bank-soal-tersedia-container').html(
          '<p class="text-danger">Gagal memuat data soal: ' + error + '</p>'
        );
      },
    });
  }

  // Fungsi untuk memuat soal yang sudah ada di ujian
  function loadSoalDiUjian() {
    $.ajax({
      url: `${BASE_URL}ujian/get_assigned_soal/${idUjian}`,
      type: 'GET',
      success: function (response) {
        if (response.status && response.data.length > 0) {
          let html =
            '<div class="table-responsive"><table class="table table-bordered table-striped table-hover">';
          html +=
            '<thead><tr><th width="5%">No</th><th>Soal</th><th width="10%">Aksi</th></tr></thead>';
          html += '<tbody id="sortable-soal">';

          response.data.forEach((soal, index) => {
            html += `<tr data-id="${soal.id_d_ujian_soal}">
                            <td>${index + 1}</td>
                            <td>${soal.soal}</td>
                            <td>
                                <button type="button" class="btn btn-xs btn-danger btn-hapus-soal" 
                                    data-id="${soal.id_d_ujian_soal}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>
                        </tr>`;
          });

          html += '</tbody></table></div>';
          $('#soal-di-ujian-container').html(html);

          // Update counter
          $('#jumlah-soal-di-ujian').text(response.data.length);

          // Inisialisasi sortable
          $('#sortable-soal').sortable({
            update: function (event, ui) {
              updateSoalOrder();
            },
          });
        } else {
          $('#soal-di-ujian-container').html(
            '<p>Belum ada soal ditambahkan.</p>'
          );
          $('#jumlah-soal-di-ujian').text('0');
        }
      },
    });
  }

  // Handler untuk checkbox "check all" di bank soal
  $(document).on('click', '#check-all-bank-soal', function () {
    $('.check-soal-bank').prop('checked', this.checked);
  });

  // Handler untuk tombol tambah soal
  $('#btn-tambah-soal-terpilih').click(function () {
    const selectedSoal = $('.check-soal-bank:checked')
      .map(function () {
        return this.value;
      })
      .get();

    if (selectedSoal.length === 0) {
      Swal.fire('Peringatan', 'Pilih soal terlebih dahulu!', 'warning');
      return;
    }

    $.ajax({
      url: BASE_URL + 'ujian/assign_soal_to_ujian',
      type: 'POST',
      data: {
        id_ujian: idUjian,
        soal_ids: selectedSoal,
        [CSRF_TOKEN_NAME]: CSRF_HASH,
      },
      success: function (response) {
        if (response.status) {
          Swal.fire('Sukses', response.message, 'success');
          loadSoalDiUjian();
          loadBankSoalTersedia(); // Reload untuk update yang tersedia
        } else {
          Swal.fire('Gagal', response.message, 'error');
        }
      },
    });
  });

  // Handler untuk tombol hapus soal
  $(document).on('click', '.btn-hapus-soal', function () {
    const idDUjianSoal = $(this).data('id');

    Swal.fire({
      title: 'Konfirmasi Hapus',
      text: 'Yakin ingin menghapus soal ini dari ujian?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Hapus!',
      cancelButtonText: 'Batal',
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: BASE_URL + 'ujian/remove_soal_from_ujian',
          type: 'POST',
          data: {
            id_ujian: idUjian,
            id_d_ujian_soal: idDUjianSoal,
            [CSRF_TOKEN_NAME]: CSRF_HASH,
          },
          success: function (response) {
            if (response.status) {
              Swal.fire('Sukses', response.message, 'success');
              loadSoalDiUjian();
              loadBankSoalTersedia();
            } else {
              Swal.fire('Gagal', response.message, 'error');
            }
          },
        });
      }
    });
  });

  // Fungsi untuk update urutan soal
  function updateSoalOrder() {
    const soalOrders = [];
    $('#sortable-soal tr').each(function (index) {
      soalOrders.push({
        id_d_ujian_soal: $(this).data('id'),
        nomor_urut: index + 1,
      });
    });

    $.ajax({
      url: BASE_URL + 'ujian/update_soal_order',
      type: 'POST',
      data: {
        id_ujian: idUjian,
        soal_orders: soalOrders,
        [CSRF_TOKEN_NAME]: CSRF_HASH,
      },
      success: function (response) {
        if (!response.status) {
          Swal.fire('Gagal', response.message, 'error');
        }
      },
    });
  }

  // Load data awal
  loadBankSoalTersedia();
  loadSoalDiUjian();
});
