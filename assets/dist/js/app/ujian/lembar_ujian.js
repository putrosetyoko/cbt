// File: assets/dist/js/app/ujian/lembar_ujian.js

// Variabel global yang diharapkan sudah didefinisikan di view PHP (melalui window.examConfig)
// var base_url;
// const ID_H_UJIAN_ENC_GLOBAL;
// const JUMLAH_SOAL_TOTAL_GLOBAL;
// const JAWABAN_TERSIMPAN_GLOBAL;
// const WAKTU_HABIS_TIMESTAMP_GLOBAL;
// const WAKTU_SELESAI; // Format YYYY-MM-DD HH:mm:ss dari PHP
// const CSRF_TOKEN_NAME_GLOBAL;
// const CSRF_HASH_GLOBAL;

// ANTI-CHEATING SETTINGS (NEW)
// const enableAntiCheating;
// const maxCheatAttempts;
// const redirectUrlOnCheat;
// const confirmFinishMessage;
// const unansweredWarning;
// const doubtfulWarning;
// const exitFullscreenWarning;
// const tabChangeWarning;
// const cheatAttemptExceeded;
// const startTime; // Timestamp saat halaman dimuat

var currentSoalNomorDisplay = 1;
var totalSoalUjian;
var idHUjianEnc;
var jawabanSiswaInternal = {};
var autoSaveTimer;
var csrfTokenName;
var csrfHash;

// === ANTI-CHEATING VARIABLES ===
let cheatAttempts = 0;
let isFullscreen = false;
let fullscreenPromptShown = false; // Flag to check if prompt was displayed
let examEndedDueToCheat = false; // Flag to prevent multiple cheat triggers after exam ends
let isContentBlurred = false;
let currentZoomInstance;

// --- Helper Functions for Anti-Cheating ---
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

// Function to handle cheat detection
function handleCheatDetection(type) {
  if (!window.examConfig.enableAntiCheating || examEndedDueToCheat) return;

  cheatAttempts++;
  console.warn(
    `Cheat detected: ${type}. Attempt: ${cheatAttempts}/${window.examConfig.maxCheatAttempts}`
  );

  Swal.fire({
    title: 'Pelanggaran!',
    html: `Anda mencoba ${type}.<br>Ini adalah pelanggaran ke-${cheatAttempts} dari ${window.examConfig.maxCheatAttempts} kali.<br><br>Mohon kembali ke mode layar penuh untuk melanjutkan.`,
    type: 'warning', // Gunakan type (SweetAlert2)
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
      // SweetAlert is about to close, but might still be visible
      $(document).off('keydown.swal');
      // Logic to show fullscreen-prompt moved to .then() to ensure SweetAlert is fully gone
    },
  }).then((result) => {
    // This callback fires AFTER SweetAlert is fully closed and animated away
    // Jika user mengklik "Lanjutkan Ujian"
    if (result.value) {
      // result.value is true if 'confirmButton' was clicked
      // Jika pelanggaran adalah keluar dari layar penuh DAN ujian belum berakhir karena cheat
      if (
        type === window.examConfig.exitFullscreenWarning &&
        !examEndedDueToCheat
      ) {
        console.log('SweetAlert closed. Displaying fullscreen prompt again.');
        $('#fullscreen-prompt').show(); // Tampilkan kembali fullscreen prompt
      }
    }
    // Jika user menekan ESC (tapi kita sudah mencegahnya di didOpen) atau dismiss dengan cara lain,
    // result.dismiss akan berisi 'cancel', 'backdrop', 'esc', 'timer'
    // Untuk kasus ini, kita hanya ingin menampilkan prompt saat 'Lanjutkan Ujian' diklik
    // dan itu dari pelanggaran keluar fullscreen.
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
  }
  // Cegah Ctrl+U (View Source)
  if (e.ctrlKey && e.keyCode === 85) {
    e.preventDefault();
    handleCheatDetection('membuka View Source');
  }
  // Handle Print Screen (PrtSc) key - keyCode 44 (Best effort detection)
  if (e.keyCode === 44) {
    e.preventDefault(); // Coba cegah default action
    handleCheatDetection('melakukan tangkapan layar (screenshot)');
  }
  // Handle ESC key (keyCode 27) - Ini akan memicu keluar dari fullscreen secara alami
  if (e.keyCode === 27) {
    // Jangan e.preventDefault() di sini untuk ESC
    console.log('Escape key detected. Browser will handle fullscreen exit.');
  }
  // Tambahan: Mencegah Ctrl+R atau F5 (Refresh)
  if ((e.ctrlKey && e.keyCode === 82) || e.keyCode === 116) {
    // Ctrl+R atau F5
    e.preventDefault();
    handleCheatDetection('melakukan refresh halaman');
  }
}

