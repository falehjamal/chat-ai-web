# 🤖 Chat AI Web

Aplikasi web chat AI modern dengan Google Gemini API, interface WhatsApp-style, dan fitur OCR.

## ✨ Fitur

- 💬 **Chat Real-time**: Interface seperti WhatsApp dengan typing indicator
- 🧠 **AI Powered**: Google Gemini 2.0 Flash untuk respons cerdas
- 📷 **OCR Support**: Extract teks dari gambar menggunakan Tesseract.js
- 📱 **Responsive**: Auto-resize textarea dan mobile-friendly
- 💾 **Riwayat Chat**: Tersimpan di localStorage browser
- 🎨 **Modern UI**: Desain clean dengan animasi smooth

## 🔧 Requirements

- Web Server dengan PHP 7.4+
- Extension PHP cURL aktif
- [Google Gemini API Key](https://makersuite.google.com/app/apikey)

## ⚡ Quick Start

### 1. Setup Environment

```bash
# Copy config template
cp config.env.example config.env

# Edit config.env dan masukkan API key Anda
GEMINI_API_KEY=your_api_key_here
```

### 2. Jalankan

- Letakkan di web server (Laragon/XAMPP: folder `www` atau `htdocs`)
- Akses: `http://localhost/chat-ai-web`
- Mulai chat! 🚀

## 📁 Struktur Project

```
chat-ai-web/
├── index.php           # Halaman utama
├── api.php            # API endpoint
├── env_helper.php     # Environment config helper
├── config.env         # Konfigurasi (buat dari .example)
├── css/
│   ├── style.css      # Main stylesheet
│   └── fonts.css      # Font definitions
└── js/
    ├── app.js         # Main application (jQuery)
    ├── jquery.min.js  # jQuery 3.7.1
    ├── tesseract.min.js # OCR engine
    └── worker.min.js  # Tesseract worker
```

## 🎯 Cara Penggunaan

### Chat Normal
1. Ketik pesan di textarea
2. Tekan **Enter** untuk kirim (Shift+Enter untuk baris baru)
3. AI akan menampilkan typing indicator saat memproses
4. Riwayat chat tersimpan otomatis

### OCR (Extract Teks dari Gambar)
1. Klik ikon kamera 📷
2. Upload gambar (JPG, PNG, WebP)
3. Tunggu proses OCR selesai
4. Teks hasil extraction akan muncul di chat
5. Anda bisa langsung chat tentang teks tersebut

### Fitur Interface
- **Auto-resize**: Textarea otomatis menyesuaikan tinggi
- **Typing Indicator**: Animated dots saat AI memproses
- **Responsive**: Optimal di desktop dan mobile
- **Scroll Smooth**: Auto-scroll ke pesan terbaru

## 🔧 Troubleshooting

### API Error
- Pastikan API key valid di `config.env`
- Cek koneksi internet
- Verifikasi quota Google Gemini API

### OCR Tidak Bekerja
- Pastikan file `tesseract.min.js` dan `worker.min.js` ada
- Coba gambar dengan teks yang jelas
- Supported: JPG, PNG, WebP

### Interface Issues
- Refresh browser untuk clear cache
- Pastikan JavaScript aktif
- Clear localStorage jika chat history bermasalah

## 📝 API Endpoint

**POST** `/api.php`
```json
{
  "message": "Halo AI!",
  "history": [...]
}
```

**Response:**
```json
{
  "reply": "Halo! Ada yang bisa saya bantu?"
}
```

## 🔐 Keamanan

- API key tersimpan di `config.env` (tidak di-commit)
- File konfigurasi di-exclude dari Git
- Gunakan HTTPS di production

---

**Teknologi:** PHP, jQuery, Tesseract.js, Google Gemini API
