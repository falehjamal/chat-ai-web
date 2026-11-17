# Chat AI Web

Aplikasi web chat AI dengan model OpenAI, OCR offline (Tesseract.js), dan analisis gambar soal.

## Fitur

- **Mode Chat** — percakapan dengan riwayat konteks
- **Mode OCR Low** — ekstrak teks dari gambar (offline, Tesseract.js v7), lalu dijawab model
- **Mode OCR High** — analisis soal dari gambar langsung oleh model vision
- **Admin panel** — history chat, katalog model, mode binding, dan reasoning effort per mode
- **Streaming** — respons AI real-time

## Requirements

| Komponen | Versi |
|----------|-------|
| Ubuntu | 22.04 / 24.04 |
| PHP | 8.3 (PHP-FPM) |
| MySQL / MariaDB | MySQL 8.0+ / MariaDB 10.6+ |
| Nginx | 1.18+ |
| OpenAI API Key | [platform.openai.com](https://platform.openai.com/api-keys) |

**Ekstensi PHP:** `cli`, `fpm`, `mysql` (`pdo_mysql`), `curl`, `mbstring`

---

## Instalasi di Ubuntu (PHP 8.3 FPM + MySQL + Nginx)

Contoh ini memakai path `/var/www/chat-ai-web` dan domain `chat-ai-web.example.com`. Ganti sesuai server Anda.

### 1. Install paket

Ubuntu 24.04 sudah menyediakan PHP 8.3. Untuk Ubuntu 22.04, tambahkan PPA dulu:

```bash
# Hanya Ubuntu 22.04
sudo apt install -y software-properties-common
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
```

Lalu install paket utama:

```bash
sudo apt update
sudo apt install -y nginx mysql-server \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-curl php8.3-mbstring unzip git

sudo systemctl enable --now nginx php8.3-fpm mysql
```

### 2. Siapkan database

```bash
sudo mysql
```

```sql
CREATE DATABASE chat_ai_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'chat_user'@'localhost' IDENTIFIED BY 'ganti_password_anda';
GRANT ALL PRIVILEGES ON chat_ai_web.* TO 'chat_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> Jangan memakai user `root` untuk aplikasi. Migrasi tabel dijalankan otomatis oleh aplikasi, jadi user ini perlu hak `ALL` pada database `chat_ai_web`.

### 3. Upload aplikasi

```bash
# Opsi A: clone dari Git
sudo mkdir -p /var/www/chat-ai-web
sudo git clone <url-repo-anda> /var/www/chat-ai-web

# Opsi B: upload manual (SCP/SFTP) ke /var/www/chat-ai-web
```

```bash
sudo chown -R www-data:www-data /var/www/chat-ai-web
sudo find /var/www/chat-ai-web -type d -exec chmod 755 {} \;
sudo find /var/www/chat-ai-web -type f -exec chmod 644 {} \;

# Folder storage harus bisa ditulis PHP (throttle login dan rate limit chat)
sudo chmod 775 /var/www/chat-ai-web/storage
```

### 4. Konfigurasi environment

```bash
cd /var/www/chat-ai-web
sudo cp .env.example .env
sudo nano .env
```

Isi minimal:

```env
OPENAI_API_KEY=sk-your-openai-api-key-here

DB_HOST=localhost
DB_PORT=3306
DB_NAME=chat_ai_web
DB_USERNAME=chat_user
DB_PASSWORD=ganti_password_anda

APP_ENV=production
DEBUG=false
APP_TIMEZONE=Asia/Jakarta
```

Jangan beri tanda kutip pada nilai `OPENAI_API_KEY`.

```bash
sudo chmod 640 .env
sudo chown www-data:www-data .env
```

### 5. Konfigurasi PHP-FPM

Chat mengirim pesan dan gambar dalam satu request JSON (maksimal sekitar 9 MB), dan respons streaming bisa berjalan beberapa menit. Atur nilai berikut di PHP 8.3 FPM:

```bash
sudo nano /etc/php/8.3/fpm/conf.d/99-chat-ai-web.ini
```

```ini
post_max_size = 10M
upload_max_filesize = 10M
max_execution_time = 180
memory_limit = 256M
expose_php = Off
```

```bash
sudo systemctl restart php8.3-fpm
```

### 6. Virtual host Nginx

```bash
sudo nano /etc/nginx/sites-available/chat-ai-web
```

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name chat-ai-web.example.com;

    root /var/www/chat-ai-web;
    index index.php index.html;

    # Chat dan gambar OCR bisa sampai ~9 MB per request
    client_max_body_size 10m;

    # Jangan tampilkan file internal, konfigurasi, atau dotfile
    location ~ /\. {
        deny all;
        return 404;
    }

    location ~* \.(env|sql|log|ini|bak|md|yml|yaml|json)$ {
        deny all;
        return 404;
    }

    location ~ ^/(app|migrations|views|storage)(/|$) {
        deny all;
        return 404;
    }

    # File WASM untuk OCR harus punya MIME type yang benar
    location ~* \.wasm$ {
        default_type application/wasm;
        try_files $uri =404;
    }

    # Endpoint streaming: jangan di-buffer, supaya teks tampil real-time
    location ~ ^/api_(stream|uas_stream|uas_math_stream)\.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_buffering off;
        fastcgi_read_timeout 180s;
        add_header X-Accel-Buffering no;
    }

    # Semua file PHP lain (index.php dan admin/*.php)
    location ~ \.php$ {
        try_files $uri =404;
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 180s;
    }

    location / {
        try_files $uri $uri/ =404;
    }

    access_log /var/log/nginx/chat-ai-web-access.log;
    error_log  /var/log/nginx/chat-ai-web-error.log;
}
```

Aktifkan dan cek konfigurasi:

```bash
sudo ln -s /etc/nginx/sites-available/chat-ai-web /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default   # opsional, jika hanya 1 site
sudo nginx -t && sudo systemctl reload nginx
```

> Ganti `chat-ai-web.example.com` dengan domain atau IP server Anda. Socket `php8.3-fpm.sock` adalah nilai default Ubuntu; cek dengan `ls /run/php/`.

### 7. Firewall (opsional)

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

### 8. Jalankan aplikasi

1. Buka `http://chat-ai-web.example.com` — halaman chat utama
2. Buka `http://chat-ai-web.example.com/admin/setup.php` — buat akun admin pertama (hanya sekali)
3. Login admin: `http://chat-ai-web.example.com/admin/login.php`

Saat pertama kali dibuka, aplikasi menjalankan `migrations/` secara otomatis: provider OpenAI, model gpt-5.2 dan gpt-5-mini, serta tiga mode. Model dan reasoning effort per mode bisa diubah di `/admin/modes.php`. Tidak perlu mengimpor file SQL terpisah.

---

## HTTPS (disarankan production)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d chat-ai-web.example.com
sudo certbot renew --dry-run
```

Certbot menambah blok `listen 443` dan redirect HTTP ke HTTPS secara otomatis. Setelah itu, pastikan `client_max_body_size 10m;` tetap ada di blok server HTTPS.

---

## Struktur penting

```
chat-ai-web/
├── index.php                 # Halaman chat utama
├── .env                      # Konfigurasi (buat dari .env.example, jangan di-commit)
├── api_stream.php            # API Mode Chat
├── api_uas_stream.php        # API Mode OCR Low
├── api_uas_math_stream.php   # API Mode OCR High
├── admin/                    # Panel admin
├── app/                      # Backend modular (tidak boleh diakses publik)
├── migrations/               # SQL auto-migration (tidak boleh diakses publik)
├── storage/                  # Data runtime, harus bisa ditulis PHP
├── js/
│   ├── app.js
│   └── tesseract/            # OCR offline (engine + core WASM + bahasa)
└── views/                    # Template (tidak boleh diakses publik)
```

---

## Cara pakai

| Mode | Kegunaan |
|------|----------|
| **Mode Chat** | Chat biasa dengan riwayat konteks |
| **Mode OCR Low** | Paste/upload gambar → teks diekstrak offline ke input, lalu dijawab model |
| **Mode OCR High** | Upload gambar soal → model vision menganalisis dan menjawab |

Admin history & konfigurasi: `/admin/history.php`

---

## Troubleshooting

**Halaman 502 Bad Gateway**
```bash
sudo systemctl status php8.3-fpm
ls -l /run/php/                       # pastikan nama socket sesuai konfigurasi Nginx
sudo tail -n 50 /var/log/nginx/chat-ai-web-error.log
```

**413 Request Entity Too Large (kirim gambar gagal)**
- Pastikan `client_max_body_size 10m;` ada di Nginx.
- Pastikan `post_max_size = 10M` di PHP-FPM, lalu `sudo systemctl restart php8.3-fpm`.

**Respons AI muncul sekaligus di akhir, bukan bertahap**
- Pastikan blok `location` untuk `api_*.php` memakai `fastcgi_buffering off;`.

**Database connection error**
```bash
php -r "new PDO('mysql:host=localhost;dbname=chat_ai_web', 'chat_user', 'password');"
echo 'OK';
```

**OCR gagal / MIME type error**
- Pastikan folder `js/tesseract/` lengkap (termasuk `core/` dan `lang/`)
- Pastikan blok `location ~* \.wasm$` dengan `default_type application/wasm;` ada di Nginx
- Hard refresh browser (Ctrl+Shift+R)

**API key invalid**
- Cek `OPENAI_API_KEY` di `.env` (tanpa tanda kutip)
- Pastikan quota OpenAI masih tersedia
- Setelah mengubah `.env`, muat ulang PHP-FPM: `sudo systemctl restart php8.3-fpm`

**Permission denied (storage tidak bisa ditulis)**
```bash
sudo chown -R www-data:www-data /var/www/chat-ai-web/storage
```

---

## Production checklist

- [ ] `.env` terisi credentials production, `DEBUG=false`, dan `chmod 640`
- [ ] HTTPS aktif
- [ ] Nginx menolak akses ke dotfile, `.env`, `.sql`, `app/`, `migrations/`, `views/`, dan `storage/`
- [ ] `client_max_body_size` dan `post_max_size` sudah 10 MB
- [ ] User database khusus aplikasi, bukan `root`
- [ ] Akun admin sudah dibuat via `/admin/setup.php`
- [ ] Semua mode chat sudah ditest, termasuk OCR High dengan gambar

---

**Teknologi:** PHP 8.3, MySQL, Nginx, jQuery, OpenAI API, Tesseract.js v7
