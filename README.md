# 🤖 Chat AI Web

Aplikasi web chat AI modern dengan OpenAI GPT API, sistem chat history lengkap, dan fitur OCR + Vision untuk analisis gambar matematika.

## ✨ Fitur Utama

- 💬 **Multi-Mode Chat**: Mode Chat, OCR Low, dan OCR High
- 🧠 **AI Powered**: OpenAI GPT (GPT-5.2, GPT-5.1, GPT-5 Mini) untuk respons cerdas
- 📷 **OCR + Vision**: Upload gambar soal matematika untuk analisis GPT Vision
- 📊 **Chat History**: Sistem perekaman dan history chat lengkap dengan database
- 🎯 **Mode OCR High**: Khusus untuk menyelesaikan soal matematika dari gambar
- 📱 **Responsive**: Interface mobile-friendly dengan desain modern
- 💾 **Database Recording**: Semua chat direkam dengan detail (IP, token, model, dll.)
- 🎨 **Modern UI**: Desain clean dengan animasi smooth
- ⚡ **Real-time Streaming**: Response AI secara real-time
- 🔐 **Security**: Proteksi file konfigurasi dan sensitive data
- 🌏 **Timezone**: Support Asia/Jakarta (GMT+7)

## 🔧 Requirements

- **Web Server**: Apache/Nginx dengan PHP 7.4+
- **Database**: MySQL 5.7+ atau MariaDB 10.3+
- **PHP Extensions**: 
  - cURL (untuk API calls)
  - PDO MySQL (untuk database)
  - mbstring (untuk text processing)
