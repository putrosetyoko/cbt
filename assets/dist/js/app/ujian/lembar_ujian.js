// File: assets/dist/js/app/ujian/lembar_ujian.js

// Variabel global yang diharapkan sudah didefinisikan di view PHP:
// var base_url; // Didefinisikan di _templates/topnav/_header.php
// const ID_H_UJIAN_ENC_GLOBAL;
// const JUMLAH_SOAL_TOTAL_GLOBAL;
// const JAWABAN_TERSIMPAN_GLOBAL;
// const WAKTU_HABIS_TIMESTAMP_GLOBAL;
// const CSRF_TOKEN_NAME_GLOBAL; // Diganti dari CSRF_TOKEN_NAME
// const CSRF_HASH_GLOBAL;       // Diganti dari CSRF_HASH

var currentSoalNomorDisplay = 1;
var totalSoalUjian;
var idHUjianEnc;
var jawabanSiswaInternal = {};
var autoSaveTimer;
var csrfTokenName; // Untuk menyimpan nama token CSRF
var csrfHash; // Untuk menyimpan hash CSRF

$(document).ready(function () {
  // Validasi konfigurasi
  if (typeof window.examConfig === 'undefined') {
    console.error('Exam configuration not found!');
    Swal.fire('Error', 'Konfigurasi ujian tidak ditemukan', 'error');
    return;
  }

  // Debug log
  console.log('Initializing exam with config:', window.examConfig);

  // Inisialisasi timer dan komponen lain
  startTimer();
  initializeNavigation();
}); // End $(document).ready

