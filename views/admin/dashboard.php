<div class="grid">
    <div class="card">
        <h3>Total Chat</h3>
        <div class="value"><?= number_format($stats['total_chats'] ?? 0) ?></div>
    </div>
    <div class="card">
        <h3>Total Token</h3>
        <div class="value"><?= number_format($stats['total_tokens'] ?? 0) ?></div>
    </div>
    <div class="card">
        <h3>Aktivitas 24 Jam</h3>
        <div class="value"><?= number_format($stats['last_24h'] ?? 0) ?></div>
    </div>
    <div class="card">
        <h3>Mode Aktif</h3>
        <div class="value"><?= number_format(count($modeBindings ?? [])) ?></div>
    </div>
</div>

<div class="panel">
    <h2 style="margin-top: 0;">Runtime Contract</h2>
    <table>
        <thead>
        <tr>
            <th>Mode</th>
            <th>Endpoint</th>
            <th>Model</th>
            <th>Provider</th>
            <th>History</th>
            <th>Image</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($runtimeModes as $modeKey => $mode): ?>
            <tr>
                <td><strong><?= htmlspecialchars($modeKey) ?></strong></td>
                <td><?= htmlspecialchars($mode['endpoint']) ?></td>
                <td><?= htmlspecialchars($mode['modelKey']) ?></td>
                <td><?= htmlspecialchars($mode['providerKey']) ?></td>
                <td><?= htmlspecialchars($mode['historyStrategy']) ?> / <?= (int) $mode['historyLimit'] ?></td>
                <td><?= !empty($mode['acceptsImage']) ? 'Ya' : 'Tidak' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="grid">
    <div class="panel">
        <h2 style="margin-top: 0;">Ringkasan Provider</h2>
        <table>
            <thead>
            <tr>
                <th>Provider</th>
                <th>Driver</th>
                <th>Base URL</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($providers as $provider): ?>
                <tr>
                    <td><?= htmlspecialchars($provider['label']) ?></td>
                    <td><?= htmlspecialchars($provider['driver']) ?></td>
                    <td><?= htmlspecialchars($provider['base_url']) ?></td>
                    <td><span class="badge"><?= !empty($provider['is_active']) ? 'Aktif' : 'Nonaktif' ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="panel">
        <h2 style="margin-top: 0;">Model Tersedia</h2>
        <table>
            <thead>
            <tr>
                <th>Model Key</th>
                <th>Provider</th>
                <th>Vision</th>
                <th>Token</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($models as $model): ?>
                <tr>
                    <td><?= htmlspecialchars($model['model_key']) ?></td>
                    <td><?= htmlspecialchars($model['provider_label']) ?></td>
                    <td><?= !empty($model['supports_vision']) ? 'Ya' : 'Tidak' ?></td>
                    <td><?= number_format((int) $model['max_tokens']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
