# OpenAI-only (hapus 9router) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aplikasi chat memanggil OpenAI langsung (`OPENAI_API_KEY`), semua jejak 9router hilang, dan admin bisa memilih model serta reasoning effort per mode.

**Architecture:** Struktur tabel tetap (`ai_providers`, `ai_models`, `mode_bindings`). Migrasi `007` menambah kolom `reasoning_effort`, dan migrasi `008` mengonversi data ke provider `openai` dengan model `gpt-5.2` / `gpt-5-mini`. Base URL dan nama env OpenAI ditanam di `ModeConfigResolver`. Kode 9router (catalog sync, vision fallback Gemini, cabang `providerKey === '9router'`, halaman Provider) dihapus.

**Tech Stack:** PHP 7.4+ (lokal PHP 8.3, Laragon), MySQL 8.4, jQuery, tanpa Composer dan tanpa test suite. Verifikasi memakai script CLI sekali pakai `storage/check_openai_only.php`, `php -l`, grep, dan curl.

**Spec:** `docs/superpowers/specs/2026-11-17-openai-only-design.md`. Baca spec ini dulu.

## Global Constraints

- Base URL OpenAI: `https://api.openai.com/v1`. Env key: `OPENAI_API_KEY`. Tidak ada env AI lain.
- Provider di DB: `provider_key = 'openai'`, `label = 'OpenAI'`, `driver = 'openai_compatible'`.
- Model seed: `gpt-5.2` (label `GPT-5.2`) dan `gpt-5-mini` (label `GPT-5 mini`). Keduanya: `temperature 0.30`, `max_tokens 16384`, `use_max_completion_tokens 1`, `supports_vision 1`, `is_active 1`.
- Binding awal: `default` → `gpt-5.2` / effort `low`; `uas` → `gpt-5-mini` / effort `high`; `uas-math` → `gpt-5.2` / effort `high`.
- Nilai effort yang valid: `NULL` (bawaan model), `none`, `minimal`, `low`, `medium`, `high`, `xhigh`. String kosong disimpan sebagai `NULL`.
- Effort hanya dikirim untuk model reasoning (`isReasoningModel()` yang sudah ada), dan tidak bergantung pada `use_max_completion_tokens`.
- **Jangan pernah** mencetak, menulis, atau meng-commit isi `.env`, terutama API key. `.env` hanya boleh di-*source* oleh shell untuk backup DB.
- Jangan ubah prompt sistem, history strategy, atau data `chat_history`.
- Teks UI admin dalam bahasa Indonesia.
- File di `storage/` hasil plan ini (`check_openai_only.php`, `prompt-snapshot.json`, `backup-before-openai.sql`) **tidak boleh di-commit**.

## Review Focus

1. DB lama yang punya ratusan model hasil sync 9router dan prompt yang sudah diedit admin → setelah migrasi prompt tetap sama persis. Dicek oleh D7 (snapshot) di Task 3.
2. Migrasi `008` gagal di tengah lalu dijalankan ulang → tidak error dan hasil akhirnya sama. Dicek oleh grup F (`--fresh`, menjalankan 008 dua kali) di Task 3.
3. `OPENAI_API_KEY` kosong atau masih placeholder → error publik "API key provider tidak dikonfigurasi.", bukan fatal. Dicek oleh K1.
4. Gambar dikirim ke OCR High → diteruskan ke model yang di-bind tanpa diganti diam-diam. Dicek oleh R2, ditambah uji live di Task 7.
5. Admin mengirim effort tidak valid (misalnya `turbo`) → ditolak; nilai kosong → `NULL`. Dicek oleh E1 dan E2 di Task 4.

---

### Task 0: Persiapan

**Files:** tidak ada perubahan kode.

- [ ] **Step 1: Pastikan working tree bersih**

Run: `git status --porcelain`
Expected: hanya berisi `docs/superpowers/` (spec dan plan ini). Kalau masih ada `css/style.css`, `index.php`, `js/*.js`, atau file lain yang berubah, **BERHENTI** dan minta pemilik meng-commit atau stash pekerjaannya dulu. Jangan commit file itu sendiri.

- [ ] **Step 2: Buat branch**

```bash
git checkout -b feat/openai-only
```

- [ ] **Step 3: Backup database lokal** (tanpa mencetak isi `.env`)

```bash
cd /c/laragon/www/faleh-ai && set -a && . <(tr -d '\r' < .env) && set +a && /c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" ${DB_PASSWORD:+-p"$DB_PASSWORD"} "$DB_NAME" > storage/backup-before-openai.sql && wc -c storage/backup-before-openai.sql
```
Expected: ukuran file > 0. Untuk restore jika perlu: `mysql ... "$DB_NAME" < storage/backup-before-openai.sql`.

- [ ] **Step 4: Commit dokumen**

```bash
git add docs/superpowers/specs/2026-11-17-openai-only-design.md docs/superpowers/plans/2026-11-17-openai-only.md
git commit -m "docs: add OpenAI-only design and implementation plan"
```

---

### Task 1: Script verifikasi + snapshot prompt

**Files:**
- Create: `storage/check_openai_only.php` (JANGAN di-commit; dihapus di Task 7)

**Interfaces:**
- Produces: `php storage/check_openai_only.php [--snapshot] [--fresh]`. Setiap cek mencetak `PASS <id> ...` atau `FAIL <id> ...`, baris terakhir `ALL PASS` atau `N FAILED`, dan exit code 0 atau 1. Opsional: env `CHECK_DB_USER` / `CHECK_DB_PASS` untuk user MySQL yang boleh `CREATE DATABASE` (grup F).

