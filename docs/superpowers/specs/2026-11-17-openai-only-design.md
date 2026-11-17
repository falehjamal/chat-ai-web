# Desain: Hapus 9router, OpenAI sebagai satu-satunya provider

Tanggal: 2026-11-17
Status: draft, menunggu persetujuan

## Intent & alasan

Aplikasi chat (`faleh-ai`) saat ini memanggil model lewat **9router lokal**
(`http://127.0.0.1:20128/v1`, env `NINEROUTER_URL` + `NINEROUTER_KEY`). Pemilik
ingin **deploy ke server yang tidak punya 9router**. Karena itu aplikasi harus
memanggil **OpenAI langsung** dengan satu-satunya env AI: `OPENAI_API_KEY`.

Sukses berarti:
- Server baru cukup diisi `.env` dengan `OPENAI_API_KEY` + DB, buka aplikasi,
  migrasi jalan otomatis, ketiga mode (Chat, OCR Low, OCR High) langsung jalan.
- Database yang sudah ada (yang pernah memakai 9router) ikut terkonversi otomatis
  tanpa kehilangan history chat, prompt, atau pengaturan mode.
- Admin bisa memilih **model** dan **reasoning effort** per mode.
- Tidak ada lagi jejak 9router di kode, UI admin, README, atau `.env.example`.

## Goals

1. Hapus semua kode 9router: `NineRouterCatalogSync`, auto-sync saat boot,
   cabang `providerKey === '9router'`, fallback model `cu/…` dan `ag/…` (Gemini).
2. Provider tetap OpenAI: base URL `https://api.openai.com/v1`, API key dari env
   `OPENAI_API_KEY`. Keduanya **ditanam di kode** sehingga tidak bisa dibelokkan
   lewat database atau admin.
3. Model awal per mode:
   | Mode | `mode_key` | Model |
   |---|---|---|
   | Chat | `default` | `gpt-5.2` |
   | OCR Low | `uas` | `gpt-5-mini` |
   | OCR High | `uas-math` | `gpt-5.2` (vision) |
4. Kolom baru `mode_bindings.reasoning_effort`, bisa diatur di halaman admin Mode.
5. Migrasi otomatis yang aman untuk instalasi baru maupun database lama.

## Non-goals

- Tidak mengganti protokol streaming. `/chat/completions` tetap dipakai, dan
  `/responses` tetap hanya untuk model `*-pro` / `codex`, sama seperti sekarang.
- Tidak mengubah tampilan atau perilaku halaman chat publik.
- Tidak mengubah data `chat_history` lama. Baris lama boleh tetap
  `provider_key = '9router'` karena itu catatan sejarah.
- Tidak menambah dukungan provider lain.

## Anti-goals (gagal walau "jalan")

- Server baru tanpa 9router gagal boot atau lambat karena mencoba menghubungi
  `127.0.0.1:20128`.
- Migrasi menghapus prompt sistem atau history chat yang sudah diedit admin.
- OCR High diam-diam diganti ke model lain (logika "vision fallback" ke Gemini).
- Admin bisa memasukkan base URL lain sehingga request keluar ke host selain OpenAI.

## Constraints

- PHP 7.4+ (lokal: PHP 8.3 di Laragon), MySQL/MariaDB, tanpa Composer dan
  tanpa test suite. Verifikasi memakai `php -l`, grep, dan uji manual lewat
  browser/curl.
- Migrasi dijalankan oleh `SqlMigrationRunner`: file `migrations/*.sql`
  diurutkan berdasarkan nama, dicatat di `app_migrations`, dan statement
  dipisah oleh `;` + newline. Migrasi yang sudah tercatat **tidak dijalankan ulang**.
- FK: `mode_bindings.model_id → ai_models.id` bersifat `ON DELETE RESTRICT`, dan
  `ai_models.provider_id → ai_providers.id` bersifat `ON DELETE CASCADE`.
