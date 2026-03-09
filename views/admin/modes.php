<div class="panel">
    <h2 style="margin-top: 0;">Konfigurasi Binding Per Mode</h2>
    <p class="muted">User publik tetap hanya melihat tiga mode utama. Dari sini Anda mengubah provider, model, prompt, dan policy runtime di balik masing-masing mode.</p>
</div>

<?php foreach ($runtimeModes as $modeKey => $mode): ?>
    <div class="panel">
        <h3 style="margin-top: 0;"><?= htmlspecialchars($mode['label']) ?> (`<?= htmlspecialchars($modeKey) ?>`)</h3>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="mode_key" value="<?= htmlspecialchars($modeKey) ?>">
            <div class="form-grid">
                <div>
                    <label for="model_id_<?= htmlspecialchars($modeKey) ?>">Model</label>
                    <select id="model_id_<?= htmlspecialchars($modeKey) ?>" name="model_id" required>
                        <?php foreach ($models as $model): ?>
                            <?php if ($modeKey !== 'uas-math' || !empty($model['supports_vision'])): ?>
                                <option value="<?= (int) $model['id'] ?>" <?= $model['model_key'] === $mode['modelKey'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($model['provider_label']) ?> - <?= htmlspecialchars($model['label']) ?> (<?= htmlspecialchars($model['model_key']) ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="history_strategy_<?= htmlspecialchars($modeKey) ?>">History Strategy</label>
                    <select id="history_strategy_<?= htmlspecialchars($modeKey) ?>" name="history_strategy">
                        <option value="recent_window" <?= $mode['historyStrategy'] === 'recent_window' ? 'selected' : '' ?>>recent_window</option>
                        <option value="none" <?= $mode['historyStrategy'] === 'none' ? 'selected' : '' ?>>none</option>
                    </select>
                </div>
                <div>
                    <label for="history_limit_<?= htmlspecialchars($modeKey) ?>">History Limit</label>
                    <input type="number" id="history_limit_<?= htmlspecialchars($modeKey) ?>" name="history_limit" min="0" value="<?= (int) $mode['historyLimit'] ?>">
                </div>
                <div>
                    <label for="ocr_strategy_<?= htmlspecialchars($modeKey) ?>">OCR Strategy</label>
                    <select id="ocr_strategy_<?= htmlspecialchars($modeKey) ?>" name="ocr_strategy">
                        <option value="client_extract_text" <?= $mode['ocrStrategy'] === 'client_extract_text' ? 'selected' : '' ?>>client_extract_text</option>
                        <option value="vision_direct" <?= $mode['ocrStrategy'] === 'vision_direct' ? 'selected' : '' ?>>vision_direct</option>
                    </select>
                </div>
                <div>
                    <label>
                        <input type="checkbox" name="accepts_image" value="1" <?= !empty($mode['acceptsImage']) ? 'checked' : '' ?>>
                        Menerima gambar
                    </label>
                </div>
                <div>
                    <label>Endpoint Publik</label>
                    <input type="text" value="<?= htmlspecialchars($mode['endpoint']) ?>" disabled>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="system_prompt_<?= htmlspecialchars($modeKey) ?>">System Prompt</label>
                    <textarea id="system_prompt_<?= htmlspecialchars($modeKey) ?>" name="system_prompt" required><?= htmlspecialchars($mode['systemPrompt']) ?></textarea>
                </div>
            </div>
            <div class="actions">
                <button type="submit" class="btn">Simpan Binding `<?= htmlspecialchars($modeKey) ?>`</button>
            </div>
        </form>
    </div>
<?php endforeach; ?>
