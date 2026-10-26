# Pengujian dan bukti laporan

Lembar ini memandu pengujian pada **Azure VM milik praktikan**. Semua hasil Azure di bawah berstatus **belum diuji** sampai Anda menjalankan langkahnya. Contoh masukan merupakan data uji, bukan hasil pengujian. Screenshot harus diambil dari terminal, browser, dan Azure Portal Anda sendiri.

Jalankan perintah Bash dari direktori `~/catatan-praktikum` di VM. Perintah PowerShell dijalankan pada laptop, bukan dalam sesi SSH. Ganti setiap `IP_PUBLIC_VM` dengan IP sebenarnya. Buat folder screenshot di laptop dan beri nama berurutan agar mudah dipasangkan ke laporan.

## Daftar screenshot

| Kode | Isi bukti yang harus terlihat | Nama file yang disarankan |
| --- | --- | --- |
| S01 | Struktur folder proyek | `S01-struktur-proyek.png` |
| S02 | Isi Dockerfile | `S02-dockerfile.png` |
| S03 | Compose yang memperlihatkan dua service, healthcheck, dependensi, restart, port, network, dan volume | `S03-compose.png`, boleh beberapa gambar |
| S04 | `docker compose ps` di VM, kedua service running/healthy dan port aplikasi | `S04-status-vm.png` |
| S05 | Browser melalui HTTP IP publik, address bar penuh dan catatan yang telah ditambahkan | `S05-aplikasi-publik.png` |
| S06 | Overview Azure menampilkan nama VM dan IP publik yang sama dengan S05 | `S06-overview-vm.png` |
| S07 | Catatan unik sebelum container dihapus | `S07-data-sebelum.png` |
| S08 | Terminal menjalankan `down` lalu `up -d` tanpa `-v` | `S08-recreate-container.png` |
| S09 | Catatan yang sama sesudah container dibuat ulang | `S09-data-sesudah.png` |
| S10 | Pemeriksaan port binding database dan network internal | `S10-isolasi-database.png` |
| S11 | Uji TCP dari laptop: HTTP terjangkau, database tidak terjangkau | `S11-port-eksternal.png` |
| S12 | Status healthy dan kebijakan restart sebelum VM restart | `S12-sebelum-restart.png` |
| S13 | Sesudah VM restart: status container sebelum perintah start manual dan aplikasi kembali dapat dibuka | `S13-sesudah-restart.png`, boleh dua gambar |
| S14 | Status VM Stopped (deallocated) sesudah semua bukti terkumpul | `S14-vm-deallocated.png` |

Modul mengizinkan struktur folder dan kode ditampilkan sebagai potongan kode, sehingga S01–S03 dapat diganti salinan kode yang terbaca dalam laporan. S14 mendokumentasikan penutupan sesi sesuai catatan modul. Tambahkan screenshot validasi bila dibutuhkan untuk menjelaskan fungsi aplikasi.

Jangan memotret `.env`, password, private key, token GitHub, atau hasil `docker compose config` yang lengkap. Kode `compose.yaml` yang berisi referensi variabel seperti `${DB_PASSWORD}` boleh ditampilkan karena bukan nilai password.

## 1 Struktur dan konfigurasi

Periksa folder proyek, `Dockerfile`, `compose.yaml`, `.env.example`, `.gitignore`, serta `.dockerignore`. Pada Compose, tandai service `app` dan `database`, volume `mysql_data`, dan network `backend` dengan `internal: true`. Pastikan hanya aplikasi yang memiliki pemetaan host port.

Bash VM:

```bash
sudo docker compose config --quiet
sudo docker compose ps
```

Kriteria lulus: konfigurasi valid; kedua service running dan healthy; aplikasi dipublikasikan pada port 80 VM. Simpan S01–S04.

## 2 Akses publik dan fungsi aplikasi

1. Buka `http://IP_PUBLIC_VM` dari browser laptop.
2. Pastikan form dan daftar catatan dapat dibaca.
3. Isi catatan unik dengan NIM dan waktu, lalu simpan.
4. Muat ulang halaman dan pastikan catatan tetap terlihat dengan waktu WIB.
5. Buka Overview VM di Azure Portal. Cocokkan IP dengan address bar browser.

