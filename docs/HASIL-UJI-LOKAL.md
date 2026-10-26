# Hasil pengujian lokal Catatan Praktikum

Pengujian dilakukan pada 26 Oktober 2026 WIB. Catatan ini mendokumentasikan pemeriksaan yang benar-benar dijalankan saat pembuatan proyek. Hasil di sini tidak menggantikan screenshot dan pengujian Azure milik praktikan.

## Lingkungan dan batas pengujian

Docker CLI 29.4.1 dan Docker Compose 5.1.3 tersedia. Validasi `docker compose config --quiet` dengan environment pengujian berisi password acak berhasil. Tanpa password, Compose menolak konfigurasi sebagaimana dirancang.

Docker Desktop 4.71.0 gagal memulai engine karena kesalahan pada socket internal `dockerInference`. Karena engine tidak tersedia, **build image, startup container PHP 8.4/MySQL 8.4, persistensi named volume, dan restart policy Docker belum dapat diuji**. Pemeriksaan konfigurasi dan peninjauan kode tidak dianggap sebagai kelulusan pengujian tersebut.

Pengujian fungsi memakai binary yang sudah terpasang: **PHP 8.2.12 dan MariaDB 10.4.32**, browser Microsoft Edge melalui Playwright, serta data sementara terpisah. Server hanya mendengarkan localhost. Tidak ada instalasi software, perubahan database pengguna, login Azure, atau deployment Azure. Paket proyek tetap menargetkan PHP 8.4 dan MySQL 8.4 melalui Docker.

## Pemeriksaan yang dijalankan

| Pemeriksaan | Hasil aktual |
| --- | --- |
| Sintaks `index.php`, `health.php`, `database.php` | Lulus `php -l`; tidak ada syntax error |
| Compose dengan environment lengkap | Validasi `config --quiet` berhasil |
| Password wajib kosong | Konfigurasi ditolak dengan pesan `DB_PASSWORD` belum diisi |
| HTTP dan akses tabel database | Halaman utama HTTP 200; `/health.php` HTTP 200 dengan isi `OK` |
| Keadaan kosong | Pesan belum ada catatan tampil; stylesheet termuat |
| Keyboard dan fokus | Tab mencapai textarea lalu tombol; outline fokus 3 px terlihat |
| Penyimpanan melalui formulir | Satu catatan multiline tersimpan dan tampil bersama pesan berhasil |
| Refresh setelah simpan | Catatan tetap satu; tidak terjadi duplikasi penyimpanan |
| Validasi kosong dan hanya spasi | Server mengembalikan HTTP 422; data tidak bertambah |
| Formulir tanpa token CSRF | Server mengembalikan HTTP 400; data tidak tersimpan |
| Panjang 1001 huruf | Ditolak HTTP 422; data tidak bertambah |
| Unicode | 1000 karakter emoji diterima HTTP 303 dan terbaca utuh; 1001 ditolak HTTP 422 |
| Teks HTML dan payload SQL | Tampil sebagai teks; JavaScript tidak dieksekusi dan tabel tetap dapat digunakan |
| Tampilan compact 375 x 812 | Tidak ada horizontal overflow; tombol minimal 44 px; screenshot diperiksa |
| Tampilan tablet 768 x 1024 | Tidak ada horizontal overflow; screenshot diperiksa |
| Tampilan desktop 1440 x 900 | Tidak ada horizontal overflow; screenshot diperiksa |
| Teks 1000 karakter tanpa spasi pada compact | Tetap terbungkus dalam layar; screenshot diperiksa |
| Console halaman | Tidak ada exception JavaScript atau pesan console error pada pemeriksaan akhir |
| Database uji dihentikan | Halaman utama HTTP 503 dan tombol dinonaktifkan; health endpoint HTTP 503 `NOT READY` |
| Input saat database tidak tersedia | Teks formulir dipertahankan pada respons kesalahan; detail PDO/password tidak ditampilkan di halaman |
| Database uji dijalankan kembali | Health endpoint kembali `OK`; empat catatan uji masih ada dan dapat dibaca |

Restart proses MariaDB pada direktori data uji membuktikan pemulihan fungsi dan penyimpanan data pada pengujian lokal tersebut. Ini **bukan** hasil `docker compose down/up`, pengujian named volume, atau restart VM.

Kontras warna juga diperiksa: teks utama 13,82:1; teks sekunder di permukaan putih 6,27:1; teks putih tombol 6,87:1; dan batas input terhadap putih 3,92:1. Nilai tersebut memenuhi ambang teks normal 4,5:1 serta batas kontrol 3:1 yang diperiksa.

Screenshot lokal digunakan untuk pemeriksaan internal tampilan dan tidak disertakan sebagai bukti Azure. Source tidak memuat data contoh atau hasil uji yang akan otomatis muncul saat deployment baru.

## Pemeriksaan yang masih harus dilakukan pada target

| Pengujian target Docker dan Azure | Status |
| --- | --- |
| Build Dockerfile dan menjalankan image PHP 8.4 | Belum teruji pada engine Docker |
| Inisialisasi MySQL 8.4 dan kedua service healthy | Belum teruji pada engine Docker |
| Data tetap ada setelah Compose down lalu up | Belum teruji pada engine Docker |
| Port binding dan network internal pada container berjalan | Konfigurasi telah ditinjau; runtime belum teruji |
| Service pulih setelah restart VM | Belum diuji |
| Akses melalui IP publik Azure dan kecocokan Overview | Belum diuji |

Jalankan [Panduan Azure](PANDUAN-AZURE.md) dan lengkapi [Bukti Pengujian](BUKTI-PENGUJIAN.md) untuk memverifikasi target tugas. Jika Docker Desktop lokal masih menampilkan error yang sama, lanjutkan deployment manual pada VM Ubuntu sesuai panduan; masalah Desktop lokal tidak menjadi bukti kegagalan maupun keberhasilan aplikasi pada VM.
