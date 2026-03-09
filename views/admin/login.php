<h2 style="margin-top: 0;">Login Admin</h2>
<p class="muted">Masuk untuk mengelola provider, model, mode binding, dan history aplikasi.</p>

<form method="post">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
    <div style="margin-bottom: 14px;">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username">
    </div>
    <div style="margin-bottom: 14px;">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
    </div>
    <div class="actions">
        <button type="submit" class="btn">Login</button>
        <a href="/index.php" class="btn light">Kembali ke Chat</a>
    </div>
</form>