- [ ] **Step 1: Tulis script ini persis seperti di bawah**

```php
<?php
// Throwaway verification for the OpenAI-only refactor. DO NOT COMMIT. Deleted in Task 7.
// Usage: php storage/check_openai_only.php [--snapshot] [--fresh]

use App\Core\DatabaseManager;
use App\Core\Env;
use App\Core\PublicException;
use App\Core\SqlMigrationRunner;
use App\Modules\Admin\Infrastructure\AIConfigRepository;
use App\Modules\AI\Infrastructure\OpenAICompatibleProvider;
use App\Modules\Chat\Application\ModeConfigResolver;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require $root . '/app/Boot/bootstrap.php';

$snapshotFile = $root . '/storage/prompt-snapshot.json';
$failures = 0;

function check($label, $ok, $detail = '')
{
    global $failures;
    echo ($ok ? 'PASS ' : 'FAIL ') . $label . (($ok || $detail === '') ? '' : ' -- ' . $detail) . PHP_EOL;
    if (!$ok) {
        $failures++;
    }
}

function group($name, callable $fn)
{
    echo PHP_EOL . '== ' . $name . ' ==' . PHP_EOL;
    try {
        $fn();
    } catch (Throwable $t) {
        check($name . ' threw', false, get_class($t) . ': ' . $t->getMessage());
    }
}

function promptMap(PDO $pdo)
{
    $rows = $pdo->query('SELECT mode_key, system_prompt FROM mode_bindings ORDER BY mode_key')->fetchAll(PDO::FETCH_KEY_PAIR);
    return array_map('md5', $rows);
}

function checkDbState(PDO $pdo, $tag)
{
    $providers = $pdo->query('SELECT provider_key FROM ai_providers ORDER BY provider_key')->fetchAll(PDO::FETCH_COLUMN);
    check("[$tag] D1 only provider is openai", $providers === ['openai'], json_encode($providers));

    $p = $pdo->query("SELECT base_url, api_key_env_var FROM ai_providers WHERE provider_key = 'openai'")->fetch(PDO::FETCH_ASSOC);
    check("[$tag] D2 openai base_url/env", $p && $p['base_url'] === 'https://api.openai.com/v1' && $p['api_key_env_var'] === 'OPENAI_API_KEY', json_encode($p));

    $models = $pdo->query('SELECT model_key, use_max_completion_tokens, supports_vision, max_tokens FROM ai_models ORDER BY model_key')->fetchAll(PDO::FETCH_ASSOC);
    check("[$tag] D3 models are gpt-5-mini + gpt-5.2", array_column($models, 'model_key') === ['gpt-5-mini', 'gpt-5.2'], json_encode(array_column($models, 'model_key')));
    foreach ($models as $m) {
        check(
            "[$tag] D4 {$m['model_key']} max_completion/vision/16384",
            (int) $m['use_max_completion_tokens'] === 1 && (int) $m['supports_vision'] === 1 && (int) $m['max_tokens'] === 16384,
            json_encode($m)
        );
    }

    $rows = $pdo->query(
        'SELECT mb.mode_key, m.model_key, mb.reasoning_effort, mb.accepts_image, mb.ocr_strategy
         FROM mode_bindings mb JOIN ai_models m ON m.id = mb.model_id'
    )->fetchAll(PDO::FETCH_ASSOC);
    $byMode = array_column($rows, null, 'mode_key');
    $expected = ['default' => ['gpt-5.2', 'low'], 'uas' => ['gpt-5-mini', 'high'], 'uas-math' => ['gpt-5.2', 'high']];
    foreach ($expected as $mode => [$model, $effort]) {
        $r = $byMode[$mode] ?? null;
        check("[$tag] D5 $mode -> $model / $effort", $r && $r['model_key'] === $model && $r['reasoning_effort'] === $effort, json_encode($r));
    }
    $math = $byMode['uas-math'] ?? [];
    check("[$tag] D6 uas-math accepts image + vision_direct", (int) ($math['accepts_image'] ?? 0) === 1 && ($math['ocr_strategy'] ?? '') === 'vision_direct', json_encode($math));
}

$pdo = DatabaseManager::connection();

if (in_array('--snapshot', $argv, true)) {
    file_put_contents($snapshotFile, json_encode(promptMap($pdo), JSON_PRETTY_PRINT));
    echo 'Snapshot written: ' . $snapshotFile . PHP_EOL;
    exit(0);
}

group('R: 9router runtime removed', function () use ($root) {
    check('R1 NineRouterCatalogSync.php deleted', !file_exists($root . '/app/Modules/AI/Application/NineRouterCatalogSync.php'));
    check('R2 StreamChatService has no vision swap', !method_exists('App\Modules\Chat\Application\StreamChatService', 'visionModelForImage'));
    $cfg = (new ModeConfigResolver())->resolve('default');
    check('R3 base url hardcoded', $cfg['providerBaseUrl'] === 'https://api.openai.com/v1', $cfg['providerBaseUrl']);
    check('R4 api key env hardcoded', $cfg['providerApiKeyEnvVar'] === 'OPENAI_API_KEY', $cfg['providerApiKeyEnvVar']);
});

group('D: database (local)', function () use ($pdo, $snapshotFile) {
    checkDbState($pdo, 'local');
    if (is_file($snapshotFile)) {
        $before = json_decode(file_get_contents($snapshotFile), true);
        check('D7 system prompts unchanged vs snapshot', $before === promptMap($pdo));
    } else {
        check('D7 snapshot file exists', false, 'run with --snapshot first (Task 1)');
    }
    $cfg = (new ModeConfigResolver())->resolve('default');
    check('D8 resolver providerKey openai', $cfg['providerKey'] === 'openai', $cfg['providerKey']);
});

if (in_array('--fresh', $argv, true)) {
    group('F: fresh install + 008 rerun', function () use ($root) {
        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $user = getenv('CHECK_DB_USER') ?: Env::get('DB_USERNAME', 'root');
        $pass = getenv('CHECK_DB_USER') ? (string) getenv('CHECK_DB_PASS') : Env::get('DB_PASSWORD', '');
        $fresh = Env::get('DB_NAME') . '_fresh_check';
        $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $admin = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port), $user, $pass, $opts);
        $admin->exec("DROP DATABASE IF EXISTS `$fresh`");
        $admin->exec("CREATE DATABASE `$fresh` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        try {
            $f = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $fresh), $user, $pass, $opts);
            $runner = new SqlMigrationRunner($f, $root . '/migrations');
            $runner->run();
            checkDbState($f, 'fresh');
            $f->exec("DELETE FROM app_migrations WHERE migration_name = '008_openai_only.sql'");
            $runner->run();
            checkDbState($f, 'fresh-rerun-008');
        } finally {
            $admin->exec("DROP DATABASE IF EXISTS `$fresh`");
        }
    });
}

group('P: payload + reasoning effort', function () {
    $provider = new OpenAICompatibleProvider();
    $chat = new ReflectionMethod($provider, 'buildChatCompletionsPayload');
    $chat->setAccessible(true);
    $resp = new ReflectionMethod($provider, 'buildResponsesPayload');
    $resp->setAccessible(true);
    $msgs = [['role' => 'user', 'content' => 'hi']];
    $base = ['apiModel' => 'gpt-5.2', 'temperature' => 0.3, 'maxTokens' => 100, 'useMaxCompletionTokens' => true, 'reasoningEffort' => 'low'];

    $p = $chat->invoke($provider, $base, $msgs);
    check('P1 reasoning model sends chosen effort', ($p['reasoning_effort'] ?? null) === 'low', json_encode($p));
    check('P2 max_completion_tokens used, no temperature', ($p['max_completion_tokens'] ?? null) === 100 && !isset($p['temperature']) && !isset($p['max_tokens']), json_encode($p));

    $p = $chat->invoke($provider, array_merge($base, ['reasoningEffort' => '']), $msgs);
    check('P3 empty effort not sent', !array_key_exists('reasoning_effort', $p), json_encode($p));

    $p = $chat->invoke($provider, array_merge($base, ['apiModel' => 'gpt-4.1', 'useMaxCompletionTokens' => false, 'reasoningEffort' => 'high']), $msgs);
    check('P4 non-reasoning model ignores effort', !array_key_exists('reasoning_effort', $p) && ($p['temperature'] ?? null) === 0.3 && ($p['max_tokens'] ?? null) === 100, json_encode($p));

    $p = $chat->invoke($provider, array_merge($base, ['useMaxCompletionTokens' => false, 'reasoningEffort' => 'medium']), $msgs);
    check('P5 effort independent of max_completion flag', ($p['reasoning_effort'] ?? null) === 'medium', json_encode($p));

    $p = $resp->invoke($provider, array_merge($base, ['apiModel' => 'gpt-5.2-pro', 'reasoningEffort' => 'high']), $msgs);
    check('P6 responses api sends reasoning.effort', ($p['reasoning']['effort'] ?? null) === 'high', json_encode($p));

    $p = $resp->invoke($provider, array_merge($base, ['apiModel' => 'gpt-5.2-pro', 'reasoningEffort' => '']), $msgs);
    check('P7 responses api omits empty effort', !isset($p['reasoning']), json_encode($p));

    $cfg = (new ModeConfigResolver())->resolve('default');
    check('P8 resolver exposes reasoningEffort', ($cfg['reasoningEffort'] ?? null) === 'low', json_encode($cfg['reasoningEffort'] ?? null));
});

group('K: missing API key', function () {
    $cfg = [
        'apiModel' => 'gpt-5.2', 'temperature' => 0.3, 'maxTokens' => 100, 'useMaxCompletionTokens' => true,
        'reasoningEffort' => '', 'providerKey' => 'openai', 'providerBaseUrl' => 'https://api.openai.com/v1',
        'providerApiKeyEnvVar' => 'OPENAI_API_KEY_CHECK_MISSING',
    ];
    try {
        (new OpenAICompatibleProvider())->stream($cfg, [['role' => 'user', 'content' => 'hi']], function () {}, function () {});
        check('K1 missing key rejected', false, 'no exception');
    } catch (PublicException $e) {
        check('K1 missing key rejected', $e->getMessage() === 'API key provider tidak dikonfigurasi.', $e->getMessage());
    }
});

group('E: effort validation', function () use ($pdo) {
    $repo = new AIConfigRepository();
    $b = $repo->modeBindings()['default'];
    $payload = [
        'model_id' => $b['model_id'], 'system_prompt' => $b['system_prompt'], 'history_strategy' => $b['history_strategy'],
        'history_limit' => $b['history_limit'], 'accepts_image' => $b['accepts_image'], 'ocr_strategy' => $b['ocr_strategy'],
    ];
    $read = function () use ($pdo) {
        return $pdo->query("SELECT reasoning_effort FROM mode_bindings WHERE mode_key = 'default'")->fetchColumn();
    };
    $pdo->beginTransaction();
    try {
        try {
            $repo->saveModeBinding('default', $payload + ['reasoning_effort' => 'turbo']);
            check('E1 invalid effort rejected', false, 'saved without error');
        } catch (PublicException $e) {
            check('E1 invalid effort rejected', true);
        }
        $repo->saveModeBinding('default', $payload + ['reasoning_effort' => '']);
        $v = $read();
        check('E2 empty effort stored as NULL', $v === null, var_export($v, true));
        $repo->saveModeBinding('default', $payload + ['reasoning_effort' => 'medium']);
        $v = $read();
        check('E3 valid effort stored', $v === 'medium', var_export($v, true));
    } finally {
        $pdo->rollBack();
    }
});

echo PHP_EOL . ($failures === 0 ? 'ALL PASS' : $failures . ' FAILED') . PHP_EOL;
exit($failures === 0 ? 0 : 1);
```

