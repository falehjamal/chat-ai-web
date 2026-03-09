<div class="panel">
    <form method="get">
        <div class="form-grid">
            <div>
                <label for="start_date">Tanggal Mulai</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>">
            </div>
            <div>
                <label for="end_date">Tanggal Akhir</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>">
            </div>
            <div>
                <label for="mode">Mode</label>
                <select id="mode" name="mode">
                    <option value="">Semua</option>
                    <option value="default" <?= $filters['mode'] === 'default' ? 'selected' : '' ?>>Chat</option>
                    <option value="uas" <?= $filters['mode'] === 'uas' ? 'selected' : '' ?>>OCR Low</option>
                    <option value="uas-math" <?= $filters['mode'] === 'uas-math' ? 'selected' : '' ?>>OCR High</option>
                </select>
            </div>
            <div>
                <label for="ip">IP Address</label>
                <input type="text" id="ip" name="ip" value="<?= htmlspecialchars($filters['ip'] ?? '') ?>">
            </div>
            <div>
                <label for="limit">Per Halaman</label>
                <select id="limit" name="limit">
                    <?php foreach ([10, 20, 50, 100] as $size): ?>
                        <option value="<?= $size ?>" <?= (int) $filters['limit'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="actions">
            <button type="submit" class="btn">Terapkan Filter</button>
            <a href="/admin/history.php" class="btn light">Reset</a>
        </div>
    </form>
</div>

<div class="grid">
    <div class="card">
        <h3>Total Chat</h3>
        <div class="value"><?= number_format($stats['total_chats']) ?></div>
    </div>
    <div class="card">
        <h3>Total Token</h3>
        <div class="value"><?= number_format($stats['total_tokens']) ?></div>
    </div>
    <div class="card">
        <h3>Aktivitas 24 Jam</h3>
        <div class="value"><?= number_format($stats['last_24h']) ?></div>
    </div>
    <div class="card">
        <h3>Total Halaman</h3>
        <div class="value"><?= number_format($history['total_pages']) ?></div>
    </div>
</div>

<div class="panel">
    <p>Menampilkan <?= count($history['items']) ?> dari <?= number_format($history['total']) ?> record.</p>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>IP</th>
            <th>User</th>
            <th>Response</th>
            <th>Mode</th>
            <th>Model</th>
            <th>Provider</th>
            <th>Token</th>
            <th>Waktu</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($history['items'] as $item): ?>
            <tr>
                <td><?= (int) $item['id'] ?></td>
                <td><?= htmlspecialchars($item['ip_address']) ?></td>
                <td class="copyable"><?= htmlspecialchars(mb_substr($item['user'], 0, 150)) ?><?= mb_strlen($item['user']) > 150 ? '...' : '' ?></td>
                <td class="copyable"><?= htmlspecialchars(mb_substr($item['response'], 0, 180)) ?><?= mb_strlen($item['response']) > 180 ? '...' : '' ?></td>
                <td><span class="badge"><?= htmlspecialchars($item['mode']) ?></span></td>
                <td><?= htmlspecialchars($item['model']) ?></td>
                <td><?= htmlspecialchars($item['provider_key'] ?? '-') ?></td>
                <td><?= number_format((int) $item['jumlah_token']) ?></td>
                <td><?= htmlspecialchars($item['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($history['total_pages'] > 1): ?>
        <div class="actions">
            <?php for ($page = 1; $page <= $history['total_pages']; $page++): ?>
                <?php if ($page === $history['page']): ?>
                    <span class="btn secondary"><?= $page ?></span>
                <?php else: ?>
                    <a class="btn light" href="?<?= htmlspecialchars(http_build_query(array_merge($filters, ['page' => $page]))) ?>"><?= $page ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
