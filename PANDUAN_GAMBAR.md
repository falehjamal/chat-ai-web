# 📸 Panduan Penggunaan Gambar di Chat AI Web

## 🎯 Perbedaan Mode dan Pengolahan Gambar

### 🔵 Mode Default 
**Teknologi**: Tesseract.js (OCR Lokal)
- **Fungsi**: Extract/OCR teks dari gambar
- **Input**: Paste atau drag-drop gambar ke textarea
- **Output**: Teks hasil OCR langsung muncul di textarea
- **Proses**: 
  1. Upload gambar (paste/drag-drop)
  2. Tesseract melakukan OCR lokal
  3. Teks hasil extraction muncul di input
  4. User bisa edit/tambah teks lalu kirim ke AI

### 🟡 Mode UAS
**Teknologi**: Tesseract.js (OCR Lokal) 
- **Fungsi**: Extract/OCR teks dari gambar 
- **Input**: Paste atau drag-drop gambar ke textarea
- **Output**: Teks hasil OCR langsung muncul di textarea
- **Proses**: 
  1. Upload gambar (paste/drag-drop)
  2. Tesseract melakukan OCR lokal  
  3. Teks hasil extraction muncul di input
  4. User bisa edit/tambah teks lalu kirim ke AI UAS

### 🟣 Mode UAS Matematika
**Teknologi**: GPT Vision (AI Cloud)
- **Fungsi**: Analisis dan penyelesaian soal matematika dari gambar
- **Input**: Button kamera 📸, paste, atau drag-drop
- **Output**: Preview thumbnail + analisis langsung dari GPT
- **Proses**:
  1. Upload gambar → Preview thumbnail muncul
  2. Kirim gambar + pesan (opsional) ke GPT Vision
  3. GPT Vision melakukan OCR + analisis soal
  4. Response langkah penyelesaian secara streaming

## 📋 Panduan Penggunaan

### Mode Default & UAS (Tesseract OCR)
```
1. Paste/drag gambar ke textarea
2. Tunggu progress OCR selesai
3. Teks muncul di textarea
4. Edit teks jika perlu
5. Kirim untuk chat dengan AI
```

### Mode UAS Matematika (GPT Vision)
```
1. Klik tombol kamera 📸 ATAU paste/drag gambar
2. Preview thumbnail muncul
3. Tambah pesan jika perlu (opsional)
4. Klik Send
5. GPT Vision langsung analisis & selesaikan soal
6. Response streaming real-time
```

## 🔧 Format Gambar yang Didukung

**Semua Mode**:
- JPEG (.jpg, .jpeg)
- PNG (.png) 
- WebP (.webp)
- GIF (.gif)

**Rekomendasi**:
- Resolusi tinggi untuk OCR yang akurat
- Kontras yang baik (teks gelap, background terang)
- Tidak blur atau miring

## ⚡ Tips Penggunaan

### Untuk Mode Default & UAS:
- Gunakan untuk extract teks dari dokumen
- Cocok untuk soal dalam bentuk teks
- Hasil OCR bisa diedit sebelum dikirim

### Untuk Mode UAS Matematika:
- Gunakan untuk soal matematika kompleks
- Tidak perlu edit hasil OCR
- GPT Vision langsung analisis rumus & diagram
- Cocok untuk soal aljabar, kalkulus, geometri

## 🚨 Troubleshooting

**Tesseract OCR Tidak Bekerja**:
- Cek console browser untuk error
- Pastikan gambar memiliki teks yang jelas
- Coba gambar dengan kontras lebih tinggi

**GPT Vision Tidak Bekerja**:
- Pastikan API key OpenAI valid
- Cek model yang dipilih (gunakan GPT-4o untuk Vision)
- Pastikan koneksi internet stabil

**Preview Gambar Tidak Muncul**:
- Cek format gambar yang didukung
- Pastikan file tidak corrupt
- Refresh halaman dan coba lagi