- [ ] **Step 2: Ambil snapshot prompt** (wajib sebelum migrasi baru ada)

Run: `php storage/check_openai_only.php --snapshot`
Expected: `Snapshot written: ...storage/prompt-snapshot.json`.

- [ ] **Step 3: Jalankan baseline**

Run: `php storage/check_openai_only.php`
Expected: banyak `FAIL` (R1–R4, D*, P*, E*). Itu normal. `K1` boleh sudah `PASS`. Tidak ada commit di task ini.

---

### Task 2: Hapus runtime 9router dan tanam konfigurasi OpenAI

Task ini harus selesai **sebelum** migrasi baru ada. Alasannya, `DatabaseManager::ensureRouterCatalog()` akan menimpa ulang tabel dengan katalog 9router setiap kali aplikasi boot.

**Files:**
- Delete: `app/Modules/AI/Application/NineRouterCatalogSync.php`
- Modify: `app/Core/DatabaseManager.php`, `app/Modules/Chat/Application/StreamChatService.php`, `app/Modules/Chat/Application/ModeConfigResolver.php`, `app/Modules/AI/Infrastructure/OpenAICompatibleProvider.php`, `app/Modules/Chat/Domain/PublicChatContract.php`, `admin/models.php`, `views/admin/models.php`, `js/app.js`, `js/streaming.js`, `js/model-config.js`

