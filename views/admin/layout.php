<?php
extract($viewData, EXTR_SKIP);
$pageTitle = $pageTitle ?? 'Admin';
$currentPage = $currentPage ?? '';
$contentTemplate = $contentTemplate ?? null;
$currentUser = $currentUser ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Admin Chat AI</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }
        .layout {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 240px;
            background: #111827;
            color: #fff;
            padding: 24px 18px;
            box-sizing: border-box;
        }
        .brand {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .muted {
            color: #9ca3af;
            font-size: 13px;
            margin-bottom: 24px;
        }
        .nav a {
            display: block;
            color: #e5e7eb;
            text-decoration: none;
            padding: 10px 12px;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .nav a.active,
        .nav a:hover {
            background: #1f2937;
        }
        .main {
            flex: 1;
            padding: 24px;
            box-sizing: border-box;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }
        .panel {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            margin-bottom: 20px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .card {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        }
        .card h3 {
            margin-top: 0;
            font-size: 14px;
            color: #6b7280;
        }
        .card .value {
            font-size: 28px;
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        th {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #6b7280;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }
        input[type="text"],
        input[type="password"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            box-sizing: border-box;
            font: inherit;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }
        .btn {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 8px;
            border: none;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            font: inherit;
        }
        .btn.secondary {
            background: #374151;
        }
        .btn.light {
            background: #e5e7eb;
            color: #111827;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 12px;
            background: #e5e7eb;
        }
        .success {
            background: #dcfce7;
            color: #166534;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
        }
        .inline-form {
            display: inline;
        }
        .auth-shell {
            max-width: 520px;
            margin: 60px auto;
        }
        .copyable {
            white-space: pre-wrap;
            word-break: break-word;
        }
        @media (max-width: 900px) {
            .layout {
                display: block;
            }
            .sidebar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<?php if (!empty($isAuthPage)): ?>
    <div class="auth-shell">
        <?php if (!empty($successMessage)): ?>
            <div class="success"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <?php if (!empty($errorMessage)): ?>
            <div class="error"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
        <div class="panel">
            <?php include $contentTemplate; ?>
        </div>
    </div>
<?php else: ?>
    <div class="layout">
        <aside class="sidebar">
            <div class="brand">Admin Chat AI</div>
            <div class="muted">
                <?php if ($currentUser): ?>
                    Login sebagai <?= htmlspecialchars($currentUser['display_name']) ?>
                <?php else: ?>
                    Panel konfigurasi runtime
                <?php endif; ?>
            </div>
            <nav class="nav">
                <a href="/admin/index.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
                <a href="/admin/providers.php" class="<?= $currentPage === 'providers' ? 'active' : '' ?>">Providers</a>
                <a href="/admin/models.php" class="<?= $currentPage === 'models' ? 'active' : '' ?>">Models</a>
                <a href="/admin/modes.php" class="<?= $currentPage === 'modes' ? 'active' : '' ?>">Mode Bindings</a>
                <a href="/admin/history.php" class="<?= $currentPage === 'history' ? 'active' : '' ?>">History</a>
                <a href="/index.php">Kembali ke Chat</a>
                <a href="/admin/logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main">
            <div class="topbar">
                <div>
                    <h1 style="margin: 0;"><?= htmlspecialchars($pageTitle) ?></h1>
                    <?php if (!empty($pageSubtitle)): ?>
                        <div class="muted" style="margin: 6px 0 0;"><?= htmlspecialchars($pageSubtitle) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($successMessage)): ?>
                <div class="success"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMessage)): ?>
                <div class="error"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>
            <?php include $contentTemplate; ?>
        </main>
    </div>
<?php endif; ?>
</body>
</html>