- Working tree saat ini punya perubahan yang belum di-commit (css, index.php,
  js/app.js, js/markdown-math.js, js/streaming.js). Perubahan itu di luar
  pekerjaan ini dan harus di-commit atau di-stash **sebelum** eksekusi supaya
  tidak tercampur.

## Approaches

| | Pendekatan | Trade-off | Keputusan |
|---|---|---|---|
| **A** | Bedah terarah: struktur tabel tetap, migrasi `007` + `008` mengonversi data, kode 9router dihapus, halaman Provider dibuang | Perubahan kecil dan mekanis, history aman. Tabel `ai_providers` tetap ada walau hanya berisi satu baris | **Dipilih** |
| B | Buang tabel `ai_providers`, OpenAI di-hardcode total | Lebih bersih, tapi menyentuh FK, join repository, dan semua query admin. Risiko tinggi tanpa tambahan fitur | Ditolak |
| C | Pindah penuh ke Responses API | Menulis ulang streaming yang sudah jalan, di luar tujuan | Ditolak |

Pemilik memilih **A**.

## Keputusan desain

### 1. Konfigurasi OpenAI di kode
`ModeConfigResolver` selalu mengisi:
- `providerBaseUrl = 'https://api.openai.com/v1'`
- `providerApiKeyEnvVar = 'OPENAI_API_KEY'`

Nilainya tidak lagi dibaca dari `ai_providers.base_url` / `api_key_env_var`.
Kolom itu tetap ada di DB dan diisi migrasi, tapi tidak dipakai saat runtime.
Fallback legacy (saat DB tidak punya binding) memakai konstanta yang sama dengan
`providerKey = 'openai'`, `providerLabel = 'OpenAI'`.

### 2. `OpenAICompatibleProvider`
- Hapus semua cabang `9router`. `useResponsesApi = requiresResponsesApi(apiModel)`.
- Hapus parameter `$nineRouter` dari `buildChatCompletionsPayload`.
- Placeholder key yang ditolak: `your_openai_api_key_here`,
  `sk-your-openai-api-key-here`.
- **Reasoning effort** dibaca dari `$modeConfig['reasoningEffort']`
  (string, boleh kosong):
  - Kosong → tidak mengirim parameter effort sama sekali (bawaan model).
  - Tidak kosong **dan** `isReasoningModel(apiModel)` → kirim
    `reasoning_effort` (Chat Completions) atau `reasoning: {effort}` (Responses).
  - Model non-reasoning → effort diabaikan.
  - Syarat lama `use_max_completion_tokens` untuk mengirim effort **dihapus**.
    Effort tidak lagi bergantung pada flag itu.
- Logika lain tidak berubah: temperature tidak dikirim untuk model reasoning,
  dan `max_completion_tokens` vs `max_tokens` tetap mengikuti flag
  `use_max_completion_tokens`.

### 3. Effort per mode
- Kolom baru: `mode_bindings.reasoning_effort VARCHAR(20) NULL DEFAULT NULL`.
- Nilai yang valid: `NULL` (bawaan model), `none`, `minimal`, `low`, `medium`,
  `high`, `xhigh`.
- `AIConfigRepository::saveModeBinding` memvalidasi nilai itu. String kosong
  disimpan sebagai `NULL`, nilai lain ditolak dengan `PublicException`.
- `resolvedModeConfig` ikut men-select `mb.reasoning_effort`. `ModeConfigResolver`
  memetakannya ke `reasoningEffort` (string, `''` jika NULL).
- Halaman admin Mode: dropdown "Reasoning Effort" dengan opsi
  "Bawaan model" (value `''`), lalu `none` … `xhigh`. Teks bantuan menjelaskan
  bahwa effort hanya dikirim untuk model reasoning (gpt-5 / o-series), dan
  kombinasi yang tidak didukung model akan memunculkan error dari OpenAI.
- Nilai awal dari migrasi: `default` = `low` (supaya chat cepat), `uas` = `high`,
  `uas-math` = `high`. *(Keputusan builder/penulis. Admin bisa mengubahnya.)*