function blurExamContent() {
  if (isContentBlurred) return; // Prevent multiple blurs
  const examContent = $('#area-soal-ujian');
  const navPanel = $('#panel-navigasi-soal');
  if (examContent.length) {
    examContent.addClass('blurred-content');
    navPanel.addClass('blurred-content'); // Blur navigasi juga
    isContentBlurred = true;
    console.log('Exam content blurred.');
  }
}

// Function to unblur content
function unblurExamContent() {
  if (!isContentBlurred) return; // Prevent unblurring if not blurred
  const examContent = $('#area-soal-ujian');
  const navPanel = $('#panel-navigasi-soal');
  if (examContent.length) {
    examContent.removeClass('blurred-content');
    navPanel.removeClass('blurred-content');
    isContentBlurred = false;
    console.log('Exam content unblurred.');
  }
}

// Function to disable cheat detection listeners
function disableCheatListeners() {
  $(document).off(
    'visibilitychange webkitvisibilitychange mozvisibilitychange msvisibilitychange'
  );
  $(document).off('keydown', handleKeyboardEvents); // Menonaktifkan penanganan keyboard
  $(document).off('contextmenu'); // Menonaktifkan klik kanan
  $(document).off(
    'fullscreenchange webkitfullscreenchange mozfullscreenchange MSFullscreenChange'
  );
  $(window).off('blur focus');
  window.onbeforeunload = null;

  // Tambahan: Menonaktifkan event copy/cut/paste
  $(document).off('copy cut paste');

  // Tambahan: Menonaktifkan back/forward/refresh (dengan onbeforeunload yang nullified)
  // dan pushState akan dihapus oleh redirect.
}

// Event listener for tab change / window blur
function handleVisibilityChange() {
  if (
    document.hidden ||
    document.webkitHidden ||
    document.mozHidden ||
    document.msHidden
  ) {
    // Tab hidden or window blurred
    handleCheatDetection(window.examConfig.tabChangeWarning);
    blurExamContent(); // Blur content when tab is hidden
  } else {
    // Tab visible or window focused
    unblurExamContent(); // Unblur content when tab is visible
  }
}

// Event listener for fullscreen change
function handleFullscreenChange() {
  if (
    !document.fullscreenElement &&
    !document.webkitFullscreenElement &&
    !document.mozFullScreenElement &&
    !document.msFullscreenElement
  ) {
    // Exited fullscreen
    if (fullscreenPromptShown) {
      handleCheatDetection(window.examConfig.exitFullscreenWarning);
      isFullscreen = false;
      blurExamContent(); // Blur content when exited fullscreen
    }
  } else {
    // Entered fullscreen
    isFullscreen = true;
    console.log('Entered fullscreen mode.');
    unblurExamContent(); // Unblur content when entered fullscreen
  }
}

// Event listener for right-click and F12
function handleRightClickAndF12(e) {
  if (e.type === 'contextmenu') {
    // Right-click
    e.preventDefault();
    handleCheatDetection('klik kanan');
  } else if (e.type === 'keydown') {
    // F12, Ctrl+Shift+I (devtools), Ctrl+Shift+J (console)
    if (
      e.keyCode === 123 ||
      (e.ctrlKey && e.shiftKey && e.keyCode === 73) ||
      (e.ctrlKey && e.shiftKey && e.keyCode === 74)
    ) {
      e.preventDefault();
      handleCheatDetection('membuka Developer Tools');
    }
    // Cegah Ctrl+U (View Source)
    if (e.ctrlKey && e.keyCode === 85) {
      e.preventDefault();
      handleCheatDetection('membuka View Source');
    }
  }
}