function initializeNavigation() {
  // Inisialisasi variabel global dari PHP
  let initSuccess = true;
  let missingVarsLog = [];

  // Extract variables from config
  const {
    base_url,
    ID_H_UJIAN_ENC_GLOBAL,
    JUMLAH_SOAL_TOTAL_GLOBAL,
    JAWABAN_TERSIMPAN_GLOBAL,
    WAKTU_HABIS_TIMESTAMP_GLOBAL,
    WAKTU_SELESAI,
    CSRF_TOKEN_NAME_GLOBAL,
    CSRF_HASH_GLOBAL,
  } = window.examConfig;

  // Validate required variables
  const requiredVars = [
    { value: base_url, name: 'base_url' },
    { value: ID_H_UJIAN_ENC_GLOBAL, name: 'ID_H_UJIAN_ENC_GLOBAL' },
    { value: JUMLAH_SOAL_TOTAL_GLOBAL, name: 'JUMLAH_SOAL_TOTAL_GLOBAL' },
    { value: JAWABAN_TERSIMPAN_GLOBAL, name: 'JAWABAN_TERSIMPAN_GLOBAL' },
    {
      value: WAKTU_HABIS_TIMESTAMP_GLOBAL,
      name: 'WAKTU_HABIS_TIMESTAMP_GLOBAL',
    },
    { value: WAKTU_SELESAI, name: 'WAKTU_SELESAI' },
    { value: CSRF_TOKEN_NAME_GLOBAL, name: 'CSRF_TOKEN_NAME_GLOBAL' },
    { value: CSRF_HASH_GLOBAL, name: 'CSRF_HASH_GLOBAL' },
  ];

  requiredVars.forEach(({ value, name }) => {
    if (typeof value === 'undefined' || value === null || value === '') {
      missingVarsLog.push(name);
      initSuccess = false;
    }
  });

  // Show error if initialization failed
  if (!initSuccess) {
    const errorMessage =
      'Variabel konfigurasi ujian berikut tidak lengkap: ' +
      missingVarsLog.join(', ');
    console.error(errorMessage);
    Swal.fire('Error', errorMessage, 'error');
    return;
  }

  // Initialize timer if all variables are present
  startTimer();

  totalSoalUjian = parseInt(JUMLAH_SOAL_TOTAL_GLOBAL) || 0;
  idHUjianEnc = ID_H_UJIAN_ENC_GLOBAL;

  try {
    if (
      typeof JAWABAN_TERSIMPAN_GLOBAL === 'object' &&
      JAWABAN_TERSIMPAN_GLOBAL !== null
    ) {
      jawabanSiswaInternal = JAWABAN_TERSIMPAN_GLOBAL;
    } else if (
      typeof JAWABAN_TERSIMPAN_GLOBAL === 'string' &&
      JAWABAN_TERSIMPAN_GLOBAL.trim() !== ''
    ) {
      jawabanSiswaInternal = JSON.parse(JAWABAN_TERSIMPAN_GLOBAL);
    } else {
      jawabanSiswaInternal = {};
    }
  } catch (e) {
    console.error(
      'Gagal parse JAWABAN_TERSIMPAN_GLOBAL:',
      e,
      JAWABAN_TERSIMPAN_GLOBAL
    );
    jawabanSiswaInternal = {};
  }

  updateSemuaNavigasiSoalStyles();

  var timerElement = $('#timer-ujian');
  if (timerElement.length && typeof sisawaktu === 'function') {
    // sisawaktu dari _footer.php
    let endTimeUnixTimestamp = parseInt(WAKTU_HABIS_TIMESTAMP_GLOBAL); // Menggunakan variabel global
    if (endTimeUnixTimestamp && endTimeUnixTimestamp > 0) {
      console.log(
        'Memulai timer dengan endTimeUnixTimestamp:',
        endTimeUnixTimestamp
      );
      sisawaktu(new Date(endTimeUnixTimestamp * 1000));
    } else {
      console.error(
        'Data WAKTU_HABIS_TIMESTAMP_GLOBAL tidak valid atau 0. Nilai:',
        WAKTU_HABIS_TIMESTAMP_GLOBAL
      );
      timerElement.text('Error Timer');
    }
  } else {
    console.warn(
      'Elemen #timer-ujian atau fungsi sisawaktu(t) tidak ditemukan.'
    );
  }

  if (totalSoalUjian > 0) {
    showSoalPanel(currentSoalNomorDisplay);
  } else {
    $('#area-soal-ujian').html(
      '<p class="text-center text-danger">Tidak ada soal yang dimuat untuk ujian ini.</p>'
    );
    $(
      '#btn-prev-soal, #btn-next-soal, #btn-ragu-ragu, #btn-selesai-ujian'
    ).hide();
  }
  updateTombolNavigasiUtama();
  updateTombolRaguRaguUtama();

  $('#panel-navigasi-soal').on('click', '.btn-soal-nav', function () {
    let nomorSoal = parseInt($(this).data('nomor'));
    currentSoalNomorDisplay = nomorSoal;
    showSoalPanel(currentSoalNomorDisplay);
  });

  $('#btn-prev-soal').on('click', function () {
    if (currentSoalNomorDisplay > 1) {
      currentSoalNomorDisplay--;
      showSoalPanel(currentSoalNomorDisplay);
    }
  });
  $('#btn-next-soal').on('click', function () {
    if (currentSoalNomorDisplay < totalSoalUjian) {
      currentSoalNomorDisplay++;
      showSoalPanel(currentSoalNomorDisplay);
    }
  });

  $('#area-soal-ujian').on('change', 'input[type="radio"]', function () {
    let panelSoalAktif = $('.panel-soal:visible');
    let idSoal = panelSoalAktif.data('id-soal');
    let jawabanDipilih = $(this).val();

    if (!jawabanSiswaInternal[idSoal]) {
      jawabanSiswaInternal[idSoal] = { j: '', r: 'N' };
    }
    jawabanSiswaInternal[idSoal].j = jawabanDipilih;

    updateNavigasiSoalSingle(currentSoalNomorDisplay, idSoal);
    updateTombolRaguRaguUtama();
    triggerAutoSave(idSoal, jawabanDipilih, jawabanSiswaInternal[idSoal].r);
  });

  $('#btn-ragu-ragu').on('click', function () {
    let panelSoalAktif = $('.panel-soal:visible');
    if (!panelSoalAktif.length) return;
    let idSoalAktif = panelSoalAktif.data('id-soal');

    if (!jawabanSiswaInternal[idSoalAktif]) {
      jawabanSiswaInternal[idSoalAktif] = { j: '', r: 'N' };
    }
    jawabanSiswaInternal[idSoalAktif].r =
      jawabanSiswaInternal[idSoalAktif].r === 'N' ? 'Y' : 'N';

    updateNavigasiSoalSingle(currentSoalNomorDisplay, idSoalAktif);
    updateTombolRaguRaguUtama();
    triggerAutoSave(
      idSoalAktif,
      jawabanSiswaInternal[idSoalAktif].j,
      jawabanSiswaInternal[idSoalAktif].r
    );
  });

  $('#btn-selesai-ujian').on('click', function () {
    konfirmasiDanSelesaikanUjian();
  });
} // End $(document).ready