### 4. Model seed
Dua model OpenAI, keduanya `use_max_completion_tokens = 1`,
`supports_vision = 1`, `temperature = 0.30`, `max_tokens = 16384`, `is_active = 1`:

| model_key = api_model | label |
|---|---|
| `gpt-5.2` | `GPT-5.2` |
| `gpt-5-mini` | `GPT-5 mini` |

Model lain ditambahkan manual lewat halaman admin Model.
(`max_tokens = 16384` karena reasoning tokens ikut memakan kuota
`max_completion_tokens`. Batas 4096 berisiko memotong jawaban saat effort `high`.)

### 5. Migrasi
**File lama:**
- `003_seed_default_ai_configuration.sql` ditulis ulang supaya langsung
  menanam provider `openai` + dua model di atas + tiga binding (prompt sama
  seperti sekarang). Tujuannya agar instalasi baru tidak pernah melihat 9router.
  Database lama tidak terpengaruh karena 003 sudah tercatat.
- `005_switch_to_9router.sql` dan `006_bind_ocr_high_to_vision_model.sql`
  **dihapus**. Runner hanya membaca file yang ada, jadi catatan lama di
  `app_migrations` tidak masalah.

**Dua file baru.** `ALTER TABLE` dipisah ke file sendiri karena tidak
idempoten: kalau migrasi data gagal di tengah lalu dijalankan ulang, `ADD COLUMN`
akan gagal. Semua statement data di `008` aman dijalankan ulang.
*(Keputusan penulis, dari builder check.)*

- `007_add_reasoning_effort.sql`: hanya berisi
  `ALTER TABLE mode_bindings ADD COLUMN reasoning_effort VARCHAR(20) NULL DEFAULT NULL AFTER ocr_strategy`.
- `008_openai_only.sql` harus benar untuk dua kondisi: (a) instalasi baru
  setelah 003 baru, dan (b) DB lama yang berisi provider `9router` serta mungkin
  ratusan model hasil sync. Urutan statement di bawah ini (langkah 3 sudah ada
  di `007`):

1. `INSERT … ON DUPLICATE KEY UPDATE` provider `openai`
   (label `OpenAI`, driver `openai_compatible`, base_url OpenAI,
   `api_key_env_var = 'OPENAI_API_KEY'`, aktif).
2. `INSERT … SELECT … ON DUPLICATE KEY UPDATE` model `gpt-5.2` dan `gpt-5-mini`
   di bawah provider `openai`. Pada konflik, perbarui `provider_id`,
   `api_model`, `label`, `use_max_completion_tokens`, `supports_vision`,
   `max_tokens`, dan `is_active`.
3. *(Pindah ke `007_add_reasoning_effort.sql`.)*
4. `UPDATE mode_bindings … JOIN ai_models` untuk mengarahkan `default` dan
   `uas-math` ke `gpt-5.2`, `uas` ke `gpt-5-mini`, sekaligus mengisi effort
   awal (bagian 3). Prompt, history strategy, dan field lain **tidak disentuh**.
5. `DELETE FROM ai_models WHERE provider_id <> (id provider openai)`.
   Aman karena semua binding sudah dipindah di langkah 4. Pakai bentuk
   `DELETE m FROM ai_models m JOIN ai_providers p ON p.id = m.provider_id WHERE p.provider_key <> 'openai'`
   supaya tidak ada subquery ke tabel yang sama.
6. `DELETE FROM ai_providers WHERE provider_key <> 'openai'`.

Migrasi 007 dan 008 tidak perlu menyebut string `9router`. Binding `uas-math` tetap memakai `accepts_image = 1` dan `ocr_strategy = vision_direct` dari 003, karena migrasi lama 006 hanya mengubah `model_id`.