**Interfaces:**
- Produces: `ModeConfigResolver::OPENAI_BASE_URL = 'https://api.openai.com/v1'` dan `ModeConfigResolver::OPENAI_API_KEY_ENV = 'OPENAI_API_KEY'` (public const). `resolve()` selalu mengembalikan `providerBaseUrl` dan `providerApiKeyEnvVar` dari konstanta ini.
- Produces: `OpenAICompatibleProvider::buildChatCompletionsPayload(array $modeConfig, array $messages)`, yaitu dua parameter (parameter `$nineRouter` dihapus).

- [ ] **Step 1: `DatabaseManager`**: hapus `use App\Modules\AI\Application\NineRouterCatalogSync;`, baris `self::ensureRouterCatalog();` di `boot()`, dan seluruh method `ensureRouterCatalog()`.

- [ ] **Step 2: Hapus file** `app/Modules/AI/Application/NineRouterCatalogSync.php`.

- [ ] **Step 3: `admin/models.php`**: hapus `use App\Modules\AI\Application\NineRouterCatalogSync;`. Blok `if (($_POST['action'] ?? '') === 'sync') { ... } else { ... }` diganti dengan isi cabang `else` saja (tanpa if/else):
```php
        $before = !empty($_POST['id']) ? $repository->findModel((int) $_POST['id']) : null;
        $modelId = $repository->saveModel($_POST);
        $after = $repository->findModel($modelId);
        $auditLog->log($currentUser['id'], 'ai_models', $modelId, $before ? 'update' : 'create', $before, $after);
        $successMessage = 'Model berhasil disimpan.';
```
Ganti juga `'pageTitle' => 'Model 9router'` menjadi `'Model'`, dan subtitle menjadi `'Model OpenAI yang bisa dipilih per mode. Isi API Model persis seperti nama model di OpenAI.'`

- [ ] **Step 4: `views/admin/models.php`**: hapus panel pertama (`<div class="panel">` berjudul "Katalog 9router" beserta form sync-nya). Ganti tiga teks bantuan dan placeholder:
  - temperature → `Diabaikan untuk model reasoning (gpt-5 / o-series).`
  - max tokens → `Dikirim sebagai max_completion_tokens atau max_tokens. Untuk model reasoning, token berpikir ikut dihitung.`
  - max_completion_tokens → `Aktifkan untuk model gpt-5 / o-series (wajib untuk model reasoning).`
  - placeholder pencarian → `Cari model...`

- [ ] **Step 5: `StreamChatService`**: hapus baris `$modeConfig = $this->visionModelForImage($modeConfig, $payload['image']);` serta method `visionModelForImage`, `forwardsImageBytes`, `imageCapableModel`. Hapus `use App\Core\DatabaseManager;` jika sudah tidak ada pemakaian (`grep -n DatabaseManager` di file itu).

