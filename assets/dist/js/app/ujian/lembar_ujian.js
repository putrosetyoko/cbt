// File: assets/dist/js/app/ujian/lembar_ujian_siswa.js

// Pastikan variabel global ini didefinisikan di view PHP sebelum skrip ini:
// const BASE_URL;
// const ID_H_UJIAN_ENC_GLOBAL; // ID h_ujian yang dienkripsi, dari view lembar_ujian_siswa.php
// const JUMLAH_SOAL_TOTAL_GLOBAL; // Jumlah total soal di ujian ini
// const CSRF_TOKEN_NAME;
// const CSRF_HASH;

var currentSoalIndex = 1; // Nomor soal yang sedang ditampilkan (mulai dari 1)
var totalSoalUjian;
var idHUjianEnc;
var jawabanSiswa = {}; // Objek untuk menyimpan jawaban: {id_soal: {j: "A", r: "N"}, ...}

$(document).ready(function () {
  // Inisialisasi
  totalSoalUjian =
    parseInt($('#form-lembar-ujian input[name="jumlah_soal_total"]').val()) ||
    0;
  idHUjianEnc = $('#form-lembar-ujian input[name="id_h_ujian_enc"]').val();

  // Load jawaban tersimpan jika ada (dari variabel PHP yang di-render ke JS di view)
  // Contoh jika $jawaban_tersimpan di-render sebagai objek JS:
  // if (typeof JAWABAN_TERSIMPAN_PHP !== 'undefined') {
  //     jawabanSiswa = JAWABAN_TERSIMPAN_PHP;
  //     updateNavigasiSoalStyles(); // Update style tombol navigasi
  // }

  // Timer Ujian
  const timerElement = $('#timer-ujian');
  if (timerElement.length) {
    const waktuSelesai = timerElement.data('waktu-selesai');
    const sisaWaktu = parseInt(timerElement.data('sisa-waktu'));

    if (waktuSelesai && sisaWaktu > 0) {
      // Start countdown
      updateTimer();
    } else {
      console.error('Data waktu akhir ujian tidak valid atau tidak ada.');
      timerElement.text('Error Timer');
    }
  }

  // Tampilkan soal pertama
  showSoal(currentSoalIndex);
  updateNavigasiButtons();
  updateStatusRaguButton();

  // Event Listener untuk tombol navigasi soal (panel kiri)
  $('#panel-navigasi-soal').on('click', '.btn-soal-nav', function () {
    let nomorSoal = $(this).data('nomor');
    showSoal(nomorSoal);
  });

  // Event Listener untuk tombol navigasi Prev/Next
  $('.btn-nav-soal').on('click', function () {
    let navigasi = $(this).data('navigasi');
    if (navigasi === 'next' && currentSoalIndex < totalSoalUjian) {
      currentSoalIndex++;
    } else if (navigasi === 'prev' && currentSoalIndex > 1) {
      currentSoalIndex--;
    }
    showSoal(currentSoalIndex);
  });

  // Event Listener untuk memilih jawaban (opsi radio)
  $('#area-soal-ujian').on('change', 'input[type="radio"]', function () {
    let idSoal = $(this).closest('.panel-soal').data('id-soal');
    let jawaban = $(this).val(); // Ini adalah original_key (A,B,C,D,E)
    let nomorSoalDisplay = $(this).data('nomor-soal-display');

    if (!jawabanSiswa[idSoal]) {
      jawabanSiswa[idSoal] = { j: '', r: 'N' };
    }
    jawabanSiswa[idSoal].j = jawaban;
    // Jika menjawab, otomatis status ragu jadi 'N' kecuali diubah lagi
    if (jawabanSiswa[idSoal].r === 'Y' && jawaban !== '') {
      // jawabanSiswa[idSoal].r = 'N'; // Opsional: hilangkan ragu jika sudah dijawab
    }

    updateNavigasiSoalSingle(nomorSoalDisplay, idSoal); // Update style tombol navigasi
    updateStatusRaguButton(); // Update tombol ragu-ragu utama
    simpanJawabanAjax(idSoal, jawaban, jawabanSiswa[idSoal].r);
  });

  // Event Listener untuk tombol Ragu-ragu
  $('#btn-ragu-ragu').on('click', function () {
    let idSoalAktif = $('.panel-soal:visible').data('id-soal');
    if (!jawabanSiswa[idSoalAktif]) {
      jawabanSiswa[idSoalAktif] = { j: '', r: 'N' };
    }
    jawabanSiswa[idSoalAktif].r =
      jawabanSiswa[idSoalAktif].r === 'N' ? 'Y' : 'N';

    updateNavigasiSoalSingle(currentSoalIndex, idSoalAktif);
    updateStatusRaguButton();
    simpanJawabanAjax(
      idSoalAktif,
      jawabanSiswa[idSoalAktif].j,
      jawabanSiswa[idSoalAktif].r
    );
  });

  // Event Listener untuk tombol Selesai Ujian
  $('#btn-selesai-ujian').on('click', function () {
    konfirmasiSelesaiUjian();
  });

  // Timer function
  function updateTimer() {
    const timerElement = $('#timer-ujian');
    const waktuSelesai = new Date(timerElement.data('waktu-selesai')).getTime();

    const timer = setInterval(function () {
      const now = new Date().getTime();
      const distance = waktuSelesai - now;

      if (distance < 0) {
        clearInterval(timer);
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
}); // End $(document).ready

function showSoal(nomorSoal) {
  currentSoalIndex = parseInt(nomorSoal);
  if (currentSoalIndex < 1) currentSoalIndex = 1;
  if (currentSoalIndex > totalSoalUjian && totalSoalUjian > 0)
    currentSoalIndex = totalSoalUjian;

  $('.panel-soal').hide();
  $('#soal-' + currentSoalIndex).show();
  $('#display-nomor-soal').text(currentSoalIndex);

  // Update style active di navigasi soal
  $('#panel-navigasi-soal .btn-soal-nav').removeClass('active');
  $(
    '#panel-navigasi-soal .btn-soal-nav[data-nomor="' + currentSoalIndex + '"]'
  ).addClass('active');

  updateNavigasiButtons();
  updateStatusRaguButton();
}

function updateNavigasiButtons() {
  if (totalSoalUjian <= 0) {
    $(
      '#btn-prev-soal, #btn-next-soal, #btn-ragu-ragu, #btn-selesai-ujian'
    ).hide();
    return;
  }
  $('#btn-prev-soal').toggle(currentSoalIndex > 1);
  $('#btn-next-soal').toggle(currentSoalIndex < totalSoalUjian);
  $('#btn-selesai-ujian').toggle(currentSoalIndex === totalSoalUjian); // Hanya tampil di soal terakhir
}

function updateStatusRaguButton() {
  let idSoalAktif = $('.panel-soal:visible').data('id-soal');
  let isRagu = jawabanSiswa[idSoalAktif] && jawabanSiswa[idSoalAktif].r === 'Y';
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
  navButton.removeClass('btn-default btn-success btn-warning');
  if (jawabanSiswa[idSoal]) {
    if (jawabanSiswa[idSoal].j !== '') {
      // Jika sudah ada jawaban
      navButton.addClass(
        jawabanSiswa[idSoal].r === 'Y' ? 'btn-warning' : 'btn-success'
      );
    } else if (jawabanSiswa[idSoal].r === 'Y') {
      // Belum dijawab tapi ragu
      navButton.addClass('btn-warning');
    } else {
      // Belum dijawab, tidak ragu
      navButton.addClass('btn-default');
    }
  } else {
    navButton.addClass('btn-default'); // Belum pernah diinteraksi
  }
}

// Fungsi untuk menyimpan jawaban via AJAX
function simpanJawabanAjax(idSoal, jawaban, raguStatus) {
  // console.log(`Simpan: Soal ID ${idSoal}, Jawaban: ${jawaban}, Ragu: ${raguStatus}`);
  $.ajax({
    url: BASE_URL + 'ujian/simpan_jawaban_ajax',
    type: 'POST',
    data: {
      id_h_ujian_enc: idHUjianEnc,
      id_soal: idSoal,
      jawaban: jawaban,
      ragu_ragu: raguStatus,
      [CSRF_TOKEN_NAME]: CSRF_HASH, // Kirim CSRF jika tidak dihandle global
    },
    dataType: 'json',
    success: function (response) {
      if (!response.status) {
        console.error('Gagal simpan jawaban:', response.message);
        // Mungkin tampilkan notifikasi kecil jika gagal simpan
      } else {
        // console.log('Jawaban disimpan:', response.message);
      }
      // Update CSRF Hash jika server mengirim yang baru (jika csrf_regenerate=TRUE)
      // if(response.csrf_hash) { CSRF_HASH = response.csrf_hash; }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error(
        'AJAX Error (simpan_jawaban_ajax):',
        textStatus,
        errorThrown
      );
    },
  });
}

function konfirmasiSelesaiUjian() {
  let belumDijawab = 0;
  let raguRaguCount = 0;
  for (let idSoal in jawabanSiswa) {
    if (jawabanSiswa.hasOwnProperty(idSoal)) {
      if (jawabanSiswa[idSoal].j === '') {
        belumDijawab++;
      }
      if (jawabanSiswa[idSoal].r === 'Y') {
        raguRaguCount++;
      }
    }
  }
  // Hitung soal yang belum pernah disentuh sama sekali (tidak ada di object jawabanSiswa)
  let soalYangBelumDisentuh = totalSoalUjian - Object.keys(jawabanSiswa).length;
  belumDijawab += soalYangBelumDisentuh;

  let pesanKonfirmasi = 'Anda yakin ingin menyelesaikan ujian ini?';
  if (belumDijawab > 0) {
    pesanKonfirmasi += `<br><strong style='color:red;'>Ada ${belumDijawab} soal yang belum terjawab.</strong>`;
  }
  if (raguRaguCount > 0) {
    pesanKonfirmasi += `<br><strong style='color:orange;'>Ada ${raguRaguCount} soal yang ditandai ragu-ragu.</strong>`;
  }
  pesanKonfirmasi +=
    '<br>Setelah selesai, Anda tidak dapat mengubah jawaban Anda lagi.';

  Swal.fire({
    title: 'Konfirmasi Selesai Ujian',
    html: pesanKonfirmasi,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, Selesaikan!',
    cancelButtonText: 'Batal',
  }).then((result) => {
    if (result.isConfirmed) {
      prosesSelesaikanUjian();
    }
  });
}

function prosesSelesaikanUjian() {
  let submitButton = $('#btn-selesai-ujian');
  let originalButtonText = submitButton.html();
  submitButton
    .html('<i class="fa fa-spinner fa-spin"></i> Memproses...')
    .prop('disabled', true);

  // Kirim semua jawaban terakhir untuk memastikan konsistensi
  // Format: { "id_soal_1": {"j":"A", "r":"N"}, "id_soal_2": ... }
  let jawabanAkhirBatch = {};
  $('.panel-soal').each(function () {
    let idSoal = $(this).data('id-soal');
    let jawabanTerpilih = $(this)
      .find('input[name="jawaban_soal_' + idSoal + '"]:checked')
      .val();
    let statusRagu =
      jawabanSiswa[idSoal] && jawabanSiswa[idSoal].r === 'Y' ? 'Y' : 'N';
    jawabanAkhirBatch[idSoal] = {
      j: jawabanTerpilih || '',
      r: statusRagu,
    };
  });

  $.ajax({
    url: BASE_URL + 'ujian/selesaikan_ujian',
    type: 'POST',
    data: {
      id_h_ujian_enc: idHUjianEnc,
      jawaban_akhir_batch: JSON.stringify(jawabanAkhirBatch), // Kirim sebagai JSON string
      [CSRF_TOKEN_NAME]: CSRF_HASH,
    },
    dataType: 'json',
    success: function (response) {
      submitButton.html(originalButtonText).prop('disabled', false);
      if (response.status) {
        Swal.fire(
          'Sukses!',
          response.message || 'Ujian telah diselesaikan.',
          'success'
        ).then(() => {
          if (response.redirect_url) {
            window.location.href = response.redirect_url;
          } else {
            window.location.href = BASE_URL + 'ujian/list_ujian_siswa'; // Fallback
          }
        });
      } else {
        Swal.fire(
          'Gagal!',
          response.message || 'Gagal menyelesaikan ujian.',
          'error'
        );
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
}

// Fungsi ini akan dipanggil oleh timer di _footer.php (topnav) saat waktu habis
function waktuHabis() {
  // Cek apakah user masih di halaman ujian
  if ($('#form-lembar-ujian').length > 0) {
    Swal.fire({
      title: 'Waktu Habis!',
      text: 'Waktu pengerjaan ujian Anda telah berakhir. Sistem akan memproses jawaban Anda.',
      icon: 'warning',
      allowOutsideClick: false,
      allowEscapeKey: false,
      showConfirmButton: false,
      timer: 3000,
    }).then(() => {
      window.location.reload();
    });
  }
}

// Fungsi sisawaktu dan countdown ada di _footer.php (topnav)
// Pastikan fungsi tersebut memanggil waktuHabis() saat timer mencapai nol.
// Contoh modifikasi di _footer.php (jika belum ada):
/*
function sisawaktu(t) {
    // ... (kode sisawaktu Anda) ...
    var x = setInterval(function() {
        // ... (kode countdown) ...
        if (dis < 0) {
            clearInterval(x);
            $('.sisawaktu').html("00:00:00");
            if (typeof waktuHabis === 'function') { // Panggil waktuHabis jika ada
                waktuHabis();
            }
        }
    }, 1000); // Interval 1 detik
    // ...
}
*/
