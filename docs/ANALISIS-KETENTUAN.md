# Analisis ketentuan Tugas Mandiri

Bagian yang dijadikan dasar adalah **Tugas Praktikum Individu — Containerize Project Pribadi** pada modul yang diberikan. Bagian inilah yang dimaksud sebagai Tugas Mandiri. Proyek yang disiapkan bernama **Catatan Praktikum** dan bukan aplikasi Portify.

## Tujuan pembelajaran

Tujuan berikut disarikan dari pekerjaan dan bukti yang diminta dalam bagian tugas: membuat image aplikasi sendiri, mengelola aplikasi dan database dengan Compose, membatasi jaringan database, mempertahankan data, memeriksa kesiapan service, menguji pemulihan setelah VM restart, dan menjalankan aplikasi melalui IP publik Azure VM milik sendiri.

## Pemetaan persyaratan wajib

| Ketentuan sumber | Penerapan pada proyek | Cara membuktikan |
| --- | --- | --- |
| Dockerfile ditulis untuk aplikasi sendiri | Dockerfile berbasis PHP 8.4 Apache, dengan ekstensi PDO MySQL | Tampilkan Dockerfile dan hasil build |
| Minimal dua service aplikasi dan database | `app` dan `database` dengan MySQL 8.4 | Tampilkan `compose.yaml` dan `docker compose ps` |
| Database melalui network internal tanpa port publik | `database` hanya pada `backend` dengan `internal: true`; tidak memiliki `ports` | Periksa Compose, port binding container, dan network |
| Data tetap ada setelah container dihapus dan dijalankan ulang | Named volume `mysql_data` menyimpan `/var/lib/mysql` | Tambah catatan, jalankan `down` dan `up -d`, lalu buka catatan yang sama |
| Healthcheck database dan dependensi kesiapan | Healthcheck database serta `app.depends_on.database.condition: service_healthy` | Tampilkan konfigurasi dan status healthy |
| Restart policy setiap service | Kedua service memakai `restart: unless-stopped` | Periksa kebijakan lalu restart VM saat service berjalan |
| Rahasia dalam `.env`, tidak masuk repository | `.env` diabaikan Git dan build context; `.env.example` tanpa password asli | Periksa `.gitignore`, `.dockerignore`, dan file yang dilacak Git |
| Azure VM sendiri dan HTTP melalui IP publik | Port aplikasi pada VM adalah 80; akses `http://IP_PUBLIC_VM` | Screenshot browser dan Overview VM dengan IP yang sama |

Healthcheck aplikasi juga disediakan untuk memeriksa HTTP dan koneksi database. Ini membantu memastikan status aplikasi siap digunakan saat demonstrasi.

## Batas proyek

Aplikasi hanya menyediakan satu isian catatan, tombol simpan, dan daftar catatan beserta waktunya dalam WIB. Isi harus tidak kosong dan maksimal 1000 karakter Unicode. Penyimpanan memakai satu tabel `catatan` dengan kolom `id`, `isi`, dan `dibuat_pada`.

PHP native dan MySQL dipilih agar tidak memerlukan framework, Composer, Node.js, atau proses build frontend. Tidak ada login, edit, hapus, unggah file, layanan email, maupun integrasi lain. Dockerfile menggunakan satu tahap karena tidak ada artefak kompilasi yang harus dipisahkan; **multi-stage build disarankan dalam modul, bukan diwajibkan**.

Network `frontend` menghubungkan aplikasi ke sisi web. Aplikasi juga bergabung ke `backend` untuk mengakses database melalui nama service `database`. Database tidak memerlukan IP publik atau aturan inbound 3306. Konsep network internal mengikuti [dokumentasi network Compose](https://docs.docker.com/reference/compose-file/networks/).

## Penafsiran pengujian restart

Modul menyediakan alternatif menghentikan container atau me-restart VM. Pengujian proyek menggunakan **restart VM ketika kedua service sedang berjalan**. Jangan menghentikan service lebih dahulu dengan `docker stop`, `compose stop`, atau `compose down`.

Kebijakan `unless-stopped` tidak menyalakan kembali container yang sengaja dihentikan manual. Karena itu, `docker stop` lalu berharap container otomatis hidup bukan bukti yang tepat. Setelah VM restart, periksa service sebelum menjalankan perintah Compose yang dapat menyalakannya secara manual. Rujukan: [restart policy Docker](https://docs.docker.com/engine/containers/start-containers-automatically/).

## Administrasi dan batas bukti

- Tugas dikerjakan individu. Nama VM, IP publik, data pengujian, dan screenshot harus berasal dari VM masing-masing.
- Pengumpulan berupa PDF pada **Template Laporan Praktikum Individu**, disertai link repository. Nama file: `NIM_Nama_TugasDocker.pdf`.
- Template resmi belum disertakan. `BAHAN-LAPORAN.md` menyediakan isi yang dapat dipindahkan ke template tersebut.
- Modul mencantumkan durasi satu minggu dan batas pengumpulan **20 Oktober 2026 pukul 23.59 WIB**. Tanggal sumber tersebut telah lewat pada tanggal penyusunan paket; konfirmasi ketentuan pengumpulan yang berlaku kepada pengampu/asisten.
- Jika belum memiliki proyek pribadi, sumber meminta konsultasi dengan asisten paling lambat dua hari setelah tugas diberikan. Pembuatan aplikasi ini tidak menghapus ketentuan tersebut.
- Deploy Azure dan pengambilan screenshot dilakukan sendiri oleh praktikan mengikuti panduan. Paket ini tidak menyatakan pengujian Azure telah berhasil sebelum ada hasil nyata.
- Setelah bukti selesai, lakukan Stop melalui Azure Portal hingga berstatus **Stopped (deallocated)**. Siapkan VM agar dapat dinyalakan kembali ketika diperiksa.

Panduan operasional tersedia di [PANDUAN-AZURE.md](PANDUAN-AZURE.md), sedangkan urutan bukti dan lembar hasil ada di [BUKTI-PENGUJIAN.md](BUKTI-PENGUJIAN.md).
