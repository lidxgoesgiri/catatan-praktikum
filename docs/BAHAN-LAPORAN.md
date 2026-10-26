# Bahan laporan praktikum Catatan Praktikum

Gunakan isi ini pada **Template Laporan Praktikum Individu** resmi. Isi identitas, hasil aktual, serta screenshot sendiri. Template resmi belum disertakan dalam paket. Teks bertanda kurung siku harus dilengkapi; jangan mengubah hasil yang belum diuji menjadi klaim keberhasilan.

## Identitas

| Keterangan | Isian |
| --- | --- |
| Nama | [Nama praktikan] |
| NIM dan kelas | [NIM dan kelas] |
| Mata kuliah | Komputasi Awan |
| Judul | Containerization dan deployment Catatan Praktikum dengan Docker Compose pada Azure VM |
| Tanggal pelaksanaan | [Tanggal dan jam WIB] |
| Repository | [URL repository sendiri] |
| Nama VM | [Nama VM sendiri] |
| IP publik saat pengujian | [IP publik yang sama dengan bukti browser dan Overview] |

## Tujuan

Praktikum ini bertujuan membuat image aplikasi sendiri, menjalankan aplikasi dan database sebagai dua service Docker Compose, membatasi akses database melalui network internal, mempertahankan data melalui volume, dan memeriksa kesiapan service menggunakan healthcheck. Deployment pada Azure VM digunakan untuk menguji akses publik melalui HTTP serta pemulihan service setelah VM restart.

## Alat dan bahan

- Laptop dengan browser, terminal PowerShell, SSH client, dan SSH key ed25519.
- Azure VM Ubuntu Server x64 milik praktikan, RAM minimal 8 GB dan disk OS 30 GB sesuai modul.
- Docker Engine dan Docker Compose plugin pada VM; cantumkan versi yang benar-benar digunakan.
- Source Catatan Praktikum, Dockerfile, `compose.yaml`, `.env` lokal VM, dan `.env.example` pada repository.
- PHP 8.4 Apache dan MySQL 8.4 yang dijalankan melalui image container.
- Repository GitHub sendiri dan Template Laporan Praktikum Individu.

Versi aktual Docker: [isi]. Versi aktual Compose: [isi]. Versi/image Ubuntu: [isi].

## Rancangan proyek

Catatan Praktikum menyediakan form untuk menyimpan isi catatan dan daftar catatan beserta waktu WIB. Aplikasi menggunakan PHP native dan PDO untuk mengakses satu tabel MySQL. Isi harus tidak kosong dan maksimal 1000 karakter Unicode. Kueri penyimpanan menggunakan parameter, sedangkan isi catatan ditampilkan sebagai teks HTML yang di-escape.

Browser mengakses port 80 VM yang diteruskan ke service `app`. Service `app` terhubung ke network `frontend` dan `backend`. Service `database` hanya terhubung ke `backend` dengan `internal: true` dan tidak mempunyai port host yang dipublikasikan. Named volume `mysql_data` menyimpan data MySQL agar tidak bergantung pada umur container.

Compose menunggu healthcheck database melalui `depends_on` dengan kondisi `service_healthy` sebelum memulai aplikasi. Healthcheck aplikasi memeriksa endpoint HTTP dan koneksi database. Kedua service memakai `restart: unless-stopped`.

Sisipkan struktur folder, Dockerfile, serta bagian penting Compose dengan penjelasan singkat. Gunakan S01–S03 atau potongan kode yang terbaca.

## Langkah kerja

Uraikan langkah yang benar-benar dilakukan dengan mengacu ke [PANDUAN-AZURE.md](PANDUAN-AZURE.md):

1. Menyiapkan VM Ubuntu dan SSH key milik sendiri, lalu membuka SSH hanya dari IP laptop serta HTTP port 80 untuk akses publik.
2. Memasang atau memverifikasi Docker Engine dan Compose plugin.
3. Menyalin proyek dari repository sendiri ke VM.
4. Membuat `.env` dari `.env.example` dan mengisi password acak tanpa mengunggah rahasia ke repository.
5. Membangun image dengan `docker build`, memvalidasi Compose, dan menjalankan kedua service.
6. Membuka aplikasi melalui IP publik dan menyimpan catatan uji.
7. Menguji persistensi, isolasi jaringan, serta pemulihan service melalui restart VM.
8. Mengumpulkan bukti lalu melakukan Stop/deallocate pada Azure Portal.

