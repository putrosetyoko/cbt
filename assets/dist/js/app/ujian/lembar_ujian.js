// File: assets/dist/js/app/ujian/lembar_ujian.js

// Variabel global yang diharapkan sudah didefinisikan di view PHP (melalui window.examConfig)
// const base_url;
// const ID_H_UJIAN_ENC_GLOBAL;
// const JUMLAH_SOAL_TOTAL_GLOBAL;
// const JAWABAN_TERSIMPAN_GLOBAL; // Ini akan menjadi objek JS yang sudah di-parse
// const WAKTU_HABIS_TIMESTAMP_GLOBAL;
// const WAKTU_SELESAI;
// const CSRF_TOKEN_NAME_GLOBAL;
// const CSRF_HASH_GLOBAL;
// const enableAntiCheating;
// const maxCheatAttempts;
// const redirectUrlOnCheat;
// const confirmFinishMessage;
// const unansweredWarning;
// const doubtfulWarning;
// const exitFullscreenWarning;
// const tabChangeWarning;
// const cheatAttemptExceeded;
// const startTime;

var currentSoalNomorDisplay = 1;
var totalSoalUjian;
var idHUjianEnc;
var jawabanSiswaInternal = {}; // Ini akan diisi dari JAWABAN_TERSIMPAN_GLOBAL
var autoSaveTimer;
var csrfTokenName;
var csrfHash;

// === ANTI-CHEATING VARIABLES ===
let cheatAttempts = 0;
let isFullscreen = false;
let fullscreenPromptShown = false;
let examEndedDueToCheat = false;
let isContentBlurred = false;
// let currentZoomInstance; // Ini harus dideklarasikan di scope global jika digunakan di luar ready, atau passed as argument

// --- Helper Functions for Anti-Cheating (Tidak berubah signifikan) ---
function requestFullscreen(element) {
  if (element.requestFullscreen) {
    element.requestFullscreen();
  } else if (element.mozRequestFullScreen) {
    /* Firefox */
    element.mozRequestFullScreen();
  } else if (element.webkitRequestFullscreen) {
    /* Chrome, Safari & Opera */
    element.webkitRequestFullscreen();
  } else if (element.msRequestFullscreen) {
    /* IE/Edge */
    element.msRequestFullscreen();
  }
}

function exitFullscreen() {
  if (document.exitFullscreen) {
    document.exitFullscreen();
  } else if (document.mozCancelFullScreen) {
    /* Firefox */
    document.mozCancelFullScreen();
  } else if (document.webkitExitFullscreen) {
    /* Chrome, Safari and Opera */
    document.webkitExitFullscreen();
  } else if (document.msExitFullscreen) {
    /* IE/Edge */
    document.msExitFullscreen();
  }
}

function handleCheatDetection(type) {
  if (!window.examConfig.enableAntiCheating || examEndedDueToCheat) return;

  cheatAttempts++;
  console.warn(
    `Cheat detected: ${type}. Attempt: ${cheatAttempts}/${window.examConfig.maxCheatAttempts}`
  );

  Swal.fire({
    title: 'Pelanggaran!',
    html: `Anda mencoba ${type}.<br>Ini adalah pelanggaran ke-${cheatAttempts} dari ${window.examConfig.maxCheatAttempts} kali.<br><br>Mohon kembali ke mode layar penuh untuk melanjutkan.`,
    type: 'warning', // Gunakan type (SweetAlert2 versi terbaru)
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: true,
    confirmButtonText: 'Lanjutkan Ujian',
    didOpen: () => {
      $(document).on('keydown.swal', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
          e.stopPropagation();
          e.preventDefault();
        }
      });
    },
    willClose: () => {
      $(document).off('keydown.swal');
    },
  }).then((result) => {
    if (result.isConfirmed) {
      // Pakai isConfirmed untuk tombol OK
      if (
        type === window.examConfig.exitFullscreenWarning &&
        !examEndedDueToCheat
      ) {
        console.log('SweetAlert closed. Displaying fullscreen prompt again.');
        $('#fullscreen-prompt').show();
      }
    }
  });

  if (cheatAttempts >= window.examConfig.maxCheatAttempts) {
    examEndedDueToCheat = true;
    disableCheatListeners();
    Swal.fire({
      title: 'Ujian Diakhiri!',
      html: window.examConfig.cheatAttemptExceeded,
      type: 'error',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      timer: 4000,
    }).then(() => {
      prosesSelesaikanUjianFinal()
        .then(() => {
          window.location.href = window.examConfig.redirectUrlOnCheat;
        })
        .catch((error) => {
          console.error('Error finalizing exam on cheat:', error);
          window.location.href = window.examConfig.redirectUrlOnCheat;
        });
    });
  }
}

