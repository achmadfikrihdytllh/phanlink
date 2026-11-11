# Phanlink API (backend)

Backend untuk **Phantom Messenger**: aplikasi chat dengan percakapan pribadi dan grup, kontak, pesan tersimpan, pencarian, dan pengaturan akun. Frontend-nya ada di repo [`phanlink-website`](https://github.com/achmadfikrihdytllh/phanlink-website) (Angular).

**Teknologi:** Laravel 12, Sanctum (token), Reverb (realtime/websocket), SQLite, Pest/PHPUnit.

## Fitur

- Registrasi, login, logout (token Sanctum).
- Chat pribadi dan grup, kirim pesan dan lampiran, tandai dibaca, realtime lewat Reverb.
- Pesan: balas (reply), reaksi emoji, edit dan hapus (soft delete), sematkan pesan, pagination riwayat.
- Grup: rencana/acara dengan RSVP (pengumuman otomatis di chat), polling, ganti nama dan deskripsi, keluarkan anggota, keluar grup.
- Lupa dan reset password lewat email, batas kecepatan (rate limit) pada login dan API.
- Grup: buat grup, undang anggota (khusus admin), bisukan dan sematkan percakapan, daftar file bersama.
- Kontak: tambah, nickname, catatan, bisukan, blokir, halaman profil kontak.
- Pesan tersimpan dengan koleksi.
- Pencarian pesan dan file dengan filter percakapan, pengirim, dan tanggal.
- Profil: nama, username, bio, nomor HP, foto (avatar).
- Pengaturan pengguna (tema, kepadatan, notifikasi, privasi).

## Menjalankan di lokal

Syarat: PHP 8.2 atau lebih baru, Composer.

```bash
cp .env.example .env            # Windows PowerShell: copy .env.example .env
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link        # supaya foto profil dan lampiran bisa dibuka
php artisan serve               # http://127.0.0.1:8000
php artisan reverb:start        # opsional, untuk realtime (ws://localhost:8080)
```

- Database memakai **SQLite** milik proyek ini (`database/database.sqlite`). Pastikan `DB_CONNECTION=sqlite` di `.env` sebelum menjalankan `migrate:fresh`, karena perintah itu menghapus seluruh isi database yang dipakai.
- Kalau port 8000 sudah dipakai proyek lain, jalankan `php artisan serve --port=8001` dan sesuaikan `apiUrl` di frontend.
- Tanpa Reverb, pesan tetap terkirim dan tersimpan. Hanya saja pesan baru tidak muncul langsung di sisi lawan sampai halaman dimuat ulang.

### Akun demo (dari seeder)

Semua memakai password `password`.

| Username | Nama |
|---|---|
| `joker` (atau `joker@phanlink.test`) | Ren Amamiya |
| `skull` | Ryuji Sakamoto |
| `panther` | Ann Takamaki |
| `queen` | Makoto Niijima |
| `oracle` | Futaba Sakura |
| `fox` | Yusuke Kitagawa |
| `mona` | Morgana |

## Konfigurasi penting (`.env`)

| Variabel | Fungsi |
|---|---|
| `FRONTEND_URL` | Alamat frontend yang diizinkan CORS (default `http://localhost:4200`). |
| `DB_CONNECTION` | `sqlite` untuk lokal. |
| `BROADCAST_CONNECTION` | `reverb` untuk realtime, `null` untuk mematikan broadcast. |
| `REVERB_APP_KEY` | Harus sama dengan `reverbAppKey` di `src/environments/*` frontend. |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Alamat server websocket. |

## Endpoint

Semua di bawah `/api` dan memakai header `Authorization: Bearer <token>`, kecuali register dan login.

| Bagian | Endpoint |
|---|---|
| Auth | `POST /register`, `POST /login`, `POST /logout`, `GET /me`, `POST /forgot-password` (`email`), `POST /reset-password` (`token`, `email`, `password`, `password_confirmation`) |
| Pengguna | `GET /users/search`, `GET /users/{id}`, `POST /profile` (`name`, `username`, `bio`, `phone_number`, `avatar` gambar maks 5 MB, kirim sebagai `multipart/form-data`) |
| Percakapan | `GET /conversations`, `GET /conversations/{id}`, `POST /conversations/private`, `POST /conversations/group`, `PATCH /conversations/{id}/preferences` (`is_muted`, `is_pinned`), `POST /conversations/{id}/participants` (admin saja), `GET /conversations/{id}/attachments` |
| Grup | `GET /groups` |
| Pesan | `GET /conversations/{id}/messages?before=ID` (halaman lebih lama), `POST /conversations/{id}/messages` (`body`, `reply_to_id`, `attachment`), `PATCH /messages/{id}` (edit), `DELETE /messages/{id}`, `POST /messages/{id}/reactions` (`emoji`, toggle), `POST /conversations/{id}/read`, `POST /conversations/{id}/pin` (`message_id` atau null) |
| Kelola grup | `PATCH /conversations/{id}` (`name`, `description`, admin), `DELETE /conversations/{id}/participants/{userId}` (keluarkan anggota, atau keluar jika id sendiri) |
| Rencana grup | `GET` dan `POST /conversations/{id}/events` (`title`, `starts_at`, `location`, `description`), `PATCH` dan `DELETE /events/{id}`, `POST /events/{id}/respond` (`going`, `maybe`, `declined`); `GET` dan `POST /conversations/{id}/polls` (`question`, `options[]`), `POST /polls/{id}/vote` (`option_id`), `DELETE /polls/{id}` |
| Kontak | `GET /contacts`, `POST /contacts`, `GET /contacts/{userId}`, `PATCH /contacts/{userId}` (`nickname`, `note`, `is_muted`, `is_blocked`), `DELETE /contacts/{userId}` |
| Tersimpan | `GET /saved?collection_id&type&q`, `POST /saved`, `DELETE /saved/{id}`, `GET` dan `POST /saved/collections`, `DELETE /saved/collections/{id}` |
| Pencarian | `GET /search?q&conversation_id&from&date_from&date_to&types[]=messages&types[]=files` |
| Pengaturan | `GET /settings`, `PUT /settings` (kunci camelCase, sama dengan tipe `UserPrefs` di Angular) |

**Realtime:** `POST /broadcasting/auth` (di luar `/api`), channel privat `conversation.{id}`, event `.message.sent` dan `.message.changed` (edit, hapus, reaksi). Indikator "sedang mengetik" dan "dibaca" memakai client event (whisper) langsung antaranggota, tanpa lewat server (`REVERB_APP_ACCEPT_CLIENT_EVENTS_FROM=members`).

## Catatan teknis

- Isi pesan **dienkripsi** di database, sehingga pencarian dan filter teks dilakukan di PHP atas 1000 pesan terbaru milik pengguna. Untuk data besar, buat indeks pencarian terpisah.
- Kegagalan broadcast tidak membuat pengiriman pesan gagal (error dicatat ke log).
- Pengaturan privasi ditegakkan: siapa yang boleh memulai chat, tanda dibaca, dan status online disembunyikan sesuai pilihan pengguna.
- Email reset password memakai `MAIL_MAILER=log` secara default, jadi tautannya muncul di `storage/logs/laravel.log`. Isi `MAIL_*` dengan SMTP sungguhan untuk produksi.

## Tes

```bash
php artisan test
```

## Deploy gratis

Tersedia `Dockerfile` dan `render.yaml` (Render Blueprint: web service + Postgres gratis).

1. Dorong repo ke GitHub, lalu di Render pilih **New, Blueprint** dan arahkan ke repo ini.
2. Isi variabel yang bertanda `sync: false`: `APP_KEY` (hasil `php artisan key:generate --show`), `APP_URL`, dan `FRONTEND_URL` (alamat frontend; pisahkan dengan koma untuk beberapa alamat, misalnya tambahkan `https://localhost` bila memakai APK Capacitor).
3. Migrasi dijalankan otomatis saat container menyala.

Batasan paket gratis: server tidur setelah sekitar 15 menit tanpa trafik, dan file unggahan (avatar, lampiran) hilang saat restart karena disk tidak permanen (pakai penyimpanan eksternal bila perlu). Realtime dimatikan (`BROADCAST_CONNECTION=null`); pesan tetap terkirim, hanya tidak muncul langsung.

## Lisensi

Berbasis [Laravel](https://laravel.com), berlisensi MIT.
