# 🤖 Chat AI Web

Aplikasi web chat AI sederhana yang menggunakan Google Gemini API untuk memberikan respons cerdas dan interaktif.

## ✨ Fitur

- 💬 **Chat Real-time**: Interface chat yang responsif dan modern
- 🧠 **AI Powered**: Menggunakan Google Gemini 2.0 Flash untuk respons yang cerdas
- 📱 **Responsive Design**: Tampilan yang optimal di desktop dan mobile
- 💾 **Riwayat Chat**: Menyimpan riwayat percakapan di localStorage
- 🔐 **Environment Config**: Konfigurasi API key yang aman menggunakan file environment
- 🎨 **UI Modern**: Desain yang bersih dengan animasi smooth

## 🔧 Prasyarat

- **Web Server**: Apache/Nginx dengan PHP support
- **PHP**: Versi 7.4 atau lebih baru
- **cURL**: Extension PHP cURL harus aktif
- **Google Gemini API Key**: Dapatkan dari [Google AI Studio](https://makersuite.google.com/app/apikey)

## 📦 Instalasi

### 1. Clone atau Download Project

```bash
git clone <repository-url>
cd chat-ai-web
```

### 2. Setup Web Server

Pastikan web server Anda mengarah ke direktori project ini.

**Untuk Laragon/XAMPP:**
- Letakkan folder project di dalam `www` atau `htdocs`
- Akses melalui `http://localhost/chat-ai-web`

### 3. Verifikasi PHP Extensions

Pastikan extension berikut aktif di PHP:
- `curl`
- `json`

## ⚙️ Konfigurasi

### 1. Setup Environment File

Salin file contoh konfigurasi:
```bash
cp config.env.example config.env
```

### 2. Edit Konfigurasi

Buka file `config.env` dan masukkan API key Anda:

```env
# Konfigurasi API Key Gemini
GEMINI_API_KEY=your_actual_api_key_here

# Konfigurasi lainnya (opsional)
APP_NAME="Chat AI Web"
APP_ENV=production
DEBUG=false
```

### 3. Dapatkan API Key Gemini

1. Kunjungi [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Login dengan akun Google Anda
3. Klik "Create API Key"
4. Salin API key dan masukkan ke file `config.env`

## 🚀 Penggunaan

### Menjalankan Aplikasi

1. Pastikan web server berjalan
2. Buka browser dan akses: `http://localhost/chat-ai-web`
3. Mulai chat dengan AI!

### Fitur Chat

- **Kirim Pesan**: Ketik pesan dan tekan Enter atau klik tombol Send
- **Riwayat**: Percakapan tersimpan otomatis di browser
- **Konteks**: AI mengingat 10 pesan terakhir untuk konteks yang lebih baik
- **Responsive**: Bekerja optimal di semua ukuran layar

## 📁 Struktur File

```
chat-ai-web/
├── index.php              # Halaman utama aplikasi
├── api.php                # API endpoint untuk komunikasi dengan Gemini
├── env_helper.php         # Helper untuk membaca konfigurasi environment
├── config.env             # File konfigurasi (tidak di-commit)
├── config.env.example     # Template konfigurasi
├── .gitignore            # File yang diabaikan Git
└── README.md             # Dokumentasi ini
```

### Deskripsi File

#### `index.php`
- Halaman utama dengan interface chat
- Menggunakan HTML, CSS, dan JavaScript vanilla
- Responsive design dengan animasi modern
- LocalStorage untuk menyimpan riwayat chat

#### `api.php`
- Endpoint API untuk komunikasi dengan Google Gemini
- Menangani request POST dari frontend
- Memproses riwayat chat untuk konteks
- Error handling yang komprehensif

#### `env_helper.php`
- Helper functions untuk membaca file environment
- `loadEnv()`: Memuat konfigurasi dari file
- `getEnvironmentVar()`: Mengambil nilai konfigurasi

#### `config.env`
- File konfigurasi utama (tidak di-commit ke Git)
- Berisi API key dan pengaturan aplikasi
- Format: `KEY=value`

## 🔌 API Endpoint

### POST `/api.php`

Endpoint untuk mengirim pesan ke AI dan menerima respons.

#### Request Body

```json
{
    "message": "Halo, bagaimana kabar Anda?",
    "history": [
        {
            "sender": "user",
            "text": "Pesan sebelumnya dari user"
        },
        {
            "sender": "bot", 
            "text": "Respons AI sebelumnya"
        }
    ]
}
```

#### Response Success

```json
{
    "reply": "Respons dari AI"
}
```

#### Response Error

```json
{
    "error": "Deskripsi error"
}
```

### Headers

- `Content-Type: application/json`
- Method: `POST`

## 🔐 Keamanan

### Environment Variables

- File `config.env` tidak di-commit ke repository
- API key disimpan terpisah dari kode sumber
- Gunakan `.gitignore` untuk mencegah kebocoran data sensitif

### Best Practices

1. **Jangan hardcode API key** dalam kode
2. **Gunakan HTTPS** di production
3. **Batasi akses** ke file konfigurasi
4. **Regular update** API key jika diperlukan
5. **Monitor usage** API untuk mencegah abuse

### File Permissions

```bash
# Set permission yang aman untuk file konfigurasi
chmod 600 config.env
```

## 🐛 Troubleshooting

### Error: "Cannot redeclare getEnv()"

**Solusi**: Fungsi sudah diganti dengan `getEnvironmentVar()` untuk menghindari konflik.

### Error: "API Key tidak ditemukan"

**Penyebab**: File `config.env` tidak ada atau API key kosong.

**Solusi**:
1. Pastikan file `config.env` ada
2. Periksa format: `GEMINI_API_KEY=your_key_here`
3. Tidak ada spasi di sekitar tanda `=`

### Error: "Failed to get response from AI"

**Penyebab**: 
- API key tidak valid
- Koneksi internet bermasalah
- Quota API habis

**Solusi**:
1. Verifikasi API key di Google AI Studio
2. Periksa koneksi internet
3. Cek quota dan billing Google Cloud

### Chat tidak muncul

**Penyebab**: JavaScript error atau masalah CORS.

**Solusi**:
1. Buka Developer Tools (F12)
2. Periksa Console untuk error
3. Pastikan mengakses melalui web server, bukan file://

### Performance Issues

**Optimasi**:
1. Kurangi `MAX_HISTORY` di `index.php` jika perlu
2. Implementasi caching untuk respons yang sering
3. Gunakan CDN untuk assets static

## 🤝 Kontribusi

Kontribusi sangat diterima! Berikut cara berkontribusi:

### 1. Fork Repository

```bash
git fork <repository-url>
```

### 2. Buat Branch Fitur

```bash
git checkout -b fitur-baru
```

### 3. Commit Perubahan

```bash
git commit -m "Menambahkan fitur baru"
```

### 4. Push dan Pull Request

```bash
git push origin fitur-baru
```

### Guidelines

- Gunakan pesan commit yang jelas
- Tambahkan dokumentasi untuk fitur baru
- Test semua perubahan sebelum submit
- Follow coding standards yang ada

## 📈 Roadmap

### Versi Mendatang

- [ ] **Authentication**: Login/register user
- [ ] **Database**: Penyimpanan chat di database
- [ ] **Multiple Models**: Support untuk model AI lainnya
- [ ] **File Upload**: Upload gambar untuk AI vision
- [ ] **Export Chat**: Export riwayat ke PDF/TXT
- [ ] **Themes**: Multiple tema UI
- [ ] **Voice Input**: Input suara
- [ ] **Admin Panel**: Dashboard untuk monitoring

## 📄 Lisensi

Project ini menggunakan lisensi MIT. Lihat file `LICENSE` untuk detail lengkap.

## 📞 Support

Jika Anda mengalami masalah atau memiliki pertanyaan:

1. Periksa bagian [Troubleshooting](#-troubleshooting)
2. Buka issue di repository
3. Hubungi developer

## 🙏 Acknowledgments

- [Google Gemini AI](https://deepmind.google/technologies/gemini/) - AI Model
- [Google Fonts](https://fonts.google.com/) - Typography
- PHP Community - Documentation dan support

---

**Dibuat dengan ❤️ untuk komunitas developer Indonesia** 