function handleKeyboardEvents(e) {
  // F12, Ctrl+Shift+I (devtools), Ctrl+Shift+J (console)
  if (
    e.keyCode === 123 ||
    (e.ctrlKey && e.shiftKey && e.keyCode === 73) ||
    (e.ctrlKey && e.shiftKey && e.keyCode === 74)
  ) {
    e.preventDefault();
    handleCheatDetection('membuka Developer Tools');
  } // Cegah Ctrl+U (View Source)
  if (e.ctrlKey && e.keyCode === 85) {
    e.preventDefault();
    handleCheatDetection('membuka View Source');
  } // Handle Print Screen (PrtSc) key - keyCode 44 (Best effort detection)
  if (e.keyCode === 44) {
    e.preventDefault();
    handleCheatDetection('melakukan tangkapan layar (screenshot)');
  } // Handle ESC key (keyCode 27) - Ini akan memicu keluar dari fullscreen secara alami
  if (e.keyCode === 27) {
    console.log('Escape key detected. Browser will handle fullscreen exit.');
  } // Tambahan: Mencegah Ctrl+R atau F5 (Refresh)
  if ((e.ctrlKey && e.keyCode === 82) || e.keyCode === 116) {
    e.preventDefault();
    handleCheatDetection('melakukan refresh halaman');
  }
}

function blurExamContent() {
  if (isContentBlurred) return;
  const examContent = $('#area-soal-ujian');
  const navPanel = $('#panel-navigasi-soal');
  if (examContent.length) {
    examContent.addClass('blurred-content');
    navPanel.addClass('blurred-content');
    isContentBlurred = true;
    console.log('Exam content blurred.');
  }
}

function unblurExamContent() {
  if (!isContentBlurred) return;
  const examContent = $('#area-soal-ujian');
  const navPanel = $('#panel-navigasi-soal');
  if (examContent.length) {
    examContent.removeClass('blurred-content');
    navPanel.removeClass('blurred-content');
    isContentBlurred = false;
    console.log('Exam content unblurred.');
  }
}

function disableCheatListeners() {
  $(document).off(
    'visibilitychange webkitvisibilitychange mozvisibilitychange msvisibilitychange'
  );
  $(document).off('keydown', handleKeyboardEvents);
  $(document).off('contextmenu');
  $(document).off(
    'fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange'
  );
  $(window).off('blur focus');
  window.onbeforeunload = null;
  $(document).off('copy cut paste');
}

function handleVisibilityChange() {
  if (
    document.hidden ||
    document.webkitHidden ||
    document.mozHidden ||
    document.msHidden
  ) {
    handleCheatDetection(window.examConfig.tabChangeWarning);
    blurExamContent();
  } else {
    unblurExamContent();
  }
}

function handleFullscreenChange() {
  if (
    !document.fullscreenElement &&
    !document.webkitFullscreenElement &&
    !document.mozFullScreenElement &&
    !document.msFullscreenElement
  ) {
    if (fullscreenPromptShown) {
      handleCheatDetection(window.examConfig.exitFullscreenWarning);
      isFullscreen = false;
      blurExamContent();
    }
  } else {
    isFullscreen = true;
    console.log('Entered fullscreen mode.');
    unblurExamContent();
  }
}

