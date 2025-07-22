# 🤖 Chat AI Web

Aplikasi web chat AI modern dengan OpenAI GPT API, interface WhatsApp-style, dan fitur OCR + Vision.

## ✨ Fitur

- 💬 **Multi-Mode Chat**: Mode Default, UAS, dan UAS Matematika
- 🧠 **AI Powered**: OpenAI GPT (3.5, 4o, 4.1) untuk respons cerdas
- 📷 **OCR + Vision**: Upload gambar soal matematika untuk analisis GPT Vision
- 🎯 **Mode UAS Matematika**: Khusus untuk menyelesaikan soal matematika dari gambar
- 📱 **Responsive**: Auto-resize textarea dan mobile-friendly
- 💾 **Riwayat Chat**: Tersimpan per mode di localStorage browser
- 🎨 **Modern UI**: Desain clean dengan animasi smooth
- ⚡ **Streaming**: Real-time streaming response

## 🔧 Requirements

- Web Server dengan PHP 7.4+
- Extension PHP cURL aktif
- [OpenAI API Key](https://platform.openai.com/api-keys)

## ⚡ Quick Start

### 1. Setup Environment

```bash
# Copy config template
cp config.env.example config.env

# Edit config.env dan masukkan API key Anda
OPENAI_API_KEY=your_api_key_here
```

### 2. Jalankan

- Letakkan di web server (Laragon/XAMPP: folder `www` atau `htdocs`)
- Akses: `http://localhost/chat-ai-web`
- Mulai chat! 🚀

## 📁 Struktur Project

```
chat-ai-web/
├── index.php                 # Halaman utama
├── api_stream.php           # API endpoint Mode Default
├── api_uas_stream.php       # API endpoint Mode UAS
├── api_uas_math_stream.php  # API endpoint Mode UAS Matematika
├── upload_image.php         # Handler upload gambar
├── env_helper.php           # Environment config helper
├── config.env               # Konfigurasi (buat dari .example)
├── css/
│   ├── style.css           # Main stylesheet
│   └── fonts.css           # Font definitions
├── js/
│   ├── app.js              # Main application (jQuery)
│   ├── streaming.js        # Streaming functionality
│   ├── jquery.min.js       # jQuery 3.7.1
│   ├── tesseract.min.js    # OCR engine (tidak digunakan)
│   └── worker.min.js       # Tesseract worker (tidak digunakan)
└── tmp/                     # Folder temporary untuk gambar
```

## 🎯 Cara Penggunaan

### Mode Default
1. Pilih "Mode Default" 
2. Ketik pesan di textarea
3. Tekan **Enter** untuk kirim (Shift+Enter untuk baris baru)
4. AI akan streaming response real-time
5. Riwayat chat tersimpan dengan konteks

### Mode UAS
1. Pilih "Mode UAS"
2. Chat AI yang dioptimalkan untuk soal UAS
3. Tanpa konteks riwayat (fokus pada satu soal)
4. Streaming response real-time

### Mode UAS Matematika (BARU!)
1. **Pilih Mode**: Klik "Mode UAS Matematika"
2. **Upload Gambar**: Klik tombol kamera � untuk upload gambar soal
3. **Preview**: Gambar ditampilkan sebagai thumbnail preview
4. **Kirim**: Gambar + pesan (opsional) dikirim ke GPT Vision
5. **Analisis**: GPT Vision melakukan OCR dan analisis soal
6. **Penyelesaian**: Dapatkan langkah-langkah penyelesaian secara detail
7. **Streaming**: Response ditampilkan real-time

### Model GPT yang Tersedia
- **GPT-3.5 Turbo**: Cepat & ekonomis (cocok untuk obrolan ringan)
- **GPT-4o**: Pintar & fleksibel (ideal untuk tugas, UAS, dan esai + Vision)
- **GPT-4.1**: Akurasi tinggi (terbaik untuk matematika dan logika kompleks)

### Fitur Interface
- **Auto-resize**: Textarea otomatis menyesuaikan tinggi
- **Typing Indicator**: Animated dots saat AI memproses
- **Responsive**: Optimal di desktop dan mobile
- **Scroll Smooth**: Auto-scroll ke pesan terbaru
- **Multi-Mode**: Riwayat chat terpisah per mode
- **Image Preview**: Thumbnail preview untuk gambar yang diupload

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
