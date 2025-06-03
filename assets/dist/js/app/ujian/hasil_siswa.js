// File: assets/dist/js/app/ujian/hasil_siswa.js

// Pastikan variabel global ini didefinisikan di view PHP sebelum skrip ini jika diperlukan:
// const BASE_URL;
// const ID_HASIL_UJIAN_ENC_GLOBAL; // ID h_ujian yang dienkripsi, dari view hasil_siswa.php
// const CSRF_TOKEN_NAME;
// const CSRF_HASH;

$(document).ready(function () {
  // Fungsi ajaxcsrf() jika Anda menggunakannya secara global dari template
  // if (typeof ajaxcsrf === 'function') {
  //     ajaxcsrf(); // Panggil jika ada dan dibutuhkan untuk setup CSRF global
  // }

  console.log('Halaman Hasil Ujian Siswa dimuat.');

  // Contoh event listener jika ada tombol aksi di halaman hasil,
  // misalnya tombol untuk kembali atau mencetak (jika cetak via JS/AJAX).

  // Tombol Kembali ke Daftar Ujian (jika tidak menggunakan link HTML biasa)
  $('#btn-kembali-ke-daftar').on('click', function (e) {
    e.preventDefault();
    window.location.href = BASE_URL + 'ujian/list_ujian_siswa';
  });

  // Tombol Cetak Hasil (jika Anda ingin menghandle via JS)
  // Ini adalah contoh, implementasi cetak bisa sangat bervariasi.
  // Biasanya, link langsung ke method controller yang generate PDF lebih umum.
  $('#btn-cetak-hasil-ujian').on('click', function (e) {
    e.preventDefault();
    let urlCetak = $(this).attr('href'); // Ambil URL dari atribut href tombol

    if (urlCetak) {
      // Opsi 1: Buka di tab baru
      window.open(urlCetak, '_blank');

      // Opsi 2: Jika ingin ada konfirmasi atau proses AJAX dulu
      // Swal.fire({
      //     title: 'Cetak Hasil',
      //     text: "Apakah Anda ingin mencetak hasil ujian ini?",
      //     icon: 'question',
      //     showCancelButton: true,
      //     confirmButtonText: 'Ya, Cetak!',
      //     cancelButtonText: 'Batal'
      // }).then((result) => {
      //     if (result.isConfirmed) {
      //         window.open(urlCetak, '_blank');
      //         // Atau jika perlu request AJAX dulu:
      //         // $.ajax({ ... });
      //     }
      // });
    } else {
      Swal.fire('Error', 'URL untuk mencetak hasil tidak ditemukan.', 'error');
    }
  });

  // Logika lain yang mungkin diperlukan di halaman hasil bisa ditambahkan di sini.
  // Misalnya, jika ada grafik nilai atau analisis jawaban yang interaktif (lebih kompleks).
});
