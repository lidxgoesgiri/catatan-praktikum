# Panduan menjalankan Catatan Praktikum di Azure VM

Panduan ini dikerjakan **manual oleh praktikan**. Gunakan akun, SSH key, repository, dan Azure VM milik sendiri agar bukti laporan sesuai kondisi sebenarnya. Aplikasi dan konfigurasi proyek sudah disiapkan; hasil deployment tetap harus diverifikasi pada VM Anda.

Blok **PowerShell laptop** dijalankan di Windows. Blok **Bash VM** dijalankan setelah berhasil SSH. Ganti `USERNAME_GITHUB`, `USER_VM`, dan `IP_PUBLIC_VM` dengan nilai sebenarnya sebelum menjalankan perintah.

## 1 Persiapan

Siapkan akun Azure yang dapat membuat VM, SSH client dan key ed25519 pada laptop, browser, serta repository GitHub berisi proyek. Bila VM Ubuntu yang memenuhi ketentuan sudah tersedia, gunakan VM tersebut dan langsung periksa konfigurasinya. Pembuatan VM di sini hanya prasyarat deployment tugas sendiri; aplikasi Portify tidak diperlukan.

Pada PowerShell laptop, periksa SSH dan lokasi public key:

```powershell
ssh -V
Test-Path "$env:USERPROFILE\.ssh\id_ed25519.pub"
```

Jika belum mempunyai key, buat sesuai petunjuk pra-praktikum:

```powershell
ssh-keygen -t ed25519
```

Jangan menimpa key yang sudah dipakai. Public key berakhiran `.pub` boleh ditempel pada Azure. Private key `id_ed25519` tetap di laptop dan tidak dimasukkan ke repository atau laporan.

## 2 Siapkan VM dan akses jaringan

Di Azure Portal, buat atau pilih VM Ubuntu Server x64 milik sendiri. Gunakan konfigurasi modul: RAM minimal 8 GB dan disk OS 30 GB. Ubuntu Server 24.04 LTS dapat dipakai. Pilih region dan ukuran yang tersedia pada subscription; baca ringkasan biaya sebelum membuat resource. Isi nama VM dengan identitas yang dapat dikenali sebagai milik Anda.