- [ ] **Step 6: `ModeConfigResolver`**:
  - Tambah kedua konstanta di atas.
  - Di `resolve()`, `'providerBaseUrl' => self::OPENAI_BASE_URL` dan `'providerApiKeyEnvVar' => self::OPENAI_API_KEY_ENV`.
  - Di `buildLegacyFallback()`, `'providerKey' => 'openai'`, `'providerLabel' => 'OpenAI'`, dan dua konstanta yang sama.
  - Hapus method privat `providerBaseUrl()` dan `providerApiKeyEnvVar()`, serta `use App\Core\Env;` jika tidak terpakai lagi.

- [ ] **Step 7: `OpenAICompatibleProvider`**:
  - `$useResponsesApi = $this->requiresResponsesApi($modeConfig['apiModel']);`
  - Panggilan payload menjadi `$this->buildChatCompletionsPayload($modeConfig, $messages)`.
  - Hapus parameter `$nineRouter` dan blok `if ($nineRouter) { ... }`.
  - Hapus `'sk-your-9router-key'` dari `isPlaceholderKey()`.
  - Logika effort **belum** diubah (itu di Task 4).

- [ ] **Step 8: `PublicChatContract`**: `default_model_key` diganti `'gpt-5.2'` (default), `'gpt-5-mini'` (uas), dan `'gpt-5.2'` (uas-math).

- [ ] **Step 9: JS default**

```bash
sed -i "s#'cu/gpt-5\.2'#'gpt-5.2'#g" js/app.js js/streaming.js js/model-config.js && grep -n "gpt-5" js/app.js js/streaming.js js/model-config.js
```
Expected: 4 baris berisi `'gpt-5.2'` dan tidak ada `cu/`.

- [ ] **Step 10: Lint dan cek**

Run: `for f in app/Core/DatabaseManager.php app/Modules/Chat/Application/StreamChatService.php app/Modules/Chat/Application/ModeConfigResolver.php app/Modules/AI/Infrastructure/OpenAICompatibleProvider.php app/Modules/Chat/Domain/PublicChatContract.php admin/models.php views/admin/models.php; do php -l $f; done && php storage/check_openai_only.php`
Expected: semua `No syntax errors detected`. Pada script, R1, R2, R3, R4, dan K1 = `PASS`. D*, P*, dan E* masih `FAIL`.

- [ ] **Step 11: Commit**

```bash
git add -A app admin views js
git commit -m "refactor: remove 9router runtime and hardcode OpenAI endpoint"
```
(Pastikan `git status` tidak menunjukkan file `storage/` yang ikut ter-stage.)

---

### Task 3: Migrasi OpenAI

**Files:**
- Modify: `migrations/003_seed_default_ai_configuration.sql`
- Delete: `migrations/005_switch_to_9router.sql`, `migrations/006_bind_ocr_high_to_vision_model.sql`
- Create: `migrations/007_add_reasoning_effort.sql`, `migrations/008_openai_only.sql`

**Interfaces:**
- Produces: kolom `mode_bindings.reasoning_effort VARCHAR(20) NULL` dan data sesuai Global Constraints.

Catatan runner: statement dipisah oleh `;` + newline. Setiap statement harus diakhiri `;` di akhir baris, dan tidak boleh ada `;` + newline di tengah statement.

- [ ] **Step 1: Tulis ulang bagian awal `003`.** Ganti semuanya dari awal file sampai **sebelum** `INSERT INTO mode_bindings` pertama dengan:

```sql
INSERT INTO ai_providers (provider_key, label, driver, base_url, api_key_env_var, is_active)
VALUES ('openai', 'OpenAI', 'openai_compatible', 'https://api.openai.com/v1', 'OPENAI_API_KEY', 1)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    driver = VALUES(driver),
    base_url = VALUES(base_url),
    api_key_env_var = VALUES(api_key_env_var),
    is_active = VALUES(is_active);

INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
SELECT p.id, 'gpt-5.2', 'gpt-5.2', 'GPT-5.2', 0.30, 16384, 1, 1, 1
FROM ai_providers p
WHERE p.provider_key = 'openai'
ON DUPLICATE KEY UPDATE
    api_model = VALUES(api_model),
    label = VALUES(label),
    supports_vision = VALUES(supports_vision),
    is_active = VALUES(is_active);

INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
SELECT p.id, 'gpt-5-mini', 'gpt-5-mini', 'GPT-5 mini', 0.30, 16384, 1, 1, 1
FROM ai_providers p
WHERE p.provider_key = 'openai'
ON DUPLICATE KEY UPDATE
    api_model = VALUES(api_model),
    label = VALUES(label),
    supports_vision = VALUES(supports_vision),
    is_active = VALUES(is_active);

```
Lalu ganti model key di tiga statement `mode_bindings`. Teks prompt **jangan disentuh**:
```bash
sed -i "s#'cu/gpt-5\.2'#'gpt-5.2'#g; s#'cu/gpt-5-mini'#'gpt-5-mini'#g" migrations/003_seed_default_ai_configuration.sql && grep -nE "9router|cu/|provider_key|model_key =" migrations/003_seed_default_ai_configuration.sql
```
Expected: tidak ada `9router` atau `cu/`. `WHERE m.model_key` bernilai `gpt-5.2`, `gpt-5-mini`, dan `gpt-5.2`.

- [ ] **Step 2: Hapus** `migrations/005_switch_to_9router.sql` dan `migrations/006_bind_ocr_high_to_vision_model.sql` (`git rm`).