function initAntiCheating() {
  if (!window.examConfig.enableAntiCheating) {
    $('#fullscreen-prompt').hide();
    return;
  }

  $('#fullscreen-prompt').show();
  $('#start-fullscreen-exam').on('click', function () {
    if (
      !document.fullscreenElement &&
      !document.webkitFullscreenElement &&
      !document.mozFullScreenElement &&
      !document.msFullscreenElement
    ) {
      requestFullscreen(document.documentElement);
    }
    $('#fullscreen-prompt').hide();
    fullscreenPromptShown = true;

    $(document).on('visibilitychange', handleVisibilityChange);
    $(document).on('webkitvisibilitychange', handleVisibilityChange);
    $(document).on('mozvisibilitychange', handleVisibilityChange);
    $(document).on('msvisibilitychange', handleVisibilityChange);

    $(document).on('fullscreenchange', handleFullscreenChange);
    $(document).on('webkitfullscreenchange', handleFullscreenChange);
    $(document).on('mozfullscreenchange', handleFullscreenChange);
    $(document).on('MSFullscreenChange', handleFullscreenChange);

    $(document).on('contextmenu', function (e) {
      e.preventDefault();
      handleCheatDetection('klik kanan');
    });
    $(document).on('keydown', handleKeyboardEvents);

    $(document).on('copy cut paste', function (e) {
      e.preventDefault();
      handleCheatDetection('melakukan salin/tempel');
    });

    lockScreenOrientation();

    window.onbeforeunload = function () {
      return 'Anda yakin ingin meninggalkan halaman ujian? Jawaban Anda mungkin tidak tersimpan.';
    };
    unblurExamContent();

    window.history.pushState(null, null, window.location.href);
    $(window).on('popstate', function (event) {
      handleCheatDetection('menggunakan tombol navigasi browser');
      window.history.pushState(null, null, window.location.href);
    });

    startDynamicWatermark();
  }); // Handle initial state if user is already in fullscreen (e.g., refresh)

  if (
    document.fullscreenElement ||
    document.webkitFullscreenElement ||
    document.mozFullScreenElement ||
    document.msFullscreenElement
  ) {
    isFullscreen = true;
    $('#fullscreen-prompt').hide();
    fullscreenPromptShown = true;

    $(document).on('visibilitychange', handleVisibilityChange);
    $(document).on('webkitvisibilitychange', handleVisibilityChange);
    $(document).on('mozvisibilitychange', handleVisibilityChange);
    $(document).on('msvisibilitychange', handleVisibilityChange);

    $(document).on('fullscreenchange', handleFullscreenChange);
    $(document).on('webkitfullscreenchange', handleFullscreenChange);
    $(document).on('mozfullscreenchange', handleFullscreenChange);
    $(document).on('MSFullscreenChange', handleFullscreenChange);

    $(document).on('contextmenu', function (e) {
      e.preventDefault();
      handleCheatDetection('klik kanan');
    });
    $(document).on('keydown', handleKeyboardEvents);

    $(document).on('copy cut paste', function (e) {
      e.preventDefault();
      handleCheatDetection('melakukan salin/tempel');
    });

    lockScreenOrientation();

    window.onbeforeunload = function () {
      return 'Anda yakin ingin meninggalkan halaman ujian? Jawaban Anda mungkin tidak tersimpan.';
    };
    unblurExamContent();

    window.history.pushState(null, null, window.location.href);
    $(window).on('popstate', function (event) {
      handleCheatDetection('menggunakan tombol navigasi browser');
      window.history.pushState(null, null, window.location.href);
    });

    startDynamicWatermark();
  } else {
    blurExamContent();
  }
}

function lockScreenOrientation() {
  if (screen.orientation && screen.orientation.lock) {
    screen.orientation
      .lock('portrait')
      .then(() => {
        console.log('Screen orientation locked to portrait.');
      })
      .catch((err) => {
        console.warn('Could not lock screen orientation:', err);
      });
  } else if (screen.lockOrientation) {
    screen.lockOrientation('portrait');
  }
}

