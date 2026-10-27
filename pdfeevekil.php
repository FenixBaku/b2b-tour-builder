<?php

declare(strict_types=1);

require_once __DIR__ . '/auth/_mail_helper.php';
require_once __DIR__ . '/auth/_auth_helper.php';

auth_require_login('/bb/auth/login.php');

$orderJson   = $_POST['order_data_json'] ?? '';
$perPerson   = $_POST['per_person'] ?? '';
$grandTotal  = $_POST['grand_total'] ?? '';

$itineraryHtml = '';
if ($orderJson) {
    $itineraryHtml = build_order_itinerary_html($orderJson);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Tour Itinerary · Destinations Azerbaijan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <style>
        /* ── Reset ── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --white: #ffffff;
            --bg: #f7f8fa;
            --border: #e4e7ec;
            --border-lt: #eef0f4;
            --navy: #0f2744;
            --navy-mid: #1e3a5f;
            --blue-acc: #1a5fa8;
            --text: #1a2332;
            --text-mid: #3d4f63;
            --text-soft: #7888a0;
            --gold: #c79d55;
            --tag-bg: #eaf2fb;
            --tag-text: #1a5fa8;
            --radius: 8px;
        }

        html {
            font-size: 15px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        /* ── Page card ── */
        .page {
            max-width: 720px;
            margin: 32px auto;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(15, 39, 68, .07);
        }

        /* ── Top bar ── */
        .top-bar {
            background: var(--navy);
            padding: 20px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .top-bar-logo img {
            max-height: 36px;
            display: block;
            filter: brightness(0) invert(1) opacity(.9);
        }

        .top-bar-right {
            text-align: right;
        }

        .top-bar-right .eyebrow {
            font-size: 9.5px;
            font-weight: 600;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 3px;
        }

        .top-bar-right .doc-title {
            font-family: 'DM Serif Display', serif;
            font-size: 22px;
            font-weight: 400;
            color: #ffffff;
            line-height: 1;
        }

        .top-bar-right .doc-title em {
            font-style: italic;
            opacity: .85;
        }

        /* ── Slim meta strip ── */
        .meta-strip {
            background: var(--navy-mid);
            padding: 9px 36px;
            display: flex;
            gap: 24px;
        }

        .meta-chip {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: rgba(255, 255, 255, .55);
        }

        .meta-chip::before {
            content: '';
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--gold);
            opacity: .6;
            flex-shrink: 0;
        }

        /* ── Body ── */
        .body {
            padding: 32px 36px 36px;
        }

        /* ── Section heading ── */
        .sec-head {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .sec-head-bar {
            width: 3px;
            height: 16px;
            background: var(--blue-acc);
            border-radius: 2px;
            flex-shrink: 0;
        }

        .sec-head-text {
            font-size: 9.5px;
            font-weight: 600;
            letter-spacing: .18em;
            text-transform: uppercase;
            color: var(--text-soft);
        }

        .sec-head-rule {
            flex: 1;
            height: 1px;
            background: var(--border-lt);
        }

        /* ── Day rows ── */
        .day-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .day-row {
            display: grid;
            grid-template-columns: 52px 1fr;
            gap: 0 16px;
            padding: 14px 0;
            border-bottom: 1px solid var(--border-lt);
        }

        .day-row:last-child {
            border-bottom: none;
        }

        /* Badge */
        .day-badge {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            background: var(--tag-bg);
            border: 1px solid #c8ddf5;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .day-badge .db-label {
            font-size: 7px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--blue-acc);
            line-height: 1;
        }

        .day-badge .db-num {
            font-family: 'DM Serif Display', serif;
            font-size: 17px;
            color: var(--navy);
            line-height: 1.1;
        }

        /* Day content */
        .day-content {}

        .day-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 5px;
            line-height: 1.3;
        }

        .day-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .day-detail {
            display: flex;
            align-items: baseline;
            gap: 7px;
            font-size: 12px;
            color: var(--text-mid);
            font-weight: 300;
            line-height: 1.55;
        }

        .day-detail .ic {
            width: 14px;
            text-align: center;
            font-size: 11px;
            flex-shrink: 0;
            color: var(--text-soft);
            opacity: .8;
        }

        .day-detail strong {
            font-weight: 500;
            color: var(--text);
        }

        /* ── Divider ── */
        .divider {
            height: 1px;
            background: var(--border);
            margin: 28px 0;
        }

        /* ── Pricing ── */
        .pricing-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .price-card {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 18px 22px;
            background: var(--bg);
        }

        .price-card.primary {
            background: var(--navy);
            border-color: var(--navy);
        }

        .pc-label {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--text-soft);
            margin-bottom: 7px;
        }

        .price-card.primary .pc-label {
            color: var(--gold);
        }

        .pc-value {
            font-family: 'DM Serif Display', serif;
            font-size: 28px;
            color: var(--navy);
            line-height: 1;
        }

        .price-card.primary .pc-value {
            color: #ffffff;
        }

        .pc-note {
            font-size: 10.5px;
            color: var(--text-soft);
            margin-top: 5px;
            font-weight: 300;
        }

        .price-card.primary .pc-note {
            color: rgba(255, 255, 255, .4);
        }

        /* ── Footer ── */
        .foot {
            margin-top: 30px;
            padding-top: 18px;
            border-top: 1px solid var(--border-lt);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .foot-brand {
            font-family: 'DM Serif Display', serif;
            font-style: italic;
            font-size: 13px;
            color: var(--blue-acc);
        }

        .foot-meta {
            font-size: 10px;
            color: var(--text-soft);
            letter-spacing: .04em;
        }

        /* ── Empty ── */
        .empty {
            text-align: center;
            padding: 44px 20px;
            color: var(--text-soft);
            font-size: 13px;
            font-style: italic;
        }

        /* ── Print ── */
        @media print {
            body {
                background: #fff;
            }

            .page {
                margin: 0;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }
        }

        /* ── Mobile ── */
        @media (max-width: 540px) {
            .page {
                margin: 0;
                border-radius: 0;
                border-left: none;
                border-right: none;
            }

            .top-bar {
                padding: 16px 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .top-bar-right {
                text-align: left;
            }

            .meta-strip {
                padding: 9px 20px;
            }

            .body {
                padding: 24px 20px 28px;
            }

            .pricing-grid {
                grid-template-columns: 1fr;
            }

            .foot {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="page">

        <!-- Header -->
        <div class="top-bar">
            <div class="top-bar-logo">
                <img src="./destinations_Azerbaijan_logo.png" alt="Destinations Azerbaijan">
            </div>
            <div class="top-bar-right">
                <div class="eyebrow">Official Travel Document</div>
                <div class="doc-title">Tour <em>Itinerary</em></div>
            </div>
        </div>

        <div class="meta-strip">
            <div class="meta-chip">Destinations Azerbaijan</div>
            <div class="meta-chip"><?php echo date('d F Y'); ?></div>
        </div>

        <!-- Content -->
        <div class="body">

            <?php if ($itineraryHtml): ?>
                <div class="sec-head">
                    <div class="sec-head-bar"></div>
                    <div class="sec-head-text">Journey Schedule</div>
                    <div class="sec-head-rule"></div>
                </div>
                <?php echo $itineraryHtml; ?>
            <?php else: ?>
                <div class="empty">Itinerary information was not found.</div>
            <?php endif; ?>

            <?php if (trim((string)$perPerson) !== '' || trim((string)$grandTotal) !== ''): ?>
                <div class="divider"></div>
                <div class="sec-head">
                    <div class="sec-head-bar"></div>
                    <div class="sec-head-text">Pricing Summary</div>
                    <div class="sec-head-rule"></div>
                </div>
                <div class="pricing-grid">
                    <?php if (trim((string)$perPerson) !== ''): ?>
                        <div class="price-card">
                            <div class="pc-label">Per Person Rate</div>
                            <div class="pc-value"><?php echo htmlspecialchars((string)$perPerson, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="pc-note">per traveller</div>
                        </div>
                    <?php endif; ?>
                    <?php if (trim((string)$grandTotal) !== ''): ?>
                        <div class="price-card primary">
                            <div class="pc-label">Grand Total</div>
                            <div class="pc-value"><?php echo htmlspecialchars((string)$grandTotal, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="pc-note">all inclusive</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="foot">
                <span class="foot-brand">Destinations Azerbaijan</span>
                <span class="foot-meta">Confidential · For recipient use only</span>
            </div>

        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>

</html>