- [ ] **Step 3: Buat `007_add_reasoning_effort.sql`** (hanya satu statement):

```sql
ALTER TABLE mode_bindings
    ADD COLUMN reasoning_effort VARCHAR(20) NULL DEFAULT NULL AFTER ocr_strategy;
```

- [ ] **Step 4: Buat `008_openai_only.sql`**. Semua statement aman dijalankan ulang. Urutannya penting karena FK `mode_bindings → ai_models` bersifat RESTRICT:

```sql
INSERT INTO ai_providers (provider_key, label, driver, base_url, api_key_env_var, is_active)
VALUES ('openai', 'OpenAI', 'openai_compatible', 'https://api.openai.com/v1', 'OPENAI_API_KEY', 1)
ON DUPLICATE KEY UPDATE
    label = VALUES(label),
    driver = VALUES(driver),
    base_url = VALUES(base_url),
    api_key_env_var = VALUES(api_key_env_var),
    is_active = VALUES(is_active);

INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
SELECT p.id, 'gpt-5.2', 'gpt-5.2', 'GPT-5.2', 0.30, 16384, 1, 1, 1
FROM ai_providers p
WHERE p.provider_key = 'openai'
ON DUPLICATE KEY UPDATE
    provider_id = VALUES(provider_id),
    api_model = VALUES(api_model),
    label = VALUES(label),
    max_tokens = VALUES(max_tokens),
    use_max_completion_tokens = VALUES(use_max_completion_tokens),
    supports_vision = VALUES(supports_vision),
    is_active = VALUES(is_active);

INSERT INTO ai_models (provider_id, model_key, api_model, label, temperature, max_tokens, use_max_completion_tokens, supports_vision, is_active)
SELECT p.id, 'gpt-5-mini', 'gpt-5-mini', 'GPT-5 mini', 0.30, 16384, 1, 1, 1
FROM ai_providers p
WHERE p.provider_key = 'openai'
ON DUPLICATE KEY UPDATE
    provider_id = VALUES(provider_id),
    api_model = VALUES(api_model),
    label = VALUES(label),
    max_tokens = VALUES(max_tokens),
    use_max_completion_tokens = VALUES(use_max_completion_tokens),
    supports_vision = VALUES(supports_vision),
    is_active = VALUES(is_active);

UPDATE mode_bindings mb
INNER JOIN ai_models m ON m.model_key = 'gpt-5.2'
SET mb.model_id = m.id, mb.reasoning_effort = 'low'
WHERE mb.mode_key = 'default';

UPDATE mode_bindings mb
INNER JOIN ai_models m ON m.model_key = 'gpt-5-mini'
SET mb.model_id = m.id, mb.reasoning_effort = 'high'
WHERE mb.mode_key = 'uas';

UPDATE mode_bindings mb
INNER JOIN ai_models m ON m.model_key = 'gpt-5.2'
SET mb.model_id = m.id, mb.reasoning_effort = 'high'
WHERE mb.mode_key = 'uas-math';

DELETE m FROM ai_models m
INNER JOIN ai_providers p ON p.id = m.provider_id
WHERE p.provider_key <> 'openai';

DELETE FROM ai_providers WHERE provider_key <> 'openai';
```

- [ ] **Step 5: Jalankan pada DB lokal dan DB baru**

Run: `php storage/check_openai_only.php --fresh`
Expected:
- Grup R, D (termasuk **D7** prompt tidak berubah, dan D8), F (`[fresh]` dan `[fresh-rerun-008]`), serta K = `PASS`.
- Sebagian cek P (P1, P3, P5, P7, P8) serta E1–E3 masih `FAIL` karena effort belum diimplementasi.

Jika grup F gagal dengan "Access denied", jalankan ulang dengan user yang boleh `CREATE DATABASE`: `CHECK_DB_USER=root CHECK_DB_PASS= php storage/check_openai_only.php --fresh`.

Jika D gagal, **jangan** coba memperbaiki data dengan tangan. Restore backup (Task 0 Step 3), perbaiki SQL, hapus baris `007_add_reasoning_effort.sql` / `008_openai_only.sql` dari `app_migrations` jika perlu, lalu ulangi.

- [ ] **Step 6: Commit**

```bash
git add -A migrations
git commit -m "feat: migrate AI config to OpenAI provider with reasoning_effort column"
```

---

### Task 4: Reasoning effort per mode

**Files:**
- Modify: `app/Modules/Admin/Infrastructure/AIConfigRepository.php`, `app/Modules/Chat/Application/ModeConfigResolver.php`, `app/Modules/AI/Infrastructure/OpenAICompatibleProvider.php`, `admin/modes.php`, `views/admin/modes.php`

**Interfaces:**
- Consumes: kolom `mode_bindings.reasoning_effort` (Task 3).
- Produces: `AIConfigRepository::REASONING_EFFORTS = ['none', 'minimal', 'low', 'medium', 'high', 'xhigh']` (public const). `resolve()` mengembalikan key `reasoningEffort` (string, `''` jika NULL). POST field admin: `reasoning_effort`.

- [ ] **Step 1: Pastikan cek P dan E masih gagal**

Run: `php storage/check_openai_only.php`
Expected: P1, P3, P5, P7, P8, E1, E2, dan E3 = `FAIL`. Grup lain `PASS`.