function startDynamicWatermark() {
  if ($('#dynamic-watermark').length === 0) {
    $('body').append(
      '<div id="dynamic-watermark" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; opacity: 0.1; z-index: 9998; overflow: hidden;"></div>'
    );
  }

  const watermarkElement = $('#dynamic-watermark');
  const userIdentifier = `${window.examConfig.siswa.nisn} - ${window.examConfig.siswa.nama}`;
  const examName = window.examConfig.ujian.nama_ujian;

  let watermarkContent = '';
  for (let i = 0; i < 50; i++) {
    watermarkContent += `<span style="display: inline-block; white-space: nowrap; margin: 50px; transform: rotate(-45deg);">${userIdentifier} | ${examName}</span>`;
  }
  watermarkElement.html(watermarkContent);

  let x = 0;
  let y = 0;
  let dx = 1;
  let dy = 1;

  setInterval(() => {
    x += dx;
    y += dy;

    if (x + watermarkElement.width() > $(window).width() || x < 0) {
      dx = -dx;
    }
    if (y + watermarkElement.height() > $(window).height() || y < 0) {
      dy = -dy;
    }
    watermarkElement.css({
      transform: `translate(${x}px, ${y}px)`,
      'text-align': 'center',
    });
  }, 100);
}

// Initial check for "Selesai" button visibility
function toggleSelesaiButton(currentQuestionNumber) {
  var totalQuestions = totalSoalUjian;
  $('#selesai-ujian-wrapper').toggle(currentQuestionNumber === totalQuestions);
}

// --- CORE EXAM LOGIC FUNCTIONS ---