function showSoalPanel(nomorSoal) {
  // ... (fungsi sama seperti sebelumnya)
  if (totalSoalUjian <= 0) return;
  currentSoalNomorDisplay = parseInt(nomorSoal);
  if (currentSoalNomorDisplay < 1) currentSoalNomorDisplay = 1;
  if (currentSoalNomorDisplay > totalSoalUjian)
    currentSoalNomorDisplay = totalSoalUjian;

  $('.panel-soal').hide();
  $('#soal-' + currentSoalNomorDisplay).show();
  $('#display-nomor-soal').text(currentSoalNomorDisplay);

  $('#panel-navigasi-soal .btn-soal-nav').removeClass('active');
  $(
    '#panel-navigasi-soal .btn-soal-nav[data-nomor="' +
      currentSoalNomorDisplay +
      '"]'
  ).addClass('active');

  updateTombolNavigasiUtama();
  updateTombolRaguRaguUtama();
}

function updateTombolNavigasiUtama() {
  // ... (fungsi sama seperti sebelumnya)
  if (totalSoalUjian <= 0) {
    $('#btn-prev-soal, #btn-next-soal, #btn-ragu-ragu').hide();
    return;
  }
  $('#btn-prev-soal').toggle(currentSoalNomorDisplay > 1);
  $('#btn-next-soal').toggle(currentSoalNomorDisplay < totalSoalUjian);
}

function updateTombolRaguRaguUtama() {
  // ... (fungsi sama seperti sebelumnya)
  let panelSoalAktif = $('.panel-soal:visible');
  if (!panelSoalAktif.length) return;
  let idSoalAktif = panelSoalAktif.data('id-soal');
  let isRagu =
    jawabanSiswaInternal[idSoalAktif] &&
    jawabanSiswaInternal[idSoalAktif].r === 'Y';

  if (isRagu) {
    $('#btn-ragu-ragu')
      .removeClass('btn-warning')
      .addClass('btn-info')
      .html('<i class="fa fa-check-circle"></i> <span>Sudah Yakin</span>');
  } else {
    $('#btn-ragu-ragu')
      .removeClass('btn-info')
      .addClass('btn-warning')
      .html('<i class="fa fa-question-circle"></i> <span>Ragu-ragu</span>');
  }
}

function updateNavigasiSoalSingle(nomorDisplay, idSoal) {
  // ... (fungsi sama seperti sebelumnya)
  let navButton = $(
    '#panel-navigasi-soal .btn-soal-nav[data-nomor="' + nomorDisplay + '"]'
  );
  if (!navButton.length) return;
  navButton.removeClass('btn-default btn-success btn-warning active');
  if (currentSoalNomorDisplay == nomorDisplay) {
    navButton.addClass('active');
  }
  if (jawabanSiswaInternal[idSoal]) {
    if (jawabanSiswaInternal[idSoal].j !== '') {
      navButton.addClass(
        jawabanSiswaInternal[idSoal].r === 'Y' ? 'btn-warning' : 'btn-success'
      );
    } else if (jawabanSiswaInternal[idSoal].r === 'Y') {
      navButton.addClass('btn-warning');
    } else {
      navButton.addClass('btn-default');
    }
  } else {
    navButton.addClass('btn-default');
  }
}

function updateSemuaNavigasiSoalStyles() {
  // ... (fungsi sama seperti sebelumnya)
  if (totalSoalUjian > 0) {
    for (let i = 1; i <= totalSoalUjian; i++) {
      let idSoalPadaNav = $(
        '#panel-navigasi-soal .btn-soal-nav[data-nomor="' + i + '"]'
      ).data('id-soal');
      if (idSoalPadaNav) {
        updateNavigasiSoalSingle(i, idSoalPadaNav);
      }
    }
  }
}

function triggerAutoSave(idSoal, jawaban, raguStatus) {
  clearTimeout(autoSaveTimer);
  autoSaveTimer = setTimeout(function () {
    simpanJawabanAjax(idSoal, jawaban, raguStatus);
  }, 700);
}