Kriteria lulus: browser benar-benar mengakses IP publik VM; data yang disimpan tampil setelah halaman dimuat ulang. Simpan S05 dan S06. Screenshot `localhost` tidak menggantikan bukti akses publik Azure.

## 3 Validasi dasar

| Skenario | Hasil yang diharapkan | Hasil aktual |
| --- | --- | --- |
| Isi kosong atau hanya spasi | Tidak tersimpan; pengguna mendapat keterangan validasi | Belum diuji di Azure |
| Catatan normal | Tersimpan satu kali dan tampil di daftar | Belum diuji di Azure |
| Teks dengan huruf Indonesia dan emoji | Tersimpan serta terbaca kembali | Belum diuji di Azure |
| Isi melewati 1000 karakter | Ditolak; tidak ada baris baru | Belum diuji di Azure |
| Teks `<script>alert('uji')</script>` | Tampil sebagai teks; tidak menjalankan JavaScript | Belum diuji di Azure |

Untuk memeriksa respons HTTP validasi server secara langsung, gunakan `curl` pada Bash VM. Formulir memiliki token CSRF, sehingga ambil formulir beserta cookie sesi dahulu. Perintah ini kemudian mengirim 1001 huruf `a` sebagai masukan uji:

```bash
curl -sS -c /tmp/catatan-uji.cookie http://localhost/ -o /tmp/catatan-uji-form.html
token_uji=$(sed -n 's/.*name="csrf_token" value="\([^"]*\)".*/\1/p' /tmp/catatan-uji-form.html)
isi_panjang=$(printf '%1001s' '' | tr ' ' 'a')
curl -sS -b /tmp/catatan-uji.cookie -o /tmp/catatan-validasi.html -w 'HTTP %{http_code}\n' --data-urlencode "csrf_token=$token_uji" --data-urlencode "isi=$isi_panjang" http://localhost/
unset isi_panjang token_uji
```

Periksa respons pada `/tmp/catatan-validasi.html` dan daftar catatan; masukan tersebut harus ditolak dengan HTTP 422 dan keterangan batas panjang. Catat kode respons yang benar-benar diterima. HTTP 400 berarti sesi/token tidak sesuai, sehingga belum membuktikan validasi panjang. Jangan menulis hasil “lulus” sebelum melihat respons dan memastikan tidak ada baris baru. Jangan menampilkan isi file cookie atau token dalam screenshot. Gunakan data uji yang tidak mengandung informasi sensitif karena demonstrasi aplikasi dilakukan melalui HTTP publik.

## 4 Persistensi data

1. Tambahkan catatan unik, misalnya `Persistensi NIM 123456 tanggal jam`. Gunakan NIM serta waktu Anda sendiri. Simpan S07.
2. Jalankan Bash VM berikut dan simpan S08:

```bash
sudo docker compose down
sudo docker compose up -d --wait --wait-timeout 180
sudo docker compose ps
sudo docker volume ls --filter name=catatan-praktikum_mysql_data
```

3. Muat ulang browser melalui IP publik.
4. Cocokkan isi dan identitas catatan dengan sebelum `down`. Simpan S09.