function initializeNavigation() {
  let initSuccess = true;
  let missingVarsLog = [];

  const {
    base_url,
    ID_H_UJIAN_ENC_GLOBAL,
    JUMLAH_SOAL_TOTAL_GLOBAL,
    JAWABAN_TERSIMPAN_GLOBAL,
    WAKTU_HABIS_TIMESTAMP_GLOBAL,
    WAKTU_SELESAI,
    CSRF_TOKEN_NAME_GLOBAL,
    CSRF_HASH_GLOBAL,
    startTime,
  } = window.examConfig;

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
    { value: startTime, name: 'startTime' },
  ];

  requiredVars.forEach(({ value, name }) => {
    if (
      typeof value === 'undefined' ||
      value === null ||
      (name !== 'JAWABAN_TERSIMPAN_GLOBAL' && value === '')
    ) {
      missingVarsLog.push(name);
      initSuccess = false;
    }
  });

  if (!initSuccess) {
    const errorMessage =
      'Variabel konfigurasi ujian berikut tidak lengkap: ' +
      missingVarsLog.join(', ');
    console.error(errorMessage);
    Swal.fire('Error', errorMessage, 'error');
    $('#area-soal-ujian').html(
      '<p class="text-center text-danger">Gagal memuat ujian. Harap hubungi administrator.</p>'
    );
    $(
      '#btn-prev-soal, #btn-next-soal, #btn-ragu-ragu, #btn-selesai-ujian'
    ).hide();
    return;
  }

  totalSoalUjian = parseInt(JUMLAH_SOAL_TOTAL_GLOBAL) || 0;
  idHUjianEnc = ID_H_UJIAN_ENC_GLOBAL;
  csrfTokenName = CSRF_TOKEN_NAME_GLOBAL;
  csrfHash = CSRF_HASH_GLOBAL;

  try {
    if (typeof JAWABAN_TERSIMPAN_GLOBAL === 'string') {
      jawabanSiswaInternal = JSON.parse(JAWABAN_TERSIMPAN_GLOBAL);
    } else if (
      typeof JAWABAN_TERSIMPAN_GLOBAL === 'object' &&
      JAWABAN_TERSIMPAN_GLOBAL !== null
    ) {
      jawabanSiswaInternal = JAWABAN_TERSIMPAN_GLOBAL;
    } else {
      jawabanSiswaInternal = {};
    }
    console.log('Jawaban tersimpan berhasil diload:', jawabanSiswaInternal);
  } catch (e) {
    console.error('Gagal parse atau inisialisasi jawaban tersimpan:', e);
    jawabanSiswaInternal = {};
  } // PENTING: Inisialisasi tampilan jawaban dari yang tersimpan

  initializeAnswers(); // Panggil di sini
  // Lalu panggil updateSemuaNavigasiSoalStyles() setelah inisialisasi jawaban
  updateSemuaNavigasiSoalStyles(); // Setup timer

  const timerElement = $('#timer-ujian');
  if (timerElement.length) {
    let endTimeUnixTimestamp = parseInt(WAKTU_HABIS_TIMESTAMP_GLOBAL);
    if (endTimeUnixTimestamp && endTimeUnixTimestamp > 0) {
      console.log(
        'Memulai timer dengan endTimeUnixTimestamp:',
        endTimeUnixTimestamp
      );
      startTimer(endTimeUnixTimestamp);
    } else {
      console.error(
        'Data WAKTU_HABIS_TIMESTAMP_GLOBAL tidak valid atau 0. Nilai:',
        WAKTU_HABIS_TIMESTAMP_GLOBAL
      );
      timerElement.text('Error Timer');
    }
  } else {
    console.warn('Elemen #timer-ujian tidak ditemukan.');
  } // Tampilkan soal pertama atau pesan jika tidak ada soal

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
  updateTombolRaguRaguUtama(); // PENTING: Panggil toggleSelesaiButton setelah showSoalPanel() pertama kali.

  toggleSelesaiButton(currentSoalNomorDisplay); // Event listeners untuk navigasi soal

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
  }); // Event listener untuk pilihan jawaban radio button

  $('#area-soal-ujian').on('change', 'input[type="radio"]', function () {
    let panelSoalAktif = $('.panel-soal:visible');
    if (!panelSoalAktif.length) return;
    let idSoal = panelSoalAktif.data('id-soal');
    let jawabanDipilih = $(this).val();

    if (!jawabanSiswaInternal[idSoal]) {
      jawabanSiswaInternal[idSoal] = { j: '', r: 'N' };
    }
    jawabanSiswaInternal[idSoal].j = jawabanDipilih;

    // Panggil updateNavigasiSoalSingle di sini juga
    updateNavigasiSoalSingle(currentSoalNomorDisplay, idSoal);
    updateTombolRaguRaguUtama();
    triggerAutoSave(idSoal, jawabanDipilih, jawabanSiswaInternal[idSoal].r);
  }); // Event listener untuk tombol Ragu-ragu

  $('#btn-ragu-ragu').on('click', function () {
    let panelSoalAktif = $('.panel-soal:visible');
    if (!panelSoalAktif.length) return;
    let idSoalAktif = panelSoalAktif.data('id-soal');

    if (!jawabanSiswaInternal[idSoalAktif]) {
      jawabanSiswaInternal[idSoalAktif] = { j: '', r: 'N' };
    }
    jawabanSiswaInternal[idSoalAktif].r =
      jawabanSiswaInternal[idSoalAktif].r === 'N' ? 'Y' : 'N';

    // Panggil updateNavigasiSoalSingle di sini juga
    updateNavigasiSoalSingle(currentSoalNomorDisplay, idSoalAktif);
    updateTombolRaguRaguUtama();
    triggerAutoSave(
      idSoalAktif,
      jawabanSiswaInternal[idSoalAktif].j,
      jawabanSiswaInternal[idSoalAktif].r
    );
  }); // Event listener untuk tombol Selesai Ujian

  $('#btn-selesai-ujian').on('click', function () {
    konfirmasiDanSelesaikanUjian();
  });
}

function showSoalPanel(nomorSoal) {
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

  toggleSelesaiButton(currentSoalNomorDisplay);
}

