<div class="panel">
    <h2 style="margin-top: 0;"><?= !empty($editingProvider) ? 'Edit Provider' : 'Tambah Provider' ?></h2>
    <form method="post">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="id" value="<?= htmlspecialchars($editingProvider['id'] ?? '') ?>">
        <div class="form-grid">
            <div>
                <label for="provider_key">Provider Key</label>
                <input type="text" id="provider_key" name="provider_key" required value="<?= htmlspecialchars($editingProvider['provider_key'] ?? '') ?>">
            </div>
            <div>
                <label for="label">Label</label>
                <input type="text" id="label" name="label" required value="<?= htmlspecialchars($editingProvider['label'] ?? '') ?>">
            </div>
            <div>
                <label for="driver">Driver</label>
                <select id="driver" name="driver">
                    <option value="openai_compatible" <?= (($editingProvider['driver'] ?? 'openai_compatible') === 'openai_compatible') ? 'selected' : '' ?>>openai_compatible</option>
                </select>
            </div>
            <div>
                <label for="api_key_env_var">Env API Key</label>
                <input type="text" id="api_key_env_var" name="api_key_env_var" required value="<?= htmlspecialchars($editingProvider['api_key_env_var'] ?? 'OPENAI_API_KEY') ?>">
            </div>
            <div style="grid-column: 1 / -1;">
                <label for="base_url">Base URL</label>
                <input type="text" id="base_url" name="base_url" required value="<?= htmlspecialchars($editingProvider['base_url'] ?? 'https://api.openai.com/v1') ?>">
            </div>
            <div>
                <label>
                    <input type="checkbox" name="is_active" value="1" <?= !isset($editingProvider['is_active']) || !empty($editingProvider['is_active']) ? 'checked' : '' ?>>
                    Aktif
                </label>
            </div>
        </div>
        <div class="actions">
            <button type="submit" class="btn">Simpan Provider</button>
            <a href="/admin/providers.php" class="btn light">Reset Form</a>
        </div>
    </form>
</div>

<div class="panel">
    <h2 style="margin-top: 0;">Daftar Provider</h2>
    <table>
        <thead>
        <tr>
            <th>Key</th>
            <th>Label</th>
            <th>Driver</th>
            <th>Base URL</th>
            <th>Env Key</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($providers as $provider): ?>
            <tr>
                <td><?= htmlspecialchars($provider['provider_key']) ?></td>
                <td><?= htmlspecialchars($provider['label']) ?></td>
                <td><?= htmlspecialchars($provider['driver']) ?></td>
                <td class="copyable"><?= htmlspecialchars($provider['base_url']) ?></td>
                <td><?= htmlspecialchars($provider['api_key_env_var']) ?></td>
                <td><span class="badge"><?= !empty($provider['is_active']) ? 'Aktif' : 'Nonaktif' ?></span></td>
                <td><a class="btn light" href="/admin/providers.php?edit=<?= (int) $provider['id'] ?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