function simpanJawabanAjax(idSoal, jawaban, raguStatus) {
  // Check if examConfig exists
  if (typeof window.examConfig === 'undefined') {
    console.error('Exam configuration not found');
    return;
  }

  // Extract required variables from examConfig
  const {
    base_url,
    ID_H_UJIAN_ENC_GLOBAL,
    CSRF_TOKEN_NAME_GLOBAL,
    CSRF_HASH_GLOBAL,
  } = window.examConfig;

  // Debug log
  console.log('Debug simpanJawabanAjax:', {
    id_h_ujian_enc: ID_H_UJIAN_ENC_GLOBAL,
    id_soal: idSoal,
    jawaban: jawaban,
    ragu_ragu: raguStatus,
  });

  // Prepare data
  const formData = {
    id_h_ujian_enc: ID_H_UJIAN_ENC_GLOBAL,
    id_soal: idSoal,
    jawaban: jawaban,
    ragu_ragu: raguStatus,
    [CSRF_TOKEN_NAME_GLOBAL]: CSRF_HASH_GLOBAL,
  };

  // Show loading indicator
  const btnRagu = $('#btn-ragu-ragu');
  const originalBtnText = btnRagu.html();
  btnRagu.prop('disabled', true);

  $.ajax({
    url: `${base_url}ujian/simpan_jawaban_ajax`,
    type: 'POST',
    data: formData,
    dataType: 'json',
    success: function (response) {
      if (response.status) {
        console.log('Jawaban berhasil disimpan');
        // Update CSRF hash
        if (response.csrf_hash_new) {
          window.examConfig.CSRF_HASH_GLOBAL = response.csrf_hash_new;
        }
      } else {
        console.error('Gagal simpan jawaban:', response);
        // Tampilkan pesan error jika perlu
        if (response.message === 'ID hasil ujian tidak valid') {
          Swal.fire({
            title: 'Error',
            text: 'Sesi ujian tidak valid. Halaman akan dimuat ulang.',
            icon: 'error',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: true,
          }).then(() => {
            window.location.reload();
          });
        }
      }
    },
    error: function (xhr, status, error) {
      console.error('AJAX Error:', { xhr, status, error });
      Swal.fire({
        title: 'Error',
        text: 'Gagal menyimpan jawaban. Silakan coba lagi.',
        icon: 'error',
        timer: 2000,
      });
    },
    complete: function () {
      btnRagu.prop('disabled', false).html(originalBtnText);
    },
  });
}

function konfirmasiDanSelesaikanUjian() {
  // Count unanswered and doubtful questions
  let belumDijawabCount = 0;
  let raguRaguCount = 0;

  for (let i = 1; i <= totalSoalUjian; i++) {
    let panelSoal = $('#soal-' + i);
    if (panelSoal.length) {
      let idSoal = panelSoal.data('id-soal');
      let jawabanData = jawabanSiswaInternal[idSoal];

      if (!jawabanData || jawabanData.j === '') {
        belumDijawabCount++;
      }
      if (jawabanData && jawabanData.r === 'Y') {
        raguRaguCount++;
      }
    }
  }

  let pesanKonfirmasi =
    'Anda yakin ingin menyelesaikan dan mengirimkan semua jawaban?';
  if (belumDijawabCount > 0) {
    pesanKonfirmasi += `<br><strong style='color:red;'>Perhatian: Ada ${belumDijawabCount} soal yang belum Anda jawab.</strong>`;
  }
  if (raguRaguCount > 0) {
    pesanKonfirmasi += `<br><strong style='color:orange;'>Ada ${raguRaguCount} soal yang masih ditandai ragu-ragu.</strong>`;
  }
  pesanKonfirmasi +=
    '<br><br>Setelah ujian diselesaikan, Anda tidak dapat mengubah jawaban Anda lagi.';

  return Swal.fire({
    title: 'Konfirmasi Selesai Ujian',
    html: pesanKonfirmasi,
    type: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, Selesaikan!',
    cancelButtonText: 'Batal',
    allowOutsideClick: false,
    showLoaderOnConfirm: true,
    preConfirm: () => {
      return prosesSelesaikanUjianFinal();
    },
  });
}