function updateTombolNavigasiUtama() {
  if (totalSoalUjian <= 0) {
    $('#btn-prev-soal, #btn-next-soal, #btn-ragu-ragu').hide();
    return;
  }
  $('#btn-prev-soal').toggle(currentSoalNomorDisplay > 1);
  $('#btn-next-soal').toggle(currentSoalNomorDisplay < totalSoalUjian);
}

function updateTombolRaguRaguUtama() {
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
  let navButton = $(
    '#panel-navigasi-soal .btn-soal-nav[data-nomor="' + nomorDisplay + '"]'
  );
  if (!navButton.length) return; // Hapus semua kelas status sebelumnya kecuali 'active'

  navButton.removeClass('btn-default btn-success btn-warning');

  if (jawabanSiswaInternal[idSoal]) {
    if (jawabanSiswaInternal[idSoal].j !== '') {
      // Jika ada jawaban
      navButton.addClass(
        jawabanSiswaInternal[idSoal].r === 'Y' ? 'btn-warning' : 'btn-success'
      );
    } else if (jawabanSiswaInternal[idSoal].r === 'Y') {
      // Hanya ragu-ragu
      navButton.addClass('btn-warning');
    } else {
      navButton.addClass('btn-default'); // Belum dijawab dan tidak ragu
    }
  } else {
    navButton.addClass('btn-default'); // Belum ada data jawaban untuk soal ini
  }
}

function updateSemuaNavigasiSoalStyles() {
  if (totalSoalUjian > 0) {
    $('#panel-navigasi-soal .btn-soal-nav').each(function () {
      let nomorDisplay = $(this).data('nomor');
      let idSoalPadaNav = $(this).data('id-soal'); // Ambil id_soal langsung dari tombol navigasi

      if (idSoalPadaNav) {
        updateNavigasiSoalSingle(nomorDisplay, idSoalPadaNav);
      }
    });
  }
}

function triggerAutoSave(idSoal, jawaban, raguStatus) {
  clearTimeout(autoSaveTimer);
  autoSaveTimer = setTimeout(function () {
    simpanJawabanAjax(idSoal, jawaban, raguStatus);
  }, 700);
}

function simpanJawabanAjax(idSoal, jawaban, raguStatus) {
  if (examEndedDueToCheat) {
    console.log('SimpanJawabanAjax: Exam ended due to cheat, preventing save.');
    return;
  }

  if (typeof window.examConfig === 'undefined') {
    console.error('Exam configuration not found');
    return;
  }

  const {
    base_url,
    ID_H_UJIAN_ENC_GLOBAL,
    CSRF_TOKEN_NAME_GLOBAL,
    CSRF_HASH_GLOBAL,
  } = window.examConfig;

  console.log('Debug simpanJawabanAjax:', {
    id_h_ujian_enc: ID_H_UJIAN_ENC_GLOBAL,
    id_soal: idSoal,
    jawaban: jawaban,
    ragu_ragu: raguStatus,
  });

  const formData = {
    id_h_ujian_enc: ID_H_UJIAN_ENC_GLOBAL,
    id_soal: idSoal,
    jawaban: jawaban,
    ragu_ragu: raguStatus,
    [CSRF_TOKEN_NAME_GLOBAL]: CSRF_HASH_GLOBAL,
  };

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
        if (response.csrf_hash_new) {
          window.examConfig.CSRF_HASH_GLOBAL = response.csrf_hash_new;
          csrfHash = response.csrf_hash_new;
        }
      } else {
        console.error('Gagal simpan jawaban:', response);
        if (
          response.message === 'ID hasil ujian tidak valid' ||
          response.message === 'Data ujian tidak ditemukan atau sudah selesai'
        ) {
          Swal.fire({
            title: 'Error',
            text: 'Sesi ujian tidak valid atau sudah berakhir. Halaman akan dimuat ulang.',
            type: 'error',
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
        type: 'error',
        timer: 2000,
      });
    },
    complete: function () {
      btnRagu.prop('disabled', false).html(originalBtnText);
    },
  });
}

