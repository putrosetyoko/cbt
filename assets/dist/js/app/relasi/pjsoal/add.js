$(document).ready(function () {
  if ($.fn.select2) {
    $('#id_tahun_ajaran_form').select2({
      placeholder: '-- Pilih Tahun Ajaran --',
      allowClear: true,
    });
    $('#mapel_id_form').select2({
      placeholder: '-- Pilih TA dulu --',
      allowClear: true,
      disabled: true,
    });
    $('#guru_id_form').select2({
      placeholder: '-- Pilih TA & Mapel dulu --',
      allowClear: true,
      disabled: true,
    });
  }

  var currentSelectedMapelId = null; // Untuk menyimpan mapel_id yang sedang dipilih
  var currentSelectedGuruId = null; // Untuk menyimpan guru_id yang mungkin sudah jadi PJ untuk mapel terpilih

  function loadMapelOptions(id_ta, selected_mapel_val = null) {
    var $selectMapel = $('#mapel_id_form');
    $selectMapel
      .empty()
      .append('<option value=""></option>')
      .prop('disabled', true)
      .trigger('change.select2');
    if (!id_ta) {
      $selectMapel.select2({ placeholder: '-- Pilih TA dulu --' });
      return;
    }
    $selectMapel.select2({ placeholder: 'Memuat mapel...' });
    $.ajax({
      url: base_url + 'pjsoal/get_mapel_available_for_pj/' + id_ta,
      type: 'GET',
      dataType: 'json',
      success: function (response) {
        $selectMapel.prop('disabled', false);
        if (response.status && response.data_mapel) {
          $.each(response.data_mapel, function (key, mapel) {
            $selectMapel.append(
              new Option(mapel.nama_mapel, mapel.id_mapel, false, false)
            );
          });
        }
        if (selected_mapel_val) $selectMapel.val(selected_mapel_val);
        $selectMapel.trigger('change.select2');
        $selectMapel.select2({
          placeholder: '-- Pilih Mata Pelajaran --',
          allowClear: true,
        });
      },
      error: function () {
        $selectMapel.select2({ placeholder: 'Error memuat mapel' });
      },
    });
  }

  function loadGuruOptions(id_ta, current_pj_guru_id = null) {
    var $selectGuru = $('#guru_id_form');
    $selectGuru
      .empty()
      .append('<option value=""></option>')
      .prop('disabled', true)
      .trigger('change.select2');
    if (!id_ta) {
      $selectGuru.select2({ placeholder: '-- Pilih TA dulu --' });
      return;
    }
    $selectGuru.select2({ placeholder: 'Memuat guru...' });
    $.ajax({
      url:
        base_url +
        'pjsoal/get_guru_available_for_pj/' +
        id_ta +
        (current_pj_guru_id ? '/' + current_pj_guru_id : ''),
      type: 'GET',
      dataType: 'json',
      success: function (response) {
        $selectGuru.prop('disabled', false);
        if (response.status && response.data_guru) {
          $.each(response.data_guru, function (key, guru) {
            $selectGuru.append(
              new Option(
                guru.nip + ' - ' + guru.nama_guru,
                guru.id_guru,
                false,
                false
              )
            );
          });
        }
        if (current_pj_guru_id) $selectGuru.val(current_pj_guru_id); // Pre-select jika ada
        $selectGuru.trigger('change.select2');
        $selectGuru.select2({
          placeholder: '-- Pilih Guru --',
          allowClear: true,
        });
      },
      error: function () {
        $selectGuru.select2({ placeholder: 'Error memuat guru' });
      },
    });
  }

  $('#id_tahun_ajaran_form').on('change', function () {
    var id_ta = $(this).val();
    currentSelectedMapelId = null;
    currentSelectedGuruId = null;
    $('#info_pj_sebelumnya').text('');
    loadMapelOptions(id_ta);
    $('#guru_id_form')
      .empty()
      .append('<option value=""></option>')
      .prop('disabled', true)
      .select2({ placeholder: '-- Pilih Mapel Dulu --' })
      .trigger('change.select2');
  });

  $('#mapel_id_form').on('change', function () {
    currentSelectedMapelId = $(this).val();
    var id_ta = $('#id_tahun_ajaran_form').val();
    $('#info_pj_sebelumnya').text('');
    $('#guru_id_form').val(null).trigger('change.select2'); // Reset pilihan guru

    if (currentSelectedMapelId && id_ta) {
      $.ajax({
        url: base_url + 'pjsoal/get_pj_for_mapel_ta',
        type: 'POST',
        data: {
          mapel_id: currentSelectedMapelId,
          id_tahun_ajaran: id_ta /*, csrf */,
        },
        dataType: 'json',
        success: function (res) {
          currentSelectedGuruId = null; // Reset
          if (res.status && res.pj_data) {
            $('#info_pj_sebelumnya').html(
              `<i class='fa fa-info-circle text-blue'></i> Mapel ini sudah memiliki PJ: <strong>${res.pj_data.nama_guru}</strong> (NIP: ${res.pj_data.nip}). Memilih guru baru akan menggantikannya.`
            );
            currentSelectedGuruId = res.pj_data.guru_id;
          }
          loadGuruOptions(id_ta, currentSelectedGuruId);
        },
        error: function () {
          loadGuruOptions(id_ta, null);
        },
      });
    } else {
      $('#guru_id_form')
        .empty()
        .append('<option value=""></option>')
        .prop('disabled', true)
        .select2({ placeholder: '-- Pilih Mapel Dulu --' })
        .trigger('change.select2');
    }
  });

  // Trigger change pada tahun ajaran jika sudah ada yang terpilih saat load
  if ($('#id_tahun_ajaran_form').val()) {
    $('#id_tahun_ajaran_form').trigger('change');
  }

  $('#formPJSoal').on('submit', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    var $form = $(this);
    var $submitBtn = $form.find('button[type="submit"]');
    var btnText = $submitBtn.html();
    $submitBtn
      .attr('disabled', 'disabled')
      .html('<i class="fa fa-spinner fa-spin"></i> Processing...');
    $('.help-block.text-danger').text('');
    $('.form-group').removeClass('has-error');

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function (response) {
        $submitBtn.removeAttr('disabled').html(btnText);
        if (response.status) {
          Swal.fire('Sukses!', response.message, 'success').then(() => {
            window.location.href = base_url + 'pjsoal';
          });
        } else {
          if (response.errors) {
            $.each(response.errors, function (key, value) {
              if (value) {
                $('#error_' + key).text(value);
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
      error: function () {
        $submitBtn.removeAttr('disabled').html(btnText);
        Swal.fire('Error Server!', 'Tidak dapat terhubung ke server.', 'error');
      },
    });
  });

  $('#resetBtnPJ').on('click', function () {
    $('#mapel_id_form')
      .empty()
      .append('<option value=""></option>')
      .prop('disabled', true)
      .select2({ placeholder: '-- Pilih TA dulu --' })
      .trigger('change.select2');
    $('#guru_id_form')
      .empty()
      .append('<option value=""></option>')
      .prop('disabled', true)
      .select2({ placeholder: '-- Pilih TA & Mapel dulu --' })
      .trigger('change.select2');
    $('#info_pj_sebelumnya').text('');
    $('#keterangan_form').val('');
    // Reset tahun ajaran ke pilihan default jika ada, atau ke kosong
    // $('#id_tahun_ajaran_form').val($('#id_tahun_ajaran_form option:first').val()).trigger('change.select2');
  });
});
