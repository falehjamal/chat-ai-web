<h2 style="margin-top: 0;">Buat Admin Pertama</h2>
<p class="muted">Project ini belum punya akun admin. Buat satu akun untuk mulai mengelola konfigurasi runtime dari `/admin`.</p>

<form method="post">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
    <div style="margin-bottom: 14px;">
        <label for="display_name">Nama Tampilan</label>
        <input type="text" id="display_name" name="display_name" required>
    </div>
    <div style="margin-bottom: 14px;">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username">
    </div>
    <div style="margin-bottom: 14px;">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="new-password">
    </div>
    <div class="actions">
        <button type="submit" class="btn">Buat Admin</button>
        <a href="/index.php" class="btn light">Kembali ke Chat</a>
    </div>
</form>
