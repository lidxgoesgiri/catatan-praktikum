# Catatan Praktikum

Aplikasi satu halaman untuk menambah dan membaca catatan. Dibuat sebagai proyek pribadi sederhana untuk Tugas Praktikum Individu Containerize Project Pribadi. Stack: PHP 8.4 dengan Apache dan MySQL 8.4. Tidak membutuhkan Composer, Node.js, atau instalasi PHP/MySQL di host.

Data berada pada MySQL, bukan pada file lokal atau memori browser. Aplikasi menggunakan prepared statement, escaping HTML, token formulir, serta validasi isi maksimal 1.000 karakter Unicode di server. Catatan terbaru ditampilkan paling atas dan waktu ditampilkan dalam WIB.

## Mulai di laptop Windows

Prasyarat: Docker Desktop sudah berjalan dengan Linux containers dan Docker Compose tersedia. Buka PowerShell di folder yang berisi file `compose.yaml`.

1. Periksa Docker dan Compose.

   ```powershell
   docker version
   docker compose version
   ```

   `docker version` harus menampilkan bagian Client dan Server. Bila Server tidak tersedia, buka Docker Desktop dan tunggu engine aktif.

2. Salin contoh environment.

   ```powershell
   Copy-Item .env.example .env
   notepad .env
   ```

   Isi `DB_PASSWORD` dan `MYSQL_ROOT_PASSWORD` dengan dua password berbeda yang panjangnya minimal 24 karakter. Untuk menghasilkan masing-masing password berupa 32 karakter huruf/angka, jalankan perintah berikut dua kali, lalu salin hasilnya ke kolom password yang sesuai. Jangan ambil screenshot saat password ditampilkan.

   ```powershell
   [guid]::NewGuid().ToString('N')
   ```

   Simpan `.env`. Kedua password sengaja kosong pada `.env.example`; Compose akan menolak berjalan sebelum diisi. Biarkan `DB_DATABASE=catatan_praktikum` dan `DB_USERNAME=catatan_user`. File `.env` diabaikan Git dan tidak disalin ke image.

3. Periksa konfigurasi tanpa menampilkan rahasia, lalu bangun image.

   ```powershell
   docker compose config --quiet
   docker build -t catatan-praktikum-app:local .
   ```

4. Jalankan kedua service dan tunggu sehat.

   ```powershell
   docker compose up -d --build --wait --wait-timeout 180
   docker compose ps
   ```

   Proses pertama membutuhkan internet untuk mengambil image. Jika versi Compose tidak mengenal `--wait`, gunakan `docker compose up -d --build`, lalu periksa `docker compose ps` sampai kedua service healthy.

5. Buka <http://localhost>. Tambahkan catatan, klik **Simpan catatan**, lalu muat ulang halaman. Catatan tetap tampil dan jumlah catatan bertambah satu. Buka <http://localhost/health.php> bila ingin memeriksa kesiapan; hasil normal adalah `OK`.

   Bila port 80 laptop dipakai aplikasi lain, ubah `APP_PORT=8080` pada `.env`, jalankan kembali `docker compose up -d --wait --wait-timeout 180`, lalu buka <http://localhost:8080>. Pada Azure gunakan `APP_PORT=80` agar sesuai alamat akses pada tugas.

## Struktur dan alur aplikasi

```text
catatan-praktikum/
├── Dockerfile
├── compose.yaml
├── .env.example
├── .gitignore
├── .dockerignore
├── .gitattributes
├── app/
│   ├── public/
│   │   ├── index.php           # Formulir, validasi, simpan, daftar
│   │   ├── health.php          # Kesiapan HTTP dan akses tabel database
│   │   └── assets/style.css
│   └── src/database.php        # Koneksi PDO ke service database
├── database/init.sql          # Pembuatan tabel pada volume baru
├── docker/
│   ├── apache.conf            # Document root hanya app/public
│   └── php.ini
└── docs/
```

Browser mengakses port 80 pada service `app`. PHP membuka koneksi ke host `database` melalui network `backend` yang memiliki `internal: true`. Database hanya bergabung ke network tersebut dan tidak memublikasikan port ke host. Service `app` juga bergabung ke network `frontend` agar HTTP dapat diakses dari luar.

SQL inisialisasi membuat satu tabel `catatan` dengan kolom `id`, `isi`, dan `dibuat_pada`. Image MySQL membuat database dan akun aplikasi berdasarkan environment. Akun aplikasi dipakai oleh PHP; password root hanya diberikan ke service database.

Database healthcheck memeriksa login akun aplikasi dan akses tabel `catatan`. Compose menunggu `service_healthy` sebelum membuat service aplikasi. Aplikasi juga memiliki healthcheck melalui HTTP agar kedua status mudah diperiksa dalam demonstrasi. Dockerfile menggunakan single-stage build karena kode PHP langsung dijalankan tanpa kompilasi aset.

## Pengujian singkat

Pemeriksaan fungsi yang sudah dilakukan dan batas pengujian saat pembuatan paket dicatat di [Hasil Uji Lokal](docs/HASIL-UJI-LOKAL.md). Pengujian container dan Azure tetap harus dijalankan pada target; hasil alternatif lokal tidak dianggap sebagai kelulusan target Docker.