function prosesSelesaikanUjianFinal() {
  return new Promise((resolve, reject) => {
    const {
      base_url,
      ID_H_UJIAN_ENC_GLOBAL,
      CSRF_TOKEN_NAME_GLOBAL,
      CSRF_HASH_GLOBAL,
    } = window.examConfig;

    // Disable buttons
    $('#btn-selesai-ujian')
      .prop('disabled', true)
      .html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');
    $('.btn-nav-soal, #btn-ragu-ragu').prop('disabled', true);

    // Prepare final answers
    let jawabanAkhirBatch = {};
    for (let i = 1; i <= totalSoalUjian; i++) {
      let panelSoal = $('#soal-' + i);
      if (panelSoal.length) {
        let idSoal = panelSoal.data('id-soal');
        jawabanAkhirBatch[idSoal] = {
          j: $(`input[name="jawaban_soal_${idSoal}"]:checked`).val() || '',
          r: jawabanSiswaInternal[idSoal]?.r || 'N',
        };
      }
    }

    // Send AJAX request
    $.ajax({
      url: base_url + 'ujian/selesaikan_ujian',
      type: 'POST',
      data: {
        id_h_ujian_enc: ID_H_UJIAN_ENC_GLOBAL,
        jawaban_akhir_batch: JSON.stringify(jawabanAkhirBatch),
        [CSRF_TOKEN_NAME_GLOBAL]: CSRF_HASH_GLOBAL,
      },
      dataType: 'json',
      success: function (response) {
        if (response.status) {
          resolve({
            status: true,
            message: 'Ujian berhasil diselesaikan',
            redirect_url: base_url + 'ujian/list_ujian_siswa',
          });
        } else {
          reject(new Error(response.message || 'Gagal menyelesaikan ujian'));
        }
      },
      error: function (xhr, status, error) {
        reject(new Error('Gagal menghubungi server: ' + error));
      },
    });
  })
    .then((result) => {
      // Success handling
      return Swal.fire({
        title: 'Berhasil!',
        text: result.message,
        type: 'success',
        timer: 2000,
        showConfirmButton: false,
        allowOutsideClick: false,
      }).then(() => {
        window.location.href = result.redirect_url;
      });
    })
    .catch((error) => {
      // Error handling
      $('#btn-selesai-ujian').prop('disabled', false).html('Selesai Ujian');
      $('.btn-nav-soal, #btn-ragu-ragu').prop('disabled', false);

      return Swal.fire({
        title: 'Error!',
        text: error.message,
        type: 'error',
      });
    });
}

function waktuHabis() {
  // ... (fungsi sama seperti sebelumnya)
  console.log('Fungsi waktuHabis() dipanggil.');
  if (
    $('#form-lembar-ujian').length > 0 &&
    !$('#btn-selesai-ujian').is(':disabled')
  ) {
    Swal.fire({
      title: 'Waktu Habis!',
      text: 'Waktu pengerjaan ujian Anda telah berakhir. Jawaban Anda akan otomatis dikirim.',
      type: 'warning',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      timer: 3500,
    }).then(() => {
      console.log(
        'Timer SweetAlert waktuHabis selesai, memanggil prosesSelesaikanUjianFinal().'
      );
      prosesSelesaikanUjianFinal();
    });
  } else {
    console.log(
      'Form lembar ujian tidak ditemukan atau tombol selesai sudah disabled saat waktuHabis().'
    );
  }
}

// Tambahkan fungsi timer
function startTimer() {
  const timerElement = $('#timer-ujian');
  if (!timerElement.length) {
    console.warn('Timer element not found');
    return;
  }

  // Gunakan waktu terlambat dari m_ujian
  const waktuSelesai = new Date(window.examConfig.WAKTU_SELESAI).getTime();

  console.log('Timer started with end time:', window.examConfig.WAKTU_SELESAI);

  const timer = setInterval(function () {
    const now = new Date().getTime();
    const distance = waktuSelesai - now;

    if (distance <= 0) {
      clearInterval(timer);
      timerElement.text('00:00:00');
      waktuHabis();
      return;
    }

    // Hitung jam, menit, detik
    const hours = Math.floor(
      (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
    );
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    // Update tampilan timer
    timerElement.text(
      String(hours).padStart(2, '0') +
        ':' +
        String(minutes).padStart(2, '0') +
        ':' +
        String(seconds).padStart(2, '0')
    );
  }, 1000);
}
