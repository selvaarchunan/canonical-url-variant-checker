<?php
// canonical-url-checker-fixed.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Canonical URL Variant Checker — Fixed</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
    body{font-family:Arial,sans-serif;background:#f6f8fb;padding:30px;text-align:center}
    .wrap{background:#fff;max-width:820px;margin:20px auto;padding:24px;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}
    input[type=text]{width:70%;padding:10px;font-size:15px;border:1px solid #ddd;border-radius:6px}
    button{padding:10px 18px;background:#0b74de;color:#fff;border:none;border-radius:6px;cursor:pointer}
    table{width:100%;border-collapse:collapse;margin-top:18px}
    th,td{padding:10px;border-bottom:1px solid #eee;text-align:left;word-break:break-word}
    th{background:#fafafa}
    .pass{color:green;font-weight:600}
    .fail{color:#c53030;font-weight:600}
    .note{color:#666;margin-top:12px;font-size:14px}
</style>
</head>
<body>
<div class="wrap">
    <h2>🌐 Canonical URL Variant Checker — Fixed</h2>
    <form method="post" style="margin-top:12px">
        <input type="text" name="input" placeholder="Enter domain or URL (e.g. example.com or https://example.com)" required>
        <button type="submit">Check Variants</button>
    </form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = trim($_POST['input']);

    // 1) Normalize input: remove protocol, trim path, remove leading/trailing slashes
    $host = preg_replace('~^https?://~i', '', $raw);
    $host = preg_replace('~/.*$~', '', $host);         // remove path
    $host = trim($host, "/ \t\n\r\0\x0B");

    // 2) Remove any accidental leading "www." so we don't end up with www.www.example.com
    if (stripos($host, 'www.') === 0) {
        $host = substr($host, 4);
    }

    // Validate basic host (very simple check)
    if ($host === '' || !preg_match('/^[A-Za-z0-9\.\-]+$/', $host)) {
        echo "<p class='note' style='color:#b00'>Invalid host. Please enter a valid domain like <code>example.com</code>.</p>";
    } else {
        // Build variants in the requested correct order:
        $variants = [
            "http://{$host}",        // 1. HTTP non-www
            "https://{$host}",       // 2. HTTPS non-www
            "http://www.{$host}",    // 3. HTTP www
            "https://www.{$host}"    // 4. HTTPS www
        ];

        // helper: follow redirects and return final URL, status, and error (if any)
        function getFinalUrlInfo($url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_NOBODY, true); // we only need headers
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Canonical-Checker/1.0 (+https://example.com)');
            // Some hosts require verifying SSL; keep it enabled but fallback handling below
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_exec($ch);

            $curlErr = curl_error($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $effective = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

            // If SSL peer verification failed, try again with verification off (but mark as insecure)
            if ($status === 0 && stripos($curlErr, 'ssl') !== false) {
                curl_close($ch);
                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, $url);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch2, CURLOPT_NOBODY, true);
                curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 8);
                curl_setopt($ch2, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch2, CURLOPT_USERAGENT, 'Canonical-Checker/1.0 (+https://example.com)');
                curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
                curl_exec($ch2);
                $curlErr = curl_error($ch2);
                $status = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                $effective = curl_getinfo($ch2, CURLINFO_EFFECTIVE_URL);
                curl_close($ch2);
                $sslInsecure = true;
            } else {
                curl_close($ch);
                $sslInsecure = false;
            }

            // Normalize returned effective URL (if empty)
            if (empty($effective)) {
                $effective = '(no response)';
            }

            return [
                'original' => $url,
                'final'    => $effective,
                'status'   => $status,
                'error'    => $curlErr,
                'ssl_insecure' => $sslInsecure
            ];
        }

        echo "<h3 style='margin-top:18px'>Checking variants for <strong>{$host}</strong></h3>";
        echo "<table><tr><th>Variant</th><th>Final Destination</th><th>Status</th><th>Match</th></tr>";

        // Use HTTPS non-www final destination as canonical reference if it responds; otherwise pick first valid final
        $canonicalRef = null;
        $refInfo = getFinalUrlInfo("https://{$host}");
        if ($refInfo['status'] >= 200 && $refInfo['status'] < 400 && $refInfo['final'] !== '(no response)') {
            $canonicalRef = $refInfo['final'];
        }

        // If HTTPS non-www didn't respond, pick the first variant that did
        if ($canonicalRef === null) {
            foreach ($variants as $v) {
                $infoTmp = getFinalUrlInfo($v);
                if ($infoTmp['status'] >= 200 && $infoTmp['status'] < 400 && $infoTmp['final'] !== '(no response)') {
                    $canonicalRef = $infoTmp['final'];
                    break;
                }
            }
        }

        // As last resort, set canonicalRef to the HTTPS non-www even if no response (to allow comparison)
        if ($canonicalRef === null) {
            $canonicalRef = "https://{$host}";
        }

        foreach ($variants as $v) {
            $info = getFinalUrlInfo($v);

            // Match if final (normalized) equals canonicalRef
            $isMatch = ($info['final'] === $canonicalRef);

            // Build status text
            $statusText = $info['status'] === 0 ? '(no response)' : $info['status'];
            if ($info['ssl_insecure']) {
                $statusText .= ' • SSL(verify skipped)';
            }
            if (!empty($info['error'])) {
                $statusText .= ' • err: ' . htmlspecialchars($info['error']);
            }

            $matchHtml = $isMatch ? "<span class='pass'>✅ Same</span>" : "<span class='fail'>❌ Different</span>";

            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($info['original']) . "</code></td>";
            echo "<td><code>" . htmlspecialchars($info['final']) . "</code></td>";
            echo "<td>" . htmlspecialchars($statusText) . "</td>";
            echo "<td>$matchHtml</td>";
            echo "</tr>";
        }

        echo "</table>";

        echo "<p class='note'><strong>Canonical reference used:</strong> <code>" . htmlspecialchars($canonicalRef) . "</code></p>";
        echo "<p class='note'>If you see <code>(no response)</code> or status 0, the host probably refused/blocked connections or DNS failed. Also make sure your server allows outgoing curl requests.</p>";
    }
}
?>
</div>
</body>
</html>