- [ ] **Step 2: `AIConfigRepository`**
  - Tambah konstanta `REASONING_EFFORTS`.
  - Di `saveModeBinding()`, hitung `$effort = strtolower(trim((string) ($payload['reasoning_effort'] ?? '')));`. Jika `$effort !== ''` dan tidak ada di konstanta, lempar `new PublicException('Reasoning effort tidak valid.')` **sebelum** query. Simpan `$effort === '' ? null : $effort`.
  - Tambahkan kolom `reasoning_effort` ke `INSERT (...) VALUES (...)` dan ke `ON DUPLICATE KEY UPDATE reasoning_effort = VALUES(reasoning_effort)`.
  - Di `resolvedModeConfig()`, tambahkan `mb.reasoning_effort` ke SELECT.

- [ ] **Step 3: `ModeConfigResolver::resolve()`**: tambah `'reasoningEffort' => (string) ($resolved['reasoning_effort'] ?? ''),`. Di `buildLegacyFallback()`, tambah `'reasoningEffort' => '',`.

- [ ] **Step 4: `OpenAICompatibleProvider`**
  - Tambah `private function reasoningEffortFor(array $modeConfig)`. Fungsi ini mengembalikan `strtolower(trim((string) ($modeConfig['reasoningEffort'] ?? '')))` jika hasilnya tidak kosong **dan** `$this->isReasoningModel($modeConfig['apiModel'])`. Selain itu, kembalikan `null`.
  - `buildChatCompletionsPayload`: blok `max_completion_tokens` / `max_tokens` tetap. Hapus `reasoning_effort = 'high'` yang ada di dalamnya. Setelah blok itu: `$effort = $this->reasoningEffortFor($modeConfig); if ($effort !== null) { $payload['reasoning_effort'] = $effort; }`.
  - `buildResponsesPayload`: ganti kondisi `useMaxCompletionTokens && isReasoningModel` dengan `$effort = $this->reasoningEffortFor($modeConfig); if ($effort !== null) { $payload['reasoning'] = ['effort' => $effort]; }`.

- [ ] **Step 5: `admin/modes.php`**
  - Subtitle: `'Pilih model OpenAI dan reasoning effort untuk setiap mode publik.'`
  - Tambah ke data view: `'effortOptions' => array_merge(['' => 'Bawaan model'], array_combine(AIConfigRepository::REASONING_EFFORTS, AIConfigRepository::REASONING_EFFORTS)),`

- [ ] **Step 6: `views/admin/modes.php`**
  - Ganti paragraf intro dengan: `Tiga mode publik tetap sama. Model diambil dari daftar di halaman Model, misalnya <code>gpt-5.2</code>.`
  - Tambah blok berikut tepat setelah `<div>` dropdown Model:
```php
                <div>
                    <label for="reasoning_effort_<?= htmlspecialchars($modeKey) ?>">Reasoning Effort</label>
                    <select id="reasoning_effort_<?= htmlspecialchars($modeKey) ?>" name="reasoning_effort">
                        <?php foreach ($effortOptions as $effortValue => $effortLabel): ?>
                            <option value="<?= htmlspecialchars((string) $effortValue) ?>" <?= ($mode['reasoningEffort'] ?? '') === (string) $effortValue ? 'selected' : '' ?>><?= htmlspecialchars($effortLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="muted" style="margin-top: 6px; font-size: 13px;">Hanya dikirim untuk model reasoning (gpt-5 / o-series). Jika model tidak mendukung nilai ini, OpenAI mengembalikan error saat chat.</p>
                </div>
```

- [ ] **Step 7: Lint dan cek**

Run: `for f in app/Modules/Admin/Infrastructure/AIConfigRepository.php app/Modules/Chat/Application/ModeConfigResolver.php app/Modules/AI/Infrastructure/OpenAICompatibleProvider.php admin/modes.php views/admin/modes.php; do php -l $f; done && php storage/check_openai_only.php --fresh`
Expected: tidak ada syntax error, dan baris terakhir `ALL PASS`.

- [ ] **Step 8: Commit**

```bash
git add app admin views
git commit -m "feat: per-mode reasoning effort selectable in admin"
```

---

### Task 5: Bersihkan admin dari 9router

**Files:**
- Delete: `admin/providers.php`, `views/admin/providers.php`
- Modify: `views/admin/layout.php`, `admin/index.php`, `views/admin/dashboard.php`, `views/admin/login.php`, `app/Modules/Admin/Infrastructure/AIConfigRepository.php`

- [ ] **Step 1: Hapus** kedua file providers (`git rm`), dan hapus baris `<a href="/admin/providers.php" ...>9router</a>` di `views/admin/layout.php`.

- [ ] **Step 2: Ganti teks**
  - `admin/index.php` subtitle → `Runtime chat memakai OpenAI sebagai satu-satunya provider model.`
  - `views/admin/dashboard.php` → `Model OpenAI yang terdaftar. Daftar lengkap ada di halaman Model.`
  - `views/admin/login.php` → `Masuk untuk mengelola model, mode binding, dan history aplikasi.`

- [ ] **Step 3: Hapus kode mati di `AIConfigRepository`**: `findProvider`, `saveProvider`, `normalizeBaseUrl`, dan `normalizeKey`. Sebelum menghapus, pastikan `grep -rn "findProvider\|saveProvider\|normalizeBaseUrl\|normalizeKey" app admin views` hanya menemukan definisinya sendiri. `allProviders` **tetap** ada karena dipakai dashboard dan halaman model.