Pilih autentikasi **SSH public key**, gunakan public key ed25519 milik sendiri, dan catat username VM. Panduan pembuatan VM dan koneksi SSH tersedia pada [Microsoft Learn](https://learn.microsoft.com/en-us/azure/virtual-machines/linux/quick-create-portal).

Atur inbound rule pada Network Security Group:

| Port TCP | Sumber | Kegunaan |
| --- | --- | --- |
| 22 | IP publik laptop sendiri dengan prefix `/32` | SSH |
| 80 | Internet atau Any | Aplikasi HTTP untuk penilaian |
| 3306 | Tidak dibuat aturan allow | Database hanya untuk aplikasi di Docker |

HTTPS 443 tidak diperlukan untuk demonstrasi HTTP ini. Jika IP koneksi laptop berubah, perbarui sumber rule SSH. NSG memfilter trafik berdasarkan rule dan prioritas; periksa tidak ada rule lain yang membuka 3306. Rujukan: [aturan NSG Azure](https://learn.microsoft.com/en-us/azure/virtual-network/network-security-groups-overview).

Pastikan VM berstatus Running, lalu salin **Public IP address** pada halaman Overview. Jangan mengisi IP privat VM sebagai alamat browser.

## 3 Hubungkan laptop ke VM

PowerShell laptop:

```powershell
ssh -i "$env:USERPROFILE\.ssh\id_ed25519" USER_VM@IP_PUBLIC_VM
```

Pada koneksi pertama, cocokkan host key dengan informasi VM yang benar sebelum menerima. Setelah login, prompt yang tampil adalah terminal Bash VM.

## 4 Instal Docker Engine dan plugin Compose

Jika `sudo docker version` dan `sudo docker compose version` sudah berjalan, tidak perlu memasang ulang. Untuk VM Ubuntu baru, perintah berikut menggunakan repository resmi Docker. Apabila sebelumnya memasang `docker.io` atau paket Compose dari repository Ubuntu, baca bagian konflik paket pada [panduan instalasi resmi Docker Ubuntu](https://docs.docker.com/engine/install/ubuntu/) sebelum mengganti instalasi yang ada.

Bash VM:

```bash
sudo apt update
sudo apt install -y ca-certificates curl git unzip openssl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
```

Tambahkan sumber paket resmi:

```bash
sudo tee /etc/apt/sources.list.d/docker.sources >/dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo docker version
sudo docker compose version
sudo systemctl is-active docker
```

Hasil yang diharapkan adalah versi Docker dan Compose terbaca serta layanan `active`. Panduan ini menggunakan `sudo` untuk perintah Docker agar tidak perlu mengubah keanggotaan grup user.

## 5 Siapkan repository dan salin proyek

Unggah kode proyek ke repository GitHub milik sendiri mengikuti README. Pastikan `.env` tidak terunggah, sedangkan `.env.example` tersedia. Repository publik memudahkan clone dan pemeriksaan; bila menggunakan repository privat, gunakan akses GitHub milik sendiri tanpa memasukkan token ke perintah, screenshot, atau laporan.

Jalur utama, Bash VM:

```bash
cd ~
git clone https://github.com/USERNAME_GITHUB/catatan-praktikum.git catatan-praktikum
cd ~/catatan-praktikum
ls -a
```

Pastikan `Dockerfile`, `compose.yaml`, `.env.example`, `app`, dan `database` ada pada direktori ini. Jangan menjalankan Compose dari direktori lain.

Jika memakai ZIP paket proyek sebagai alternatif, kirim dari PowerShell laptop. Sesuaikan lokasi ZIP di laptop:

```powershell
scp -i "$env:USERPROFILE\.ssh\id_ed25519" "C:\lokasi\catatan-praktikum.zip" USER_VM@IP_PUBLIC_VM:/home/USER_VM/
```

Kemudian pada Bash VM:

```bash
cd ~
unzip catatan-praktikum.zip
cd ~/catatan-praktikum
ls -a
```

Paket ZIP menggunakan folder teratas `catatan-praktikum`. Jika ZIP dibuat sendiri dengan susunan berbeda, masuklah ke folder yang langsung berisi `compose.yaml`. Tetap siapkan repository untuk memenuhi pengumpulan link repository.

## 6 Isi konfigurasi tanpa membocorkan password

Bash VM, dari direktori proyek:

```bash
cp .env.example .env
chmod 600 .env
openssl rand -hex 24
openssl rand -hex 24
nano .env
```

Dua perintah `openssl` menghasilkan dua nilai acak berbeda untuk password database user dan root. Salin masing-masing ke field yang sesuai. Jangan memotret terminal saat nilai rahasia tampil.

Isian nonrahasia dan nama field:

```dotenv
APP_PORT=80
DB_DATABASE=catatan_praktikum
DB_USERNAME=catatan_user
DB_PASSWORD=ISI_DENGAN_PASSWORD_ACAK_PERTAMA
MYSQL_ROOT_PASSWORD=ISI_DENGAN_PASSWORD_ACAK_KEDUA
```

Ganti kedua placeholder password; jangan menjalankannya apa adanya. Password heksadesimal hanya berisi angka dan huruf sehingga sederhana untuk ditulis dalam `.env`. Simpan di nano dengan `Ctrl+O`, Enter, lalu keluar dengan `Ctrl+X`.

Jangan menampilkan `.env` untuk screenshot. Pada clone Git, periksa bahwa `.env` diabaikan:

```bash
git check-ignore .env
git ls-files --error-unmatch .env
```

Perintah pertama diharapkan menyebut `.env`; perintah kedua diharapkan gagal karena `.env` tidak dilacak. Jangan mengubah password pada `.env` setelah volume database berisi data dan menganggap user MySQL otomatis ikut berubah. Konfigurasi awal database berlaku saat datadir masih kosong; perubahan password database yang sudah ada memerlukan perubahan akun MySQL yang sesuai.

## 7 Build dan jalankan

Bash VM:

```bash
sudo docker build -t catatan-praktikum-app:praktikum .
sudo docker compose config --quiet
sudo docker compose up -d --build --wait --wait-timeout 180
sudo docker compose ps
```

`config --quiet` memvalidasi konfigurasi tanpa mencetak hasil interpolasi rahasia. Jangan menggunakan keluaran `docker compose config` lengkap sebagai screenshot. `--wait` menunggu service running atau healthy sesuai konfigurasi; lihat [referensi Compose up](https://docs.docker.com/reference/cli/docker/compose/up/). Pada VM lambat, waktu unduh/build pertama dapat lebih lama; baca log jika batas tunggu terlampaui.

Database diperiksa dahulu, lalu aplikasi dimulai setelah database healthy melalui `depends_on`. Rujukan: [startup order Compose](https://docs.docker.com/compose/how-tos/startup-order/).

Periksa HTTP dari VM:

```bash
curl -f http://localhost/health.php
```

Lalu buka **`http://IP_PUBLIC_VM`** pada browser laptop. Gunakan awalan `http://`, bukan HTTPS. Tambahkan catatan uji milik sendiri, misalnya `Uji persistensi NIM tanggal jam`, lalu pastikan catatan tampil kembali setelah halaman dimuat ulang.

## 8 Jalankan pengujian dan ambil bukti

Ikuti [BUKTI-PENGUJIAN.md](BUKTI-PENGUJIAN.md) secara berurutan. Bagian itu mencakup status container, fungsi aplikasi, validasi, persistensi, jaringan, dan restart VM. Isi hasil aktual serta nama screenshot setelah masing-masing langkah benar-benar dilakukan.

Untuk diagnosis di VM:

```bash
sudo docker compose ps
sudo docker compose logs --tail=80 app database
sudo ss -ltnp
```

Jika port 80 sudah dipakai aplikasi lain, hentikan atau pindahkan aplikasi tersebut secara sadar. Untuk hasil tugas Azure, kembalikan `APP_PORT=80`; penggunaan port alternatif seperti 8080 hanya untuk latihan lokal. Jika HTTP dari VM berhasil tetapi browser laptop gagal, periksa IP publik, status VM, rule NSG port 80, dan apakah browser memaksa HTTPS. Jangan memasang Nginx host tambahan karena Apache sudah ada di container aplikasi.

## 9 Selesaikan sesi dan siapkan pemeriksaan

Setelah semua screenshot disimpan, pilih **Stop** pada Overview VM di Azure Portal dan tunggu sampai **Stopped (deallocated)**. Jangan hanya mematikan sistem operasi lewat terminal. Deallocation menghentikan biaya komputasi VM; disk dan sebagian resource jaringan dapat tetap dikenai biaya. Rujukan: [status dan penagihan Azure VM](https://learn.microsoft.com/en-us/azure/virtual-machines/states-billing).

Jangan menghapus VM, disk, atau volume database yang masih dibutuhkan untuk penilaian. Saat VM dihidupkan kembali, salin lagi IP publik pada Overview dan gunakan nilai terkini untuk SSH dan browser. Periksa `docker compose ps` dan aplikasi. Bila alamat berubah, ambil ulang pasangan screenshot Overview dan browser agar IP yang dilaporkan konsisten.