- **OpenAI API Key**: [Dapatkan di sini](https://platform.openai.com/api-keys)

## ⚡ Quick Start

### 1. Setup Database

```sql
-- Buat database baru
CREATE DATABASE chat_ai_web;
```

### 2. Setup Environment

```bash
# Copy config template
cp config.env.example config.env
```

Edit `config.env`:
```env
# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key_here

# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=chat_ai_web
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
```

### 3. Deploy & Run

1. Upload semua file ke web server
2. Akses website: `http://your-domain.com`
3. Database table dan konfigurasi runtime akan dibuat otomatis dari folder `migrations/`

### 4. Test

- Buka halaman utama
- Pilih mode chat yang diinginkan
- Mulai chat dengan AI
- Cek history di: `http://your-domain.com/chat_history.php`

## 📁 Struktur Project

```
chat-ai-web/
├── index.php                    # 🏠 Halaman utama chat
├── chat_history.php             # 📊 Compatibility redirect ke admin history
├── database.php                 # 💾 Compatibility database adapter
├── env_helper.php               # ⚙️ Compatibility env adapter
├── model_config.php             # 🤖 Compatibility model adapter
├── config.env                   # 🔐 Konfigurasi utama (buat dari .example)
├── config.env.example           # 📋 Template konfigurasi
├── .htaccess                    # 🔒 Security rules
├── admin/                       # 🔐 Panel admin runtime config
├── app/                         # 🧩 Modular monolith internals
├── migrations/                  # 🗄️ SQL migrations & seed runtime config
├── views/                       # 🖼️ Page templates
├── 
├── # API Endpoints
├── api_stream.php               # 🔄 API Mode Chat
├── api_uas_stream.php           # 🎓 API Mode OCR Low  
├── api_uas_math_stream.php      # 📐 API Mode OCR High
├── 
├── # Frontend Assets
├── css/
│   ├── style.css               # 🎨 Main stylesheet
│   ├── fonts.css               # 🔤 Font definitions
│   └── fonts/                  # 📁 Font files
├── js/
│   ├── app.js                  # ⚙️ Main application logic
│   ├── streaming.js            # 📡 Streaming functionality
│   ├── markdown-math.js        # 📝 Markdown & math rendering
│   ├── jquery.min.js           # 🔧 jQuery library
│   └── tesseract.min.js        # 🔍 OCR engine
```

## 🎯 Cara Penggunaan

### 💬 Mode Chat
1. **Pilih Mode**: "Mode Chat" di dropdown
2. **Chat**: Ketik pesan dan tekan Enter
3. **Context**: Riwayat chat tersimpan dengan konteks
4. **Streaming**: Response real-time dari AI

### 🎓 Mode OCR Low  
1. **Pilih Mode**: "Mode OCR Low"
2. **Fokus Soal**: Dioptimalkan untuk menjawab soal dengan OCR dasar
3. **No Context**: Setiap soal ditangani independen
4. **Academic**: Response yang lebih formal dan akademis

### 📐 Mode OCR High
1. **Pilih Mode**: "Mode OCR High"
2. **Upload Gambar**: Klik tombol kamera 📷
3. **Preview**: Lihat thumbnail gambar yang diupload
4. **Kirim**: Gambar + teks tambahan (opsional)
5. **AI Vision**: GPT Vision menganalisis gambar
6. **Solution**: Dapatkan langkah penyelesaian detail

### 📊 Chat History Dashboard
- **URL utama admin**: `/admin/history.php`
- **Compatibility URL**: `/chat_history.php`
- **Features**:
  - 📅 Filter berdasarkan tanggal (default 30 hari terakhir)
  - 🔍 Filter berdasarkan IP address dan mode
  - 📋 Copy message (klik untuk copy ke clipboard)
  - 📈 Statistik penggunaan (total chat, token, model)
  - 📄 Pagination untuk performa optimal
  - 📱 Responsive design

## 🤖 Model AI yang Tersedia

| Model | Kecepatan | Akurasi | Best For | Token Limit |
|-------|-----------|---------|----------|-------------|
| **GPT-5.2** | ⚡⚡⚡ | ⭐⭐⭐⭐⭐ | Default semua mode | 4,096 |
| **GPT-5.1** | ⚡⚡⚡ | ⭐⭐⭐⭐⭐ | Alternatif unggulan | 4,096 |
| **GPT-5 Nano** | ⚡⚡⚡⚡ | ⭐⭐⭐ | Chat ringan & ekonomis | 2,048 |

## 💾 Database Schema

```sql
CREATE TABLE chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    user TEXT NOT NULL,                    -- Pesan dari user
    response TEXT NOT NULL,                -- Response dari AI
    jumlah_token INT DEFAULT 0,            -- Estimasi token usage
    model VARCHAR(50) NOT NULL,            -- Model yang digunakan
    mode VARCHAR(20) DEFAULT 'default',    -- Mode chat (default/uas/uas-math)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## 🔐 Keamanan

### File Protection (.htaccess)
```apache
# Blokir akses direct ke file sensitif
<Files "config.env">
    Order deny,allow
    Deny from all
</Files>

# Blokir file SQL dan backup
<Files "*.sql">
    Order deny,allow
    Deny from all
</Files>
```

### Best Practices
- ✅ **API Key**: Simpan di `config.env`, jangan commit ke Git
- ✅ **Database**: Gunakan user dengan privilege minimal
- ✅ **HTTPS**: Selalu gunakan HTTPS di production
- ✅ **Backup**: Regular backup database dan files
- ✅ **Monitoring**: Monitor penggunaan API dan token

## �️ Troubleshooting

### ❌ Database Connection Error
```bash
# Cek koneksi database
php -r "new PDO('mysql:host=localhost;dbname=chat_ai_web', 'user', 'pass');"
```

### ❌ API Key Invalid
1. Verifikasi API key di OpenAI dashboard
2. Cek format di `config.env` (tanpa quotes)
3. Pastikan credit/quota tersedia

### ❌ File Upload Error
```bash
# Pastikan web server bisa melayani asset frontend dan request upload normal
# OCR High mengirim gambar langsung sebagai base64, tidak butuh folder tmp
```

### ❌ Timezone Issues
- Cek nilai `APP_TIMEZONE` di `config.env`
- Pastikan server support timezone Asia/Jakarta

## 📈 Monitoring & Analytics

### Chat Statistics
- **Total Chats**: Jumlah percakapan
- **Token Usage**: Monitoring biaya API
- **Popular Models**: Model yang paling digunakan
- **Active Hours**: Peak usage time
- **Error Rate**: Success vs failed requests

### Performance Metrics
- **Response Time**: Kecepatan streaming
- **Database Performance**: Query optimization
- **File Upload Speed**: Image processing time

## � API Documentation

### Chat Streaming Endpoints

#### POST `/api_stream.php`
```json
{
    "message": "Halo AI!",
    "model": "gpt-5-nano",
    "history": [...],
    "selectedModel": "gpt-5.2"
}
```

#### POST `/api_uas_math_stream.php` (Mode OCR High)
```json
{
    "message": "Analisis gambar ini",
    "model": "gpt-5-nano",
    "imageData": "data:image/jpeg;base64,..."
}
```

### Response Format
```
data: {"type": "content", "content": "Respons dari AI..."}
data: {"type": "done"}
```

## 🚀 Deployment

### Production Checklist
- [ ] Set `config.env` dengan credentials production
- [ ] Enable HTTPS/SSL
- [ ] Set proper file permissions (644 for files, 755 for folders)
- [ ] Configure backup strategy
- [ ] Set up monitoring
- [ ] Test all chat modes
- [ ] Verify database connectivity
- [ ] Check .htaccess security rules

### Server Requirements
- **Memory**: Minimum 512MB RAM
- **Storage**: 100MB+ free space
- **Bandwidth**: Unlimited (untuk streaming)
- **PHP Version**: 7.4+ recommended

---

## 📞 Support

Jika mengalami masalah:
1. Cek file log server
2. Verifikasi konfigurasi `config.env`
3. Test koneksi database
4. Cek quota API OpenAI

**Teknologi:** PHP, MySQL, jQuery, OpenAI GPT API, Apache

**Author:** Chat AI Web Team  
**Version:** 2.0 (dengan Database & History)  
**License:** MIT
