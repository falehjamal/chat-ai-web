# Chat AI Web

Aplikasi web chat AI dengan OpenAI GPT, OCR offline (Tesseract.js), dan analisis gambar (GPT Vision).

## Fitur

- **Mode Chat** — percakapan dengan riwayat konteks
- **Mode OCR Low** — ekstrak teks dari gambar (offline, Tesseract.js v7)
- **Mode OCR High** — analisis soal matematika dari gambar (GPT Vision)
- **Admin panel** — history chat, konfigurasi model & provider
- **Streaming** — respons AI real-time

## Requirements

| Komponen | Versi |
|----------|-------|
| Ubuntu | 20.04 / 22.04 / 24.04 |
| PHP | 7.4+ (disarankan 8.1+) |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Apache | dengan `mod_rewrite`, `mod_headers` |
| OpenAI API Key | [platform.openai.com](https://platform.openai.com/api-keys) |

**Ekstensi PHP:** `curl`, `pdo_mysql`, `mbstring`

---

## Instalasi di Ubuntu

### 1. Install paket

```bash
sudo apt update
sudo apt install -y apache2 mysql-server \
  php php-cli php-mysql php-curl php-mbstring libapache2-mod-php

sudo a2enmod rewrite headers
sudo systemctl restart apache2
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
```

### 4. Konfigurasi environment

```bash
cd /var/www/chat-ai-web
sudo cp config.env.example config.env
sudo nano config.env
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

```bash
sudo chmod 640 config.env
sudo chown www-data:www-data config.env
```

### 5. Virtual host Apache

```bash
sudo nano /etc/apache2/sites-available/chat-ai-web.conf
```

```apache
<VirtualHost *:80>
    ServerName chat-ai-web.example.com
    DocumentRoot /var/www/chat-ai-web

    <Directory /var/www/chat-ai-web>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/chat-ai-web-error.log
    CustomLog ${APACHE_LOG_DIR}/chat-ai-web-access.log combined
</VirtualHost>
```

```bash
sudo a2ensite chat-ai-web.conf
sudo a2dissite 000-default.conf   # opsional, jika hanya 1 site
sudo systemctl reload apache2
```

> Ganti `chat-ai-web.example.com` dengan domain atau IP server Anda.

### 6. Jalankan aplikasi

1. Buka `http://chat-ai-web.example.com` — halaman chat utama
2. Buka `http://chat-ai-web.example.com/admin/setup.php` — buat akun admin pertama (hanya sekali)
3. Login admin: `http://chat-ai-web.example.com/admin/login.php`

Tabel database dibuat **otomatis** dari folder `migrations/` saat aplikasi pertama kali diakses.

---

## HTTPS (opsional, disarankan production)

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d chat-ai-web.example.com
```

---

## Struktur penting

```
chat-ai-web/
├── index.php                 # Halaman chat utama
├── config.env                # Konfigurasi (buat dari .example)
├── .htaccess                 # Aturan keamanan & MIME WASM (untuk OCR)
├── api_stream.php            # API Mode Chat
├── api_uas_stream.php        # API Mode OCR Low
├── api_uas_math_stream.php   # API Mode OCR High
├── admin/                    # Panel admin
├── app/                      # Backend modular
├── migrations/               # SQL auto-migration
├── js/
│   ├── app.js
│   └── tesseract/            # OCR offline (engine + core WASM + bahasa)
└── views/
```

---

## Cara pakai

| Mode | Kegunaan |
|------|----------|
| **Mode Chat** | Chat biasa dengan riwayat konteks |
| **Mode OCR Low** | Paste/upload gambar → teks diekstrak offline ke input |
| **Mode OCR High** | Upload gambar soal → GPT Vision menganalisis |

Admin history & konfigurasi: `/admin/history.php`

---

## Troubleshooting

**Database connection error**
```bash
php -r "new PDO('mysql:host=localhost;dbname=chat_ai_web', 'chat_user', 'password');"
echo 'OK';
```

**OCR gagal / MIME type error**
- Pastikan folder `js/tesseract/` lengkap (termasuk `core/` dan `lang/`)
- Pastikan `.htaccess` ada dan `AllowOverride All` aktif di Apache
- Hard refresh browser (Ctrl+Shift+R)

**API key invalid**
- Cek `OPENAI_API_KEY` di `config.env` (tanpa tanda kutip)
- Pastikan quota OpenAI masih tersedia

**Permission denied**
```bash
sudo chown -R www-data:www-data /var/www/chat-ai-web
```

---

## Production checklist

- [ ] `config.env` terisi credentials production
- [ ] `DEBUG=false`
- [ ] HTTPS aktif
- [ ] `config.env` tidak bisa diakses publik (`.htaccess`)
- [ ] Akun admin sudah dibuat via `/admin/setup.php`
- [ ] Semua mode chat sudah ditest

---

**Teknologi:** PHP, MySQL, jQuery, OpenAI GPT API, Tesseract.js v7, Apache
