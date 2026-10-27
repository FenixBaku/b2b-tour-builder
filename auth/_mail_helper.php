<?php

// PHPMailer autoload (Composer)
// Layihə kökü: c:\xampp\htdocs\bb\vendor\autoload.php
// Yol fərqlidirsə, bu sətri uyğunlaşdırın.
require_once __DIR__ . '/../vendor/autoload.php';
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ─────────────────────────────────────────────
// SMTP helper — məlumatlar config.local.php-dən (SMTP_*), koda yazılmır
// ─────────────────────────────────────────────
function create_mailer(): PHPMailer
{
    if (SMTP_HOST === '' || SMTP_USER === '') {
        throw new Exception('SMTP is not configured (config.local.php: SMTP_HOST, SMTP_USER, SMTP_PASS)');
    }
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = (int) SMTP_PORT === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) SMTP_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(SMTP_FROM ?: SMTP_USER, SMTP_FROM_NAME);
    return $mail;
}

// ─────────────────────────────────────────────
// Shared HTML wrapper
// ─────────────────────────────────────────────
function email_wrap(string $title, string $content): string
{
    $contact = htmlspecialchars(SMTP_FROM ?: SMTP_USER, ENT_QUOTES, 'UTF-8');
    $year = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html lang="az">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{$title}</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f4f8; color: #1a2332; }
    .wrapper { max-width: 620px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.10); }
    .header { background: linear-gradient(135deg, #1a3a5c 0%, #2a6496 100%); padding: 36px 40px; text-align: center; }
    .header img { height: 36px; margin-bottom: 14px; }
    .header-logo-text { color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: 0.5px; }
    .header-logo-sub { color: rgba(255,255,255,0.65); font-size: 13px; margin-top: 4px; }
    .body { padding: 40px 40px 32px; }
    .greeting { font-size: 17px; color: #1a2332; margin-bottom: 20px; }
    .card { background: #f7fafd; border: 1px solid #dde6f0; border-radius: 10px; padding: 28px; margin: 24px 0; }
    .card-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #7a96b0; margin-bottom: 8px; }
    .otp-code { font-size: 44px; font-weight: 800; letter-spacing: 10px; color: #1a3a5c; text-align: center; padding: 10px 0; }
    .otp-note { font-size: 13px; color: #7a96b0; text-align: center; margin-top: 10px; }
    .status-badge { display: inline-block; padding: 6px 18px; border-radius: 20px; font-size: 14px; font-weight: 700; letter-spacing: 0.3px; }
    .status-approved { background: #e6f9f0; color: #1a8754; }
    .status-rejected { background: #fdecea; color: #c0392b; }
    .status-pending  { background: #fff8e1; color: #b7860b; }
    .meta-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #dde6f0; font-size: 14px; }
    .meta-row:last-child { border-bottom: none; }
    .meta-key { color: #7a96b0; font-weight: 600; }
    .meta-val { color: #1a2332; font-weight: 600; text-align: right; }
    .itinerary-section { margin-top: 28px; }
    .itinerary-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #7a96b0; margin-bottom: 14px; }
    .day-block { border-left: 3px solid #2a6496; padding: 12px 16px; margin-bottom: 12px; background: #f7fafd; border-radius: 0 8px 8px 0; }
    .day-title { font-size: 14px; font-weight: 700; color: #1a3a5c; margin-bottom: 8px; }
    .day-detail { font-size: 13px; color: #4a637a; margin: 3px 0; padding-left: 4px; }
    .day-detail span { display: inline-block; width: 18px; }
    .divider { border: none; border-top: 1px solid #dde6f0; margin: 28px 0; }
    .footer { background: #f7fafd; padding: 24px 40px; text-align: center; border-top: 1px solid #dde6f0; }
    .footer p { font-size: 12px; color: #a0b4c8; line-height: 1.7; }
    .footer a { color: #2a6496; text-decoration: none; }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="header">
      <div class="header-logo-text">✈ B2B Tour Builder</div>
      <div class="header-logo-sub">Professional Travel Management</div>
    </div>
    <div class="body">
      {$content}
    </div>
    <div class="footer">
      <p>This email was sent automatically by B2B Tour Builder.<br>
         For questions, contact: <a href="mailto:{$contact}">{$contact}</a></p>
      <p style="margin-top:8px;">© {$year} B2B Tour Builder. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
HTML;
}

// ─────────────────────────────────────────────
// OTP Email
// ─────────────────────────────────────────────
function send_otp_email(string $to, string $otp): bool
{
    try {
        $mail = create_mailer();
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'B2B Tour Builder – Registration confirmation code';

        $content = <<<HTML
<p class="greeting">Hello,</p>
<p style="font-size:15px;color:#4a637a;line-height:1.7;">
  To complete your registration on the B2B Tour Builder platform, please use the
  <strong>one-time confirmation code</strong> below.
</p>
<div class="card">
  <div class="card-label">Your confirmation code</div>
  <div class="otp-code">{$otp}</div>
  <div class="otp-note">⏱ This code is valid for 10 minutes.</div>
</div>
<p style="font-size:13px;color:#7a96b0;line-height:1.7;">
  If you did not request this code, you can safely ignore this email.
</p>
HTML;

        $mail->Body    = email_wrap('Confirmation Code', $content);
        $mail->AltBody = "Your confirmation code is: {$otp}\n\nEnter this code in the registration form.\nThe code is valid for 10 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP email error: " . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()) . "");
        return false;
    }
}

// ─────────────────────────────────────────────
// Order Status Email
// ─────────────────────────────────────────────
function send_order_status_email(
    string  $to,
    int|string $status,
    int|string $orderId,
    ?string $orderName    = null,
    mixed   $totalAmount  = null,
    mixed   $orderDataJson = null
): bool {
    try {
        $mail = create_mailer();
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = 'B2B Tour Builder – Order status';

        $status = (int) $status;
        [$statusText, $badgeClass, $statusIcon] = match ($status) {
            1       => ['Approved',   'status-approved', '✅'],
            2       => ['Rejected',   'status-rejected', '❌'],
            default => ['Pending', 'status-pending',  '⏳'],
        };

        $title = ($orderName && $orderName !== '') ? $orderName : "Order #$orderId";

        // Meta rows
        $metaRows = '';
        $metaRows .= "<div class='meta-row'><span class='meta-key'>Order</span><span class='meta-val'>{$title}</span></div>";
        $metaRows .= "<div class='meta-row'><span class='meta-key'>Order №</span><span class='meta-val'>#{$orderId}</span></div>";
        if ($totalAmount !== null) {
            $amt = number_format((float) $totalAmount, 2, '.', '');
            $metaRows .= "<div class='meta-row'><span class='meta-key'>Total amount</span><span class='meta-val'>${$amt}</span></div>";
        }
        $metaRows .= "<div class='meta-row'><span class='meta-key'>Status</span><span class='meta-val'><span class='status-badge {$badgeClass}'>{$statusIcon} {$statusText}</span></span></div>";

        // Itinerary
        $itineraryHtml = '';
        if ($orderDataJson) {
            $itineraryHtml = build_order_itinerary_html($orderDataJson);
        }

        $content = <<<HTML
<p class="greeting">Hello,</p>
<p style="font-size:15px;color:#4a637a;line-height:1.7;margin-bottom:6px;">
  The status of your order has been updated. You can see the details below.
</p>
<div class="card">
  {$metaRows}
</div>
{$itineraryHtml}
<hr class="divider">
<p style="font-size:14px;color:#4a637a;line-height:1.7;">
  Best regards,<br>
  <strong style="color:#1a3a5c;">B2B Tour Builder team</strong>
</p>
HTML;

        $mail->Body    = email_wrap('Order Status', $content);
        $mail->AltBody = "Hello,\n\nOrder: {$title}\nStatus: {$statusText}\n\nBest regards,\nB2B Tour Builder team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Order status email error: " . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()) . "");
        return false;
    }
}

// ─────────────────────────────────────────────
// Itinerary – HTML version
// ─────────────────────────────────────────────
function build_order_itinerary_html(mixed $orderDataJson): string
{
    if (!$orderDataJson) return '';
    $data = is_array($orderDataJson) ? $orderDataJson : json_decode($orderDataJson, true);
    if (!is_array($data)) return '';

    $basics = $data['basics'] ?? [];
    $days   = isset($data['days']) && is_array($data['days']) ? $data['days'] : [];

    $html = '<div class="itinerary-section"><div class="itinerary-title">🗺 Order Details</div>';

    // Basics card
    if (!empty($basics)) {
        $checkin  = $basics['checkin']  ?? '';
        $checkout = $basics['checkout'] ?? '';
        $adults   = (int)($basics['adults']   ?? 0);
        $children = (int)($basics['children'] ?? 0);

        $html .= '<div class="card" style="margin-bottom:16px;">';
        if ($checkin || $checkout) {
            $html .= "<div class='meta-row'><span class='meta-key'>Dates</span><span class='meta-val'>" . ($checkin ?: '–') . " → " . ($checkout ?: '–') . "</span></div>";
        }
        if ($adults || $children) {
            $html .= "<div class='meta-row'><span class='meta-key'>Guests</span><span class='meta-val'>{$adults} adults, {$children} children</span></div>";
        }   
        $html .= '</div>';
    }

    // Day blocks
    foreach ($days as $idx => $d) {
        if (!is_array($d)) continue;
        $dayNo  = $idx + 1;
        $date   = $d['date'] ?? '';
        $city   = $d['city'] ?? '';
        $header = "Day {$dayNo}" . ($date ? " · {$date}" : '') . ($city ? " — {$city}" : '');

        $details = '';

        if (!empty($d['hotel'])) {
            $room = $d['roomType'] ?? '';
            $details .= "<div class='day-detail'>🏨 <span></span><strong>Hotel:</strong> " . htmlspecialchars($d['hotel']) . ($room ? " / {$room}" : '') . "</div>";
        }

        if (!empty($d['transferA']) && !empty($d['transferB'])) {
            $svc = $d['transferService'] ?? '';
            $details .= "<div class='day-detail'>🚌 <span></span><strong>Transfer:</strong> " . htmlspecialchars($d['transferA']) . " → " . htmlspecialchars($d['transferB']) . ($svc ? " ({$svc})" : '') . "</div>";
        }

        // Vehicles
        $veh = [];
        if (!empty($d['vehicles']) && is_array($d['vehicles'])) {
            $veh = array_filter($d['vehicles']);
        } elseif (!empty($d['vehicle'])) {
            $veh = [$d['vehicle']];
        }
        if ($veh) {
            $details .= "<div class='day-detail'>🚗 <span></span><strong>Transportation:</strong> " . htmlspecialchars(implode(', ', $veh)) . "</div>";
        }

        // Activities
        $acts = []; 
        if (!empty($d['activities']) && is_array($d['activities'])) {
            foreach ($d['activities'] as $a) {
                if (!$a) continue;
                $tour = $a['tour'] ?? '';
                $entr = $a['entrance'] ?? '';
                if ($tour && $entr) $acts[] = "{$tour} | {$entr}";
                elseif ($tour) $acts[] = $tour;
                elseif ($entr) $acts[] = $entr;
            }
        } elseif (!empty($d['tour'])) {
            $acts[] = $d['tour'];
        }
        if ($acts) {
            $actsHtml = implode('</li><li style="margin:2px 0;">', array_map('htmlspecialchars', $acts));
            $details .= "<div class='day-detail'>🎯 <span></span><strong>Activities:</strong><ul style='margin:4px 0 0 22px;padding:0;color:#4a637a;font-size:13px;'><li style='margin:2px 0;'>{$actsHtml}</li></ul></div>";
        }

        // Meals
        if (!empty($d['meals']) && is_array($d['meals'])) {
            $lunch  = $d['meals']['lunch']  ?? [];
            $dinner = $d['meals']['dinner'] ?? [];
            $lunVals = !empty($lunch['options'])  ? array_filter($lunch['options'])  : (!empty($lunch['option'])  ? [$lunch['option']]  : []);
            $dinVals = !empty($dinner['options']) ? array_filter($dinner['options']) : (!empty($dinner['option']) ? [$dinner['option']] : []);
            if ($lunVals || $dinVals) {
                $l = $lunVals ? htmlspecialchars(implode(', ', $lunVals)) : '–';
                $dn = $dinVals ? htmlspecialchars(implode(', ', $dinVals)) : '–';
                $details .= "<div class='day-detail'>🍽 <span></span><strong>Meals:</strong> Lunch: {$l} | Dinner: {$dn}</div>";
            }
        }

        $html .= <<<HTML
<div class="day-block">
  <div class="day-title">{$header}</div>
  {$details}
</div>
HTML;
    }

    $html .= '</div>';
    return $html;
}

// ─────────────────────────────────────────────
// Plain-text itinerary (kept for backward compat)
// ─────────────────────────────────────────────
function build_order_itinerary_text(mixed $orderDataJson): string
{
    if (!$orderDataJson) return '';
    $data = is_array($orderDataJson) ? $orderDataJson : json_decode($orderDataJson, true);
    if (!is_array($data)) return '';

    $basics = $data['basics'] ?? [];
    $days   = isset($data['days']) && is_array($data['days']) ? $data['days'] : [];
    $lines  = [];

    if (!empty($basics)) {
        $checkin  = $basics['checkin']  ?? '';
        $checkout = $basics['checkout'] ?? '';
        if ($checkin || $checkout) {
            $lines[] = "Dates: " . ($checkin ?: '–') . " → " . ($checkout ?: '–');
        }
        if (!empty($basics['adults']) || !empty($basics['children'])) {
            $lines[] = "Pax: " . (int)($basics['adults'] ?? 0) . " adults, " . (int)($basics['children'] ?? 0) . " children";
        }
        if (!empty($lines)) $lines[] = str_repeat('-', 32);
    }

    foreach ($days as $idx => $d) {
        if (!is_array($d)) continue;
        $dayNo = $idx + 1;
        $lines[] = "Day $dayNo: " . trim(($d['date'] ?? '') . ' – ' . ($d['city'] ?? ''));
        if (!empty($d['hotel'])) {
            $room = $d['roomType'] ?? '';
            $lines[] = "  Hotel: " . $d['hotel'] . ($room ? " / $room" : '');
        }
        if (!empty($d['transferA']) && !empty($d['transferB'])) {
            $svc = $d['transferService'] ?? '';
            $lines[] = "  Transfer: " . $d['transferA'] . " → " . $d['transferB'] . ($svc ? " ($svc)" : '');
        }
        $lines[] = '';
    }

    return trim(implode("\n", $lines));
}