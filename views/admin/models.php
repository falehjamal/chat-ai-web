<div class="panel">
    <h2 style="margin-top: 0;">Katalog 9router</h2>
    <p class="muted">Menghapus model lama lalu mengisi ulang dari <code>GET /v1/models</code> di 9router lokal. Binding mode tetap memakai prompt yang sudah ada.</p>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="action" value="sync">
        <button type="submit" class="btn">Sinkronkan dari 9router</button>
    </form>
</div>

<div class="panel">
    <h2 style="margin-top: 0;"><?= !empty($editingModel) ? 'Edit Model' : 'Tambah Model' ?></h2>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="id" value="<?= htmlspecialchars($editingModel['id'] ?? '') ?>">
        <div class="form-grid">
            <div>
                <label for="provider_id">Provider</label>
                <select id="provider_id" name="provider_id" required>
                    <?php foreach ($providers as $provider): ?>
                        <option value="<?= (int) $provider['id'] ?>" <?= ((int) ($editingModel['provider_id'] ?? 0) === (int) $provider['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($provider['label']) ?> (<?= htmlspecialchars($provider['provider_key']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="model_key">Model Key</label>
                <input type="text" id="model_key" name="model_key" required value="<?= htmlspecialchars($editingModel['model_key'] ?? '') ?>">
            </div>
            <div>
                <label for="api_model">API Model</label>
                <input type="text" id="api_model" name="api_model" required value="<?= htmlspecialchars($editingModel['api_model'] ?? '') ?>">
            </div>
            <div>
                <label for="label">Label</label>
                <input type="text" id="label" name="label" required value="<?= htmlspecialchars($editingModel['label'] ?? '') ?>">
            </div>
            <div>
                <label for="temperature">Temperature</label>
                <input type="number" step="0.1" min="0" max="2" id="temperature" name="temperature" required value="<?= htmlspecialchars($editingModel['temperature'] ?? '0.3') ?>">
                <p class="muted" style="margin-top: 6px; font-size: 13px;">Dikirim ke 9router sebagai temperature pada /v1/chat/completions.</p>
            </div>
            <div>
                <label for="max_tokens">Max Tokens</label>
                <input type="number" min="1" id="max_tokens" name="max_tokens" required value="<?= htmlspecialchars($editingModel['max_tokens'] ?? '4096') ?>">
                <p class="muted" style="margin-top: 6px; font-size: 13px;">Dikirim sebagai max_tokens. Nilai katalog 9router dibatasi 16384 saat sinkronisasi.</p>
            </div>
            <div>
                <label>
                    <input type="checkbox" name="use_max_completion_tokens" value="1" <?= !empty($editingModel['use_max_completion_tokens']) ? 'checked' : '' ?>>
                    Gunakan `max_completion_tokens`
                </label>
                <p class="muted" style="margin-top: 6px; font-size: 13px;">Biarkan nonaktif untuk 9router. Router lokal memakai max_tokens, bukan max_completion_tokens.</p>
            </div>
            <div>
                <label>
                    <input type="checkbox" name="supports_vision" value="1" <?= !empty($editingModel['supports_vision']) ? 'checked' : '' ?>>
                    Mendukung Vision
                </label>
            </div>
            <div>
                <label>
                    <input type="checkbox" name="is_active" value="1" <?= !isset($editingModel['is_active']) || !empty($editingModel['is_active']) ? 'checked' : '' ?>>
                    Aktif
                </label>
            </div>
        </div>
        <div class="actions">
            <button type="submit" class="btn">Simpan Model</button>
            <a href="/admin/models.php" class="btn light">Reset Form</a>
        </div>
    </form>
</div>

<div class="panel">
    <h2 style="margin-top: 0;">Daftar Model</h2>
    <input type="search" id="model-filter" placeholder="Cari model 9router..." style="width: 100%; margin-bottom: 12px;">
    <table>
        <thead>
        <tr>
            <th>Model Key</th>
            <th>Provider</th>
            <th>API Model</th>
            <th>Vision</th>
            <th>Max Tokens</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($models as $model): ?>
            <tr data-model="<?= htmlspecialchars(strtolower($model['model_key'])) ?>">
                <td><?= htmlspecialchars($model['model_key']) ?></td>
                <td><?= htmlspecialchars($model['provider_label']) ?></td>
                <td><?= htmlspecialchars($model['api_model']) ?></td>
                <td><?= !empty($model['supports_vision']) ? 'Ya' : 'Tidak' ?></td>
                <td><?= number_format((int) $model['max_tokens']) ?></td>
                <td><span class="badge"><?= !empty($model['is_active']) ? 'Aktif' : 'Nonaktif' ?></span></td>
                <td><a class="btn light" href="/admin/models.php?edit=<?= (int) $model['id'] ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
document.getElementById('model-filter').addEventListener('input', function () {
    var query = this.value.toLowerCase();
    document.querySelectorAll('tr[data-model]').forEach(function (row) {
        row.style.display = row.getAttribute('data-model').indexOf(query) !== -1 ? '' : 'none';
    });
});
</script>