- [ ] **Step 4: Verifikasi**

Run: `php -l views/admin/layout.php && php -l admin/index.php && php -l views/admin/dashboard.php && php -l views/admin/login.php && php -l app/Modules/Admin/Infrastructure/AIConfigRepository.php && php storage/check_openai_only.php && curl -s -o /dev/null -w "%{http_code}\n" http://faleh-ai.test/admin/providers.php && curl -s -o /dev/null -w "%{http_code}\n" http://faleh-ai.test/admin/models.php`
Expected: tidak ada syntax error, `ALL PASS`, `404` untuk providers, dan `302` untuk models (redirect ke login, bukan 500).

- [ ] **Step 5: Commit**

```bash
git add -A admin views app
git commit -m "refactor: drop provider admin page and 9router copy"
```

---

### Task 6: `.env.example` dan README

**Files:** Modify: `.env.example`, `README.md`

- [ ] **Step 1: `.env.example`**: ganti baris `NINEROUTER_URL=...` dan `NINEROUTER_KEY=...` dengan satu baris `OPENAI_API_KEY=sk-your-openai-api-key-here`.

- [ ] **Step 2: `README.md`**
  - Pada blok env "Isi minimal", ganti dua baris `NINEROUTER_*` dengan `OPENAI_API_KEY=sk-your-openai-api-key-here`.
  - Kalimat "...lalu mengisi katalog model dari 9router." diganti `Saat pertama kali dibuka, aplikasi menjalankan migrations/ secara otomatis: provider OpenAI, model gpt-5.2 dan gpt-5-mini, serta tiga mode. Model dan reasoning effort per mode bisa diubah di /admin/modes.php.` (pakai format backtick seperti teks di sekitarnya).
  - Troubleshooting "API key invalid": `Cek OPENAI_API_KEY di .env (tanpa tanda kutip)`.

- [ ] **Step 3: Verifikasi tidak ada jejak tersisa**

Run: `grep -rniI "9router\|ninerouter\|cu/gpt\|ag/gemini\|20128" --exclude-dir=.git --exclude-dir=docs --exclude-dir=storage --exclude=.env .`
Expected: tidak ada output.

- [ ] **Step 4: Commit**

```bash
git add .env.example README.md
git commit -m "docs: document OPENAI_API_KEY setup"
```

---

### Task 7: Verifikasi akhir dan uji live

- [ ] **Step 1: Cek `.env` pemilik tanpa mencetak isinya**

Run: `grep -c '^OPENAI_API_KEY=sk-' .env; grep -c '^NINEROUTER' .env`
Expected: `1` lalu `0`. Kalau tidak sesuai, **BERHENTI** dan minta pemilik menambahkan `OPENAI_API_KEY=<key asli>` dan menghapus baris `NINEROUTER_*` di `.env`. Jangan mengedit `.env` sendiri.

- [ ] **Step 2: Lint seluruh PHP**

Run: `git ls-files '*.php' | xargs -n1 php -l | grep -v "No syntax errors"`
Expected: tidak ada output.

- [ ] **Step 3: Script verifikasi lengkap**

Run: `php storage/check_openai_only.php --fresh`
Expected: `ALL PASS`.

- [ ] **Step 4: Uji live ketiga mode**

```bash
curl -sN --max-time 120 -X POST http://faleh-ai.test/api_stream.php -H 'Content-Type: application/json' -d '{"message":"Balas satu kata: halo","history":[]}' | tail -c 600
```
```bash
curl -sN --max-time 120 -X POST http://faleh-ai.test/api_uas_stream.php -H 'Content-Type: application/json' -d '{"message":"2+2 berapa?"}' | tail -c 600
```
```bash
curl -sN --max-time 180 -X POST http://faleh-ai.test/api_uas_math_stream.php -H 'Content-Type: application/json' -d '{"message":"Gambar ini berwarna apa?","image":"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=="}' | tail -c 600
```
Expected: setiap output berisi event `chunk` dan `complete`, tanpa event `error`. Jika muncul error OpenAI tentang `reasoning_effort` atau model, catat pesannya dan laporkan ke pemilik. **Jangan** mengganti model atau effort sendiri.

- [ ] **Step 5: Cek history tercatat sebagai openai**

Run: `php -r 'require "app/Boot/bootstrap.php"; print_r(App\Core\DatabaseManager::connection()->query("SELECT mode, model, provider_key FROM chat_history ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC));'`
Expected: 3 baris dengan `provider_key` = `openai`, serta model `gpt-5.2`, `gpt-5-mini`, dan `gpt-5.2`.

- [ ] **Step 6: Bersihkan file sementara**

```bash
rm storage/check_openai_only.php storage/prompt-snapshot.json && git status --porcelain
```
Expected: `git status` bersih, kecuali `storage/backup-before-openai.sql` (biarkan sampai pemilik puas, jangan di-commit).

- [ ] **Step 7: Serahkan ke pemilik untuk cek manual di browser.** Haiku tidak mengisi password admin. Yang perlu pemilik cek:
  - Login admin. Menu tidak lagi punya "9router".
  - Halaman Model: tidak ada tombol sinkron. Bisa menambah model baru.
  - Halaman Mode: dropdown Reasoning Effort ada. Simpan `medium` untuk Chat, muat ulang halaman, dan nilainya tetap `medium`.
  - Halaman chat publik: tiga mode menjawab, dan OCR High menerima gambar.