function konfirmasiDanSelesaikanUjian() {
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

  let pesanKonfirmasi = window.examConfig.confirmFinishMessage;
  if (belumDijawabCount > 0) {
    pesanKonfirmasi += `<br><br><strong style='color:red;'>${window.examConfig.unansweredWarning.replace(
      '{count}',
      belumDijawabCount
    )}</strong>`;
  }
  if (raguRaguCount > 0) {
    pesanKonfirmasi += `<br><strong style='color:orange;'>${window.examConfig.doubtfulWarning.replace(
      '{count}',
      raguRaguCount
    )}</strong>`;
  }

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
  if (examEndedDueToCheat) {
    console.log('ProsesSelesaikanUjianFinal: Exam already ended due to cheat.');
    return Promise.resolve({
      status: true,
      message: 'Ujian sudah berakhir.',
      redirect_url: window.examConfig.redirectUrlOnCheat,
    });
  }

  return new Promise((resolve, reject) => {
    const {
      base_url,
      ID_H_UJIAN_ENC_GLOBAL,
      CSRF_TOKEN_NAME_GLOBAL,
      CSRF_HASH_GLOBAL,
    } = window.examConfig;

    $('#btn-selesai-ujian')
      .prop('disabled', true)
      .html('<i class="fa fa-spinner fa-spin"></i> Mengirim...');
    $('.btn-nav-soal, #btn-ragu-ragu').prop('disabled', true);

    let jawabanAkhirBatch = {};
    for (let i = 1; i <= totalSoalUjian; i++) {
      let panelSoal = $('#soal-' + i);
      if (panelSoal.length) {
        let idSoal = panelSoal.data('id-soal');
        let currentAnswer =
          $(`input[name="jawaban_soal_${idSoal}"]:checked`).val() ||
          jawabanSiswaInternal[idSoal]?.j ||
          '';
        jawabanAkhirBatch[idSoal] = {
          j: currentAnswer,
          r: jawabanSiswaInternal[idSoal]?.r || 'N',
        };
      }
    }

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
          if (response.csrf_hash_new) {
            window.examConfig.CSRF_HASH_GLOBAL = response.csrf_hash_new;
            csrfHash = response.csrf_hash_new;
          }
          resolve({
            status: true,
            message: response.message || 'Ujian berhasil diselesaikan',
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
  console.log('Fungsi waktuHabis() dipanggil.');
  if (
    $('#area-soal-ujian').length > 0 &&
    !$('#btn-selesai-ujian').is(':disabled') &&
    !examEndedDueToCheat
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
      'Form lembar ujian tidak ditemukan, tombol selesai sudah disabled, atau ujian sudah berakhir saat waktuHabis().'
    );
  }
}

function startTimer(endTimeUnixTimestamp) {
  const timerElement = $('#timer-ujian');
  if (!timerElement.length) {
    console.warn('Timer element not found');
    return;
  }

  const timer = setInterval(function () {
    const now = Date.now();
    const distance = endTimeUnixTimestamp * 1000 - now;

    if (distance <= 0) {
      clearInterval(timer);
      timerElement.text('00:00:00');
      waktuHabis();
      return;
    }

    const hours = Math.floor(
      (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
    );
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    timerElement.text(
      String(hours).padStart(2, '0') +
        ':' +
        String(minutes).padStart(2, '0') +
        ':' +
        String(seconds).padStart(2, '0')
    );
  }, 1000);
}

// Inisialisasi tampilan jawaban saat halaman pertama kali dimuat
function initializeAnswers() {
  // Loop melalui semua tombol navigasi soal
  $('#panel-navigasi-soal .btn-soal-nav').each(function () {
    let nomorSoalDisplay = $(this).data('nomor');
    let idSoal = $(this).data('id-soal');

    // Pastikan idSoal ada dan data jawaban untuk soal tersebut tersedia
    if (idSoal && jawabanSiswaInternal.hasOwnProperty(idSoal)) {
      const jawabanData = jawabanSiswaInternal[idSoal];

      // Hapus kelas warna default/success/warning
      $(this).removeClass('btn-default btn-success btn-warning');

      if (jawabanData.j !== '') {
        // Jika ada jawaban
        if (jawabanData.r === 'Y') {
          // Dan ditandai ragu-ragu
          $(this).addClass('btn-warning');
        } else {
          // Dijawab dan tidak ragu-ragu
          $(this).addClass('btn-success');
        }
      } else if (jawabanData.r === 'Y') {
        // Hanya ragu-ragu (belum dijawab)
        $(this).addClass('btn-warning');
      } else {
        // Belum dijawab dan tidak ragu-ragu
        $(this).addClass('btn-default');
      }
    } else {
      // Jika tidak ada data jawaban atau idSoal tidak valid, set ke default
      $(this).removeClass('btn-success btn-warning').addClass('btn-default');
    }
  });

  // PENTING: Juga set radio button yang dipilih
  for (let idSoal in jawabanSiswaInternal) {
    if (jawabanSiswaInternal[idSoal].j) {
      // Gunakan selector yang lebih spesifik untuk radio button soal
      // Karena `id` radio button sekarang unik (misal: opsi_a_123)
      // Kita perlu menemukan radio button berdasarkan name dan value
      $(
        `input[name="jawaban_soal_${idSoal}"][value="${jawabanSiswaInternal[idSoal].j}"]`
      ).prop('checked', true);
    }
  }
}

// --- Main Document Ready Block ---
$(document).ready(function () {
  // Validasi dan inisialisasi konfigurasi
  if (typeof window.examConfig === 'undefined') {
    console.error('Exam configuration not found!');
    Swal.fire('Error', 'Konfigurasi ujian tidak ditemukan', 'error');
    return;
  }

  console.log('Initializing exam with config:', window.examConfig); // Call initializeNavigation to setup main exam UI and data

  initializeNavigation(); // Call anti-cheating specific initialization

  initAntiCheating(); // Initialize Medium-Zoom (Moved here to ensure it runs after general init)

  const zoomableImages = document.querySelectorAll(
    '.soal-media img, .opsi-media img'
  );
  let currentZoomInstance; // Declare here to make it accessible for listeners

  currentZoomInstance = mediumZoom(zoomableImages, {
    margin: 24,
    background: '#000000e6',
    scrollOffset: 0,
  }); // Attach Medium-Zoom event listeners

  currentZoomInstance.on('open', () => {
    console.log('MediumZoom: Zoom opened.');
  });

  currentZoomInstance.on('closed', () => {
    console.log('MediumZoom: Zoom closed.');
    const zoomOverlay = document.querySelector('.medium-zoom-overlay');
    if (zoomOverlay) {
      zoomOverlay.remove();
      console.log('MediumZoom: Removed residual overlay.');
    }
    $('#btn-prev-soal').focus();
  }); // Ensure navigation buttons can close the zoom

  const navigationButtons = document.querySelectorAll(
    '.btn-soal-nav, #btn-prev-soal, #btn-next-soal'
  );
  navigationButtons.forEach((button) => {
    button.addEventListener('click', () => {
      if (currentZoomInstance.opened) {
        currentZoomInstance.close();
        console.log('MediumZoom: Navigation button clicked, closing zoom.');
      }
    });
  }); // Handle image loading states (remains as is)

  function handleImageLoad(img) {
    const container = img.closest('.soal-media, .opsi-media');
    if (container) {
      container.classList.remove('loading');
    }
  }

  zoomableImages.forEach((img) => {
    const container = img.closest('.soal-media, .opsi-media');
    if (container) {
      container.classList.add('loading');
    }
    if (img.complete) {
      handleImageLoad(img);
    } else {
      img.addEventListener('load', () => handleImageLoad(img));
      img.addEventListener('error', () => {
        const container = img.closest('.soal-media, .opsi-media');
        if (container) {
          container.classList.remove('loading');
          container.classList.add('error');
        }
      });
    }
  });
});