### 6. Hapus dari kode
- `app/Modules/AI/Application/NineRouterCatalogSync.php`: hapus file.
- `DatabaseManager`: hapus `ensureRouterCatalog()` dan pemanggilannya, serta
  `use NineRouterCatalogSync`.
- `StreamChatService`: hapus `visionModelForImage`, `forwardsImageBytes`,
  `imageCapableModel`, baris pemanggilnya, dan import `DatabaseManager` jika
  tidak terpakai lagi. Gambar dikirim apa adanya ke model yang di-bind
  (gpt-5.2 mendukung vision).
- `PublicChatContract`: `default_model_key` → `gpt-5.2` / `gpt-5-mini` / `gpt-5.2`.
- JS (`js/app.js:111`, `js/streaming.js:18,46`, `js/model-config.js:9`):
  default string `cu/gpt-5.2` → `gpt-5.2`. Server tidak membaca field `model`
  dari request, jadi ini hanya kosmetik, tapi tetap dibersihkan.

### 7. Admin
- Hapus `admin/providers.php` dan `views/admin/providers.php`, serta link
  "9router" di menu `views/admin/layout.php`.
- `admin/models.php` + `views/admin/models.php`: hapus panel dan aksi
  "Sinkronkan dari 9router" serta `use NineRouterCatalogSync`. Ganti semua teks
  9router dengan teks OpenAI. Dropdown provider boleh tetap ada (isinya hanya
  OpenAI). Teks bantuan `max_completion_tokens`: "Aktifkan untuk model gpt-5 /
  o-series (wajib untuk model reasoning)."
- `admin/modes.php` + `views/admin/modes.php`: tambah dropdown effort
  (bagian 3) dan ganti teks 9router.
- `admin/index.php`, `views/admin/dashboard.php`, `views/admin/login.php`:
  ganti teks 9router dengan OpenAI.

### 8. Dokumen & env
- `.env.example`: ganti dua baris `NINEROUTER_*` dengan
  `OPENAI_API_KEY=sk-your-openai-api-key-here`.
- `README.md`: blok env, kalimat "mengisi katalog model dari 9router",
  troubleshooting "API key invalid" → `OPENAI_API_KEY`.
- `.env` lokal pemilik (tidak di-commit): pemilik sendiri yang menambah
  `OPENAI_API_KEY=<key asli>` dan menghapus `NINEROUTER_*`. Eksekutor **tidak**
  menulis atau menampilkan key asli.

## Verifikasi (definisi selesai)

1. `grep -rniI "9router\|ninerouter\|NINEROUTER\|cu/gpt\|ag/gemini"` di luar
   `.git`, `docs/`, dan `.env` → tidak ada hasil.
2. `php -l` untuk setiap file PHP yang diubah → tanpa error.
3. Di Laragon dengan DB lama: buka aplikasi sekali → `app_migrations` berisi
   `007_add_reasoning_effort.sql` dan `008_openai_only.sql`, `ai_providers` hanya berisi `openai`, `ai_models`
   hanya berisi `gpt-5.2` dan `gpt-5-mini`, ketiga binding mengarah ke model yang
   benar, dan prompt tidak berubah.
4. Admin: Model, Mode, dan Dashboard terbuka tanpa error. URL Provider
   menghasilkan 404. Simpan effort di halaman Mode → nilainya tersimpan dan
   tercatat di audit log.
5. Chat nyata dengan `OPENAI_API_KEY` valid: Mode Chat streaming lancar,
   OCR Low menjawab, OCR High menerima gambar dan menjawab. `chat_history`
   baris baru ber-`provider_key = 'openai'`.
6. Uji instalasi baru (DB kosong): semua migrasi jalan tanpa error, hasil akhir
   sama seperti poin 3.

## Diserahkan ke builder

- Kata-kata teks bantuan dan subtitle admin (bahasa Indonesia, singkat).
- Apakah `reasoning_effort` ikut ditampilkan di tabel/ringkasan dashboard.
- Nama konstanta untuk base URL dan env var (misalnya di `ModeConfigResolver`).