// Initialize anti-cheating listeners
function initAntiCheating() {
  if (!window.examConfig.enableAntiCheating) {
    $('#fullscreen-prompt').hide();
    return;
  }

  $('#fullscreen-prompt').show();
  $('#start-fullscreen-exam').on('click', function () {
    // Request fullscreen only if not already in it
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

    // Attach listeners
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
    $(document).on('keydown', handleKeyboardEvents); // Menggunakan fungsi keyboard events yang lebih komprehensif

    // Menonaktifkan copy/cut/paste
    $(document).on('copy cut paste', function (e) {
      e.preventDefault();
      handleCheatDetection('melakukan salin/tempel');
    });

    // Mengunci orientasi layar untuk perangkat mobile (opsional)
    lockScreenOrientation();

    window.onbeforeunload = function () {
      return 'Anda yakin ingin meninggalkan halaman ujian? Jawaban Anda mungkin tidak tersimpan.';
    };
    unblurExamContent(); // Pastikan konten tidak blur saat ujian dimulai

    // Menambahkan entri ke history API untuk mencegah tombol back/forward yang tidak terkontrol
    window.history.pushState(null, null, window.location.href);
    $(window).on('popstate', function (event) {
      handleCheatDetection('menggunakan tombol navigasi browser');
      // Dorong state lagi untuk mencegah navigasi
      window.history.pushState(null, null, window.location.href);
    });

    // Tampilkan watermark dinamis
    startDynamicWatermark();
  });

  // Handle initial state if user is already in fullscreen (e.g., refresh)
  if (
    document.fullscreenElement ||
    document.webkitFullscreenElement ||
    document.mozFullScreenElement ||
    document.msFullscreenElement
  ) {
    isFullscreen = true;
    $('#fullscreen-prompt').hide();
    fullscreenPromptShown = true;

    // Attach listeners directly
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
    $(document).on('keydown', handleKeyboardEvents); // Menggunakan fungsi keyboard events yang lebih komprehensif

    // Menonaktifkan copy/cut/paste
    $(document).on('copy cut paste', function (e) {
      e.preventDefault();
      handleCheatDetection('melakukan salin/tempel');
    });

    lockScreenOrientation(); // Lock orientation on refresh if already in fullscreen

    window.onbeforeunload = function () {
      return 'Anda yakin ingin meninggalkan halaman ujian? Jawaban Anda mungkin tidak tersimpan.';
    };
    unblurExamContent(); // Pastikan konten tidak blur jika user refresh di fullscreen

    // Menambahkan entri ke history API untuk mencegah tombol back/forward yang tidak terkontrol
    window.history.pushState(null, null, window.location.href);
    $(window).on('popstate', function (event) {
      handleCheatDetection('menggunakan tombol navigasi browser');
      window.history.pushState(null, null, window.location.href);
    });

    startDynamicWatermark();
  } else {
    // Jika user tidak di fullscreen saat halaman dimuat (atau refresh di luar fullscreen), blur konten
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
    // Old API
    screen.lockOrientation('portrait');
  }
  // Jika tidak ada dukungan API, tidak ada yang terjadi.
}