1. Tambahkan catatan dengan penanda unik, misalnya `Uji persistensi NIM Anda tanggal dan waktu pengujian`.
2. Pastikan catatan muncul dan muat ulang browser untuk memastikan tersimpan.
3. Hentikan dan hapus container tanpa menghapus volume.

   ```powershell
   docker compose down
   docker compose up -d --wait --wait-timeout 180
   docker compose ps
   ```

4. Buka kembali aplikasi. Catatan dengan penanda yang sama harus masih ada.
5. Periksa kolom PORTS pada `docker compose ps`. Database boleh menampilkan `3306/tcp` atau `33060/tcp` sebagai port internal image, tetapi tidak boleh ada pemetaan seperti `0.0.0.0:3306->3306/tcp`.

Jangan menambahkan opsi `-v` pada `down` untuk uji persistensi karena opsi tersebut menghapus named volume. Jangan mengganti nama proyek Compose saat menguji: nama proyek menentukan named volume yang dipakai.

## Operasi dan pemecahan masalah

```powershell
# Status dan log tanpa mencetak isi .env
docker compose ps
docker compose logs --tail 60 app database

# Menghentikan sementara tanpa menghapus data
docker compose down

# Menyalakan kembali
docker compose up -d --wait --wait-timeout 180

# Memeriksa sintaks PHP setelah mengubah kode
docker compose exec app php -l public/index.php
docker compose exec app php -l src/database.php

# Membangun ulang setelah mengubah kode PHP atau CSS
docker compose up -d --build --wait --wait-timeout 180
```

- **Password belum diisi:** lengkapi `.env`, lalu ulangi `docker compose config --quiet`.
- **Port sudah dipakai:** gunakan `APP_PORT=8080` untuk laptop. Pada VM, periksa program yang memakai port 80.
- **Database belum sehat:** tunggu inisialisasi, kemudian periksa log. Healthcheck menunggu tabel dan akun aplikasi siap.
- **Password diubah setelah volume terisi:** environment MySQL hanya membuat akun saat volume baru. Mengubah `.env` tidak mengubah password akun yang sudah tersimpan. Gunakan password awal yang cocok atau ubah password melalui MySQL; jangan menghapus volume yang memuat bukti/data penting.
- **Kode yang diubah belum tampil:** source disalin ke image saat build. Jalankan kembali dengan `--build` dan muat ulang browser.
- **Service unhealthy saat database berhenti:** aplikasi memerlukan database. Setelah database pulih, muat ulang halaman dan tunggu healthcheck berikutnya. Status unhealthy sendiri tidak memicu restart policy.

## Deployment dan screenshot Azure secara manual

Ikuti [Panduan Azure](docs/PANDUAN-AZURE.md), lalu [Bukti Pengujian](docs/BUKTI-PENGUJIAN.md). Semua langkah Portal, SSH, restart VM, dan screenshot dilakukan oleh Anda. Bukti browser harus menampilkan IP publik VM yang sama dengan halaman Overview Azure.

Uji restart menggunakan **restart VM ketika kedua service berjalan**, kemudian periksa keduanya kembali healthy tanpa menjalankan `compose up` secara manual setelah reboot. `unless-stopped` menghormati penghentian manual melalui `docker stop`; karena itu penghentian tersebut tidak dijadikan bukti restart otomatis.

[Analisis Ketentuan](docs/ANALISIS-KETENTUAN.md) memetakan proyek ke tugas. [Bahan Laporan](docs/BAHAN-LAPORAN.md) dapat dipindahkan ke template laporan resmi setelah Anda mengisi hasil dan memasukkan screenshot nyata. Laporan akhir mengikuti format `NIM_Nama_TugasDocker.pdf` dan menyertakan link repository. Tidak ada screenshot Azure atau klaim deployment Azure dalam paket ini.

## Menyiapkan repository GitHub sendiri

Pada GitHub, buat repository kosong bernama `catatan-praktikum` tanpa menambahkan README atau `.gitignore` otomatis. Lalu jalankan dari folder proyek pada PowerShell laptop. Ganti `USERNAME_GITHUB` dengan username sendiri.

```powershell
git init
git add .
git status --short
git ls-files --error-unmatch .env
```

Perintah terakhir seharusnya gagal karena `.env` tidak dilacak. Pastikan daftar file tidak memuat `.env` atau rahasia sebelum commit. File `.env.example` harus tetap ada.

```powershell
git commit -m "Buat aplikasi Catatan Praktikum dengan Docker Compose"
git branch -M main
git remote add origin https://github.com/USERNAME_GITHUB/catatan-praktikum.git
git push -u origin main
```

Login GitHub melalui mekanisme autentikasi Git yang tersedia di laptop; jangan menaruh token pada URL repository. Jika Git meminta identitas commit, atur nama dan email milik sendiri untuk repository ini dengan `git config user.name` dan `git config user.email`, lalu ulangi commit. Jika remote `origin` sudah ada, periksa `git remote -v` dan gunakan repository sendiri yang benar; jangan menimpa repository yang tidak berkaitan. Salin URL repository ke laporan.

## Referensi

- [Image PHP resmi dan pemasangan ekstensi](https://hub.docker.com/_/php)
- [Image MySQL resmi dan perilaku inisialisasi volume](https://hub.docker.com/_/mysql)
- [Urutan startup Compose dan service_healthy](https://docs.docker.com/compose/how-tos/startup-order/)
- [Network Compose](https://docs.docker.com/compose/how-tos/networking/)
- [Restart policy Docker](https://docs.docker.com/engine/containers/start-containers-automatically/)