Kriteria lulus: catatan yang dibuat sebelum container dihapus masih ada sesudah `up`. Jangan menambahkan ulang catatan yang sama untuk membuat bukti seolah data bertahan. Jangan menggunakan `down -v`; opsi itu menghapus named volume yang justru sedang diuji. Rujukan: [Compose down](https://docs.docker.com/reference/cli/docker/compose/down/).

## 5 Isolasi jaringan database

Bash VM:

```bash
sudo docker compose ps
id_database=$(sudo docker compose ps -q database)
sudo docker inspect --format '{{json .HostConfig.PortBindings}}' "$id_database"
sudo docker inspect --format '{{range $name, $_ := .NetworkSettings.Networks}}{{$name}} {{end}}' "$id_database"
sudo docker network inspect --format '{{.Internal}}' catatan-praktikum_backend
```

Kriteria lulus: database tidak mempunyai pemetaan port host, hanya bergabung ke `catatan-praktikum_backend`, dan network internal bernilai `true`. Port binding kosong dapat tampil sebagai `{}` atau `null`. Pada `compose ps`, teks `3306/tcp` sendiri hanya menyatakan port container; pemetaan publik memiliki bentuk seperti `0.0.0.0:3306->3306/tcp`, yang **tidak boleh ada pada database**. Simpan S10.

PowerShell laptop, di luar SSH:

```powershell
Test-NetConnection -ComputerName IP_PUBLIC_VM -Port 80 -InformationLevel Detailed
Test-NetConnection -ComputerName IP_PUBLIC_VM -Port 3306 -InformationLevel Detailed
```

Kriteria lulus: koneksi TCP port 80 berhasil, sedangkan port 3306 gagal. Pengujian database dapat memerlukan waktu sampai timeout. Simpan hasil nyata sebagai S11. Kegagalan port 3306 saja belum membuktikan konfigurasi Docker benar; gunakan bersama Compose, inspect, dan aturan NSG.

## 6 Restart VM

Gunakan **Restart**, bukan Stop/deallocate, untuk pengujian ini. Pastikan kedua service telah berjalan dan healthy setidaknya beberapa saat. Jangan menjalankan `docker stop`, `compose stop`, atau `compose down` sebelum reboot.

Sebelum restart, Bash VM:

```bash
sudo docker compose ps
sudo docker inspect --format '{{.Name}} restart={{.HostConfig.RestartPolicy.Name}}' $(sudo docker compose ps -q)
```

Simpan S12. Pada Overview Azure VM, pilih **Restart**. Tunggu VM kembali Running, lalu hubungkan SSH dari PowerShell laptop:

```powershell
ssh -i "$env:USERPROFILE\.ssh\id_ed25519" USER_VM@IP_PUBLIC_VM
```

Setelah login, Bash VM:

```bash
cd ~/catatan-praktikum
sudo systemctl is-active docker
sudo docker compose ps
curl -f http://localhost/health.php
```

Tunggu pemeriksaan kesiapan jika database masih memulai. **Jangan menjalankan `compose up`, `start`, atau `restart` sebelum mengambil status**, karena perintah tersebut dapat menyalakan service secara manual dan menggugurkan bukti pemulihan otomatis. Buka kembali browser melalui IP publik serta periksa catatan. Simpan S13.

Kriteria lulus: Docker aktif, kedua service kembali berjalan dan healthy tanpa start manual, aplikasi dapat dibuka, dan data tetap ada. Jika gagal, dokumentasikan hasil dan perbaikan, lalu ulangi uji secara utuh. Perilaku `unless-stopped` dijelaskan pada [dokumentasi resmi Docker](https://docs.docker.com/engine/containers/start-containers-automatically/).

## 7 Lembar hasil aktual

Ganti status dan isi kolom setelah pengujian nyata. Pisahkan hasil pengujian lokal dari pengujian VM.

| Pengujian pada Azure | Status awal | Hasil aktual dan tanggal WIB | Bukti |
| --- | --- | --- | --- |
| Build dan Compose valid | Belum diuji | Isi setelah pengujian | S02–S04 |
| Service healthy di VM | Belum diuji | Isi setelah pengujian | S04 |
| Akses IP publik dan tambah/daftar catatan | Belum diuji | Isi setelah pengujian | S05–S06 |
| Validasi dan teks Unicode | Belum diuji | Isi setelah pengujian | Catat bukti tambahan |
| Data bertahan setelah down/up | Belum diuji | Isi setelah pengujian | S07–S09 |
| Database tidak terpublikasi | Belum diuji | Isi setelah pengujian | S10–S11 |
| Service pulih setelah restart VM | Belum diuji | Isi setelah pengujian | S12–S13 |
| VM didealokasikan sesudah sesi | Belum dilakukan | Isi setelah dilakukan | S14 |

Setelah semua bukti tersimpan, lakukan Stop melalui Azure Portal sampai **Stopped (deallocated)** dan simpan S14. Aplikasi tidak dapat diakses ketika VM didealokasikan; nyalakan kembali bila asisten perlu memeriksa.
