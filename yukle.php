<?php
/**
 * Excel → MySQL yükləmə paneli
 */
require_once __DIR__ . '/auth/_auth_helper.php';
require_once __DIR__ . '/lib/excel_import.php';

auth_require_admin('/bb/auth/login.php');

$expectedFiles = [
    'Destinations Azerbaijan Activities 2026_updated 31.12.2025.xlsx' => 'activities',
    'Destinations Azerbaijan Transfers PVT 2026_31.12.2025.xlsx' => 'transfers',
    'Sembark Hotel Listing _Revise 10.12.2025 xx.xlsx' => 'hotels',
];
$found = excel_import_find_xlsx(__DIR__);
$byName = [];
foreach ($found as $p) {
    $byName[basename($p)] = $p;
}
?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excel Yüklə — MySQL Import</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, Segoe UI, sans-serif; margin: 0; background: #f1f5f9; color: #0f172a; }
        .wrap { max-width: 960px; margin: 0 auto; padding: 24px 16px 48px; }
        h1 { font-size: 1.5rem; margin: 0 0 8px; }
        .sub { color: #64748b; font-size: 0.9rem; margin-bottom: 24px; }
        .card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .card h2 { font-size: 1rem; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        th { color: #64748b; font-weight: 600; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-miss { background: #fee2e2; color: #991b1b; }
        .badge-type { background: #e0e7ff; color: #3730a3; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
        button { border: none; cursor: pointer; padding: 10px 18px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        input[type=file] { font-size: 0.875rem; }
        #log { background: #0f172a; color: #e2e8f0; padding: 14px; border-radius: 8px; font-family: ui-monospace, monospace; font-size: 0.8rem; white-space: pre-wrap; max-height: 400px; overflow: auto; display: none; margin-top: 12px; }
        #status { margin-top: 12px; font-size: 0.9rem; }
        .loading { opacity: 0.6; pointer-events: none; }
        a.back { color: #2563eb; text-decoration: none; font-size: 0.875rem; }
        ul.rules { margin: 0; padding-left: 1.2rem; color: #475569; font-size: 0.875rem; line-height: 1.6; }
    </style>
</head>
<body>
<div class="wrap">
    <p><a class="back" href="tour-admin.php">← Admin panel</a></p>
    <h1>Excel → MySQL Yükləmə</h1>
    <p class="sub">Aktivitet, Transfer və Otel Excel fayllarını veritabanına yükləyir. Eyni qeyd varsa təkrar yazılmır; dəyişiklik varsa yenilənir.</p>

    <div class="card">
        <h2>Gözlənilən fayllar (layihə qovluğunda)</h2>
        <table>
            <thead>
                <tr><th>Fayl</th><th>Növ</th><th>Status</th></tr>
            </thead>
            <tbody>
            <?php foreach ($expectedFiles as $name => $type): ?>
                <?php $exists = isset($byName[$name]); ?>
                <tr>
                    <td><?= htmlspecialchars($name) ?></td>
                    <td><span class="badge badge-type"><?= htmlspecialchars($type) ?></span></td>
                    <td>
                        <?php if ($exists): ?>
                            <span class="badge badge-ok">Tapıldı</span>
                        <?php else: ?>
                            <span class="badge badge-miss">Yoxdur</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="actions">
            <button type="button" class="btn-primary" id="btn-scan">Layihədəki bütün .xlsx faylları import et</button>
        </div>
    </div>

    <div class="card">
        <h2>Fayl yüklə</h2>
        <p style="font-size:0.875rem;color:#64748b;margin:0 0 12px;">Bir və ya bir neçə .xlsx seçin (Activities, Transfers, Sembark Hotel).</p>
        <form id="upload-form">
            <input type="file" name="files[]" id="file-input" accept=".xlsx" multiple />
            <div class="actions" style="margin-top:12px;">
                <button type="submit" class="btn-primary" id="btn-upload">Yüklə və MySQL-ə yaz</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Qaydalar</h2>
        <ul class="rules">
            <li><strong>Activities</strong> → <code>tour_azerbaijan_activities_2026</code> (name + city + service)</li>
            <li><strong>Transfers</strong> → <code>transfers</code> (Excel: A=Duty Code, B=A, C=B, D=Service, H=Day Schedule, I+=qiymət)</li>
            <li>Transfer import yalnız <strong>2026</strong> vərəqini oxuyur; köhnə səhv sətirlər avtomatik silinir.</li>
            <li><strong>Hotels</strong> → <code>tour_hotels</code>, <code>tour_hotel_prices</code>, <code>hotel_listing_full</code></li>
            <li><strong>cPanel / paylaşımlı hosting:</strong> import avtomatik <strong>PHP</strong> ilə işləyir (<code>exec</code> lazım deyil). <code>vendor/</code> üçün serverdə <code>composer install</code> və PHP <code>zip</code> extension aktiv olmalıdır.</li>
            <li><strong>Lokal XAMPP:</strong> istəyə görə Python: <code>py -m pip install openpyxl pymysql</code></li>
            <li>Import test: <a href="api/excel-import-test.php" target="_blank">api/excel-import-test.php</a></li>
        </ul>
    </div>

    <div id="status"></div>
    <pre id="log"></pre>
</div>

<script>
(function () {
    const statusEl = document.getElementById('status');
    const logEl = document.getElementById('log');
    const wrap = document.querySelector('.wrap');

    function setLoading(on) {
        wrap.classList.toggle('loading', on);
    }

    function showResult(data) {
        logEl.style.display = 'block';
        logEl.textContent = JSON.stringify(data, null, 2);
        if (!data.ok) {
            let err = '<span style="color:#b91c1c">Xəta: ' + (data.error || 'Import uğursuz') + '</span>';
            if (data.python) err += '<br><small>Python: ' + data.python + '</small>';
            if (data.log && data.log.indexOf('cannot execute') !== -1) {
                err += '<br><small>config.php faylında <code>PYTHON_BIN</code> tam yolunu təyin edin.</small>';
            }
            statusEl.innerHTML = err;
            return;
        }
        let html = '<strong style="color:#166534">Import tamamlandı</strong>';
        if (data.engine) html += ' <small>(motor: ' + data.engine + ')</small>';
        html += '<ul style="margin:8px 0 0;padding-left:1.2rem;">';
        (data.results || []).forEach(function (r) {
            const s = r.stats || {};
            const parts = [];
            Object.keys(s).forEach(function (k) {
                if (s[k] > 0) parts.push(k + ': ' + s[k]);
            });
            html += '<li>' + (r.file ? r.file.split(/[\\\\/]/).pop() : '?') + ' (' + r.type + ') — ' +
                (r.ok ? parts.join(', ') || 'OK' : (r.error || 'xəta')) + '</li>';
        });
        html += '</ul>';
        statusEl.innerHTML = html;
    }

    async function postForm(action, formData) {
        setLoading(true);
        statusEl.textContent = action === 'scan'
            ? 'Import edilir (bir neçə dəqiqə çəkə bilər)...'
            : 'Yüklənir və import edilir...';
        logEl.style.display = 'none';
        try {
            if (action) formData.append('action', action);
            const res = await fetch('api/excel-import-mysql.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            });
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseErr) {
                logEl.style.display = 'block';
                logEl.textContent = text.substring(0, 8000);
                const preview = text.replace(/<[^>]+>/g, ' ').trim().substring(0, 200);
                statusEl.innerHTML = '<span style="color:#b91c1c">Server JSON əvəzinə HTML/qaytardı (HTTP ' + res.status + '). '
                    + (res.status === 401 ? 'Admin kimi yenidən giriş edin.' : 'Aşağıdakı log-a baxın.') + '</span>'
                    + (preview ? '<br><small>' + preview + '</small>' : '');
                return;
            }
            if (!res.ok && data.error) {
                data.ok = false;
            }
            showResult(data);
        } catch (e) {
            statusEl.innerHTML = '<span style="color:#b91c1c">Şəbəkə xətası: ' + e.message + '</span>';
        } finally {
            setLoading(false);
        }
    }

    document.getElementById('btn-scan').addEventListener('click', function () {
        postForm('scan', new FormData());
    });

    document.getElementById('upload-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const input = document.getElementById('file-input');
        if (!input.files.length) {
            statusEl.innerHTML = '<span style="color:#b91c1c">Ən azı bir fayl seçin</span>';
            return;
        }
        const fd = new FormData();
        for (let i = 0; i < input.files.length; i++) {
            fd.append('files[]', input.files[i]);
        }
        postForm('upload', fd);
    });
})();
</script>
</body>
</html>