// Fungsi untuk membuat watermark dinamis
function startDynamicWatermark() {
  // Buat elemen watermark jika belum ada
  if ($('#dynamic-watermark').length === 0) {
    $('body').append(
      '<div id="dynamic-watermark" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; opacity: 0.1; z-index: 9998; overflow: hidden;"></div>'
    );
  }

  const watermarkElement = $('#dynamic-watermark');
  // Akses data siswa dan ujian dari window.examConfig
  const userIdentifier = `${window.examConfig.siswa.nisn} - ${window.examConfig.siswa.nama}`;
  const examName = window.examConfig.ujian.nama_ujian;

  // Pastikan $siswa dan $ujian tersedia di lembar_ujian.php dan data valid

  // Buat banyak watermark text
  let watermarkContent = '';
  for (let i = 0; i < 50; i++) {
    watermarkContent += `<span style="display: inline-block; white-space: nowrap; margin: 50px; transform: rotate(-45deg);">${userIdentifier} | ${examName}</span>`;
  }
  watermarkElement.html(watermarkContent);

  // Gerakkan watermark secara acak atau dalam pola
  let x = 0;
  let y = 0;
  let dx = 1; // Kecepatan gerakan horizontal
  let dy = 1; // Kecepatan gerakan vertikal

  setInterval(() => {
    x += dx;
    y += dy;

    // Bounce off edges
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
  var totalQuestions = totalSoalUjian; // Use the global variable
  $('#selesai-ujian-wrapper').toggle(currentQuestionNumber === totalQuestions);
}
toggleSelesaiButton(currentSoalNomorDisplay); // Initial call for question 1
// --- CORE EXAM LOGIC FUNCTIONS ---

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
    startTime, // Get startTime from config
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
    { value: startTime, name: 'startTime' }, // Validate startTime
  ];

  requiredVars.forEach(({ value, name }) => {
    if (typeof value === 'undefined' || value === null || value === '') {
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
    // Prevent exam from starting if essential config is missing
    // Disable UI elements to prevent interaction
    $('#area-soal-ujian').html(
      '<p class="text-center text-danger">Gagal memuat ujian. Harap hubungi administrator.</p>'
    );
    $(
      '#btn-prev-soal, #btn-next-soal, #btn-ragu-ragu, #btn-selesai-ujian'
    ).hide();
    return;
  }

  // Assign global variables
  totalSoalUjian = parseInt(JUMLAH_SOAL_TOTAL_GLOBAL) || 0;
  idHUjianEnc = ID_H_UJIAN_ENC_GLOBAL;
  csrfTokenName = CSRF_TOKEN_NAME_GLOBAL;
  csrfHash = CSRF_HASH_GLOBAL;

  // Perbaiki inisialisasi jawaban
  try {
    if (typeof JAWABAN_TERSIMPAN_GLOBAL === 'string') {
      jawabanSiswaInternal = JSON.parse(JAWABAN_TERSIMPAN_GLOBAL);
    } else {
      jawabanSiswaInternal = JAWABAN_TERSIMPAN_GLOBAL;
    }
    console.log('Jawaban tersimpan berhasil diload:', jawabanSiswaInternal);
  } catch (e) {
    console.error('Gagal parse jawaban tersimpan:', e);
    jawabanSiswaInternal = {};
  }

  // Inisialisasi tampilan jawaban dari yang tersimpan
  initializeAnswers();
  updateSemuaNavigasiSoalStyles();

  // Setup timer
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
  }

  // Tampilkan soal pertama atau pesan jika tidak ada soal
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

  // Event listeners untuk navigasi soal
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

  // Event listener untuk pilihan jawaban radio button
  $('#area-soal-ujian').on('change', 'input[type="radio"]', function () {
    let panelSoalAktif = $('.panel-soal:visible');
    if (!panelSoalAktif.length) return; // Safeguard
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

  // Event listener untuk tombol Ragu-ragu
  $('#btn-ragu-ragu').on('click', function () {
    let panelSoalAktif = $('.panel-soal:visible');
    if (!panelSoalAktif.length) return; // Safeguard
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

  // Event listener untuk tombol Selesai Ujian
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

  // Toggle "Selesai" button visibility
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
  if (examEndedDueToCheat) {
    // Prevent save if exam already ended due to cheat
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
          csrfHash = response.csrf_hash_new; // Update local variable as well
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
    type: 'warning', // Use type instead of type for newer SweetAlert2
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
    // Prevent multiple finalizations
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
        // Ambil jawaban dari radio button yang terpilih, jika tidak ada, ambil dari internal state, jika masih kosong, default ke ''
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
          // Update CSRF hash
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
    $('#area-soal-ujian').length > 0 && // Check if content area is present
    !$('#btn-selesai-ujian').is(':disabled') && // Check if button is not already disabled
    !examEndedDueToCheat // Ensure not already ended by cheat
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

// Tambahkan fungsi timer
function startTimer(endTimeUnixTimestamp) {
  const timerElement = $('#timer-ujian');
  if (!timerElement.length) {
    console.warn('Timer element not found');
    return;
  }

  const timer = setInterval(function () {
    const now = Date.now(); // Use Date.now() for milliseconds
    const distance = endTimeUnixTimestamp * 1000 - now; // Convert to milliseconds for comparison

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

// Inisialisasi tampilan jawaban
function initializeAnswers() {
  for (let idSoal in jawabanSiswaInternal) {
    if (jawabanSiswaInternal[idSoal].j) {
      $(
        `input[name="jawaban_soal_${idSoal}"][value="${jawabanSiswaInternal[idSoal].j}"]`
      ).prop('checked', true);
    }
  }
  updateSemuaNavigasiSoalStyles();
}

// --- Main Document Ready Block ---
$(document).ready(function () {
  // Validasi dan inisialisasi konfigurasi
  if (typeof window.examConfig === 'undefined') {
    console.error('Exam configuration not found!');
    Swal.fire('Error', 'Konfigurasi ujian tidak ditemukan', 'error');
    return;
  }

  console.log('Initializing exam with config:', window.examConfig);

  // Call initializeNavigation to setup main exam UI and data
  initializeNavigation();

  // Call anti-cheating specific initialization
  initAntiCheating();

  // Initialize Medium-Zoom (Moved here to ensure it runs after general init)
  const zoomableImages = document.querySelectorAll(
    '.soal-media img, .opsi-media img'
  );
  let currentZoomInstance; // Declare here to make it accessible for listeners

  currentZoomInstance = mediumZoom(zoomableImages, {
    margin: 24,
    background: '#000000e6',
    scrollOffset: 0,
    // No 'template' property needed as discussed
  });

  // Attach Medium-Zoom event listeners
  currentZoomInstance.on('open', () => {
    console.log('MediumZoom: Zoom opened.');
  });

  currentZoomInstance.on('closed', () => {
    console.log('MediumZoom: Zoom closed.');
    // Safeguard: Remove residual overlay if still present
    const zoomOverlay = document.querySelector('.medium-zoom-overlay');
    if (zoomOverlay) {
      zoomOverlay.remove();
      console.log('MediumZoom: Removed residual overlay.');
    }
    // Attempt to re-focus on an interactive element to prevent 'lock'
    $('#btn-prev-soal').focus();
  });

  // Ensure navigation buttons can close the zoom
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
  });

  // Handle image loading states (remains as is)
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
