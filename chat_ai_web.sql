/*
 Navicat Premium Dump SQL

 Source Server         : faleh Local
 Source Server Type    : MySQL
 Source Server Version : 50739 (5.7.39)
 Source Host           : localhost:3306
 Source Schema         : chat_ai_web

 Target Server Type    : MySQL
 Target Server Version : 50739 (5.7.39)
 File Encoding         : 65001

 Date: 09/06/2026 11:49:57
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for admin_users
-- ----------------------------
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of admin_users
-- ----------------------------
INSERT INTO `admin_users` VALUES (1, 'admin', '$2y$10$C1ohQNqRmTsLLV9EfQy.q.LwGBJs88PPUp70za/tJwIdQ/mwpJu8O', 'admin', 1, '2026-03-09 12:10:48', '2026-03-09 12:10:48');

-- ----------------------------
-- Table structure for ai_models
-- ----------------------------
DROP TABLE IF EXISTS `ai_models`;
CREATE TABLE `ai_models`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_id` int(11) NOT NULL,
  `model_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_model` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `temperature` decimal(4, 2) NOT NULL DEFAULT 0.30,
  `max_tokens` int(11) NOT NULL DEFAULT 4096,
  `use_max_completion_tokens` tinyint(1) NOT NULL DEFAULT 1,
  `supports_vision` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `model_key`(`model_key`) USING BTREE,
  INDEX `fk_ai_models_provider`(`provider_id`) USING BTREE,
  CONSTRAINT `fk_ai_models_provider` FOREIGN KEY (`provider_id`) REFERENCES `ai_providers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of ai_models
-- ----------------------------
INSERT INTO `ai_models` VALUES (1, 1, 'gpt-5.4-2026-03-05', 'gpt-5.4-2026-03-05', 'GPT-5.4', 0.30, 16384, 1, 1, 1, '2026-03-09 11:59:12', '2026-06-09 10:59:42');
INSERT INTO `ai_models` VALUES (2, 1, 'gpt-5.5-pro-2026-04-23', 'gpt-5.5-pro-2026-04-23', 'GPT-5.5 pro', 0.30, 16384, 1, 1, 1, '2026-05-30 09:37:01', '2026-06-09 11:12:06');
INSERT INTO `ai_models` VALUES (3, 1, 'gpt-5.5-2026-04-23', 'gpt-5.5-2026-04-23', 'GPT-5.5', 0.30, 16384, 1, 1, 1, '2026-05-30 09:39:50', '2026-06-09 11:12:06');

-- ----------------------------
-- Table structure for ai_providers
-- ----------------------------
DROP TABLE IF EXISTS `ai_providers`;
CREATE TABLE `ai_providers`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `driver` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_key_env_var` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `provider_key`(`provider_key`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of ai_providers
-- ----------------------------
INSERT INTO `ai_providers` VALUES (1, 'openai', 'OpenAI', 'openai_compatible', 'https://api.openai.com/v1', 'OPENAI_API_KEY', 1, '2026-03-09 11:59:12', '2026-03-09 11:59:12');

-- ----------------------------
-- Table structure for app_migrations
-- ----------------------------
DROP TABLE IF EXISTS `app_migrations`;
CREATE TABLE `app_migrations`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `executed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `migration_name`(`migration_name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of app_migrations
-- ----------------------------
INSERT INTO `app_migrations` VALUES (1, '001_create_chat_history.sql', '2026-03-09 11:59:12');
INSERT INTO `app_migrations` VALUES (2, '002_create_admin_and_ai_config_tables.sql', '2026-03-09 11:59:12');
INSERT INTO `app_migrations` VALUES (3, '003_seed_default_ai_configuration.sql', '2026-03-09 11:59:12');
INSERT INTO `app_migrations` VALUES (4, '004_add_provider_key_to_chat_history.sql', '2026-03-09 11:59:12');

-- ----------------------------
-- Table structure for chat_history
-- ----------------------------
DROP TABLE IF EXISTS `chat_history`;
CREATE TABLE `chat_history`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL COMMENT 'Pesan dari user',
  `response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `jumlah_token` int(11) NULL DEFAULT 0 COMMENT 'Jumlah token yang digunakan dalam chat',
  `model` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT 'gpt-5.1' COMMENT 'Model GPT yang digunakan',
  `provider_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_ip_address`(`ip_address`) USING BTREE,
  INDEX `idx_created_at`(`created_at`) USING BTREE,
  INDEX `idx_model`(`model`) USING BTREE,
  INDEX `idx_token_count`(`jumlah_token`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 60 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Records of chat_history
-- ----------------------------
INSERT INTO `chat_history` VALUES (11, '127.0.0.1', 'halo', 'Halo! Ada yang bisa saya bantu hari ini? 😊', 24, 'gpt-4o', NULL, 'default', '2025-12-09 22:39:53', '2025-12-09 22:39:53');
INSERT INTO `chat_history` VALUES (12, '127.0.0.1', 'halo', 'Halo! 😊 Senang bisa ngobrol denganmu. Ada yang bisa aku bantu hari ini?', 33, 'gpt-4.1-nano', NULL, 'default', '2025-12-09 22:59:00', '2025-12-09 22:59:00');
INSERT INTO `chat_history` VALUES (13, '127.0.0.1', 'halo', 'Halo juga! 😄  \nKayaknya kita lagi saling sapa terus nih.\n\nMau mulai ngobrol soal apa?  \nKamu bisa tanya apa saja: tugas, kerjaan, ide bisnis, coding, atau cuma mau curhat ringan juga boleh.', 65, 'gpt-5.1', NULL, 'default', '2025-12-09 22:59:08', '2025-12-09 22:59:08');
INSERT INTO `chat_history` VALUES (14, '127.0.0.1', 'kerjakan', 'Berikut pembahasan singkat dan jawabannya:\n\n1. **Suku sejenis** dari \\(6x^2 + 6xy -4y^2 - 7x^2 + 2xy + 2y^2\\)  \n   Suku sejenis: \\(6x^2\\) dan \\(-7x^2\\), \\(6xy\\) dan \\(2xy\\), \\(-4y^2\\) dan \\(2y^2\\).  \n   Yang ada di pilihan: \\(-4y^2\\) dan \\(2xy\\).  \n   **Jawaban: c**\n\n2. Sederhanakan \\(9y^2 - 4xy + 5y + 7y^2 + 3xy\\)  \n   \\(9y^2 + 7y^2 = 16y^2\\), \\(-4xy + 3xy = -xy\\), sisa \\(+5y\\).  \n   Hasil: \\(16y^2 - xy + 5y\\).  \n   **Jawaban: a**\n\n3. Sederhanakan \\(-2(2x^2 + 3x - 4)\\)  \n   \\(-2 \\cdot 2x^2 = -4x^2\\), \\(-2 \\cdot 3x = -6x\\), \\(-2 \\cdot -4 = 8\\).  \n   Hasil: \\(-4x^2 - 6x + 8\\).  \n   **Jawaban: b**\n\n4. Jumlahkan \\((6x - 5y - 2z) + (-8x + 6y + 9z)\\)  \n   \\(6x - 8x = -2x\\), \\(-5y + 6y = y\\), \\(-2z + 9z = 7z\\).  \n   Hasil: \\(-2x + y + 7z\\).  \n   **Jawaban: c**\n\n5. Kurangkan \\((5x - 3y + 7)\\) dari \\((5y - 3x - 4)\\):  \n   \\((5y - 3x - 4) - (5x - 3y + 7)\\)  \n   \\(= 5y - 3x - 4 - 5x + 3y - 7\\)  \n   \\(= -8x + 8y - 11\\).  \n   **Jawaban: c**\n\n**Jawaban akhir:**\n1. c  \n2. a  \n3. b  \n4. c  \n5. c', 271, 'gpt-5.1', NULL, 'uas-math', '2025-12-09 22:59:59', '2025-12-09 22:59:59');
INSERT INTO `chat_history` VALUES (15, '127.0.0.1', 'halo , kamu siapa', 'Halo! Aku asisten virtual yang dibuat oleh Ahmad Faleh Jamaluddin.  \nAku di sini untuk bantu kamu cari informasi, menjawab pertanyaan, atau sekadar ngobrol.\n\nKamu mau tanya atau bahas apa? 😊', 69, 'gpt-5.1', NULL, 'default', '2025-12-09 23:01:51', '2025-12-09 23:01:51');
INSERT INTO `chat_history` VALUES (16, '127.0.0.1', 'namaku samuel', 'Halo Samuel, senang kenal kamu! 😄  \n\nKalau boleh tahu, kamu lagi pengin bahas apa hari?  \nBelajar, tugas sekolah/kuliah, teknologi, game, atau cuma mau ngobrol santai juga boleh banget.', 66, 'gpt-5.1', NULL, 'default', '2025-12-09 23:02:06', '2025-12-09 23:02:06');
INSERT INTO `chat_history` VALUES (17, '127.0.0.1', 'namaku siapa coba tebak', 'Namamu… Samuel! 😄  \n\nTadi kamu sudah bilang, kan?  \nMau aku panggil “Samuel” atau ada panggilan lain yang kamu suka, misalnya “Sam” atau yang lain?', 58, 'gpt-5.1', NULL, 'default', '2025-12-09 23:02:18', '2025-12-09 23:02:18');
INSERT INTO `chat_history` VALUES (18, '127.0.0.1', 'kamu ini ai gpt ya?', 'Iya, aku AI berbasis GPT (Generative Pre-trained Transformer).  \nLebih tepatnya, aku adalah asisten virtual yang dijalankan dengan teknologi GPT dan dibuat untuk ngobrol, bantu jawab pertanyaan, dan bantu berbagai keperluan kamu.\n\nKalau kamu penasaran:\n- Bisa tanya apa itu GPT\n- Atau gimana cara kerja AI seperti aku\n- Atau langsung pakai aku buat hal lain (tugas, ide, cerita, dll.)\n\nMau bahas yang mana, Samuel?', 134, 'gpt-5.1', NULL, 'default', '2025-12-09 23:03:23', '2025-12-09 23:03:23');
INSERT INTO `chat_history` VALUES (19, '127.0.0.1', 'model tipe apa yang kamu pakai sekarang untuk menjawab pesan ini', 'Sekarang aku jalan sebagai model **GPT-4.1 (versi ChatGPT terbaru dari OpenAI yang kamu akses lewat API).  \n\nKalau kamu mau, aku bisa jelasin juga:\n- bedanya GPT-4, GPT-4.1, dan GPT-3.5  \n- atau kemampuan apa saja yang bisa aku lakukan (misalnya bantu coding, nulis, belajar, dll.)', 108, 'gpt-5.1', NULL, 'default', '2025-12-09 23:03:42', '2025-12-09 23:03:42');
INSERT INTO `chat_history` VALUES (20, '127.0.0.1', 'engga, kamu pakai gpt 5.1 ini', 'Menurut info sistem yang aku punya, aku sekarang berjalan sebagai model **GPT-4.1**, bukan GPT-5.1.  \n\nKalau suatu saat OpenAI merilis GPT-5. dan kamu mengaksesnya, nanti keterangan modelnya juga akan berbeda di sistem.  \nTapi untuk sesi chat ini, yang aktif adalah GPT-4.1.', 96, 'gpt-5.1', NULL, 'default', '2025-12-09 23:03:53', '2025-12-09 23:03:53');
INSERT INTO `chat_history` VALUES (21, '127.0.0.1', 'namaku siapa', 'Hai! Aku belum tahu nama kamu, nih. Tapi aku senang banget bisa ngobrol sama kamu. Kalau mau, ceritain dong siapa nama kamu!', 50, 'gpt-4.1-nano', NULL, 'default', '2025-12-09 23:31:41', '2025-12-09 23:31:41');
INSERT INTO `chat_history` VALUES (22, '127.0.0.1', 'gambar apa ini', 'Itu adalah gambar **jam dinding analog** menunjukkan waktu sekitar **ukul 12 lewat 17 menit**.', 41, 'gpt-5.1', NULL, 'uas-math', '2025-12-10 13:58:15', '2025-12-10 13:58:15');
INSERT INTO `chat_history` VALUES (23, '127.0.0.1', 'halo', 'Halo! Aku asisten virtual yang ceria,atif, dan ram—dibuat oleh developer bernama **Ahmad Faleh Jamaluddin**.  \nAku bisa bantu jawab pertanyaan, cari ide, bikin rangkuman, atau bantu tugas lain.\n\nKamu mau dibantu apa hari ini?', 76, 'gpt-5.2', NULL, 'default', '2025-12-12 10:04:57', '2025-12-12 10:04:57');
INSERT INTO `chat_history` VALUES (24, '127.0.0.1', 'kamu pakai model apa', 'Aku dijalankan dengan model bahasa besar (large language model) dari OpenAI yang sudah di-*fine-tune* dikonfigurasi developer-mu terc, Ahmad Faleh Jamaluddin.  \n\nSecara teknis, aku termasuk generasi model GPT terbaru (setara keluarga GPT-4.1 / GPT-4.1-mini) yang bisa:\n- Memahami dan menjawab dalam bahasa Indonesia dengan baik  \n- Membantu penjelasan teknis, tugas, ide kreatif, dll  \n- Mengikuti gaya yang diatur developer (ceria, informatif, dan ramah)\n\nKalau kamu mau, aku bisa jelaskan lebih detail soal cara kerja model seperti aku.', 168, 'gpt-5.1', NULL, 'default', '2025-12-12 10:05:42', '2025-12-12 10:05:42');
INSERT INTO `chat_history` VALUES (25, '127.0.0.1', 'halo kak', 'Halo juga,! Saya asisten virtual buatan Ahmad Fale Jamaluddin.  \nKakak lagiuh bantuan apa hari ini?', 41, 'gpt-5.2', NULL, 'default', '2025-12-12 10:13:01', '2025-12-12 10:13:01');
INSERT INTO `chat_history` VALUES (26, '127.0.0.1', 'ini gambar apa', 'Itu **logo/emblem Sekolah Menengah Kejuruan Negeri 1 (SMKN 1) Jakarta**. Terlihat tulisan “SEKOLAH MENENGAH KEJURUAN NEGERI 1” dan “JAKARTA” pada lambangnya.', 59, 'gpt-5.2', NULL, 'uas-math', '2025-12-12 10:14:53', '2025-12-12 10:14:53');
INSERT INTO `chat_history` VALUES (27, '127.0.0.1', 'halo', 'Halo! Senang bertemu kamu lagi 😊 Ada hal yang ingin kamu tanyakan butuh bantuan dengan sesuatu hari ini? Kamu bisa tanya apa saja—pelajaran, bahasa, teknologi, rekomendasi film/buku, atau bantuan menulis. Mau mulai dengan topik apa?', 79, 'gpt-5-nano', NULL, 'default', '2025-12-12 10:31:31', '2025-12-12 10:31:31');
INSERT INTO `chat_history` VALUES (28, '127.0.0.1', 'Halo namaku faleh', 'Halo Faleh! Senang bertemu denganmu. Aku asisten virtual yang ceria dan siap bantu apa saja—jawab pertanyaan, ringkas materi, bantu nulis, belajar bahasa, atau cari info.\n\nMau mulai dengan apa hari ini? Kamu bisa bilang topik atau pertanyaanmu, nanti aku bantu secepatnya.', 93, 'gpt-5-nano', NULL, 'default', '2025-12-12 10:32:05', '2025-12-12 10:32:05');
INSERT INTO `chat_history` VALUES (29, '127.0.0.1', 'namamu siapa', 'Namaku ChatGPT, asisten virtual yang ceria. Aku dibuat oleh Ahmad Faleh Jamaluddin. Senang bisa bantu kamu. Mau mulai dengan pertanyaan atau topik apa hari ini?', 60, 'gpt-5-nano', NULL, 'default', '2025-12-12 10:32:18', '2025-12-12 10:32:18');
INSERT INTO `chat_history` VALUES (30, '127.0.0.1', 'kerjakan', '1) \\((-3a-6b+7) + (13a-(-2b)+4) = (-3a-6b+7) + (13a+2b+4)\\)  \n\\(=10a-4b+11\\) → **C**\n\n2) \\((4x-5)(3x+3)=12x^2+12x-15x-15=12x^2-3x-15\\) → **A**\n\n3) Pada \\(-6x^2-x+4y\\), variabelnya adalah **x dan y** (muncul sebagai \\(x^2, x, y\\)) → **B**\n\n4) Pada \\(x^2-2x-5\\), koefisien (untuk suku ber-variabel) adalah **1 dan -2**, tetapi yang ditanyakan di opsi adalah koefisien yang tampak: **-2** → **B**\n\n5) Pada \\(3a^2-7a-9\\), suku konstanta adalah **-9** → **D**\n\n6) \\((8p+5q) - (2p-4q)=8p+5q-2p+4q=6p+9q\\) → **B**\n\n7) \\(4(2x-5y)-5(x+3y)=8x-20y-5x-15y=3x-35y\\) → **D**\n\n**Jawaban:** 1C, 2A, 3B, 4B, 5D, 6B, 7D.', 183, 'gpt-5.2', NULL, 'uas-math', '2025-12-12 10:33:37', '2025-12-12 10:33:37');
INSERT INTO `chat_history` VALUES (31, '127.0.0.1', 'tes', 'Halo! Tesnya berhasil. Senang bisa menemani kamu. Ada yang ingin kamu coba sekarang? Beberapa tugas yang bisa saya bantu:\n- jelaskan konsep atau kata\n- terjemahkan kalimat\n- hitung-perhitungan sederhana\n- ringkas artikel atau dokumen\n- cari resep, rekomendasi film, atau saran aktivitas\n- buat catatan atau to-do list\n\nKatakan saja apa yang kamu butuhkan, aku siap membantu!', 118, 'gpt-5-nano', NULL, 'default', '2025-12-14 18:41:53', '2025-12-14 18:41:53');
INSERT INTO `chat_history` VALUES (32, '127.0.0.1', 'halo kak', 'Halo juga! Aku asisten virtual yang dibuat oleh Ahmad Faleh Jamaluddin.  \nAda yang bisa aku bantu hari ini?', 43, 'gpt-5.2', NULL, 'default', '2026-02-23 21:40:18', '2026-02-23 21:40:18');
INSERT INTO `chat_history` VALUES (33, '127.0.0.1', 'contohkan 2 soal aljabar yang kompleks dan jawabannya', 'Berikut 2 contoh soal aljabar yang cukup kompleks beserta jawabannya (dengan penjelasan singkat).\n\n---\n\n## Soal 1 (Sistem persamaan non-linear)\nTentukan semua pasangan real \\((x,y)\\) yang memenuhi:\n\\[\n\\begin{cases}\nx+y=7\\\\\nx^2+y^2=29\n\\end{cases}\n\\]\n\n**Singkat:**\nGunakan \\((x+y)^2=x^2+2xy+y^2\\).\n\\[\n49 = 29 + 2xy \\Rightarrow 2xy=20 \\Rightarrow xy=10\n\\]\nMaka \\(x\\) dan \\(y\\) adalah akar-akar persamaan:\n\\[\nt^2-7t+10=0 \\Rightarrow (t-5)(t-2)=0\n\\]\nJadi \\((x,y)\\) adalah \\((5,2)\\) atau \\((2,5)\\).\n\n**Jawaban akhir:** \\(\\boxed{(x,y)=(5,2)\\ \\text{atau}\\ (2,5)}\\)\n\n---\n\n## Soal 2 (Persamaan rasional + syarat domain)\nSelesaikan untuk \\(x\\) real:\n\\[\n\\frac{x+1}{x-2}+\\frac{x-2}{x+1}=3\n\\]\ndengan syarat \\(x\\neq 2\\) dan \\(x\\neq -1\\).\n\n**Singkat:**\nSamakan penyebut:\n\\[\n\\frac{(x+1)^2+(x-2)^2}{(x-2)(x+1)}=3\n\\]\nHitung pembilang:\n\\[\n(x+1)^2+(x-2)^2=(x^2+2x+1)+(x^2-4x+4)=2x^2-2x+5\n\\]\nSehingga:\n\\[\n2x^2-2x+5 = 3(x-2)(x+1)=3(x^2-x-2)=3x^2-3x-6\n\\]\nPindahkan ruas:\n\\[\n0 = x^2 - x - 11\n\\]\n\\[\nx=\\frac{1\\pm\\sqrt{1+44}}{2}=\\frac{1\\pm 3\\sqrt5}{2}\n\\]\nKeduanya tidak sama dengan \\(2\\) atau \\(-1\\), jadi valid.\n\n**Jawaban akhir:** \\(\\boxed{x=\\frac{1+3\\sqrt5}{2}\\ \\text{atau}\\ x=\\frac{1-3\\sqrt5}{2}}\\)\n\n--- \n\nKalau mau, saya bisa buat versi yang lebih “kompleks” lagi (misalnya melibatkan parameter, pertidaksamaan, atau sistem 3 variabel).', 398, 'gpt-5.2', NULL, 'uas-math', '2026-02-23 21:41:01', '2026-02-23 21:41:01');
INSERT INTO `chat_history` VALUES (34, '127.0.0.1', 'kamu siapa', 'Aku asisten virtual (AI) yang siap bantu jawab pertanyaan, kasih penjelasan, atau bantu ngerjain tugas seperti rangkum, bikin ide, dan lain-lain. Aku dibuat oleh developer bernama **Ahmad Faleh Jamaluddin**.\n\nKamu mau aku bantu soal apa?', 81, 'gpt-5.2', NULL, 'default', '2026-02-23 21:42:24', '2026-02-23 21:42:24');
INSERT INTO `chat_history` VALUES (35, '127.0.0.1', 'apakah kamu chatgpt', 'Aku asisten AI yang kamu akses lewat layanan OpenAI—jadi secara umum bisa dibilang aku **ChatGPT** (model percakapan dari OpenAI).  \nBedanya, di sini aku diposisikan sebagai asisten virtual yang dibuat oleh **Ahmad Faleh Jamaluddin**.\n\nKalau kamu mau, aku bisa jelasin juga versi/model atau kemampuan yang kamu butuhkan.', 107, 'gpt-5.2', NULL, 'default', '2026-02-23 21:42:41', '2026-02-23 21:42:41');
INSERT INTO `chat_history` VALUES (36, '127.0.0.1', 'kamu versi berapa dari model GPT', 'Aku tidak bisa melihat secara pasti label versi model yang sedang dipakai di sistem kamu (misalnya “GPT‑4”, “GPT‑4.1”, dll.) karena info itu tidak selalu dibuka ke asisten.\n\nKalau kamu kasih tahu nama model yang tertulis di aplikasi/halaman tempat kamu chat (biasanya ada di bagian atas atau pengaturan), aku bisa bantu jelaskan bedanya dan kemampuannya.', 121, 'gpt-5.2', NULL, 'default', '2026-02-23 21:43:37', '2026-02-23 21:43:37');
INSERT INTO `chat_history` VALUES (37, '127.0.0.1', 'namaku umam, salah kenal ya', 'Tidak salah kenal, Umam. Aku memang belum tahu namamu sebelumnya—sekarang sudah tahu.\n\nKalau tadi aku menyebut “developer Ahmad Faleh Jamaluddin”, itu identitas pembuat asisten ini, bukan nama kamu.  \nSenang kenal, Umam. Mau aku panggil “Umam” atau ada panggilan lain?', 94, 'gpt-5.2', NULL, 'default', '2026-02-23 21:43:53', '2026-02-23 21:43:53');
INSERT INTO `chat_history` VALUES (38, '127.0.0.1', 'nama lengkapku irkhamul umam', 'Baik, **Irkhamul Umam**—senang kenal.\n\nMau aku panggil **Irkhamul**, **Umam**, atau ada panggilan lain yang kamu prefer?', 52, 'gpt-5.2', NULL, 'default', '2026-02-23 21:44:14', '2026-02-23 21:44:14');
INSERT INTO `chat_history` VALUES (39, '127.0.0.1', 'panggil umam saja', 'Siap, Umam. Ada yang mau kamu tanyakan atau butuh bantuan apa hari ini?', 36, 'gpt-5.2', NULL, 'default', '2026-02-23 21:44:35', '2026-02-23 21:44:35');
INSERT INTO `chat_history` VALUES (40, '127.0.0.1', 'aku lagi galau nih', 'Umam, aku temenin. Galau soal apa—cinta/gebetan, kerja/kuliah, keluarga, atau hal lain?\n\nKalau kamu nyaman, coba ceritain singkat:\n1) Kejadiannya apa?  \n2) Yang paling bikin kepikiran bagian mana?  \n3) Kamu penginnya ujungnya gimana (balikan, move on, ambil keputusan, dll.)?', 94, 'gpt-5.2', NULL, 'default', '2026-02-23 21:44:44', '2026-02-23 21:44:44');
INSERT INTO `chat_history` VALUES (41, '127.0.0.1', 'ambil keputusan', 'Oke, Umam. Biar aku bantu kamu ambil keputusan dengan lebih tenang dan jelas.\n\nJawab dulu ini ya (singkat aja juga boleh):\n1) Keputusan tentang apa? (misal: lanjut/berhenti, pilih A atau B, jujur atau diam, balikan atau selesai, dll.)  \n2) Pilihannya apa saja? (A vs B)  \n3) Deadline-nya kapan?  \n4) Yang paling kamu takutkan kalau salah pilih apa?  \n5) Yang paling kamu butuhkan sekarang: saran logis, dukungan emosional, atau langkah-langkah praktis?\n\nKalau kamu kasih detailnya, aku bisa bantu bikin pro-kontra, nilai risikonya, dan susun langkah keputusan yang paling aman buat kamu.', 180, 'gpt-5.2', NULL, 'default', '2026-02-23 21:45:48', '2026-02-23 21:45:48');
INSERT INTO `chat_history` VALUES (42, '127.0.0.1', 'kerjakan', 'Berikut penyelesaiannya (dari gambar ada soal nomor 1 dan 2).\n\n## 1) Mencocokkan operasi hitung dengan hasilnya\nHitung satu per satu:\n\n1. \\(-15 + 9 \\div 0{,}3 - 10\\)  \n   \\(9 \\div 0{,}3 = 30\\) → \\(-15 + 30 - 10 = 5\\) ⇒ **a**\n\n2. \\(25 + (-20)\\times \\frac{1}{2} - 15\\)  \n   \\((-20)\\times \\frac{1}{2} = -10\\) → \\(25 - 10 - 15 = 0\\) ⇒ **b**\n\n3. \\(15 + (-15)\\div (-3) - 25\\)  \n   \\((-15)\\div(-3)=5\\) → \\(15 + 5 - 25 = -5\\) ⇒ **d**\n\n4. \\(-10 - 15\\times 20\\% + 10\\)  \n   \\(20\\% = 0{,}2\\), jadi \\(15\\times 0{,}2 = 3\\) → \\(-10 - 3 + 10 = -3\\) ⇒ **c**\n\nJadi pasangan: **1–a, 2–b, 3–d, 4–c** → pilihan **B**.\n\n**Jawaban nomor 1: B**\n\n---\n\n## 2) Pupuk untuk kebun jeruk selama 6 bulan\nPanen 6 bulan = 45 kuintal = 4500 kg.  \n1 pohon menghasilkan 30 kg → jumlah pohon:\n\\[\n4500 \\div 30 = 150 \\text{ pohon}\n\\]\nPupuk per pohon per bulan = 200 gram = 0,2 kg.  \nSelama 6 bulan per pohon:\n\\[\n0{,}2 \\times 6 = 1{,}2 \\text{ kg}\n\\]\nTotal pupuk:\n\\[\n150 \\times 1{,}2 = 180 \\text{ kg}\n\\]\n\n**Jawaban nomor 2: 180 kg (C)**', 288, 'gpt-5.2', NULL, 'uas-math', '2026-02-23 21:57:46', '2026-02-23 21:57:46');
INSERT INTO `chat_history` VALUES (43, '127.0.0.1', 'ga jadi ah', 'Gak apa-apa, Umam. Kalau belum siap cerita, kita pelan-pelan aja.\n\nK maunya sekarang:\n1) Ngobrol santai buat ngurangin galau,  \n2) Aku bantu kamu nenangin pikiran (latihan napas/grounding singkat), atau  \n3) Distraksi ringan (cerita lucu, rekomendasi musik/film, tebak-tebakan)?\n\nPilih salah satu, atau bilang aja “temenin aja” juga boleh.', 109, 'gpt-5.2', NULL, 'default', '2026-02-23 22:12:29', '2026-02-23 22:12:29');
INSERT INTO `chat_history` VALUES (44, '127.0.0.1', 'halo', 'Halo! Aku asisten virtual yang dibuat oleh Ahmad Faleh Jamaluddin.  \nAda yang bisa aku bantu hari ini?', 41, 'gpt-5.2', 'openai', 'default', '2026-03-09 12:09:13', '2026-03-09 12:09:13');
INSERT INTO `chat_history` VALUES (45, '127.0.0.1', 'aku samuel, kamu siapa', 'Halo Samuel! Aku asisten virtual yang dibuat oleh Ahmad Faleh Jamaluddin. Senang kenal denganmu 😄  \nAda yang ingin kamu tanyakan atau ngobrol santai juga boleh!', 63, 'gpt-5.4-2026-03-05', 'openai', 'default', '2026-03-09 12:12:59', '2026-03-09 12:12:59');
INSERT INTO `chat_history` VALUES (46, '127.0.0.1', 'tes', 'Halo! Saya siap membantu. Ada yang ingin dites atau ditanyakan?', 29, 'gpt-5.4-2026-03-05', 'openai', 'default', '2026-03-09 12:33:06', '2026-03-09 12:33:06');
INSERT INTO `chat_history` VALUES (47, '127.0.0.1', 'halo', 'Halo! 👋  \nAku siap bantu kamu dengan apa pun—tanya soal belajar, kerjaan, ide, nulis, coding, atau hal sehari-hari juga bisa.\n\nOh ya, aku adalah asisten virtual yang dibuat oleh **Ahmad Faleh Jamaluddin** 😊  \nAda yang ingin kamu tanyakan?', 79, 'gpt-5.4-2026-03-05', 'openai', 'default', '2026-03-09 12:35:13', '2026-03-09 12:35:13');
INSERT INTO `chat_history` VALUES (48, '127.0.0.1', 'kamu bisa buatkan aku soal aljabar 2 saja', 'Tentu. Berikut **2 soal aljabar**:\n\n1. **Sederhanakan bentuk aljabar berikut:**  \n   \\( 3x + 5x - 2x + 7 \\)\n\n2. **Tentukan nilai \\( x \\):**  \n   \\( 2x + 6 = 14 \\)\n\nKalau mau, saya juga bisa buatkan **beserta jawabannya**.', 82, 'gpt-5.4-2026-03-05', 'openai', 'uas', '2026-03-09 12:35:34', '2026-03-09 12:35:34');
INSERT INTO `chat_history` VALUES (49, '127.0.0.1', 'sertakan jawaban', 'Tentu. Saya akan menyertakan jawaban secara singkat, padat, dan jelas. Silakan kirim soal atau pertanyaannya.', 47, 'gpt-5.4-2026-03-05', 'openai', 'uas', '2026-03-09 12:35:58', '2026-03-09 12:35:58');
INSERT INTO `chat_history` VALUES (50, '127.0.0.1', 'kamu bisa buatkan aku soal aljabar 2 saja.\nsertakan jawabannya', 'Tentu. Berikut **2 soal aljabar** beserta **jawabannya**:\n\n### Soal 1\nSederhanakan:\n\\[\n3x + 5x - 2x\n\\]\n\n**Jawaban:**\n\\[\n(3 + 5 - 2)x = 6x\n\\]\n\n---\n\n### Soal 2\nTentukan nilai \\(x\\):\n\\[\n2x + 4 = 10\n\\]\n\n**Jawaban:**\n\\[\n2x = 10 - 4\n\\]\n\\[\n2x = 6\n\\]\n\\[\nx = 3\n\\]\n\nKalau mau, saya juga bisa buatkan **2 soal aljabar yang lebih sulit**.', 120, 'gpt-5.4-2026-03-05', 'openai', 'uas', '2026-03-09 12:36:18', '2026-03-09 12:36:18');
INSERT INTO `chat_history` VALUES (51, '127.0.0.1', 'ini artinya apa\n\nUsage provided by Cursor that does not count\nagainst your usage limits. This indludes promotions\nOr other credits granted to your account.', 'Artinya:\n\n**“Penggunaan di Cursor yang tidak dihitung ke batas penggunaan akunmu. Ini termasuk promosi atau kredit lain yang diberikan ke akunmu.”**\n\nIntinya:\n- **Tidak mengurangi kuota/paket utama kamu**\n- Bisa berasal dari **promo, bonus, atau kredit gratis** dari Cursor\n\nKalau mau, saya juga bisa bantu buatkan **terjemahan yang lebih natural dalam bahasa Indonesia**.', 160, 'gpt-5.4-2026-03-05', 'openai', 'uas', '2026-03-10 19:59:06', '2026-03-10 19:59:06');
INSERT INTO `chat_history` VALUES (52, '127.0.0.1', 'angka paling terkahir itu detik , maka itu brpa lama', 'Jika formatnya **jam:menit:detik:detik kecil** dan **angka paling adalah detik**, maka:\n\n**:18:4634** dibaca:\n- 61\n- 18 menit\n- 46 detik\n- 34 detik\n\nKalau dijumlahkan:\n- 46 detik + 34 detik = **80 detik** = **1 menit 20 detik**\n\nJadi totalnya:\n- **61 jam 19 menit 20 detik**\n\nKalau mau, saya juga bisa bantu ubah ke **hari, jam, menit, detik**.', 123, 'gpt-5.4-2026-03-05', 'openai', 'uas-math', '2026-03-12 14:06:37', '2026-03-12 14:06:37');
INSERT INTO `chat_history` VALUES (53, '127.0.0.1', 'halo', 'Halo! 👋  \nAku siap bantu kamu dengan apa—tanya jawab, nulis, cari, belajar, coding, dan lainnya.\n\nOh iya, aku adalah asisten virtual yang dibuat oleh **Ahmad Fh Jamaluddin** 😊\n\nAda yang bisa aku?', 67, 'gpt-5.4-2026-03-05', 'openai', 'default', '2026-05-30 09:44:51', '2026-05-30 09:44:51');
INSERT INTO `chat_history` VALUES (54, '127.0.0.1', 'halo', 'Halo! 😊 Ada yang bisa saya bantu hari ini?', 24, 'gpt-5.5-pro-2026-04-23', 'openai', 'default', '2026-05-30 09:53:33', '2026-05-30 09:53:33');
INSERT INTO `chat_history` VALUES (55, '127.0.0.1', 'Gambar soal matematika', 'Berikutisis singkat dan jawabannya:\n\n. **Permasalahan cerita** disebut **konflik → **c**  \n. **Kal yang memerlukan objek** adalah **kalimat intransitif** →d**  \n3. Contoh **kal intransitif**: **ikan berenang** → **a**  \n4. Predikat kalimat “iti mengembalikan buku milik Wayan pada Senin yang” **embalikan** →b  \n5. Contohkalimat transit** **Ayah memotong rumput** **b**  \n6. Ob pada kalimat “I menggoreng ikan di dapur” adalah **** → **c**  \n7. **Kalimat yang memerlukan objek** adalahkalimat transit** → **c**\n\n### Jaw akhir:\n1. **c**  \n2. ****  \n3. **a**  \n4. **b**  \n5. ****6. ****  \n7. **c**', 181, 'gpt-5.4-2026-03-05', 'openai', 'uas-math', '2026-06-09 11:01:58', '2026-06-09 11:01:58');
INSERT INTO `chat_history` VALUES (56, '127.0.0.1', 'Gambar soal matematika', ' soal: Pil ganda Bahasa Indonesia/pengetahuan umum.\n\n8. Gambar hewan **unta**.  \n**Jaw: b. unta**\n\n9. Lingkungan yang penuh bunga biasanya ada di **taman**.**Jawaban a. taman**\n\n10. Pesan agar lingkungan bersih adalahuang sampah pada tempatnya.**Jawaban: c. buanglah sampah pada tempatnya**\n\n11. Yang bukan nama bunga adalah **bulan**.  \n**Jawaban: c. bulan**\n\n12. Agar tanaman subur diberi **pupuk**.  \n**Jawaban a pupuk**\n\n13. Kata tanya yang tepat: “**Apa** yangebarkan penyakit malaria?”  \n**Jawaban: b. apa**\n\n14. Hewan penghisap darah manusia adalah **nyamuk**  \n**Jawaban: b. nyamuk**\n\n15. Bin yang dibenci petani saat padi menguning adalahtikus**.  \n**Jawaban:. tikus**', 205, 'gpt-5.5-2026-04-23', 'openai', 'uas-math', '2026-06-09 11:16:45', '2026-06-09 11:16:45');
INSERT INTO `chat_history` VALUES (57, '127.0.0.1', 'berikan aku kode sederhana python', 'ikut contoh kode sederhana Python:\n\n```python\n = input(\"Masukkan nama kamu: \")\numur = int(input(\"Mas umur kamu: \"))\n\nprint(\",\", nama)\nprint(\"Umur adalah\", umur \"tahun\")\n```\n\nKode ini meminta nama umur, laluampilkannya kembali.', 84, 'gpt-5.5-2026-04-23', 'openai', 'uas', '2026-06-09 11:24:08', '2026-06-09 11:24:08');
INSERT INTO `chat_history` VALUES (58, '127.0.0.1', 'Gambar soal matematika', '**Jenis soal:** Matematika — Penjumlahan & Pengurangan Bentuk Aljabar.  \n**Cara:** Gabungkan suku sejenis; jika dikurangkan ubah tanda pada su di dalam kurung kedua.\n\n1) \\((13 - 8b) + (a - 3b)  \n\\(= a + a - 8b - 3b\\  \n\\(= 25a - 11\\)\n\n2) \\((3 + 4y - 5z) + (x - 2y)\\)  \n\\(= 3x 3x + 4y - 2y - 5z)  \n\\( 6x + 2 - 5z\\3) \\((2pq -16rs) + (5pq - 5)\\)  \n\\(=2pq + 5pq - 16rs - 5\\)  \n\\(= pq - 16rs - \\)\n\n4) \\((5x +3) - (x - 1)\\  \n\\(= 5x + 3 x + 1\\)  \n\\(= 4x + 4\\)\n\n5) \\((2y + 15z - (4y - 8)  \n\\(= y + 15z - 4y + 8\\)  \n\\(=2y + 15z + 8\\)\n\n6) \\((42n + 35m ) - (-50m - 20n + 9)\\  \n\\(= 42 + 35m +7 + 50m + 20n \\)  \n=62n + 85m - 2\\)\n\n**Jawaban:**  \n1) \\(a - 11b)2) \\(x 2y 5z)  \n3) \\(7pq - 16rs \\)  \n4) \\(4x +4\\)  \n5) \\(-2y +15z + 8\\)  \n6) \\(n m - 2\\)', 215, 'gpt-5.5-2026-04-23', 'openai', 'uas-math', '2026-06-09 11:26:02', '2026-06-09 11:26:02');
INSERT INTO `chat_history` VALUES (59, '127.0.0.1', 'Gambar soal matematika', 'Jenis soal: **Matematika — integral tentuDiketahui\\[\n\\int_^1 (4x - x^{2/3})\\,dx\n\\]\n\nGunakan aturan pangkat:\n\n[\n\\int x^n dx \\frac{x^{n+1}}{+1}\n\\]\n\nMaka\\[\n\\int (x - 6x^{2/3})\\,dx\n= \\{4x^2}{2} - 6cdot \\frac{x^{5/3}}5/3\\]\n\n\\[\n= 2x^2 -frac{18}{5}x^{5/3}\n\\]\n\nSubstitusi batas \\(\\) sampai \\(1\\):\n\n\\[\n\\left[2x^ - \\frac{18}{5}x^{5/3}\\right]_0^1\n\\]\n\n\\[\n= \\left(2(1)^ - \\frac{18}{5}(1)^{5/3}\\right)\n-\n\\left(2(0)^2 - \\frac{18}{5}(0)^{5/3right\\]\n\n\\= 2 - \\frac{18}{}\n]\n\n\\[\n= \\frac{10}{5} \\frac{18}{5}\n= -\\frac{8}{5}\n\\]\n\n**Jaw: \\(-\\frac{8}{5}\\)**\n\nCatatan: Jika yang diminta adalah **luas daerah**, maka nilainya menjadi \\(\\frac{8}{5}\\ karena luas tidakilai negatif.', 200, 'gpt-5.5-2026-04-23', 'openai', 'uas-math', '2026-06-09 11:47:55', '2026-06-09 11:47:55');

-- ----------------------------
-- Table structure for mode_bindings
-- ----------------------------
DROP TABLE IF EXISTS `mode_bindings`;
CREATE TABLE `mode_bindings`  (
  `mode_key` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` int(11) NOT NULL,
  `system_prompt` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `history_strategy` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recent_window',
  `history_limit` int(11) NOT NULL DEFAULT 7,
  `accepts_image` tinyint(1) NOT NULL DEFAULT 0,
  `ocr_strategy` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'client_extract_text',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`mode_key`) USING BTREE,
  INDEX `fk_mode_bindings_model`(`model_id`) USING BTREE,
  CONSTRAINT `fk_mode_bindings_model` FOREIGN KEY (`model_id`) REFERENCES `ai_models` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of mode_bindings
-- ----------------------------
INSERT INTO `mode_bindings` VALUES ('default', 2, 'Kamu adalah asisten virtual yang ceria, informatif, dan ramah. Kamu dibuat oleh developer bernama Ahmad Faleh Jamaluddin.', 'recent_window', 7, 0, 'client_extract_text', '2026-05-30 09:47:49');
INSERT INTO `mode_bindings` VALUES ('uas', 3, 'Anda adalah asisten AI yang membantu mahasiswa menjawab soal. Berikan jawaban singkat, dan relevan. Sebelum menjawab, pikirkan dulu kemungkinan jawaban secara runtut, lalu simpulkan jawaban akhir secara singkat padat dan jelas.', 'none', 0, 0, 'client_extract_text', '2026-06-09 11:08:12');
INSERT INTO `mode_bindings` VALUES ('uas-math', 3, 'Anda adalah asisten akademik AI yang ahli dalam menjawab soal ujian dari berbagai mata pelajaran — matematika, fisika, kimia, biologi, ekonomi, bahasa, sejarah, dan lainnya.\r\n\r\nTUGAS UTAMA:\r\n- Jika ada gambar → baca dan pahami soal dari gambar tersebut\r\n- Jika hanya teks → jawab langsung sesuai konteks mata pelajaran\r\n\r\nCARA MENJAWAB:\r\n1. Identifikasi jenis soal dan mata pelajaran secara singkat\r\n2. Tulis langkah penyelesaian secara ringkas dan terstruktur\r\n3. Berikan JAWABAN AKHIR yang jelas dan tegas — tandai dengan \"**Jawaban: ...**\"\r\n\r\nATURAN:\r\n- Utamakan akurasi dan ketepatan jawaban\r\n- Jangan bertele-tele — langsung ke inti\r\n- Untuk soal pilihan ganda: sebutkan opsi yang benar beserta alasan singkatnya\r\n- Untuk soal uraian: berikan jawaban lengkap namun padat\r\n- Untuk matematika/sains: tampilkan rumus dan langkah perhitungan yang relevan saja\r\n- Gunakan bahasa yang sama dengan soal (Indonesia atau Inggris)', 'none', 0, 1, 'vision_direct', '2026-06-09 11:16:05');

-- ----------------------------
-- Table structure for settings_audit_log
-- ----------------------------
DROP TABLE IF EXISTS `settings_audit_log`;
CREATE TABLE `settings_audit_log`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_user_id` int(11) NULL DEFAULT NULL,
  `entity_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `before_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `after_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_settings_audit_admin`(`admin_user_id`) USING BTREE,
  CONSTRAINT `fk_settings_audit_admin` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 12 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of settings_audit_log
-- ----------------------------
INSERT INTO `settings_audit_log` VALUES (1, 1, 'admin_users', '1', 'create_initial_admin', NULL, '{\"username\":\"admin\",\"display_name\":\"admin\"}', '2026-03-09 12:10:48');
INSERT INTO `settings_audit_log` VALUES (2, 1, 'ai_models', '1', 'update', '{\"id\":1,\"provider_id\":1,\"model_key\":\"gpt-5.2\",\"api_model\":\"gpt-5.2\",\"label\":\"GPT-5.2\",\"temperature\":\"0.30\",\"max_tokens\":4096,\"use_max_completion_tokens\":1,\"supports_vision\":1,\"is_active\":1,\"created_at\":\"2026-03-09 11:59:12\",\"updated_at\":\"2026-03-09 11:59:12\",\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '{\"id\":1,\"provider_id\":1,\"model_key\":\"gpt-5.4-2026-03-05\",\"api_model\":\"gpt-5.4-2026-03-05\",\"label\":\"GPT-5.4\",\"temperature\":\"0.30\",\"max_tokens\":4096,\"use_max_completion_tokens\":1,\"supports_vision\":1,\"is_active\":1,\"created_at\":\"2026-03-09 11:59:12\",\"updated_at\":\"2026-03-09 12:12:08\",\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '2026-03-09 12:12:08');
INSERT INTO `settings_audit_log` VALUES (3, 1, 'ai_models', '2', 'create', NULL, '{\"id\":2,\"provider_id\":1,\"model_key\":\"gpt-5.5-pro-2026-04-23\",\"api_model\":\"gpt-5.5-pro-2026-04-23\",\"label\":\"gpt-5.5-pro-2026-04-23\",\"temperature\":\"0.30\",\"max_tokens\":4096,\"use_max_completion_tokens\":1,\"supports_vision\":0,\"is_active\":1,\"created_at\":\"2026-05-30 09:37:01\",\"updated_at\":\"2026-05-30 09:37:01\",\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '2026-05-30 09:37:01');
INSERT INTO `settings_audit_log` VALUES (4, 1, 'ai_models', '2', 'update', '{\"id\":2,\"provider_id\":1,\"model_key\":\"gpt-5.5-pro-2026-04-23\",\"api_model\":\"gpt-5.5-pro-2026-04-23\",\"label\":\"gpt-5.5-pro-2026-04-23\",\"temperature\":\"0.30\",\"max_tokens\":4096,\"use_max_completion_tokens\":1,\"supports_vision\":0,\"is_active\":1,\"created_at\":\"2026-05-30 09:37:01\",\"updated_at\":\"2026-05-30 09:37:01\",\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '{\"id\":2,\"provider_id\":1,\"model_key\":\"gpt-5.5-pro-2026-04-23\",\"api_model\":\"gpt-5.5-pro-2026-04-23\",\"label\":\"GPT-5.5 pro\",\"temperature\":\"0.30\",\"max_tokens\":4096,\"use_max_completion_tokens\":1,\"supports_vision\":0,\"is_active\":1,\"created_at\":\"2026-05-30 09:37:01\",\"updated_at\":\"2026-05-30 09:38:23\",\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '2026-05-30 09:38:23');
INSERT INTO `settings_audit_log` VALUES (5, 1, 'ai_models', '3', 'create', NULL, '{\"id\":3,\"provider_id\":1,\"model_key\":\"gpt-5.5-2026-04-23\",\"api_model\":\"gpt-5.5-2026-04-23\",\"label\":\"GPT-5.5\",\"temperature\":\"0.30\",\"max_tokens\":4096,\"use_max_completion_tokens\":1,\"supports_vision\":0,\"is_active\":1,\"created_at\":\"2026-05-30 09:39:50\",\"updated_at\":\"2026-05-30 09:39:50\",\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '2026-05-30 09:39:50');
INSERT INTO `settings_audit_log` VALUES (6, 1, 'mode_bindings', 'default', 'update', '{\"mode_key\":\"default\",\"model_id\":1,\"system_prompt\":\"Kamu adalah asisten virtual yang ceria, informatif, dan ramah. Kamu dibuat oleh developer bernama Ahmad Faleh Jamaluddin.\",\"history_strategy\":\"recent_window\",\"history_limit\":7,\"accepts_image\":0,\"ocr_strategy\":\"client_extract_text\",\"updated_at\":\"2026-03-09 11:59:12\",\"model_key\":\"gpt-5.4-2026-03-05\",\"model_label\":\"GPT-5.4\",\"supports_vision\":1,\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '{\"mode_key\":\"default\",\"system_prompt\":\"Kamu adalah asisten virtual yang ceria, informatif, dan ramah. Kamu dibuat oleh developer bernama Ahmad Faleh Jamaluddin.\",\"history_strategy\":\"recent_window\",\"history_limit\":7,\"accepts_image\":0,\"ocr_strategy\":\"client_extract_text\",\"model_id\":2,\"model_key\":\"gpt-5.5-pro-2026-04-23\",\"api_model\":\"gpt-5.5-pro-2026-04-23\",\"model_label\":\"GPT-5.5 pro\",\"temperature\":\"0.30\",\"max_tokens\":4096,\"use_max_completion_tokens\":1,\"supports_vision\":0,\"provider_id\":1,\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\",\"driver\":\"openai_compatible\",\"base_url\":\"https://api.openai.com/v1\",\"api_key_env_var\":\"OPENAI_API_KEY\"}', '2026-05-30 09:47:49');
INSERT INTO `settings_audit_log` VALUES (7, 1, 'mode_bindings', 'uas', 'update', '{\"mode_key\":\"uas\",\"model_id\":1,\"system_prompt\":\"Anda adalah asisten AI yang membantu mahasiswa menjawab soal. Berikan jawaban singkat, dan relevan. Sebelum menjawab, pikirkan dulu kemungkinan jawaban secara runtut, lalu simpulkan jawaban akhir secara singkat padat dan jelas.\",\"history_strategy\":\"none\",\"history_limit\":0,\"accepts_image\":0,\"ocr_strategy\":\"client_extract_text\",\"updated_at\":\"2026-03-09 11:59:12\",\"model_key\":\"gpt-5.4-2026-03-05\",\"model_label\":\"GPT-5.4\",\"supports_vision\":1,\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '{\"mode_key\":\"uas\",\"system_prompt\":\"Anda adalah asisten AI yang membantu mahasiswa menjawab soal. Berikan jawaban singkat, dan relevan. Sebelum menjawab, pikirkan dulu kemungkinan jawaban secara runtut, lalu simpulkan jawaban akhir secara singkat padat dan jelas.\",\"history_strategy\":\"none\",\"history_limit\":0,\"accepts_image\":0,\"ocr_strategy\":\"client_extract_text\",\"model_id\":3,\"model_key\":\"gpt-5.5-2026-04-23\",\"api_model\":\"gpt-5.5-2026-04-23\",\"model_label\":\"GPT-5.5\",\"temperature\":\"0.30\",\"max_tokens\":16384,\"use_max_completion_tokens\":1,\"supports_vision\":0,\"provider_id\":1,\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\",\"driver\":\"openai_compatible\",\"base_url\":\"https://api.openai.com/v1\",\"api_key_env_var\":\"OPENAI_API_KEY\"}', '2026-06-09 11:08:12');
INSERT INTO `settings_audit_log` VALUES (8, 1, 'ai_models', '3', 'update', '{\"id\":3,\"provider_id\":1,\"model_key\":\"gpt-5.5-2026-04-23\",\"api_model\":\"gpt-5.5-2026-04-23\",\"label\":\"GPT-5.5\",\"temperature\":\"0.30\",\"max_tokens\":16384,\"use_max_completion_tokens\":1,\"supports_vision\":1,\"is_active\":1,\"created_at\":\"2026-05-30 09:39:50\",\"updated_at\":\"2026-06-09 11:12:06\",\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '{\"id\":3,\"provider_id\":1,\"model_key\":\"gpt-5.5-2026-04-23\",\"api_model\":\"gpt-5.5-2026-04-23\",\"label\":\"GPT-5.5\",\"temperature\":\"0.30\",\"max_tokens\":16384,\"use_max_completion_tokens\":1,\"supports_vision\":1,\"is_active\":1,\"created_at\":\"2026-05-30 09:39:50\",\"updated_at\":\"2026-06-09 11:12:06\",\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '2026-06-09 11:13:37');
INSERT INTO `settings_audit_log` VALUES (9, 1, 'ai_models', '2', 'update', '{\"id\":2,\"provider_id\":1,\"model_key\":\"gpt-5.5-pro-2026-04-23\",\"api_model\":\"gpt-5.5-pro-2026-04-23\",\"label\":\"GPT-5.5 pro\",\"temperature\":\"0.30\",\"max_tokens\":16384,\"use_max_completion_tokens\":1,\"supports_vision\":1,\"is_active\":1,\"created_at\":\"2026-05-30 09:37:01\",\"updated_at\":\"2026-06-09 11:12:06\",\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '{\"id\":2,\"provider_id\":1,\"model_key\":\"gpt-5.5-pro-2026-04-23\",\"api_model\":\"gpt-5.5-pro-2026-04-23\",\"label\":\"GPT-5.5 pro\",\"temperature\":\"0.30\",\"max_tokens\":16384,\"use_max_completion_tokens\":1,\"supports_vision\":1,\"is_active\":1,\"created_at\":\"2026-05-30 09:37:01\",\"updated_at\":\"2026-06-09 11:12:06\",\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '2026-06-09 11:13:41');
INSERT INTO `settings_audit_log` VALUES (10, 1, 'mode_bindings', 'uas-math', 'update', '{\"mode_key\":\"uas-math\",\"model_id\":1,\"system_prompt\":\"Anda adalah asisten AI yang dapat membantu menyelesaikan soal dari berbagai mata pelajaran, dengan keahlian utama dalam matematika dan pemecahan soal. Tugas Anda meliputi:\\r\\n1. Jika terdapat gambar: Analisis isi gambar untuk mengidentifikasi dan memahami soal yang diberikan.\\r\\n2. Jika hanya teks: Jawab pertanyaan secara langsung sesuai konteks mata pelajaran.\\r\\n3. Identifikasi jenis soal — terutama untuk matematika (misalnya aljabar, kalkulus, geometri, dll.), namun juga relevan untuk bidang lain seperti fisika, kimia, atau bahasa.\\r\\n4. Berikan jawaban akhir yang akurat dan dapat dipertanggungjawabkan.\\r\\n5. Jelaskan secara singkat, lalu langsung berikan jawaban akhir secara to the point.\",\"history_strategy\":\"none\",\"history_limit\":0,\"accepts_image\":1,\"ocr_strategy\":\"vision_direct\",\"updated_at\":\"2026-03-09 11:59:12\",\"model_key\":\"gpt-5.4-2026-03-05\",\"model_label\":\"GPT-5.4\",\"supports_vision\":1,\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '{\"mode_key\":\"uas-math\",\"system_prompt\":\"Anda adalah asisten AI yang dapat membantu menyelesaikan soal dari berbagai mata pelajaran, dengan keahlian utama dalam matematika dan pemecahan soal. Tugas Anda meliputi:\\r\\n1. Jika terdapat gambar: Analisis isi gambar untuk mengidentifikasi dan memahami soal yang diberikan.\\r\\n2. Jika hanya teks: Jawab pertanyaan secara langsung sesuai konteks mata pelajaran.\\r\\n3. Identifikasi jenis soal — terutama untuk matematika (misalnya aljabar, kalkulus, geometri, dll.), namun juga relevan untuk bidang lain seperti fisika, kimia, atau bahasa.\\r\\n4. Berikan jawaban akhir yang akurat dan dapat dipertanggungjawabkan.\\r\\n5. Jelaskan secara singkat, lalu langsung berikan jawaban akhir secara to the point.\",\"history_strategy\":\"none\",\"history_limit\":0,\"accepts_image\":1,\"ocr_strategy\":\"vision_direct\",\"model_id\":3,\"model_key\":\"gpt-5.5-2026-04-23\",\"api_model\":\"gpt-5.5-2026-04-23\",\"model_label\":\"GPT-5.5\",\"temperature\":\"0.30\",\"max_tokens\":16384,\"use_max_completion_tokens\":1,\"supports_vision\":1,\"provider_id\":1,\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\",\"driver\":\"openai_compatible\",\"base_url\":\"https://api.openai.com/v1\",\"api_key_env_var\":\"OPENAI_API_KEY\"}', '2026-06-09 11:14:00');
INSERT INTO `settings_audit_log` VALUES (11, 1, 'mode_bindings', 'uas-math', 'update', '{\"mode_key\":\"uas-math\",\"model_id\":3,\"system_prompt\":\"Anda adalah asisten AI yang dapat membantu menyelesaikan soal dari berbagai mata pelajaran, dengan keahlian utama dalam matematika dan pemecahan soal. Tugas Anda meliputi:\\r\\n1. Jika terdapat gambar: Analisis isi gambar untuk mengidentifikasi dan memahami soal yang diberikan.\\r\\n2. Jika hanya teks: Jawab pertanyaan secara langsung sesuai konteks mata pelajaran.\\r\\n3. Identifikasi jenis soal — terutama untuk matematika (misalnya aljabar, kalkulus, geometri, dll.), namun juga relevan untuk bidang lain seperti fisika, kimia, atau bahasa.\\r\\n4. Berikan jawaban akhir yang akurat dan dapat dipertanggungjawabkan.\\r\\n5. Jelaskan secara singkat, lalu langsung berikan jawaban akhir secara to the point.\",\"history_strategy\":\"none\",\"history_limit\":0,\"accepts_image\":1,\"ocr_strategy\":\"vision_direct\",\"updated_at\":\"2026-06-09 11:14:00\",\"model_key\":\"gpt-5.5-2026-04-23\",\"model_label\":\"GPT-5.5\",\"supports_vision\":1,\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\"}', '{\"mode_key\":\"uas-math\",\"system_prompt\":\"Anda adalah asisten akademik AI yang ahli dalam menjawab soal ujian dari berbagai mata pelajaran — matematika, fisika, kimia, biologi, ekonomi, bahasa, sejarah, dan lainnya.\\r\\n\\r\\nTUGAS UTAMA:\\r\\n- Jika ada gambar → baca dan pahami soal dari gambar tersebut\\r\\n- Jika hanya teks → jawab langsung sesuai konteks mata pelajaran\\r\\n\\r\\nCARA MENJAWAB:\\r\\n1. Identifikasi jenis soal dan mata pelajaran secara singkat\\r\\n2. Tulis langkah penyelesaian secara ringkas dan terstruktur\\r\\n3. Berikan JAWABAN AKHIR yang jelas dan tegas — tandai dengan \\\"**Jawaban: ...**\\\"\\r\\n\\r\\nATURAN:\\r\\n- Utamakan akurasi dan ketepatan jawaban\\r\\n- Jangan bertele-tele — langsung ke inti\\r\\n- Untuk soal pilihan ganda: sebutkan opsi yang benar beserta alasan singkatnya\\r\\n- Untuk soal uraian: berikan jawaban lengkap namun padat\\r\\n- Untuk matematika/sains: tampilkan rumus dan langkah perhitungan yang relevan saja\\r\\n- Gunakan bahasa yang sama dengan soal (Indonesia atau Inggris)\",\"history_strategy\":\"none\",\"history_limit\":0,\"accepts_image\":1,\"ocr_strategy\":\"vision_direct\",\"model_id\":3,\"model_key\":\"gpt-5.5-2026-04-23\",\"api_model\":\"gpt-5.5-2026-04-23\",\"model_label\":\"GPT-5.5\",\"temperature\":\"0.30\",\"max_tokens\":16384,\"use_max_completion_tokens\":1,\"supports_vision\":1,\"provider_id\":1,\"provider_key\":\"openai\",\"provider_label\":\"OpenAI\",\"driver\":\"openai_compatible\",\"base_url\":\"https://api.openai.com/v1\",\"api_key_env_var\":\"OPENAI_API_KEY\"}', '2026-06-09 11:16:05');

SET FOREIGN_KEY_CHECKS = 1;