Tambahkan perintah serta perubahan konfigurasi yang memang Anda gunakan. Jangan menyatakan langkah yang tidak dijalankan sebagai pelaksanaan.

## Hasil pengujian

Tabel berikut belum berisi hasil. Lengkapi setelah menjalankan [BUKTI-PENGUJIAN.md](BUKTI-PENGUJIAN.md).

| Pengujian | Kriteria keberhasilan | Hasil aktual | Bukti |
| --- | --- | --- | --- |
| Build dan kesiapan service | Build selesai; `app` dan `database` healthy di VM | [Belum diuji di Azure] | [S02–S04] |
| Akses publik | HTTP melalui IP publik, IP sesuai Overview | [Belum diuji di Azure] | [S05–S06] |
| Tambah dan daftar catatan | Catatan tersimpan dan tampil setelah refresh | [Belum diuji di Azure] | [S05] |
| Validasi | Kosong dan terlalu panjang ditolak; Unicode terbaca | [Belum diuji di Azure] | [Bukti aktual] |
| Persistensi | Catatan sama tetap ada setelah `down` dan `up` | [Belum diuji di Azure] | [S07–S09] |
| Isolasi database | Tidak ada port host database; TCP 3306 tidak terjangkau dari laptop | [Belum diuji di Azure] | [S10–S11] |
| Restart VM | Service kembali healthy tanpa perintah start manual | [Belum diuji di Azure] | [S12–S13] |

Untuk setiap gambar, tulis caption yang menjelaskan perintah atau tindakan, hasil yang terlihat, dan kaitannya dengan ketentuan. Bila ada kegagalan, catat pesan kesalahan, penyebab yang ditemukan, perbaikan, serta hasil pengujian ulang.

## Pembahasan

Kembangkan pembahasan berikut berdasarkan hasil nyata:

1. **Dockerfile dan Compose.** Jelaskan peran image PHP Apache, ekstensi PDO MySQL, serta dua service. Single-stage build dipilih karena aplikasi tidak mempunyai langkah kompilasi frontend.
2. **Kesiapan service.** Jelaskan perbedaan container sedang berjalan dan database siap menerima koneksi. Hubungkan hasil healthcheck dengan `depends_on: service_healthy`.
3. **Persistensi.** Bandingkan catatan sebelum dan sesudah penghapusan container. Jelaskan mengapa volume database dipertahankan dan mengapa uji tidak menggunakan `down -v`.
4. **Jaringan.** Jelaskan pemetaan port 80 aplikasi dan ketiadaan pemetaan port database. Kaitkan network internal, hasil inspect, NSG, serta uji TCP dari laptop.
5. **Restart.** Jelaskan hasil restart VM saat service sebelumnya berjalan. Kebijakan `unless-stopped` tidak dimaksudkan untuk menyalakan container yang sengaja dihentikan manual.
6. **Pengelolaan resource.** Tuliskan status Stop/deallocate setelah bukti selesai dan kesiapan VM untuk dinyalakan kembali saat penilaian.

Rujukan teknis: [startup order Compose](https://docs.docker.com/compose/how-tos/startup-order/), [volume saat Compose down](https://docs.docker.com/reference/cli/docker/compose/down/), [network Compose](https://docs.docker.com/reference/compose-file/networks/), dan [restart policy](https://docs.docker.com/engine/containers/start-containers-automatically/).

## Kesimpulan

Lengkapi paragraf berikut sesudah pengujian:

> Pada pengujian tanggal [tanggal], aplikasi Catatan Praktikum [hasil akses publik] pada Azure VM [nama VM]. Pengujian persistensi menunjukkan [hasil faktual]. Pemeriksaan jaringan menunjukkan [hasil faktual]. Setelah VM restart, kedua service [hasil faktual tanpa start manual]. Berdasarkan bukti tersebut, ketentuan yang telah terpenuhi adalah [daftar singkat], sedangkan bagian yang masih memerlukan perbaikan adalah [isi bila ada].

## Pemeriksaan sebelum dikumpulkan

- Identitas, link repository, nama VM, dan IP publik benar.
- Seluruh hasil didukung bukti sendiri; tidak ada placeholder yang dianggap hasil.
- Screenshot browser menampilkan address bar dan cocok dengan Overview VM.
- Tidak ada password, `.env` lengkap, private key, atau token dalam laporan/repository.
- Laporan telah dipindahkan ke template resmi dan diekspor menjadi `NIM_Nama_TugasDocker.pdf`.
- VM sudah Stopped (deallocated) setelah sesi dan dapat dinyalakan kembali untuk penilaian.
