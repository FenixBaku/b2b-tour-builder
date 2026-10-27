<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/tour_config_functions.php';
require_once __DIR__ . '/auth/_auth_helper.php';

auth_require_login('/bb/auth/login.php?redirect=' . urlencode('/bb/tour-builder.php'));
auth_start_session();
$userLoggedIn = auth_user_logged_in();
$currentUser = auth_get_user();

$pdo = dbConnect();
$initialTourConfig = [];
if ($pdo) {
    $initialTourConfig = getTourConfig($pdo, null);
}
$activitiesByCity = $pdo ? fetchActivitiesByCity($pdo) : [];

?>
<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Destinations Azerbaijan | B2B Tour Builder</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script>
        var ACTIVITIES_BY_CITY = <?php echo json_encode($activitiesByCity, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        var INITIAL_TOUR_CONFIG = <?php echo json_encode($initialTourConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        var USER_LOGGED_IN = <?php echo $userLoggedIn ? 'true' : 'false'; ?>;
    </script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#2463eb",
                        "background-light": "#f6f6f8",
                        "background-dark": "#111621",
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
    <style>
        :root {
            --primary: #2463eb;
            --primary-glow: rgba(36, 99, 235, 0.15);
            --primary-border: rgba(36, 99, 235, 0.3);
            --surface: #ffffff;
            --surface-2: #f8faff;
            --border: #e8ecf4;
            /* Inputlar üçün bir az tünd border — açıq fonda sahə sərhədi görünsün */
            --border-input: #dbe2ee;
            --text-main: #0f172a;
            --text-sub: #64748b;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #eef3ff 0%, #f6f8ff 40%, #fafbff 100%);
            font-family: 'Montserrat', sans-serif;
            color: var(--text-main);
            min-height: 100vh;
        }

        /* === HEADER === */
        header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 20px rgba(36, 99, 235, 0.07);
        }

        header h1 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--text-main);
            font-size: 1.1rem;
        }

        .header-logo {
            background: linear-gradient(135deg, #2463eb, #4f8ef7);
            box-shadow: 0 4px 12px rgba(36, 99, 235, 0.35);
            border-radius: 10px;
            padding: 8px;
        }

        .btn-save {
            background: linear-gradient(135deg, #2463eb, #1d4fd8);
            color: white;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            display: flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(36, 99, 235, 0.4);
            transition: all 0.2s ease;
        }

        .btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(36, 99, 235, 0.5);
        }

        .btn-save-user {
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
            cursor: pointer;
        }

        .btn-drafts {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-sub);
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-drafts:hover {
            background: var(--primary-glow);
            color: var(--primary);
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 12px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            color: #dc2626;
            background: #fff;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.2s, border-color 0.2s, color 0.2s;
        }

        .btn-logout:hover,
        .btn-logout:focus-visible {
            border-color: #fca5a5;
            color: #b91c1c;
            background: #fef2f2;
            outline: none;
        }

        /* === STEPPER === */
        .stepper-wrap {
            background: white;
            border-radius: 16px;
            padding: 20px 32px;
            box-shadow: 0 2px 20px rgba(36, 99, 235, 0.08), 0 1px 4px rgba(0, 0, 0, 0.04);
            max-width: 560px;
            margin: 0 auto;
            border: 1px solid var(--border);
        }

        .step-circle-active {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2463eb, #4f8ef7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(36, 99, 235, 0.4);
        }

        .step-circle-inactive {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f1f5f9;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            border: 2px solid #e2e8f0;
        }



        .step-label-active {
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-top: 6px;
        }

        .step-label-inactive {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-top: 6px;
        }

        .step-line {
            height: 2px;
            background: linear-gradient(90deg, #e2e8f0, #e2e8f0);
            flex: 1;
            margin-bottom: 24px;
            border-radius: 2px;
        }

        /* === SECTIONS === */
        .card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 2px 24px rgba(36, 99, 235, 0.07), 0 1px 4px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2463eb, #60a5fa, #2463eb);
            background-size: 200% 100%;
            animation: shimmer 3s ease infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 14px;
            margin-bottom: 24px;
        }

        .section-icon {
            width: 32px;
            height: 32px;
            background: var(--primary-glow);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-main);
        }

        /* === FORM INPUTS === */
        .form-input-compact {
            display: block;
            width: 100%;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: var(--surface-2);
            font-size: 0.85rem;
            font-family: 'Montserrat', sans-serif;
            font-weight: 500;
            color: var(--text-main);
            padding: 8px 12px;
            transition: all 0.2s ease;
            outline: none;
            margin-top: 4px;
            appearance: auto;
        }

        .form-input-compact:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .form-input-compact:disabled {
            background: #f1f5f9;
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .form-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: var(--text-sub);
        }

        .form-input-compact1 {
            width: 100%;
        }

        /*
         * Vahid form elementləri. Tailwind "forms" plugini [type='date'], [type='number'] üçün öz stilini
         * (kvadrat künc, tünd border, 16px şrift) bizim class-dan sonra yükləyir; :is(input, select) ilə
         * spesifiklik artırılır ki, bütün inputlar və select-lər eyni görünsün.
         */
        :is(input, select, textarea).form-input-compact {
            height: 40px;
            margin-top: 6px;
            padding: 0 12px;
            border: 1.5px solid var(--border-input);
            border-radius: 10px;
            background-color: var(--surface-2);
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            line-height: 1.25;
            color: var(--text-main);
            box-shadow: none;
        }

        textarea.form-input-compact {
            height: auto;
            min-height: 80px;
            padding: 10px 12px;
        }

        select.form-input-compact:not([multiple]) {
            appearance: none;
            -webkit-appearance: none;
            padding-right: 34px;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.6' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
        }

        :is(input, select, textarea).form-input-compact:hover:not(:disabled):not([readonly]) {
            border-color: #c7d2e5;
        }

        :is(input, select, textarea).form-input-compact:focus {
            border-color: var(--primary);
            background-color: #fff;
            box-shadow: 0 0 0 3px var(--primary-glow);
            outline: none;
        }

        :is(input, select).form-input-compact:disabled,
        input.form-input-compact[readonly] {
            background-color: #f1f5f9;
            color: var(--text-sub);
            cursor: default;
        }

        input.form-input-compact[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            opacity: 0.6;
        }

        input.form-input-compact[readonly][type="date"]::-webkit-calendar-picker-indicator {
            display: none;
        }

        /* Step 1: label + kontrol cütləri (tarixlər, qonaqlar) */
        .field-pair {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .field-check {
            display: flex;
            align-items: center;
            gap: 10px;
            height: 40px;
            margin-top: 6px;
            padding: 0 12px;
            border: 1.5px solid var(--border-input);
            border-radius: 10px;
            background: var(--surface-2);
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-main);
            cursor: pointer;
        }

        .field-check:hover {
            border-color: #c7d2e5;
        }

        .field-check:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        /* === DAY ROW GRID (Step 2) ===
         * Hər gün kartı grid-dir: ekran enindən asılı olaraq 1 / 2 / 3 / 6 sütun.
         * Otaq tipi və otaq sayı .day-row-un birbaşa uşaqları qalmalıdır (collectDayRooms :scope selektoru).
         */
        #days-container {
            counter-reset: day;
        }

        #days-container .day-row {
            counter-increment: day;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 14px 16px;
            align-items: start;
            padding: 16px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        #days-container .day-row:focus-within {
            border-color: var(--primary-border);
            box-shadow: 0 4px 18px rgba(36, 99, 235, 0.08);
        }

        #days-container .day-row + .day-row {
            margin-top: 12px;
        }

        .day-row .form-label {
            display: block;
            font-size: 0.62rem;
            line-height: 16px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .day-row .day-field--date .form-label::after {
            content: " · Day " counter(day);
            color: var(--primary);
        }

        .day-row .day-room-capacity-hint {
            display: block;
            margin-top: 4px;
            font-size: 0.62rem;
            line-height: 1.3;
            color: var(--text-sub);
        }

        .day-row .day-room-capacity-hint:empty {
            display: none;
        }

        @media (min-width: 640px) {
            #days-container .day-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .day-row .day-field--wide {
                grid-column: span 2;
            }
        }

        @media (min-width: 1024px) {
            #days-container .day-row {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 1440px) {
            #days-container .day-row {
                grid-template-columns: repeat(6, minmax(0, 1fr));
            }
        }

        /* Modal açan sahələr (hotel extras, transfers, tours): input kimi görünən düymə + seçimlərin xülasəsi */
        .day-field-box {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            min-height: 40px;
            margin-top: 6px;
            padding: 6px 10px 6px 12px;
            border: 1.5px solid var(--border-input);
            border-radius: 10px;
            background: var(--surface-2);
            font-family: 'Montserrat', sans-serif;
            font-size: 0.8rem;
            font-weight: 500;
            line-height: 1.3;
            text-align: left;
            color: var(--text-muted);
            cursor: pointer;
            transition: border-color 0.2s, background-color 0.2s, box-shadow 0.2s;
        }

        .day-field-box:hover {
            border-color: var(--primary-border);
            background: #fff;
        }

        .day-field-box:focus-visible {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .day-field-box.has-value {
            color: var(--text-main);
        }

        .day-field-box .box-icon {
            flex-shrink: 0;
            font-size: 18px;
            color: var(--primary);
        }

        .day-field-box .box-text {
            flex: 1;
            min-width: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            overflow-wrap: anywhere;
        }

        .day-field-box .box-action {
            flex-shrink: 0;
            font-size: 18px;
            color: var(--text-muted);
        }

        .day-field-box:hover .box-action {
            color: var(--primary);
        }

        .day-add-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            width: 100%;
            height: 40px;
            margin-top: 6px;
            border: 1.5px dashed var(--primary-border);
            border-radius: 10px;
            background: transparent;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            color: var(--primary);
            cursor: pointer;
            transition: background-color 0.2s, border-color 0.2s;
        }

        .day-add-btn:hover {
            background: var(--primary-glow);
            border-color: var(--primary);
        }

        .day-additional-room-row {
            margin-top: 8px;
        }

        /* Modal təsdiq düymələri (Confirm transfers / extras / selection) */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 38px;
            padding: 0 18px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #2463eb, #1d4fd8);
            font-size: 0.8rem;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(36, 99, 235, 0.35);
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(36, 99, 235, 0.45);
        }

        .btn-primary:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px var(--primary-glow), 0 4px 14px rgba(36, 99, 235, 0.35);
        }

        /* Activity siyahısı: mətn solda, Tickets sağda eyni xətt üzrə */
        #activities-modal-list label > span {
            flex: 1;
            min-width: 0;
        }

        #activities-modal-list label > div {
            flex-shrink: 0;
        }

        #activities-modal-list label input[type="number"] {
            width: 72px;
            height: 34px;
            margin-top: 0;
        }

        /* Modal daxilində axtarış (Activity siyahısı) */
        .modal-search {
            position: relative;
            display: flex;
            align-items: center;
        }

        .modal-search .material-symbols-outlined {
            position: absolute;
            left: 12px;
            font-size: 18px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .modal-search input {
            width: 100%;
            height: 40px;
            padding: 0 90px 0 38px;
            border: 1.5px solid var(--border-input);
            border-radius: 10px;
            background: var(--surface-2);
            font-family: 'Montserrat', sans-serif;
            font-size: 0.85rem;
            color: var(--text-main);
            outline: none;
            box-shadow: none;
        }

        .modal-search input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px var(--primary-glow);
        }

        .modal-search-count {
            position: absolute;
            right: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            pointer-events: none;
        }

        .day-additional-room-line {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 60px 32px;
            gap: 6px;
            align-items: center;
        }

        .day-additional-room-row .form-input-compact {
            margin-top: 0;
        }

        .day-additional-room-row input.day-room-count-input {
            padding: 0 8px;
        }

        .day-remove-room-type {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: #ef4444;
            background: transparent;
            border: none;
            cursor: pointer;
        }

        .day-remove-room-type:hover,
        .day-remove-room-type:focus-visible {
            background: #fef2f2;
            color: #b91c1c;
            outline: none;
        }

        .day-remove-room-type .material-symbols-outlined {
            font-size: 18px;
        }

        /* === CHECKBOX === */
        input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
            accent-color: var(--primary);
            cursor: pointer;
        }

        /* === STEP 2 DAILY PLAN ROWS — styles in the "DAY ROW GRID" block below === */

        /* Multi-select: selection with checkboxes – each product as checkbox */
        .multi-select-checkbox-wrap {
            position: relative;
        }

        .multi-select-checkbox-wrap select.multi-select-native {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
        }

        .multi-select-checkboxes {
            max-height: 180px;
            overflow-y: auto;
            padding: 8px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: var(--surface-2);
            margin-top: 4px;
        }

        .multi-select-checkboxes:focus-within {
            border-color: var(--primary);
            background: white;
        }

        .multi-select-checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-main);
        }

        .multi-select-checkbox-item:hover {
            background: rgba(36, 99, 235, 0.06);
            margin: 0 -8px;
            padding: 6px 8px;
        }

        .multi-select-checkbox-item input[type="checkbox"] {
            flex-shrink: 0;
            width: 16px;
            height: 16px;
            accent-color: var(--primary);
        }

        /* Meal checkboxes */
        .meal-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--text-sub);
            cursor: pointer;
        }

        .meal-select {
            display: block;
        }

        .meal-select.hidden {
            display: none;
        }

        /* === ADD DAY / COPY BTNS === */
        .btn-add-day {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            font-weight: 800;
            color: var(--primary);
            background: var(--primary-glow);
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid var(--primary-border);
            cursor: pointer;
            letter-spacing: 0.05em;
            transition: all 0.2s;
        }

        .btn-add-day:hover {
            background: rgba(36, 99, 235, 0.22);
        }

        .btn-add-transfer {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.65rem;
            font-weight: 800;
            color: var(--primary);
            background: var(--primary-glow);
            padding: 4px 10px;
            border-radius: 8px;
            border: 1px solid var(--primary-border);
            cursor: pointer;
            letter-spacing: 0.04em;
            transition: all 0.2s;
        }

        .btn-add-transfer:hover {
            background: rgba(36, 99, 235, 0.22);
        }

        .day-transfer-row {
            display: flex;
            flex-direction: row;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .day-transfers-wrap .day-transfer-field {
            width: 180px !important;
        }

        .day-transfers-list.hidden-storage {
            display: none !important;
        }

        .modal-transfer-row {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 3fr) auto;
            gap: 12px;
            align-items: start;
            padding: 12px;
            background: var(--surface-2);
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .modal-transfer-row .day-transfer-field {
            width: auto !important;
        }

        .modal-transfer-row .day-transfer-remove {
            align-self: center;
        }

        .day-transfer-duty-input.is-invalid {
            border-color: #dc2626;
        }

        .day-transfer-duty-info {
            font-size: 0.7rem;
            line-height: 1.4;
            color: #4b5563;
            padding-top: 18px;
            min-width: 0;
        }

        .day-transfer-duty-info .duty-info-route {
            font-weight: 600;
            color: #1f2937;
        }

        .day-transfer-duty-info .duty-info-muted {
            color: #6b7280;
            overflow-wrap: anywhere;
        }

        .day-transfer-duty-info .duty-info-price {
            font-weight: 600;
            color: #047857;
        }

        .day-transfer-duty-info .duty-info-warn,
        .day-transfer-duty-info.is-error {
            color: #b45309;
        }

        .day-transfer-duty-info.is-error {
            color: #dc2626;
        }

        .modal-hotel-extra-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 12px;
            align-items: end;
            padding: 12px;
            background: var(--surface-2);
            border-radius: 10px;
            border: 1px solid var(--border);
        }

        .day-hotel-extras-list.hidden-storage {
            display: none !important;
        }

        @media (max-width: 768px) {
            .modal-transfer-row {
                grid-template-columns: 1fr;
            }

            .day-transfer-duty-info {
                padding-top: 0;
            }

            .modal-hotel-extra-row {
                grid-template-columns: 1fr;
            }
        }

        .btn-copy {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--text-sub);
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            letter-spacing: 0.05em;
            transition: all 0.2s;
        }

        .btn-copy:hover {
            background: #e2e8f0;
        }

        /* === SUMMARY SECTION === */
        .summary-card {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #162040 100%);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 8px 40px rgba(15, 23, 42, 0.35), 0 2px 8px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.06);
            position: relative;
            overflow: hidden;
        }

        .summary-card::after {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(36, 99, 235, 0.18) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .summary-icon-bg {
            position: absolute;
            bottom: -20px;
            right: -10px;
            opacity: 0.04;
            font-size: 9rem !important;
            pointer-events: none;
        }

        .summary-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 14px;
            margin-bottom: 24px;
        }

        .summary-section-icon {
            width: 32px;
            height: 32px;
            background: rgba(36, 99, 235, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .summary-section-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: rgba(255, 255, 255, 0.85);
        }

        .summary-table {
            width: 100%;
            font-size: 0.85rem;
            text-align: left;
        }

        .summary-th {
            color: rgba(255, 255, 255, 0.35);
            text-transform: uppercase;
            font-size: 0.63rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        }

        .summary-td-cat {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 600;
            padding: 12px 0 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .summary-td-desc {
            color: rgba(255, 255, 255, 0.45);
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .summary-td-qty {
            color: rgba(255, 255, 255, 0.7);
            text-align: right;
            font-weight: 600;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .summary-td-price {
            color: rgba(255, 255, 255, 0.8);
            text-align: right;
            font-weight: 600;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .price-box {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid rgba(255, 255, 255, 0.07);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            backdrop-filter: blur(10px);
        }

        .per-person-label {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.78rem;
            font-weight: 500;
        }

        .per-person-val {
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.03em;
        }

        .grand-total-label {
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255, 255, 255, 0.35);
        }

        .grand-total-val {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            color: #60a5fa;
            letter-spacing: -0.04em;
            line-height: 1;
        }

        .btn-pdf {
            width: 100%;
            background: linear-gradient(135deg, #2463eb, #1d4fd8);
            color: white;
            padding: 14px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.8rem;
            letter-spacing: 0.06em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(36, 99, 235, 0.45);
            transition: all 0.2s;
        }

        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(36, 99, 235, 0.55);
        }

        .btn-send {
            width: 100%;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.6);
            padding: 11px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-send:hover {
            background: rgba(255, 255, 255, 0.07);
            color: white;
            border-color: rgba(255, 255, 255, 0.25);
        }

        /* === FOOTER === */
        footer {
            color: var(--text-muted);
            font-size: 0.72rem;
            text-align: center;
            margin: 48px 0 32px;
            letter-spacing: 0.03em;
        }

        /* === ANIMATIONS === */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        main>* {
            animation: fadeUp 0.5s ease forwards;
        }

        main>*:nth-child(1) {
            animation-delay: 0.05s;
            opacity: 0;
        }

        main>*:nth-child(2) {
            animation-delay: 0.12s;
            opacity: 0;
        }

        main>*:nth-child(3) {
            animation-delay: 0.19s;
            opacity: 0;
        }

        main>*:nth-child(4) {
            animation-delay: 0.26s;
            opacity: 0;
        }

        #day1-meals {
            margin-top: 20px;
        }

        /* === RESPONSIVE: planşet və telefon === */
        .section-header {
            flex-wrap: wrap;
            row-gap: 10px;
        }

        @media (max-width: 767px) {
            .app-header {
                flex-wrap: wrap;
                gap: 10px;
                padding: 10px 16px;
            }

            .app-header h1 {
                font-size: 0.95rem;
            }

            .app-header .header-sub {
                display: none;
            }

            .app-header > div:last-child {
                width: 100%;
                justify-content: space-between;
                gap: 6px;
            }

            .app-header .btn-drafts {
                padding: 6px 8px;
                font-size: 0.78rem;
                white-space: nowrap;
            }

            .app-header .btn-save-user {
                padding: 7px 12px;
                font-size: 0.75rem;
                white-space: nowrap;
            }

            .app-header .btn-logout {
                padding: 7px 9px;
            }

            .app-header .logout-text {
                display: none;
            }

            main.max-w-9xl {
                padding: 16px;
            }

            .card,
            .summary-card {
                padding: 18px 16px;
                border-radius: 16px;
            }

            .stepper-wrap {
                padding: 16px;
            }

            #days-container .day-row {
                padding: 14px 12px;
            }
        }
    </style>
    <!-- Axtarışlı select-lər (assets/searchable-select.js) -->
    <link rel="stylesheet" href="/bb/assets/searchable-select.css?v=<?php echo @filemtime(__DIR__ . '/assets/searchable-select.css'); ?>" />
    <script src="/bb/assets/searchable-select.js?v=<?php echo @filemtime(__DIR__ . '/assets/searchable-select.js'); ?>" defer></script>
</head>

<body>
    <!-- Header -->
    <header class="app-header sticky top-0 z-50 px-6 py-3 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="header-logo text-white">
                <span class="material-symbols-outlined text-lg">travel_explore</span>
            </div>
            <h1>
                Destinations Azerbaijan <span class="header-sub" style="color:#cbd5e1; font-weight:300; padding:0 8px;">|</span> <span class="header-sub" style="color:var(--primary);">B2B Tour Builder</span>
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <a href="user-panel.php" class="btn-drafts text-decoration-none" style="text-decoration:none;">My Orders</a>
            <?php if (auth_is_admin_or_assistant()): ?>
                <a href="tour-admin.php" class="btn-drafts text-decoration-none" style="text-decoration:none;">Admin</a>
            <?php endif; ?>
            <!-- <button class="btn-drafts">Drafts</button>
            <button class="btn-save" id="btn-save-draft">
                <span class="material-symbols-outlined" style="font-size:16px;">save</span>
                Save Draft
            </button> -->
            <button class="btn-save-user" id="btn-save-order" style="display:inline-flex; background:#059669; color:white;">
                <span class="material-symbols-outlined" style="font-size:16px;">cloud_upload</span>
                Save Order
            </button>
            <a href="auth/logout.php" class="btn-logout" aria-label="Log out">
                <span class="material-symbols-outlined" aria-hidden="true" style="font-size:18px;">logout</span>
                <span class="logout-text">Log out</span>
            </a>
        </div>
    </header>

    <main class="max-w-9xl mx-auto p-6 space-y-6">

        <!-- Progress Stepper -->
        <div class="stepper-wrap flex items-center justify-between">
            <div class="flex flex-1 flex-col items-center gap-0">
                <div class="step-circle-active">1</div>
                <span class="step-label-active">Basics</span>
            </div>
            <div class="step-line"></div>
            <div class="flex flex-1 flex-col items-center gap-0">
                <div class="step-circle-inactive">2</div>
                <span class="step-label-inactive">Itinerary</span>
            </div>
            <div class="step-line"></div>
            <div class="flex flex-1 flex-col items-center gap-0">
                <div class="step-circle-inactive">3</div>
                <span class="step-label-inactive">Services</span>
            </div>
            <div class="step-line"></div>
            <div class="flex flex-1 flex-col items-center gap-0">
                <div class="step-circle-inactive">4</div>
                <span class="step-label-inactive">Summary</span>
            </div>
        </div>

        <!-- Section 1: Tour Basics -->
        <section class="card">
            <div class="section-header">
                <div class="section-icon">
                    <span class="material-symbols-outlined text-primary" style="font-size:18px;">info</span>
                </div>
                <h2 class="section-title">Step 1: Tour Basics</h2>
            </div>
            <!-- Sətir-sətir düzülüş: hər sətirdə label-lər və kontrollar eyni hündürlükdədir -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-5">
                <!-- Row 1 -->
                <label class="block">
                    <span class="form-label">Nationality</span>
                    <select class="form-input-compact" data-admin-key="countries">
                        <option>Select Country (87 available)</option>
                    </select>
                </label>
                <div class="field-pair">
                    <label class="block">
                        <span class="form-label">Check-in</span>
                        <input class="form-input-compact" type="date" data-basics="checkin" />
                    </label>
                    <label class="block">
                        <span class="form-label">Check-out</span>
                        <input class="form-input-compact" type="date" data-basics="checkout" />
                    </label>
                </div>
                <label class="block">
                    <span class="form-label">Water Package</span>
                    <select class="form-input-compact" data-admin-key="water">
                    </select>
                </label>

                <!-- Row 2 -->
                <label class="block">
                    <span class="form-label">Visit To</span>
                    <select class="form-input-compact" disabled="">
                        <option>Azerbaijan</option>
                    </select>
                </label>
                <div class="field-pair">
                    <label class="block">
                        <span class="form-label">Adults</span>
                        <input class="form-input-compact" min="1" type="number" value="1" data-basics="adults" />
                    </label>
                    <label class="block">
                        <span class="form-label">Children</span>
                        <input class="form-input-compact" min="0" type="number" value="0" data-basics="children" />
                    </label>
                </div>
                <label class="block">
                    <span class="form-label">Guide / Driver</span>
                    <select class="form-input-compact" data-admin-key="guides">
                    </select>
                </label>

                <!-- Row 3 -->
                <label class="block">
                    <span class="form-label">Visa Type</span>
                    <select class="form-input-compact" data-admin-key="visa">
                    </select>
                </label>
                <label class="block">
                    <span class="form-label">Vehicle Type</span>
                    <select class="form-input-compact" data-admin-key="vehicles">
                    </select>
                </label>
                <div class="block">
                    <span class="form-label">Travel Insurance</span>
                    <label class="field-check" for="insurance">
                        <input id="insurance" type="checkbox" />
                        <span>Include travel insurance</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- Section 2: Itinerary Builder -->
        <section class="card">
            <div class="section-header justify-between">
                <div class="flex items-center gap-2">
                    <div class="section-icon">
                        <span class="material-symbols-outlined text-primary" style="font-size:18px;">calendar_today</span>
                    </div>
                    <h2 class="section-title">Step 2: Tour Itinerary Details</h2>
                </div>
                <div class="flex gap-2">
                    <button class="btn-add-day">
                        <span class="material-symbols-outlined" style="font-size:16px;">add</span> ADD DAY
                    </button>
                    <button class="btn-copy">
                        <span class="material-symbols-outlined" style="font-size:16px;">content_copy</span> COPY PREVIOUS
                    </button>
                </div>
            </div>

            <!-- Days Container -->
            <div id="days-container" class="mt-2">
                <!-- Gün kartı: grid (CSS "DAY ROW GRID"). Otaq tipi və otaq sayı .day-row-un birbaşa uşaqlarıdır. -->
                <div class="day-row">

                    <!-- Tarix (avtomatik - check-in/checkout-dan) -->
                    <label class="block day-field day-field--date">
                        <span class="form-label">Date</span>
                        <input type="date" class="form-input-compact day-date-input" readonly title="Automatically filled from Check-in / Check-out" />
                    </label>

                    <!-- Region / City -->
                    <label class="block day-field">
                        <span class="form-label">Region / City</span>
                        <select class="form-input-compact day-city-select">
                            <option value="" selected>No select</option>
                            <?php foreach (($initialTourConfig['cities'] ?? [CITY_DEFAULT_CODE]) as $cityCode): ?>
                                <option value="<?php echo htmlspecialchars($cityCode); ?>" data-keywords="<?php echo htmlspecialchars(CITY_REGISTRY[$cityCode]['az'] . ' ' . implode(' ', CITY_REGISTRY[$cityCode]['aliases'])); ?>"><?php echo htmlspecialchars(CITY_REGISTRY[$cityCode]['name'] . ' (' . $cityCode . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <!-- Hotel -->
                    <div class="block day-field">
                        <span class="form-label">Hotel name</span>
                        <select class="form-input-compact" data-admin-key="hotels"></select>
                    </div>
                    <!-- Room type -->
                    <div class="block day-field">
                        <span class="form-label">Room type</span>
                        <select class="form-input-compact day-room-type-select" data-admin-key="hotel-room-type"></select>
                    </div>
                    <!-- Room count (hotel prices are per room, not per guest) -->
                    <label class="block day-field">
                        <span class="form-label">Room count</span>
                        <input type="number" min="1" step="1" value="1" class="form-input-compact day-room-count-input" />
                        <span class="day-room-capacity-hint"></span>
                    </label>
                    <div class="day-additional-rooms block day-field">
                        <span class="form-label">More room types</span>
                        <button type="button" class="day-add-room-type day-add-btn">
                            <span class="material-symbols-outlined" style="font-size:16px;" aria-hidden="true">add</span> Add room type
                        </button>
                        <div class="day-additional-rooms-list"></div>
                    </div>
                    <!-- Hotel extras (modal ilə idarə) -->
                    <div class="day-hotel-extras-wrap block day-field">
                        <span class="form-label">Hotel extras</span>
                        <button type="button" class="day-hotel-extras-modal-toggle day-field-box" aria-label="Manage hotel extras">
                            <span class="material-symbols-outlined box-icon" aria-hidden="true">hotel</span>
                            <span class="box-text day-hotel-extras-summary">Add hotel extras</span>
                            <span class="material-symbols-outlined box-action" aria-hidden="true">edit</span>
                        </button>
                        <div class="day-hotel-extras-list hidden-storage" aria-hidden="true">
                            <div class="day-hotel-extra-row flex gap-2">
                                <select class="form-input-compact flex-1" data-admin-key="hotel-extras"></select>
                                <input type="number" min="0" step="1" class="form-input-compact w-14 day-hotel-extra-qty" placeholder="Qty" />
                            </div>
                        </div>
                    </div>
                    <!-- Transfers (modal ilə idarə) -->
                    <div class="day-transfers-wrap block day-field day-field--wide">
                        <span class="form-label">Transfers</span>
                        <button type="button" class="day-transfers-modal-toggle day-field-box" aria-label="Manage transfers">
                            <span class="material-symbols-outlined box-icon" aria-hidden="true">directions_car</span>
                            <span class="box-text day-transfers-summary">Add transfers</span>
                            <span class="material-symbols-outlined box-action" aria-hidden="true">edit</span>
                        </button>
                        <div class="day-transfers-list hidden-storage" aria-hidden="true">
                            <div class="day-transfer-row">
                                <select class="day-transfer-a-select" data-admin-key="transfer-a"></select>
                                <select class="day-transfer-b-select" data-admin-key="transfer-b"></select>
                                <select class="day-transfer-service-select" data-admin-key="transfer-service"></select>
                            </div>
                        </div>
                    </div>

                    <!-- Tur / Aktivlik / Entrances (name + service birləşdirilmiş) - multi select, seçim modalda -->
                    <div class="block day-field multi-select-checkbox-wrap">
                        <span class="form-label">Activity</span>
                        <button type="button" class="day-activities-modal-toggle day-field-box" aria-label="Select activities">
                            <span class="material-symbols-outlined box-icon" aria-hidden="true">tour</span>
                            <span class="box-text day-activities-summary">Select activities</span>
                            <span class="material-symbols-outlined box-action" aria-hidden="true">open_in_full</span>
                        </button>
                        <select class="form-input-compact multi-select-native day-activity-select" name="tour_activity" data-admin-key="activities" multiple></select>
                        <input type="hidden" class="day-activity-tickets-count" value="" />
                    </div>
                    <div class="block day-field">
                        <span class="form-label">Lunch</span>
                        <select class="form-input-compact" data-admin-key="meals-lunch"></select>
                    </div>
                    <div class="block day-field">
                        <span class="form-label">Dinner</span>
                        <select class="form-input-compact" data-admin-key="meals-dinner"></select>
                    </div>
                </div>
            </div>

        </section>

        <!-- Section 3: Extra Services -->
        <section class="card">
            <div class="section-header">
                <div class="section-icon">
                    <span class="material-symbols-outlined text-primary" style="font-size:18px;">grade</span>
                </div>
                <div>
                    <h2 class="section-title">Step 3: Any other special service for this trip</h2>
                    <p class="text-xs text-gray-500 mt-1">Add any extra services like off road dinner, side trekking etc. that are associated with overall trip package.</p>
                </div>
            </div>

            <div id="extra-services-rows" class="space-y-3 mt-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 extra-service-row">
                    <div class="space-y-1">
                        <label class="block">
                            <span class="form-label">Service</span>
                            <select class="form-input-compact mt-1 extra-service-select" data-admin-key="extra-services">
                                <option value="">Select or add a service...</option>
                            </select>
                        </label>
                        <p class="extra-service-error hidden text-[0.65rem] text-red-500 font-medium mt-1">Select a service for this row</p>
                    </div>

                    <label class="block">
                        <span class="form-label">Qty</span>
                        <input type="number" min="1" step="1" class="form-input-compact mt-1 extra-service-qty" placeholder="1" />
                        <input type="hidden" class="extra-service-price" />
                    </label>

                    <label class="block">
                        <span class="form-label">Date</span>
                        <input type="date" class="form-input-compact mt-1 extra-service-date" />
                    </label>

                    <div class="flex flex-col gap-2 md:col-span-2">
                        <label class="block flex-1">
                            <span class="form-label">Comments</span>
                            <input type="text" class="form-input-compact mt-1 extra-service-comments" placeholder="Any comments regarding service" />
                        </label>
                        <button type="button" class="text-xs text-red-500 self-end mt-1 extra-service-remove hidden">Remove</button>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="button" id="btn-add-extra-service" class="btn-add-day">
                    <span class="material-symbols-outlined" style="font-size:16px;">add</span>
                    ADD EXTRA SERVICE
                </button>
            </div>
        </section>

        <!-- Section 4: Pricing Summary -->
        <section class="summary-card">
            <span class="material-symbols-outlined summary-icon-bg">payments</span>
            <div class="summary-section-header">
                <div class="summary-section-icon">
                    <span class="material-symbols-outlined" style="color:#60a5fa; font-size:18px;">analytics</span>
                </div>
                <h2 class="summary-section-title">Step 4: Package Summary</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-2">
                    <table class="summary-table">
                        <thead>
                            <tr>
                                <th class="summary-th">Category</th>
                                <th class="summary-th">Description</th>
                                <th class="summary-th" style="text-align:right;">Qty</th>

                            </tr>
                        </thead>
                        <tbody id="summary-tbody">
                            <!-- Dynamic rows from selections -->
                        </tbody>
                    </table>
                </div>
                <div class="price-box">
                    <div class="space-y-4">
                        <div style="border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
                            <span class="per-person-label">Per Person Rate</span>
                            <span class="per-person-val" id="summary-per-person">$0.00</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding-top:8px;">
                            <span class="grand-total-label">Grand Total</span>
                            <span class="grand-total-val" id="summary-grand-total">$0.00</span>
                        </div>
                    </div>
                    <div style="margin-top:32px; display:flex; flex-direction:column; gap:10px;">
                        <button class="btn-pdf">
                            <span class="material-symbols-outlined" style="font-size:18px;">picture_as_pdf</span>
                            EXPORT TO PDF
                        </button>
                        <button class="btn-send">
                            Send Quote to Client
                        </button>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        © 2024 Destinations Azerbaijan Tourism Services. All rights reserved. B2B Partner Portal v2.4
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const STORAGE_KEY = 'tour_builder_admin_data';
            var defaultData = (typeof INITIAL_TOUR_CONFIG !== 'undefined' && INITIAL_TOUR_CONFIG && Object.keys(INITIAL_TOUR_CONFIG).length > 0) ? {
                countries: INITIAL_TOUR_CONFIG.countries || [],
                visa: INITIAL_TOUR_CONFIG.visa || [],
                vehicles: INITIAL_TOUR_CONFIG.vehicles || INITIAL_TOUR_CONFIG.transferVehicles || [],
                transferVehicles: INITIAL_TOUR_CONFIG.transferVehicles || INITIAL_TOUR_CONFIG.vehicles || [],
                transferVehiclePrices: INITIAL_TOUR_CONFIG.transferVehiclePrices || {},
                transferDuties: INITIAL_TOUR_CONFIG.transferDuties || [],
                hotelPrices: INITIAL_TOUR_CONFIG.hotelPrices || {},
                hotelRoomPrices: INITIAL_TOUR_CONFIG.hotelRoomPrices || {},
                hotelExtras: INITIAL_TOUR_CONFIG.hotelExtras || {},
                water: INITIAL_TOUR_CONFIG.water || [],
                guides: INITIAL_TOUR_CONFIG.guides || [],
                hotels: INITIAL_TOUR_CONFIG.hotels || [],
                hotelsByRegion: INITIAL_TOUR_CONFIG.hotelsByRegion || {},
                tours: INITIAL_TOUR_CONFIG.tours || [],
                entrances: INITIAL_TOUR_CONFIG.entrances || [],
                extraServices: INITIAL_TOUR_CONFIG.extraServices || INITIAL_TOUR_CONFIG.extras || [],
                transferA: INITIAL_TOUR_CONFIG.transferA || [],
                transferB: INITIAL_TOUR_CONFIG.transferB || [],
                transferServices: INITIAL_TOUR_CONFIG.transferServices || [],
                transfersByRegion: INITIAL_TOUR_CONFIG.transfersByRegion || {},
                meals: INITIAL_TOUR_CONFIG.meals || {
                    breakfast: [],
                    lunch: [],
                    dinner: []
                },
                mealsByCity: INITIAL_TOUR_CONFIG.mealsByCity || {},
                activitiesByCity: typeof ACTIVITIES_BY_CITY !== 'undefined' ? ACTIVITIES_BY_CITY : {},
                cities: INITIAL_TOUR_CONFIG.cities || ['BAK']
            } : {
                countries: [],
                visa: [],
                vehicles: [],
                transferVehicles: [],
                transferVehiclePrices: {},
                transferDuties: [],
                hotelPrices: {},
                hotelRoomPrices: {},
                hotelExtras: {},
                water: [],
                guides: [],
                hotels: [],
                hotelsByRegion: {},
                tours: [],
                entrances: [],
                extraServices: [],
                transferA: [],
                transferB: [],
                transferServices: [],
                transfersByRegion: {},
                meals: {
                    breakfast: [],
                    lunch: [],
                    dinner: []
                },
                mealsByCity: {},
                activitiesByCity: typeof ACTIVITIES_BY_CITY !== 'undefined' ? ACTIVITIES_BY_CITY : {},
                cities: ['BAK']
            };

            function getAdminDataSync() {
                return defaultData;
            }

            function getAdminData(callback, checkin) {
                function mergeFromApi(data) {
                    var merged = {
                        countries: data.countries || defaultData.countries,
                        visa: data.visa || defaultData.visa,
                        vehicles: data.vehicles || defaultData.vehicles,
                        transferVehicles: data.transferVehicles || data.vehicles || defaultData.vehicles,
                        water: data.water || defaultData.water,
                        guides: data.guides || defaultData.guides,
                        hotels: data.hotels || defaultData.hotels,
                        tours: data.tours || defaultData.tours,
                        entrances: data.entrances || defaultData.entrances,
                        extraServices: data.extraServices || data.extras || defaultData.extraServices || [],
                        transferA: data.transferA || defaultData.transferA,
                        transferB: data.transferB || defaultData.transferB,
                        transferServices: data.transferServices || defaultData.transferServices,
                        transferVehiclePrices: data.transferVehiclePrices || defaultData.transferVehiclePrices || {},
                        transferDuties: data.transferDuties || defaultData.transferDuties || [],
                        hotelPrices: data.hotelPrices || defaultData.hotelPrices || {},
                        hotelRoomPrices: data.hotelRoomPrices || defaultData.hotelRoomPrices || {},
                        hotelExtras: data.hotelExtras || defaultData.hotelExtras || {},
                        meals: data.meals && data.meals.breakfast ? data.meals : defaultData.meals,
                        mealsByCity: data.mealsByCity || defaultData.mealsByCity || {},
                        activitiesByCity: data.activitiesByCity || defaultData.activitiesByCity || (typeof ACTIVITIES_BY_CITY !== 'undefined' ? ACTIVITIES_BY_CITY : {}),
                        cities: data.cities || defaultData.cities || ['BAK']
                    };
                    merged.hotelsByRegion = data.hotelsByRegion || defaultData.hotelsByRegion || {
                        baku: merged.hotels || []
                    };
                    merged.transfersByRegion = data.transfersByRegion || defaultData.transfersByRegion || {
                        baku: {
                            transferA: merged.transferA || [],
                            transferB: merged.transferB || [],
                            transferServices: merged.transferServices || []
                        }
                    };
                    return merged;
                }
                if (!checkin && typeof INITIAL_TOUR_CONFIG !== 'undefined' && INITIAL_TOUR_CONFIG && Object.keys(INITIAL_TOUR_CONFIG).length > 0) {
                    callback(mergeFromApi(INITIAL_TOUR_CONFIG));
                    return;
                }
                const url = checkin ? ('api/tour-config.php?checkin=' + encodeURIComponent(checkin)) : 'api/tour-config.php';
                fetch(url)
                    .then(function(r) {
                        return r.ok ? r.json() : Promise.reject();
                    })
                    .then(function(data) {
                        callback(mergeFromApi(data));
                    })
                    .catch(function() {
                        try {
                            var raw = localStorage.getItem(STORAGE_KEY);
                            if (raw) {
                                var parsed = JSON.parse(raw);
                                var fallback = {
                                    ...defaultData,
                                    ...parsed
                                };
                                fallback.activitiesByCity = fallback.activitiesByCity || (typeof ACTIVITIES_BY_CITY !== 'undefined' ? ACTIVITIES_BY_CITY : {});
                                fallback.mealsByCity = fallback.mealsByCity || {};
                                fallback.hotelsByRegion = fallback.hotelsByRegion || {
                                    baku: fallback.hotels || []
                                };
                                fallback.transfersByRegion = fallback.transfersByRegion || {
                                    baku: {
                                        transferA: fallback.transferA || [],
                                        transferB: fallback.transferB || [],
                                        transferServices: fallback.transferServices || []
                                    }
                                };
                                fallback.hotelPrices = fallback.hotelPrices || {};
                                fallback.hotelRoomPrices = fallback.hotelRoomPrices || {};
                                fallback.hotelExtras = fallback.hotelExtras || {};
                                fallback.transferVehiclePrices = fallback.transferVehiclePrices || {};
                                fallback.transferDuties = fallback.transferDuties || [];
                                callback(fallback);
                                return;
                            }
                        } catch (e) {}
                        var def = {
                            ...defaultData
                        };
                        def.activitiesByCity = typeof ACTIVITIES_BY_CITY !== 'undefined' ? ACTIVITIES_BY_CITY : {};
                        def.mealsByCity = {};
                        def.hotelsByRegion = {
                            baku: def.hotels || []
                        };
                        def.transfersByRegion = {
                            baku: {
                                transferA: def.transferA || [],
                                transferB: def.transferB || [],
                                transferServices: def.transferServices || []
                            }
                        };
                        def.hotelPrices = def.hotelPrices || {};
                        def.hotelRoomPrices = def.hotelRoomPrices || {};
                        def.hotelExtras = def.hotelExtras || {};
                        def.transferVehiclePrices = def.transferVehiclePrices || {};
                        def.transferDuties = def.transferDuties || [];
                        callback(def);
                    });
            }

            // === Şəhər kodları (BAK, QBL, SHK, ...): bütün data şəhər koduna görə saxlanılır ===
            const DEFAULT_CITY_CODE = 'BAK';

            function getCityRegistry() {
                return (typeof INITIAL_TOUR_CONFIG !== 'undefined' && INITIAL_TOUR_CONFIG && INITIAL_TOUR_CONFIG.cityRegistry) || [];
            }

            /** Kod, ad və ya köhnə yazılış (baku, qabala, gabala...) → kod. Tanınmırsa ''. */
            function cityCodeOf(value) {
                const raw = String(value || '').trim();
                if (!raw) return '';
                const upper = raw.toUpperCase();
                const low = raw.toLowerCase();
                const list = getCityRegistry();
                for (let i = 0; i < list.length; i++) {
                    const c = list[i];
                    if (c.code === upper) return c.code;
                    if (String(c.name).toLowerCase() === low || String(c.az).toLowerCase() === low || (c.aliases || []).indexOf(low) >= 0) return c.code;
                }
                return '';
            }

            function cityNameOf(value) {
                const code = cityCodeOf(value);
                const city = getCityRegistry().find(function(c) {
                    return c.code === code;
                });
                return city ? city.name : String(value || '');
            }

            function getDayCityCode(dayRow) {
                const citySel = dayRow ? dayRow.querySelector('.day-city-select') : null;
                return cityCodeOf(citySel && citySel.value) || DEFAULT_CITY_CODE;
            }

            function getActivitiesForCity(data, city) {
                const byCity = (data && data.activitiesByCity) || (typeof ACTIVITIES_BY_CITY !== 'undefined' ? ACTIVITIES_BY_CITY : {}) || {};
                const list = byCity[cityCodeOf(city) || DEFAULT_CITY_CODE];
                return Array.isArray(list) ? list : [];
            }

            function populateSelect(selectEl, options, placeholder) {
                if (!selectEl || !Array.isArray(options)) return;
                const key = selectEl.getAttribute('data-admin-key');
                const isMultiple = !!selectEl.multiple;
                const currentVal = selectEl.value;
                const selectedVals = isMultiple
                    ? Array.from(selectEl.options || []).filter(function(o) {
                        return o.selected && o.value;
                    }).map(function(o) {
                        return o.value;
                    })
                    : [];
                const ticketsByValue = {};
                Array.from(selectEl.options || []).forEach(function(o) {
                    if (o.value && o.dataset && o.dataset.tickets) {
                        ticketsByValue[o.value] = o.dataset.tickets;
                    }
                });
                const optionsIncludeVal = function(val) {
                    if (options.length && typeof options[0] === 'object' && options[0] !== null && 'value' in options[0]) {
                        return options.some(function(o) {
                            return o.value === val;
                        });
                    }
                    return options.includes(val);
                };

                selectEl.innerHTML = '';
                if (placeholder) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = placeholder;
                    opt.selected = !isMultiple && (!currentVal || !optionsIncludeVal(currentVal));
                    selectEl.appendChild(opt);
                }
                options.forEach(function(item) {
                    const opt = document.createElement('option');
                    const val = typeof item === 'object' && item !== null && 'value' in item ? item.value : item;
                    const label = typeof item === 'object' && item !== null && 'label' in item ? item.label : item;
                    opt.value = val;
                    opt.textContent = label;
                    if (typeof item === 'object' && item !== null && item.price != null && !isNaN(parseFloat(item.price))) {
                        opt.dataset.price = String(item.price);
                    }
                    if (ticketsByValue[val]) opt.dataset.tickets = ticketsByValue[val];
                    selectEl.appendChild(opt);
                });
                if (isMultiple) {
                    Array.from(selectEl.options).forEach(function(opt) {
                        if (!opt.value) return;
                        opt.selected = selectedVals.indexOf(opt.value) !== -1;
                    });
                } else if (currentVal && optionsIncludeVal(currentVal)) {
                    selectEl.value = currentVal;
                }
            }

            function populateAllSelects() {
                getAdminData(function(data) {
                    doPopulateAllSelects(data);
                });
            }

            function initActivitiesModal() {
                const modal = document.getElementById('activities-modal');
                if (!modal) return;
                const listEl = document.getElementById('activities-modal-list');
                const dayLabelEl = document.getElementById('activities-modal-day-label');
                const btnClose = document.getElementById('activities-modal-close');
                const btnDone = document.getElementById('activities-modal-done');
                const searchEl = document.getElementById('activities-modal-search');
                const countEl = document.getElementById('activities-modal-count');
                const emptyEl = document.getElementById('activities-modal-empty');
                const norm = (window.SearchableSelect && window.SearchableSelect.normalize) || function(v) {
                    return String(v || '').toLowerCase();
                };

                /** Axtarış: siyahıdakı aktivitetləri adla süz (Azərbaycan hərfləri nəzərə alınır). */
                function filterActivities() {
                    const q = norm(searchEl ? searchEl.value : '');
                    let shown = 0;
                    let total = 0;
                    listEl.querySelectorAll('label').forEach(function(item) {
                        total++;
                        const match = !q || norm(item.textContent).indexOf(q) !== -1;
                        item.classList.toggle('hidden', !match);
                        if (match) shown++;
                    });
                    if (emptyEl) emptyEl.classList.toggle('hidden', shown > 0 || total === 0);
                    if (countEl) countEl.textContent = q ? shown + ' / ' + total : total + ' items';
                }
                if (searchEl) {
                    searchEl.addEventListener('input', filterActivities);
                    searchEl.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') e.preventDefault();
                    });
                }
                if (!listEl) return;

                let currentSelect = null;
                let currentRow = null;

                function openForRow(row) {
                    if (!row) return;
                    const sel = row.querySelector('.day-activity-select');
                    if (!sel) return;
                    currentSelect = sel;
                    currentRow = row;
                    listEl.innerHTML = '';

                    const date = (row.querySelector('.day-date-input') || {}).value || '';
                    const city = (row.querySelector('.day-city-select') || {}).value || '';
                    dayLabelEl.textContent = [date, city ? cityNameOf(city) : ''].filter(Boolean).join(' · ');

                    const adminData = window._lastAdminData || getAdminDataSync();
                    const activityOptions = getActivitiesForCity(adminData, city);
                    if (activityOptions.length) {
                        populateSelect(sel, activityOptions, 'Select (Tour / Activity / Entrance)');
                    }

                    Array.from(sel.options || []).forEach(function(opt) {
                        if (!opt.value) return;
                        const item = document.createElement('label');
                        item.className = 'flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer text-sm text-gray-800';
                        const cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.className = 'mt-1 rounded border-gray-300';
                        cb.checked = opt.selected;
                        cb.addEventListener('change', function() {
                            opt.selected = cb.checked;
                            sel.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                            saveFormState();
                            updateSummary();
                        });
                        const text = document.createElement('span');
                        text.textContent = opt.textContent || opt.value;

                        const qtyWrap = document.createElement('div');
                        qtyWrap.className = 'flex items-center gap-1 ml-auto';
                        const qtyLabel = document.createElement('span');
                        qtyLabel.className = 'text-[0.65rem] text-gray-500';
                        qtyLabel.textContent = 'Tickets';
                        const qtyInput = document.createElement('input');
                        qtyInput.type = 'number';
                        qtyInput.min = '1';
                        qtyInput.step = '1';
                        qtyInput.className = 'form-input-compact w-16 text-xs';
                        const defaultTickets = isPerPersonActivity(opt.textContent || opt.value)
                            ? Math.max(1, getGuestCounts().totalPax)
                            : Math.max(1, getGuestCounts().adultMultiplier);
                        const rawTickets = (opt.dataset && opt.dataset.tickets) ? String(opt.dataset.tickets).trim() : '';
                        let ticketsVal = parseInt(rawTickets, 10);
                        if (!rawTickets || isNaN(ticketsVal) || ticketsVal < 1) ticketsVal = defaultTickets;
                        qtyInput.value = String(ticketsVal);
                        // Seçili olmayan activity için de qty'yi koru
                        opt.dataset.tickets = String(ticketsVal);
                        qtyInput.addEventListener('input', function() {
                            let n = parseInt(qtyInput.value, 10);
                            if (!n || n < 1) n = 1;
                            qtyInput.value = String(n);
                            if (opt.dataset) opt.dataset.tickets = String(n);
                            saveFormState();
                            updateSummary();
                        });

                        item.appendChild(cb);
                        item.appendChild(text);
                        qtyWrap.appendChild(qtyLabel);
                        qtyWrap.appendChild(qtyInput);
                        item.appendChild(qtyWrap);
                        listEl.appendChild(item);
                    });

                    if (!listEl.children.length) {
                        const empty = document.createElement('p');
                        empty.className = 'text-xs text-gray-500 py-2';
                        empty.textContent = 'No activities found for this city.';
                        listEl.appendChild(empty);
                    }

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    if (searchEl) {
                        searchEl.value = '';
                        filterActivities();
                        searchEl.focus();
                    }
                }

                function closeModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    currentSelect = null;
                    currentRow = null;
                }

                if (!document._activitiesModalToggleDelegated) {
                    document._activitiesModalToggleDelegated = true;
                    document.addEventListener('click', function(e) {
                        const toggle = e.target.closest('.day-activities-modal-toggle');
                        if (!toggle) return;
                        e.preventDefault();
                        e.stopPropagation();
                        openForRow(toggle.closest('.day-row'));
                    });
                }

                if (btnClose && !btnClose._activitiesCloseBound) {
                    btnClose._activitiesCloseBound = true;
                    btnClose.addEventListener('click', closeModal);
                }
                if (btnDone && !btnDone._activitiesDoneBound) {
                    btnDone._activitiesDoneBound = true;
                    btnDone.addEventListener('click', closeModal);
                }
                if (!modal._activitiesBackdropBound) {
                    modal._activitiesBackdropBound = true;
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) closeModal();
                    });
                }
            }

            window.__tourBuilderInitActivitiesModal = initActivitiesModal;

            function getMealsForDay(sel, data) {
                const city = getDayCityCode(sel.closest('.day-row'));
                const byCity = data.mealsByCity || {};
                const cityMeals = byCity[city] || (city === DEFAULT_CITY_CODE ? (data.meals || {
                    breakfast: [],
                    lunch: [],
                    dinner: []
                }) : {
                    breakfast: [],
                    lunch: [],
                    dinner: []
                });
                return cityMeals;
            }

            function getTransfersForDayRow(dayRow, data) {
                const city = getDayCityCode(dayRow);
                const byRegion = data.transfersByRegion || {};
                return byRegion[city] || byRegion[DEFAULT_CITY_CODE] || {
                    transferA: data.transferA || [],
                    transferB: data.transferB || [],
                    transferServices: data.transferServices || []
                };
            }

            function getTransfersForDay(sel, data) {
                const row = sel.closest('.day-row');
                return getTransfersForDayRow(row, data);
            }

            function setTransferSelectValue(sel, val) {
                if (!sel) return;
                const v = (val != null) ? String(val) : '';
                if (v === '') {
                    sel.value = '';
                    return;
                }
                const hasOption = Array.from(sel.options || []).some(function(o) {
                    return o.value === v;
                });
                if (!hasOption) {
                    const opt = document.createElement('option');
                    opt.value = v;
                    opt.textContent = v;
                    sel.appendChild(opt);
                }
                sel.value = v;
            }

            // === Transfer duty kodları: hər transfer duty kodu ilə seçilir (A|B|Service unikal deyil) ===
            function getTransferDutyIndex(data) {
                const list = (data && Array.isArray(data.transferDuties)) ? data.transferDuties : [];
                if (list._byCode) return list._byCode;
                const byCode = {};
                list.forEach(function(d) {
                    if (d && d.code != null) byCode[String(d.code)] = d;
                });
                Object.defineProperty(list, '_byCode', {
                    value: byCode,
                    enumerable: false
                });
                return byCode;
            }

            function findTransferDuty(data, code) {
                if (code == null || code === '') return null;
                return getTransferDutyIndex(data)[String(code)] || null;
            }

            function parseDutyCode(value) {
                const m = String(value || '').match(/^\s*#?\s*(\d+)/);
                return m ? parseInt(m[1], 10) : null;
            }

            function transferDutyRoute(duty) {
                const route = [duty.a, duty.b].filter(Boolean).join(' → ');
                return route + (duty.service ? ' (' + duty.service + ')' : '');
            }

            function transferDutyOptionLabel(duty) {
                return duty.code + ' — ' + transferDutyRoute(duty) + (duty.title ? ' · ' + duty.title : '');
            }

            /** duty.prices massivi data.transferVehicles sırası ilədir. */
            function transferDutyVehiclePrice(duty, vehicleName, data) {
                if (!duty || !vehicleName || !Array.isArray(duty.prices)) return null;
                const vehicles = (data && (data.transferVehicles || data.vehicles)) || [];
                const want = normalizeLookupStr(vehicleName);
                for (let i = 0; i < vehicles.length; i++) {
                    const v = vehicles[i];
                    const name = (v && typeof v === 'object') ? (v.value || v.label) : v;
                    if (normalizeLookupStr(name) !== want) continue;
                    const p = duty.prices[i];
                    return (p != null && !isNaN(parseFloat(p))) ? parseFloat(p) : null;
                }
                return null;
            }

            function populateTransferDutyDatalist(data) {
                const datalist = document.getElementById('transfer-duty-options');
                const list = (data && Array.isArray(data.transferDuties)) ? data.transferDuties : [];
                if (!datalist || datalist._source === list) return;
                datalist._source = list;
                const frag = document.createDocumentFragment();
                list.forEach(function(duty) {
                    const opt = document.createElement('option');
                    opt.value = transferDutyOptionLabel(duty);
                    frag.appendChild(opt);
                });
                datalist.innerHTML = '';
                datalist.appendChild(frag);
            }

            function populateTransferRowSelects(transferRow, dayRow, data) {
                if (!transferRow || !dayRow) return;
                const regionTransfers = getTransfersForDayRow(dayRow, data);
                const aSel = transferRow.querySelector('.day-transfer-a-select');
                const bSel = transferRow.querySelector('.day-transfer-b-select');
                const sSel = transferRow.querySelector('.day-transfer-service-select');
                const keepA = aSel ? aSel.value : '';
                const keepB = bSel ? bSel.value : '';
                const keepS = sSel ? sSel.value : '';
                if (aSel) populateSelect(aSel, regionTransfers.transferA || [], 'Select (A)');
                if (bSel) populateSelect(bSel, regionTransfers.transferB || [], 'Select (B)');
                if (sSel) populateSelect(sSel, regionTransfers.transferServices || [], 'Select service');
                setTransferSelectValue(aSel, keepA);
                setTransferSelectValue(bSel, keepB);
                setTransferSelectValue(sSel, keepS);
            }

            function persistDayTransfersToRow(dayRow, transfers) {
                if (!dayRow) return;
                const wrap = dayRow.querySelector('.day-transfers-wrap');
                const list = dayRow.querySelector('.day-transfers-list');
                if (!list) return;

                const rows = Array.isArray(transfers) ? transfers : [];
                if (wrap) {
                    wrap.dataset.transfersJson = JSON.stringify(rows);
                }

                let storageTemplate = list.querySelector('.day-transfer-row');
                if (!storageTemplate) {
                    storageTemplate = document.createElement('div');
                    storageTemplate.className = 'day-transfer-row';
                    storageTemplate.innerHTML = '<select class="day-transfer-a-select" data-admin-key="transfer-a"></select>' +
                        '<select class="day-transfer-b-select" data-admin-key="transfer-b"></select>' +
                        '<select class="day-transfer-service-select" data-admin-key="transfer-service"></select>';
                }
                const templateClone = storageTemplate.cloneNode(true);
                const data = window._lastAdminData || getAdminDataSync();
                const rowsToSave = rows.length ? rows : [{
                    transferA: '',
                    transferB: '',
                    transferService: ''
                }];

                list.innerHTML = '';
                rowsToSave.forEach(function(t) {
                    const storageRow = templateClone.cloneNode(true);
                    populateTransferRowSelects(storageRow, dayRow, data);
                    setTransferSelectValue(storageRow.querySelector('.day-transfer-a-select'), t.transferA || '');
                    setTransferSelectValue(storageRow.querySelector('.day-transfer-b-select'), t.transferB || '');
                    setTransferSelectValue(storageRow.querySelector('.day-transfer-service-select'), t.transferService || '');
                    list.appendChild(storageRow);
                });
            }

            function getHotelsForDay(sel, data) {
                const city = getDayCityCode(sel.closest('.day-row'));
                const byRegion = data.hotelsByRegion || {};
                return byRegion[city] || (city === DEFAULT_CITY_CODE ? data.hotels : null) || [];
            }

            function getRoomTypesForHotel(sel, data) {
                const row = sel.closest('.day-row');
                const hotelSel = row ? row.querySelector('select[data-admin-key="hotels"]') : null;
                const hotelVal = (hotelSel && hotelSel.value) ? hotelSel.value : '';
                if (!hotelVal) return [];
                const roomMap = (data.hotelRoomPrices || {})[hotelVal];
                if (!roomMap || typeof roomMap !== 'object') return [];
                return Object.keys(roomMap).map(function(rt) {
                    return {
                        value: rt,
                        label: rt
                    };
                });
            }

            function getHotelExtrasForDayRow(dayRow, data) {
                const hotelSel = dayRow ? dayRow.querySelector('select[data-admin-key="hotels"]') : null;
                const hotelVal = (hotelSel && hotelSel.value) ? hotelSel.value : '';
                if (!hotelVal) return [];
                const list = (data.hotelExtras || {})[hotelVal];
                if (Array.isArray(list) && list.length) return list;
                const upper = hotelVal.toUpperCase();
                for (const k in (data.hotelExtras || {})) {
                    const ku = k.toUpperCase();
                    if (ku === upper || ku.indexOf(upper) >= 0 || upper.indexOf(ku) >= 0) {
                        return data.hotelExtras[k];
                    }
                }
                return [];
            }

            function getHotelExtrasForHotel(sel, data) {
                const row = sel.closest('.day-row');
                return getHotelExtrasForDayRow(row, data);
            }

            function doPopulateAllSelects(data) {
                document.querySelectorAll('[data-admin-key]').forEach(function(sel) {
                    if (sel.closest('#transfers-modal-row-template') || sel.closest('.day-transfers-list.hidden-storage') ||
                        sel.closest('#hotel-extras-modal-row-template') || sel.closest('.day-hotel-extras-list.hidden-storage')) return;
                    const key = sel.getAttribute('data-admin-key');
                    let options = [];
                    let placeholder = '';

                    if (key === 'countries') {
                        options = data.countries || [];
                        placeholder = 'Select Country (' + (options.length || 0) + ' available)';
                    } else if (key === 'visa') options = data.visa || [];
                    else if (key === 'vehicles') {
                        options = data.vehicles || data.transferVehicles || [];
                    } else if (key === 'water') options = data.water || [];
                    else if (key === 'guides') options = data.guides || [];
                    else if (key === 'hotels') {
                        options = getHotelsForDay(sel, data);
                        placeholder = 'No select';
                    } else if (key === 'hotel-room-type') {
                        options = getRoomTypesForHotel(sel, data);
                        placeholder = 'No select';
                    } else if (key === 'hotel-extras') {
                        options = getHotelExtrasForHotel(sel, data);
                        placeholder = 'Select hotel extra';
                    } else if (key === 'tours') options = data.tours || [];
                    else if (key === 'entrances') options = data.entrances || [];
                    else if (key === 'activities') {
                        const row = sel.closest('.day-row');
                        const city = getDayCityCode(row);
                        options = getActivitiesForCity(data, city);
                        placeholder = 'Select (Tour / Activity / Entrance)';
                    } else if (key === 'meals-breakfast') {
                        const cityMeals = getMealsForDay(sel, data);
                        options = cityMeals.breakfast || [];
                        placeholder = 'Select breakfast option';
                    } else if (key === 'meals-lunch') {
                        const cityMeals = getMealsForDay(sel, data);
                        options = cityMeals.lunch || [];
                        placeholder = 'No select';
                    } else if (key === 'meals-dinner') {
                        const cityMeals = getMealsForDay(sel, data);
                        options = cityMeals.dinner || [];
                        placeholder = 'No select';
                    } else if (key === 'transfer-a') {
                        const regionTransfers = getTransfersForDay(sel, data);
                        options = regionTransfers.transferA || [];
                        placeholder = 'Select (A)';
                    } else if (key === 'transfer-b') {
                        const regionTransfers = getTransfersForDay(sel, data);
                        options = regionTransfers.transferB || [];
                        placeholder = 'Select (B)';
                    } else if (key === 'transfer-service') {
                        const regionTransfers = getTransfersForDay(sel, data);
                        options = regionTransfers.transferServices || [];
                        placeholder = 'Select service';
                    } else if (key === 'extra-services') {
                        options = data.extraServices || data.extras || [];
                        placeholder = 'Select a service';
                    }

                    populateSelect(sel, options, placeholder);
                });
                populateTransferDutyDatalist(data);
                enhanceMultiSelectsToCheckboxes();
            }

            function enhanceMultiSelectsToCheckboxes() {
                document.querySelectorAll('.multi-select-checkbox-wrap').forEach(function(wrap) {
                    var sel = wrap.querySelector('select.multi-select-native');
                    var box = wrap.querySelector('.multi-select-checkboxes');
                    if (!sel || !box) return;
                    box.innerHTML = '';
                    Array.from(sel.options || []).forEach(function(opt) {
                        if (opt.disabled || !opt.value) return;
                        var item = document.createElement('label');
                        item.className = 'multi-select-checkbox-item';
                        var cb = document.createElement('input');
                        cb.type = 'checkbox';
                        cb.value = opt.value;
                        cb.checked = opt.selected;
                        cb.addEventListener('change', function() {
                            opt.selected = cb.checked;
                            sel.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                            saveFormState();
                            updateSummary();
                        });
                        item.appendChild(cb);
                        item.appendChild(document.createTextNode(opt.textContent || opt.value));
                        box.appendChild(item);
                    });
                });
            }

            var _adminDataLoadCount = 0;

            function loadAdminDataWithCheckin() {
                const [checkinInput] = getCheckinCheckoutInputs();
                const isFirstLoad = _adminDataLoadCount === 0;
                _adminDataLoadCount++;
                const checkin = isFirstLoad ? null : (checkinInput && checkinInput.value ? checkinInput.value : null);
                getAdminData(function(data) {
                    window._lastAdminData = data;
                    doPopulateAllSelects(data);
                    initExtraServiceHandlers();
                    syncExtraServiceDateLimits();
                    var loadId = (function() {
                        var m = /[?&]load=(\d+)/.exec(location.search || '');
                        return m ? parseInt(m[1], 10) : 0;
                    })();
                    if (loadId > 0 && typeof USER_LOGGED_IN !== 'undefined' && USER_LOGGED_IN) {
                        fetch('api/user-orders.php?id=' + loadId).then(function(r) {
                            return r.json();
                        }).then(function(res) {
                            if (res.ok && res.order && res.order.order_data) {
                                applyFormState(res.order.order_data);
                                doPopulateAllSelects(data);
                                if (window.history && window.history.replaceState) {
                                    window.history.replaceState({}, document.title, location.pathname);
                                }
                            }
                            updateSummary();
                        }).catch(function() {
                            updateSummary();
                        });
                    } else {
                        updateSummary();
                    }
                }, checkin);
            }

            function initExtraServiceHandlers() {
                const rowsContainer = document.getElementById('extra-services-rows');
                if (!rowsContainer) return;
                const data = window._lastAdminData || {};
                const list = data.extraServices || data.extras || [];

                rowsContainer.querySelectorAll('.extra-service-row').forEach(function(row) {
                    const sel = row.querySelector('.extra-service-select');
                    const qtyInput = row.querySelector('.extra-service-qty');
                    const priceInput = row.querySelector('.extra-service-price');
                    const dateInput = row.querySelector('.extra-service-date');
                    const removeBtn = row.querySelector('.extra-service-remove');
                    if (!sel || !priceInput) return;

                    if (!sel._extraBound) {
                        sel._extraBound = true;
                        sel.addEventListener('change', function() {
                            const v = sel.value;
                            let found = null;
                            if (Array.isArray(list) && list.length && typeof list[0] === 'object') {
                                found = list.find(function(o) {
                                    return (o.value || o.label) === v;
                                }) || null;
                            }
                            if (found && found.price != null && !isNaN(found.price)) {
                                const p = Number(found.price);
                                priceInput.value = (p % 1 === 0) ? String(p) : p.toFixed(2);
                                if (qtyInput && !qtyInput.value) qtyInput.value = '1';
                            }
                            saveFormState();
                            updateSummary();
                        });
                    }

                    if (qtyInput && !qtyInput._extraBound) {
                        qtyInput._extraBound = true;
                        qtyInput.addEventListener('input', function() {
                            saveFormState();
                            updateSummary();
                        });
                    }

                    if (dateInput && !dateInput._extraDateBound) {
                        dateInput._extraDateBound = true;
                        syncExtraServiceDateLimitsForInput(dateInput);
                    }

                    if (removeBtn && !removeBtn._extraRemoveBound) {
                        removeBtn._extraRemoveBound = true;
                        removeBtn.addEventListener('click', function() {
                            const allRows = rowsContainer.querySelectorAll('.extra-service-row');
                            if (allRows.length > 1) {
                                row.remove();
                                saveFormState();
                                updateSummary();
                            } else {
                                // son satırsa yalnız dəyərləri təmizlə
                                if (sel) sel.value = '';
                                if (priceInput) priceInput.value = '';
                                if (dateInput) dateInput.value = '';
                                const commentsInput = row.querySelector('.extra-service-comments');
                                if (commentsInput) commentsInput.value = '';
                                saveFormState();
                                updateSummary();
                            }
                        });
                    }
                });

                const addBtn = document.getElementById('btn-add-extra-service');
                if (addBtn && !addBtn._extraAddBound) {
                    addBtn._extraAddBound = true;
                    addBtn.addEventListener('click', function() {
                        const firstRow = rowsContainer.querySelector('.extra-service-row');
                        if (!firstRow) return;
                        const newRow = firstRow.cloneNode(true);
                        // sahələri sıfırla
                        newRow.querySelectorAll('select').forEach(function(sel) {
                            sel.value = '';
                            sel._extraBound = false;
                        });
                        newRow.querySelectorAll('input').forEach(function(inp) {
                            if (inp.type === 'checkbox' || inp.type === 'radio') {
                                inp.checked = false;
                            } else {
                                inp.value = '';
                            }
                            inp._extraDateBound = false;
                        });
                        const removeBtn = newRow.querySelector('.extra-service-remove');
                        if (removeBtn) removeBtn.classList.remove('hidden');
                        rowsContainer.appendChild(newRow);
                        initExtraServiceHandlers();
                        saveFormState();
                    });

                    // ilk sətirdə Remove gizli qalsın
                    const firstRemove = rowsContainer.querySelector('.extra-service-row .extra-service-remove');
                    if (firstRemove) firstRemove.classList.add('hidden');
                }
            }

            // === Meal helper (gün sətiri içində) ===
            function initMealToggles(cardEl) {
                if (!cardEl) return;
                const container = cardEl.querySelector('.meal-checkboxes');
                const mealSelectsContainer = cardEl.querySelector('.meal-selects');

                if (!container || !mealSelectsContainer) return;

                const checkboxes = container.querySelectorAll('input[type="checkbox"][data-meal]');
                checkboxes.forEach(function(cb) {
                    cb.removeEventListener('change', cb._mealHandler);
                    cb._mealHandler = function() {
                        const meal = this.getAttribute('data-meal');
                        const selectBlock = mealSelectsContainer.querySelector('.meal-select[data-meal="' + meal + '"]');
                        if (!selectBlock) return;
                        if (this.checked) selectBlock.classList.remove('hidden');
                        else selectBlock.classList.add('hidden');
                    };
                    cb.addEventListener('change', cb._mealHandler);
                });
            }

            // Day 1 meal helper (yeni strukturda lazım deyil amma
            // geriyə uyğunluq üçün birinci sətri ötürürük)
            initMealToggles(document.querySelector('#days-container .day-row'));

            // Şəhər dəyişəndə həmin günün yemək seçimlərini yenilə
            function initCityChangeHandlers() {
                document.querySelectorAll('.day-city-select').forEach(function(citySel) {
                    if (citySel._cityHandler) return;
                    citySel._cityHandler = function() {
                        if (window._lastAdminData) doPopulateAllSelects(window._lastAdminData);
                        const row = citySel.closest('.day-row');
                        if (row) updateDayTransfersSummary(row);
                    };
                    citySel.addEventListener('change', citySel._cityHandler);
                });
            }
            initCityChangeHandlers();

            // Multi-select: sadə kliklə (Ctrl/Cmd olmadan) seçimi aç/bağla
            function initMultiSelectClickToggle() {
                document.addEventListener('mousedown', function(e) {
                    const opt = e.target;
                    if (opt.tagName !== 'OPTION') return;
                    const sel = opt.closest('select[multiple]');
                    if (!sel) return;
                    e.preventDefault();
                    opt.selected = !opt.selected;
                    sel.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }, true);
            }
            initMultiSelectClickToggle();

            function hasTransferData(t) {
                return !!(t && (t.dutyCode || t.transferA || t.transferB || t.transferService));
            }

            /** Duty kodu seçilmiş transfer: A/B/Service duty-dən götürülür (PDF və köhnə kod üçün saxlanılır). */
            function transferFromDuty(duty) {
                return {
                    dutyCode: duty.code,
                    transferA: duty.a || '',
                    transferB: duty.b || '',
                    transferService: duty.service || ''
                };
            }

            function getTransfersFromTransferRow(transferRow) {
                const dutyInput = transferRow.querySelector('.day-transfer-duty-input');
                if (dutyInput) {
                    const data = window._lastAdminData || getAdminDataSync();
                    const duty = findTransferDuty(data, parseDutyCode(dutyInput.value));
                    if (duty) return transferFromDuty(duty);
                    // Duty kodu olmayan köhnə sifariş marşrutu (dəyişdirilməyibsə saxlanılır)
                    if (dutyInput.value.trim() === '' && transferRow.dataset.legacyTransfer) {
                        try {
                            return JSON.parse(transferRow.dataset.legacyTransfer);
                        } catch (e) { /* ignore */ }
                    }
                    return {
                        dutyCode: '',
                        transferA: '',
                        transferB: '',
                        transferService: ''
                    };
                }
                return {
                    transferA: (transferRow.querySelector('.day-transfer-a-select') || {}).value || '',
                    transferB: (transferRow.querySelector('.day-transfer-b-select') || {}).value || '',
                    transferService: (transferRow.querySelector('.day-transfer-service-select') || {}).value || ''
                };
            }

            function collectDayTransfers(dayRow) {
                const list = [];
                if (!dayRow) return list;
                const wrap = dayRow.querySelector('.day-transfers-wrap');
                if (wrap && wrap.dataset.transfersJson) {
                    try {
                        const parsed = JSON.parse(wrap.dataset.transfersJson);
                        if (Array.isArray(parsed)) {
                            parsed.forEach(function(t) {
                                if (hasTransferData(t)) list.push(t);
                            });
                            if (list.length) return list;
                        }
                    } catch (e) { /* fallback */ }
                }
                dayRow.querySelectorAll('.day-transfer-row').forEach(function(transferRow) {
                    const t = getTransfersFromTransferRow(transferRow);
                    if (hasTransferData(t)) list.push(t);
                });
                return list;
            }

            function describeTransfer(t) {
                const parts = [t.transferA, t.transferB].filter(Boolean);
                const route = parts.join(' → ') + (t.transferService ? ' (' + t.transferService + ')' : '');
                return t.dutyCode ? 'Duty ' + t.dutyCode + ': ' + route : route;
            }

            function updateDayTransfersSummary(dayRow) {
                if (!dayRow) return;
                const summaryEl = dayRow.querySelector('.day-transfers-summary');
                if (!summaryEl) return;
                const transfers = collectDayTransfers(dayRow);
                if (!transfers.length) {
                    setFieldBoxSummary(summaryEl, 'Add transfers', false);
                    return;
                }
                setFieldBoxSummary(summaryEl, transfers.map(describeTransfer).join('; '), true);
            }

            function syncDayTransferRemoveVisibility(container) {
                if (!container) return;
                const rows = container.querySelectorAll('.day-transfer-row, .modal-transfer-row');
                rows.forEach(function(transferRow) {
                    const removeBtn = transferRow.querySelector('.day-transfer-remove');
                    if (removeBtn) removeBtn.classList.toggle('hidden', rows.length <= 1);
                });
            }

            function resetDayToSingleTransferRow(dayRow) {
                persistDayTransfersToRow(dayRow, []);
            }

            function applyTransfersToDayCard(card, dayData) {
                if (!card) return;
                let transfers = Array.isArray(dayData.transfers) ? dayData.transfers.slice() : [];
                if (!transfers.length && (dayData.transferA || dayData.transferB || dayData.transferService)) {
                    transfers.push({
                        dutyCode: dayData.transferDutyCode || '',
                        transferA: dayData.transferA || '',
                        transferB: dayData.transferB || '',
                        transferService: dayData.transferService || ''
                    });
                }
                persistDayTransfersToRow(card, transfers);
                updateDayTransfersSummary(card);
            }

            function initTransfersModal() {
                const modal = document.getElementById('transfers-modal');
                if (!modal || modal._transfersInitialized) return;

                const listEl = document.getElementById('transfers-modal-list');
                const dayLabelEl = document.getElementById('transfers-modal-day-label');
                const btnClose = document.getElementById('transfers-modal-close');
                const btnDone = document.getElementById('transfers-modal-done');
                const btnAdd = document.getElementById('transfers-modal-add');
                const rowTemplate = document.getElementById('transfers-modal-row-template');
                if (!listEl || !rowTemplate || !btnDone) return;

                modal._transfersInitialized = true;

                let currentDayRow = null;

                function closeModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    currentDayRow = null;
                }

                function createModalTransferRow(transferData) {
                    const transferRow = rowTemplate.querySelector('.modal-transfer-row').cloneNode(true);
                    const data = window._lastAdminData || getAdminDataSync();
                    const input = transferRow.querySelector('.day-transfer-duty-input');
                    const t = transferData || {};
                    if (t.dutyCode) {
                        const duty = findTransferDuty(data, t.dutyCode);
                        input.value = duty ? transferDutyOptionLabel(duty) : String(t.dutyCode);
                    } else if (hasTransferData(t)) {
                        transferRow.dataset.legacyTransfer = JSON.stringify(t);
                    }
                    renderModalDutyInfo(transferRow);
                    return transferRow;
                }

                function appendInfoLine(parent, text, className) {
                    const el = document.createElement('div');
                    if (className) el.className = className;
                    el.textContent = text;
                    parent.appendChild(el);
                }

                /** Seçilmiş duty: marşrut, proqram və seçilmiş maşının qiyməti. Xəta varsa false qaytarır. */
                function renderModalDutyInfo(transferRow) {
                    const input = transferRow.querySelector('.day-transfer-duty-input');
                    const info = transferRow.querySelector('.day-transfer-duty-info');
                    if (!input || !info) return true;
                    const data = window._lastAdminData || getAdminDataSync();
                    const raw = input.value.trim();
                    info.innerHTML = '';
                    info.classList.remove('is-error');
                    input.classList.remove('is-invalid');

                    if (raw === '') {
                        if (transferRow.dataset.legacyTransfer) {
                            let legacy = {};
                            try {
                                legacy = JSON.parse(transferRow.dataset.legacyTransfer);
                            } catch (e) { /* ignore */ }
                            appendInfoLine(info, 'Old route without duty code: ' + describeTransfer(legacy), 'duty-info-route');
                            appendInfoLine(info, 'Pick a duty code to price it correctly.', 'duty-info-muted');
                        } else {
                            appendInfoLine(info, 'Type a duty code or search by place / description.', 'duty-info-muted');
                        }
                        return true;
                    }

                    const code = parseDutyCode(raw);
                    const duty = findTransferDuty(data, code);
                    if (!duty) {
                        appendInfoLine(info, code == null ? 'Enter a duty code (number) or pick one from the list.' : 'Duty code ' + code + ' not found.');
                        info.classList.add('is-error');
                        input.classList.add('is-invalid');
                        return false;
                    }

                    appendInfoLine(info, transferDutyRoute(duty), 'duty-info-route');
                    if (duty.title) appendInfoLine(info, duty.title, 'duty-info-muted');
                    const vehicleSel = document.querySelector('select[data-admin-key="vehicles"]');
                    const vehicleName = (vehicleSel && vehicleSel.value) || '';
                    if (!vehicleName) {
                        appendInfoLine(info, 'Select a vehicle to see the price.', 'duty-info-muted');
                    } else {
                        const price = transferDutyVehiclePrice(duty, vehicleName, data);
                        appendInfoLine(info, price == null ? 'No price for ' + vehicleName + ' on this duty.' : vehicleName + ': $' + price.toFixed(2), price == null ? 'duty-info-warn' : 'duty-info-price');
                    }
                    return true;
                }

                function getModalTransferRows() {
                    return Array.from(listEl.querySelectorAll('.modal-transfer-row'));
                }

                function addEmptyModalRow() {
                    return createModalTransferRow({});
                }

                function openForDayRow(dayRow) {
                    if (!dayRow) return;
                    currentDayRow = dayRow;
                    listEl.innerHTML = '';

                    const date = (dayRow.querySelector('.day-date-input') || {}).value || '';
                    const city = cityNameOf((dayRow.querySelector('.day-city-select') || {}).value || '');
                    dayLabelEl.textContent = [date, city ? cityNameOf(city) : ''].filter(Boolean).join(' · ');

                    let transfers = collectDayTransfers(dayRow);
                    if (!transfers.length) transfers.push({});

                    transfers.forEach(function(t) {
                        listEl.appendChild(createModalTransferRow(t));
                    });
                    syncDayTransferRemoveVisibility(listEl);

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    const firstInput = listEl.querySelector('.day-transfer-duty-input');
                    if (firstInput && !firstInput.value) firstInput.focus();
                }

                /** Yanlış duty kodu varsa false qaytarır (modal bağlanmır). */
                function saveModalToDayRow() {
                    if (!currentDayRow) return true;

                    let firstInvalid = null;
                    const modalTransfers = [];
                    getModalTransferRows().forEach(function(transferRow) {
                        if (!renderModalDutyInfo(transferRow) && !firstInvalid) {
                            firstInvalid = transferRow.querySelector('.day-transfer-duty-input');
                        }
                        const t = getTransfersFromTransferRow(transferRow);
                        if (hasTransferData(t)) modalTransfers.push(t);
                    });
                    if (firstInvalid) {
                        firstInvalid.focus();
                        return false;
                    }

                    persistDayTransfersToRow(currentDayRow, modalTransfers);
                    updateDayTransfersSummary(currentDayRow);
                    saveFormState();
                    updateSummary();
                    return true;
                }

                document.querySelectorAll('.day-transfers-modal-toggle').forEach(function(btn) {
                    btn.addEventListener('click', function() {
                        const row = btn.closest('.day-row');
                        openForDayRow(row);
                    });
                });

                const daysContainer = document.getElementById('days-container');
                if (daysContainer && !daysContainer._transfersModalToggleDelegated) {
                    daysContainer._transfersModalToggleDelegated = true;
                    daysContainer.addEventListener('click', function(e) {
                        const toggle = e.target.closest('.day-transfers-modal-toggle');
                        if (!toggle) return;
                        e.preventDefault();
                        openForDayRow(toggle.closest('.day-row'));
                    });
                }

                if (btnAdd && !btnAdd._transfersAddBound) {
                    btnAdd._transfersAddBound = true;
                    btnAdd.addEventListener('click', function() {
                        listEl.appendChild(addEmptyModalRow());
                        syncDayTransferRemoveVisibility(listEl);
                    });
                }

                if (!listEl._transfersRemoveDelegated) {
                    listEl._transfersRemoveDelegated = true;
                    listEl.addEventListener('click', function(e) {
                        const removeBtn = e.target.closest('.day-transfer-remove');
                        if (!removeBtn || removeBtn.classList.contains('hidden')) return;
                        e.preventDefault();
                        const transferRow = removeBtn.closest('.modal-transfer-row');
                        if (!transferRow) return;
                        if (listEl.querySelectorAll('.modal-transfer-row').length <= 1) return;
                        transferRow.remove();
                        syncDayTransferRemoveVisibility(listEl);
                    });
                }

                if (!listEl._transfersDutyDelegated) {
                    listEl._transfersDutyDelegated = true;
                    listEl.addEventListener('input', function(e) {
                        const input = e.target.closest('.day-transfer-duty-input');
                        if (!input) return;
                        renderModalDutyInfo(input.closest('.modal-transfer-row'));
                    });
                    // Datalist seçimi və ya blur: daxil edilmiş kodu tam etiketə çevir
                    listEl.addEventListener('change', function(e) {
                        const input = e.target.closest('.day-transfer-duty-input');
                        if (!input) return;
                        const data = window._lastAdminData || getAdminDataSync();
                        const duty = findTransferDuty(data, parseDutyCode(input.value));
                        if (duty) input.value = transferDutyOptionLabel(duty);
                        renderModalDutyInfo(input.closest('.modal-transfer-row'));
                    });
                }

                if (btnClose && !btnClose._transfersCloseBound) {
                    btnClose._transfersCloseBound = true;
                    btnClose.addEventListener('click', closeModal);
                }
                if (btnDone && !btnDone._transfersDoneBound) {
                    btnDone._transfersDoneBound = true;
                    btnDone.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (saveModalToDayRow()) closeModal();
                    });
                }
                if (!modal._transfersBackdropBound) {
                    modal._transfersBackdropBound = true;
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) closeModal();
                    });
                }
            }

            function refreshAllDayTransferSummaries() {
                document.querySelectorAll('#days-container .day-row').forEach(updateDayTransfersSummary);
            }

            window.__tourBuilderInitTransfersModal = initTransfersModal;

            initVehicleChangeHandler();

            function getHotelExtraFromRow(extraRow) {
                const sel = extraRow.querySelector('select[data-admin-key="hotel-extras"]');
                const qtyInput = extraRow.querySelector('.day-hotel-extra-qty');
                const type = (sel && sel.value) || '';
                const opt = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex] : null;
                return {
                    type: type,
                    qty: (qtyInput && qtyInput.value) || '0',
                    price: (opt && opt.dataset && opt.dataset.price) ? opt.dataset.price : ''
                };
            }

            function collectDayHotelExtras(dayRow) {
                const list = [];
                if (!dayRow) return list;
                const wrap = dayRow.querySelector('.day-hotel-extras-wrap');
                if (wrap && wrap.dataset.hotelExtrasJson) {
                    try {
                        const parsed = JSON.parse(wrap.dataset.hotelExtrasJson);
                        if (Array.isArray(parsed)) {
                            parsed.forEach(function(ex) {
                                if (ex && ex.type) list.push(ex);
                            });
                            if (list.length) return list;
                        }
                    } catch (e) { /* fallback */ }
                }
                dayRow.querySelectorAll('.day-hotel-extra-row').forEach(function(extraRow) {
                    const ex = getHotelExtraFromRow(extraRow);
                    if (ex.type) list.push(ex);
                });
                return list;
            }

            function persistDayHotelExtrasToRow(dayRow, extras) {
                if (!dayRow) return;
                const wrap = dayRow.querySelector('.day-hotel-extras-wrap');
                const listEl = dayRow.querySelector('.day-hotel-extras-list');
                if (!listEl) return;

                const rows = Array.isArray(extras) ? extras.filter(function(ex) {
                    return ex && ex.type;
                }) : [];
                if (wrap) {
                    wrap.dataset.hotelExtrasJson = JSON.stringify(rows);
                }

                let storageTemplate = listEl.querySelector('.day-hotel-extra-row');
                if (!storageTemplate) {
                    storageTemplate = document.createElement('div');
                    storageTemplate.className = 'day-hotel-extra-row flex gap-2';
                    storageTemplate.innerHTML = '<select class="form-input-compact flex-1" data-admin-key="hotel-extras"></select>' +
                        '<input type="number" min="0" step="1" class="form-input-compact w-14 day-hotel-extra-qty" placeholder="Qty" />';
                }
                const templateClone = storageTemplate.cloneNode(true);
                const data = window._lastAdminData || getAdminDataSync();
                const rowsToSave = rows.length ? rows : [];

                listEl.innerHTML = '';
                if (!rowsToSave.length) {
                    const emptyRow = templateClone.cloneNode(true);
                    populateHotelExtraRowSelects(dayRow, emptyRow, data);
                    listEl.appendChild(emptyRow);
                    return;
                }
                rowsToSave.forEach(function(ex) {
                    const extraRow = templateClone.cloneNode(true);
                    populateHotelExtraRowSelects(dayRow, extraRow, data);
                    const sel = extraRow.querySelector('select[data-admin-key="hotel-extras"]');
                    const qtyInput = extraRow.querySelector('.day-hotel-extra-qty');
                    if (sel && ex.type) sel.value = ex.type;
                    if (qtyInput && ex.qty) qtyInput.value = ex.qty;
                    listEl.appendChild(extraRow);
                });
            }

            function populateHotelExtraRowSelects(dayRow, extraRow, data) {
                const sel = extraRow.querySelector('select[data-admin-key="hotel-extras"]');
                if (!sel) return;
                const options = getHotelExtrasForDayRow(dayRow, data || window._lastAdminData || getAdminDataSync());
                populateSelect(sel, options, 'Select hotel extra');
            }

            function updateDayHotelExtrasSummary(dayRow) {
                if (!dayRow) return;
                const summaryEl = dayRow.querySelector('.day-hotel-extras-summary');
                if (!summaryEl) return;
                const hotelSel = dayRow.querySelector('select[data-admin-key="hotels"]');
                const hotel = (hotelSel && hotelSel.value) || '';
                const extras = collectDayHotelExtras(dayRow);
                if (!hotel) {
                    setFieldBoxSummary(summaryEl, 'Select a hotel first', false);
                    return;
                }
                if (!extras.length) {
                    setFieldBoxSummary(summaryEl, 'Add hotel extras', false);
                    return;
                }
                setFieldBoxSummary(summaryEl, extras.map(function(ex) {
                    const q = parseInt(ex.qty || '1', 10);
                    return ex.type + (q > 1 ? ' x' + q : '');
                }).join('; '), true);
            }

            /** Modal açan sahənin xülasəsi: mətn, tam mətn tooltip-də, seçim varsa tünd rəng. */
            function setFieldBoxSummary(summaryEl, text, hasValue) {
                if (!summaryEl) return;
                summaryEl.textContent = text;
                const box = summaryEl.closest('.day-field-box');
                if (box) {
                    box.classList.toggle('has-value', !!hasValue);
                    box.title = hasValue ? text : '';
                }
            }

            function updateDayActivitiesSummary(dayRow) {
                const summaryEl = dayRow ? dayRow.querySelector('.day-activities-summary') : null;
                const sel = dayRow ? dayRow.querySelector('.day-activity-select') : null;
                if (!summaryEl || !sel) return;
                const labels = Array.from(sel.selectedOptions || []).filter(function(o) {
                    return !!o.value;
                }).map(function(o) {
                    return o.text || o.value;
                });
                if (!labels.length) {
                    setFieldBoxSummary(summaryEl, 'Select activities', false);
                    return;
                }
                setFieldBoxSummary(summaryEl, (labels.length > 1 ? labels.length + ' activities: ' : '') + labels.join('; '), true);
            }

            function applyHotelExtrasToDayCard(card, dayData) {
                if (!card) return;
                const extras = Array.isArray(dayData.hotelExtras) ? dayData.hotelExtras.slice() : [];
                persistDayHotelExtrasToRow(card, extras);
                updateDayHotelExtrasSummary(card);
            }

            function initHotelExtrasModal() {
                const modal = document.getElementById('hotel-extras-modal');
                if (!modal || modal._hotelExtrasInitialized) return;

                const listEl = document.getElementById('hotel-extras-modal-list');
                const dayLabelEl = document.getElementById('hotel-extras-modal-day-label');
                const hotelLabelEl = document.getElementById('hotel-extras-modal-hotel-label');
                const emptyHintEl = document.getElementById('hotel-extras-modal-empty-hint');
                const btnClose = document.getElementById('hotel-extras-modal-close');
                const btnDone = document.getElementById('hotel-extras-modal-done');
                const btnAdd = document.getElementById('hotel-extras-modal-add');
                const rowTemplate = document.getElementById('hotel-extras-modal-row-template');
                if (!listEl || !rowTemplate || !btnDone) return;

                modal._hotelExtrasInitialized = true;
                let currentDayRow = null;

                function closeModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    currentDayRow = null;
                }

                function syncModalRemoveVisibility() {
                    const rows = listEl.querySelectorAll('.modal-hotel-extra-row');
                    rows.forEach(function(extraRow) {
                        const removeBtn = extraRow.querySelector('.day-hotel-extra-remove');
                        if (removeBtn) removeBtn.classList.toggle('hidden', rows.length <= 1);
                    });
                }

                function createModalHotelExtraRow(extraData) {
                    const extraRow = rowTemplate.querySelector('.modal-hotel-extra-row').cloneNode(true);
                    const data = window._lastAdminData || getAdminDataSync();
                    populateHotelExtraRowSelects(currentDayRow, extraRow, data);
                    const sel = extraRow.querySelector('select[data-admin-key="hotel-extras"]');
                    const qtyInput = extraRow.querySelector('.day-hotel-extra-qty');
                    if (sel && extraData.type) sel.value = extraData.type;
                    if (qtyInput && extraData.qty) qtyInput.value = extraData.qty;
                    if (!sel._hotelExtraModalBound) {
                        sel._hotelExtraModalBound = true;
                        sel.addEventListener('change', function() {
                            const opt = sel.options[sel.selectedIndex];
                            const price = opt && opt.dataset && opt.dataset.price ? parseFloat(opt.dataset.price) : null;
                            if (qtyInput && price != null && !isNaN(price) && (!qtyInput.value || parseInt(qtyInput.value, 10) < 1)) {
                                qtyInput.value = '1';
                            }
                        });
                    }
                    return extraRow;
                }

                function openForDayRow(dayRow) {
                    if (!dayRow) return;
                    currentDayRow = dayRow;
                    listEl.innerHTML = '';

                    const date = (dayRow.querySelector('.day-date-input') || {}).value || '';
                    const city = cityNameOf((dayRow.querySelector('.day-city-select') || {}).value || '');
                    const hotel = (dayRow.querySelector('select[data-admin-key="hotels"]') || {}).value || '';
                    dayLabelEl.textContent = [date, city ? cityNameOf(city) : ''].filter(Boolean).join(' · ');
                    if (hotelLabelEl) hotelLabelEl.textContent = hotel ? ('Hotel: ' + hotel) : 'No hotel selected';
                    if (emptyHintEl) emptyHintEl.classList.toggle('hidden', !!hotel);

                    let extras = collectDayHotelExtras(dayRow);
                    if (!extras.length) extras.push({
                        type: '',
                        qty: '0',
                        price: '0'
                    });

                    extras.forEach(function(ex) {
                        listEl.appendChild(createModalHotelExtraRow(ex));
                    });
                    syncModalRemoveVisibility();

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function saveModalToDayRow() {
                    if (!currentDayRow) return;
                    const modalExtras = [];
                    listEl.querySelectorAll('.modal-hotel-extra-row').forEach(function(extraRow) {
                        const ex = getHotelExtraFromRow(extraRow);
                        if (ex.type) modalExtras.push(ex);
                    });
                    persistDayHotelExtrasToRow(currentDayRow, modalExtras);
                    updateDayHotelExtrasSummary(currentDayRow);
                    saveFormState();
                    updateSummary();
                }

                const daysContainer = document.getElementById('days-container');
                if (daysContainer && !daysContainer._hotelExtrasModalToggleDelegated) {
                    daysContainer._hotelExtrasModalToggleDelegated = true;
                    daysContainer.addEventListener('click', function(e) {
                        const toggle = e.target.closest('.day-hotel-extras-modal-toggle');
                        if (!toggle) return;
                        e.preventDefault();
                        openForDayRow(toggle.closest('.day-row'));
                    });
                }

                if (btnAdd && !btnAdd._hotelExtrasAddBound) {
                    btnAdd._hotelExtrasAddBound = true;
                    btnAdd.addEventListener('click', function() {
                        listEl.appendChild(createModalHotelExtraRow({
                            type: '',
                            qty: '0',
                            price: '0'
                        }));
                        syncModalRemoveVisibility();
                    });
                }

                if (!listEl._hotelExtrasRemoveDelegated) {
                    listEl._hotelExtrasRemoveDelegated = true;
                    listEl.addEventListener('click', function(e) {
                        const removeBtn = e.target.closest('.day-hotel-extra-remove');
                        if (!removeBtn || removeBtn.classList.contains('hidden')) return;
                        e.preventDefault();
                        const extraRow = removeBtn.closest('.modal-hotel-extra-row');
                        if (!extraRow || listEl.querySelectorAll('.modal-hotel-extra-row').length <= 1) return;
                        extraRow.remove();
                        syncModalRemoveVisibility();
                    });
                }

                if (btnClose && !btnClose._hotelExtrasCloseBound) {
                    btnClose._hotelExtrasCloseBound = true;
                    btnClose.addEventListener('click', closeModal);
                }
                if (btnDone && !btnDone._hotelExtrasDoneBound) {
                    btnDone._hotelExtrasDoneBound = true;
                    btnDone.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        saveModalToDayRow();
                        closeModal();
                    });
                }
                if (!modal._hotelExtrasBackdropBound) {
                    modal._hotelExtrasBackdropBound = true;
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) closeModal();
                    });
                }
            }

            function refreshAllDayHotelExtrasSummaries() {
                document.querySelectorAll('#days-container .day-row').forEach(updateDayHotelExtrasSummary);
            }

            window.__tourBuilderInitHotelExtrasModal = initHotelExtrasModal;

            function getRoomCapacity(roomType) {
                const value = String(roomType || '').trim();
                const explicit = value.match(/(?:\(|\b)(\d+)\s*(?:p|pax|person)s?\b/i);
                if (explicit) return Math.max(1, parseInt(explicit[1], 10));
                if (/\bsingle\b/i.test(value)) return 1;
                if (/\b(?:double|twin)\b/i.test(value)) return 2;
                if (/\btriple\b/i.test(value)) return 3;
                if (/\bquad(?:ruple)?\b/i.test(value)) return 4;
                return null;
            }

            function updateSuggestedRoomCount(row, force) {
                if (!row) return;
                const roomSel = row.querySelector('select[data-admin-key="hotel-room-type"]');
                const countInput = row.querySelector('.day-room-count-input');
                const hint = row.querySelector('.day-room-capacity-hint');
                updateRoomAllocationSuggestion(roomSel, countInput, hint, force);
            }

            function updateRoomAllocationSuggestion(roomSel, countInput, hint, force) {
                if (!roomSel || !countInput) return;
                const capacity = getRoomCapacity(roomSel.value);
                if (hint) hint.textContent = capacity ? capacity + ' guest(s) per room' : '';
                if (!capacity || (!force && countInput.dataset.manual === '1')) return;
                countInput.value = String(Math.max(1, Math.ceil(getGuestCounts().totalPax / capacity)));
            }

            function refreshSuggestedRoomCounts() {
                document.querySelectorAll('#days-container .day-row').forEach(function(row) {
                    updateSuggestedRoomCount(row, false);
                    row.querySelectorAll('.day-additional-room-row').forEach(function(roomRow) {
                        updateRoomAllocationSuggestion(
                            roomRow.querySelector('select[data-admin-key="hotel-room-type"]'),
                            roomRow.querySelector('.day-room-count-input'),
                            roomRow.querySelector('.day-room-capacity-hint'),
                            false
                        );
                    });
                });
            }

            function collectDayRooms(row) {
                if (!row) return [];
                const rooms = [];
                const addRoom = function(roomSel, countInput) {
                    const roomType = (roomSel && roomSel.value) || '';
                    if (!roomType) return;
                    rooms.push({
                        roomType,
                        roomCount: Math.max(1, parseInt((countInput && countInput.value) || '1', 10) || 1)
                    });
                };
                addRoom(
                    row.querySelector(':scope > div > select.day-room-type-select, :scope > .block > select.day-room-type-select'),
                    row.querySelector(':scope > label > .day-room-count-input')
                );
                row.querySelectorAll('.day-additional-room-row').forEach(function(roomRow) {
                    addRoom(
                        roomRow.querySelector('select[data-admin-key="hotel-room-type"]'),
                        roomRow.querySelector('.day-room-count-input')
                    );
                });
                return rooms;
            }

            function addAdditionalRoomRow(dayRow, roomData) {
                const list = dayRow ? dayRow.querySelector('.day-additional-rooms-list') : null;
                if (!list) return;
                const roomRow = document.createElement('div');
                roomRow.className = 'day-additional-room-row';
                // Bir sətir: otaq tipi | say | sil — gün kartının hündürlüyünü az artırır
                roomRow.innerHTML = '<div class="day-additional-room-line">' +
                    '<select class="form-input-compact" data-admin-key="hotel-room-type" aria-label="Room type"></select>' +
                    '<input type="number" min="1" step="1" value="1" class="form-input-compact day-room-count-input" aria-label="Room count" />' +
                    '<button type="button" class="day-remove-room-type" aria-label="Remove room type" title="Remove room type">' +
                    '<span class="material-symbols-outlined" aria-hidden="true">close</span></button>' +
                    '</div>' +
                    '<span class="day-room-capacity-hint"></span>';
                list.appendChild(roomRow);

                const roomSel = roomRow.querySelector('select[data-admin-key="hotel-room-type"]');
                const countInput = roomRow.querySelector('.day-room-count-input');
                const hint = roomRow.querySelector('.day-room-capacity-hint');
                const data = window._lastAdminData || getAdminDataSync();
                populateSelect(roomSel, getRoomTypesForHotel(roomSel, data), 'No select');
                if (roomData && roomData.roomType) roomSel.value = roomData.roomType;
                if (roomData && roomData.roomCount != null) {
                    countInput.value = String(Math.max(1, parseInt(roomData.roomCount, 10) || 1));
                    countInput.dataset.manual = '1';
                } else {
                    countInput.dataset.manual = '1';
                }
                updateRoomAllocationSuggestion(roomSel, countInput, hint, false);

                roomSel.addEventListener('change', function() {
                    updateRoomAllocationSuggestion(roomSel, countInput, hint, false);
                    saveFormState();
                    updateSummary();
                });
                countInput.addEventListener('input', function() {
                    let count = parseInt(countInput.value || '1', 10);
                    if (!count || count < 1) count = 1;
                    countInput.value = String(count);
                    countInput.dataset.manual = '1';
                    saveFormState();
                    updateSummary();
                });
                roomRow.querySelector('.day-remove-room-type').addEventListener('click', function() {
                    roomRow.remove();
                    saveFormState();
                    updateSummary();
                });
            }

            function initRoomAllocationHandlers() {
                document.querySelectorAll('#days-container .day-row').forEach(function(row) {
                    const addButton = row.querySelector('.day-add-room-type');
                    if (!addButton || addButton._roomAddBound) return;
                    addButton._roomAddBound = true;
                    addButton.addEventListener('click', function() {
                        addAdditionalRoomRow(row, null);
                    });
                });
            }

            function initHotelChangeHandlers() {
                document.querySelectorAll('.day-row').forEach(function(row) {
                    const hotelSel = row.querySelector('select[data-admin-key="hotels"]');
                    const roomSel = row.querySelector('select[data-admin-key="hotel-room-type"]');
                    const countInput = row.querySelector('.day-room-count-input');
                    if (!hotelSel || !roomSel || hotelSel._hotelHandler) return;
                    hotelSel._hotelHandler = function() {
                        roomSel.value = '';
                        const extraRooms = row.querySelector('.day-additional-rooms-list');
                        if (extraRooms) extraRooms.innerHTML = '';
                        if (countInput) {
                            countInput.value = '1';
                            delete countInput.dataset.manual;
                        }
                        persistDayHotelExtrasToRow(row, []);
                        updateDayHotelExtrasSummary(row);
                        if (window._lastAdminData) doPopulateAllSelects(window._lastAdminData);
                        updateSummary();
                    };
                    hotelSel.addEventListener('change', hotelSel._hotelHandler);
                    roomSel.addEventListener('change', function() {
                        if (countInput) delete countInput.dataset.manual;
                        updateSuggestedRoomCount(row, true);
                        saveFormState();
                        updateSummary();
                    });
                    if (countInput) {
                        countInput.addEventListener('input', function() {
                            let count = parseInt(countInput.value || '1', 10);
                            if (!count || count < 1) count = 1;
                            countInput.value = String(count);
                            countInput.dataset.manual = '1';
                            saveFormState();
                            updateSummary();
                        });
                    }
                });
            }
            initHotelChangeHandlers();
            initRoomAllocationHandlers();

            // === Gün sətri helper (artıq accordion yoxdur) ===
            function initDayCard(card) {
                // hazırda xüsusi klik davranışı yoxdur
                return card;
            }

            function initActivityEntranceLinks(card) {
                if (!card) return;
                // Activity-entrance select link logic (stub – lazım olanda genişləndirilə bilər)
            }

            // === ADD DAY: yeni gün oluşturma + mevcut günler için toggle ===
            const addDayBtn = document.querySelector('.btn-add-day');
            const daysContainer = document.getElementById('days-container');

            function createDayCard(nextIndex) {
                if (!daysContainer) return null;

                const currentDays = daysContainer.querySelectorAll('.day-row');

                // İlk sətiri şablon kimi klonla ki, hər yeni gün
                // ilk gün sətirinə oxşar görünsün.
                const templateCard = daysContainer.querySelector('.day-row');
                if (!templateCard) return null;

                const dayCard = templateCard.cloneNode(true);

                resetDayToSingleTransferRow(dayCard);

                // Bütün select və input dəyərlərini sıfırla
                dayCard.querySelectorAll('select').forEach(function(sel) {
                    if (!sel.closest('.day-transfers-list')) sel.selectedIndex = 0;
                });

                dayCard.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                    cb.checked = false;
                });
                dayCard.querySelectorAll('.day-room-count-input').forEach(function(input) {
                    input.value = '1';
                    delete input.dataset.manual;
                });
                dayCard.querySelectorAll('.day-additional-rooms-list').forEach(function(list) {
                    list.innerHTML = '';
                });

                // Yeni günün tarixini avtomatik təyin et (son gün + 1)
                const lastCard = currentDays[currentDays.length - 1];
                const dateInput = dayCard.querySelector('.day-date-input');
                if (dateInput) {
                    if (lastCard) {
                        const lastDateInput = lastCard.querySelector('.day-date-input');
                        if (lastDateInput && lastDateInput.value) {
                            const d = new Date(lastDateInput.value);
                            d.setDate(d.getDate() + 1);
                            dateInput.value = d.toISOString().slice(0, 10);
                        }
                    } else {
                        const [checkinInput] = getCheckinCheckoutInputs();
                        if (checkinInput && checkinInput.value) {
                            dateInput.value = checkinInput.value;
                        }
                    }
                }

                daysContainer.appendChild(dayCard);

                resetDayToSingleTransferRow(dayCard);

                // Yeni kart üçün event-ləri yenidən qur
                initDayCard(dayCard);
                initMealToggles(dayCard);
                initCityChangeHandlers();
                initHotelChangeHandlers();
                initRoomAllocationHandlers();

                // Admin məlumatları ilə select-ləri doldur
                if (window._lastAdminData) doPopulateAllSelects(window._lastAdminData);
                else populateAllSelects();
                updateDayTransfersSummary(dayCard);
                updateDayHotelExtrasSummary(dayCard);
                updateSummary();

                return dayCard;
            }

            function getCheckinCheckoutInputs() {
                const checkin = document.querySelector('input[data-basics="checkin"]');
                const checkout = document.querySelector('input[data-basics="checkout"]');
                return [checkin, checkout];
            }

            // Check-in / check-out tarixlərinə görə gün sətirlərini avtomatik yarat
            function generateDaysFromDates() {
                if (!daysContainer) return;

                const [checkinInput, checkoutInput] = getCheckinCheckoutInputs();
                if (!checkinInput || !checkoutInput) return;

                const checkinVal = checkinInput.value;
                const checkoutVal = checkoutInput.value;
                if (!checkinVal || !checkoutVal) return;

                const start = new Date(checkinVal);
                const end = new Date(checkoutVal);
                if (isNaN(start.getTime()) || isNaN(end.getTime())) return;
                if (end < start) return;

                const diffMs = end.getTime() - start.getTime();
                const numDays = Math.round(diffMs / (1000 * 60 * 60 * 24)) + 1; // checkout günü də daxildir
                if (numDays <= 0 || numDays > 60) return;

                const templateCard = daysContainer.querySelector('.day-row');
                if (!templateCard) return;

                daysContainer.innerHTML = '';

                for (let i = 0; i < numDays; i++) {
                    const row = templateCard.cloneNode(true);

                    // form kontrollerlərini sıfırla — hər gün müstəqil (1-ci günün transferi köçməsin)
                    row.querySelectorAll('select').forEach(function(sel) {
                        sel.selectedIndex = 0;
                    });
                    row.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                        cb.checked = false;
                    });
                    row.querySelectorAll('.day-room-count-input').forEach(function(input) {
                        input.value = '1';
                        delete input.dataset.manual;
                    });
                    row.querySelectorAll('.day-additional-rooms-list').forEach(function(list) {
                        list.innerHTML = '';
                    });
                    resetDayToSingleTransferRow(row);
                    persistDayHotelExtrasToRow(row, []);

                    const dateInput = row.querySelector('.day-date-input');
                    if (dateInput) {
                        const d = new Date(start.getTime());
                        d.setDate(start.getDate() + i);
                        const iso = d.toISOString().slice(0, 10);
                        dateInput.value = iso;
                    }

                    daysContainer.appendChild(row);

                    initDayCard(row);
                    initMealToggles(row);
                    initCityChangeHandlers();
                    initHotelChangeHandlers();
                    initRoomAllocationHandlers();
                }

                if (window._lastAdminData) doPopulateAllSelects(window._lastAdminData);
                else populateAllSelects();

                refreshAllDayTransferSummaries();
                refreshAllDayHotelExtrasSummaries();
                saveFormState();
            }

            if (daysContainer) {
                const existingCards = daysContainer.querySelectorAll('.day-row');
                existingCards.forEach(function(card) {
                    initDayCard(card);
                    initMealToggles(card);
                    initCityChangeHandlers();
                    initHotelChangeHandlers();
                    updateDayTransfersSummary(card);
                    updateDayHotelExtrasSummary(card);
                });
            }

            if (addDayBtn && daysContainer) {
                addDayBtn.addEventListener('click', function() {
                    const currentDays = daysContainer.querySelectorAll('.day-row');
                    const nextIndex = currentDays.length + 1;
                    createDayCard(nextIndex);
                    saveFormState();
                });
            }

            function initDateRangeHandler() {
                const [checkinInput, checkoutInput] = getCheckinCheckoutInputs();
                if (!checkinInput || !checkoutInput) return;

                const handler = function() {
                    // Check-in dəyişəndə: checkout boşdursa və ya check-in-dən əvvəldirsə, avtomatik təyin et
                    if (this === checkinInput && checkinInput.value) {
                        const start = new Date(checkinInput.value);
                        if (!isNaN(start.getTime())) {
                            const checkoutDate = new Date(start);
                            checkoutDate.setDate(checkoutDate.getDate() + 1);
                            const checkoutVal = checkoutInput.value;
                            if (!checkoutVal || new Date(checkoutVal) <= start) {
                                checkoutInput.value = checkoutDate.toISOString().slice(0, 10);
                            }
                        }
                    }
                    generateDaysFromDates();
                    // Check-in dəyişəndə meal fiyatlarını sezona görə yenilə
                    if (typeof loadAdminDataWithCheckin === 'function') loadAdminDataWithCheckin();
                };

                ['change', 'blur', 'input'].forEach(function(evt) {
                    checkinInput.addEventListener(evt, handler);
                    checkoutInput.addEventListener(evt, handler);
                });
            }

            function setDefaultDates() {
                const [checkinInput, checkoutInput] = getCheckinCheckoutInputs();
                if (!checkinInput || !checkoutInput) return;
                const saved = localStorage.getItem('tour_builder_form_state');
                if (saved) return;
                const today = new Date();
                const tomorrow = new Date(today);
                tomorrow.setDate(tomorrow.getDate() + 1);
                checkinInput.value = today.toISOString().slice(0, 10);
                checkoutInput.value = tomorrow.toISOString().slice(0, 10);
            }

            // === FORM STATE PERSISTENCE (refresh sonrası yadda saxla) ===
            const FORM_STATE_KEY = 'tour_builder_form_state';

            function serializeForm() {
                const state = {};

                // Step 1 - Basics
                const [checkinInput, checkoutInput] = getCheckinCheckoutInputs();
                state.basics = {
                    country: (document.querySelector('select[data-admin-key="countries"]') || {}).value || '',
                    visa: (document.querySelector('select[data-admin-key="visa"]') || {}).value || '',
                    vehicle: (document.querySelector('select[data-admin-key="vehicles"]') || {}).value || '',
                    water: (document.querySelector('select[data-admin-key="water"]') || {}).value || '',
                    guide: (document.querySelector('select[data-admin-key="guides"]') || {}).value || '',
                    checkin: (checkinInput || {}).value || '',
                    checkout: (checkoutInput || {}).value || '',
                    adults: (document.querySelector('input[data-basics="adults"]') || {}).value || '',
                    children: (document.querySelector('input[data-basics="children"]') || {}).value || '',
                    insurance: !!(document.getElementById('insurance') && document.getElementById('insurance').checked)
                };

                const globalVehicle = (document.querySelector('select[data-admin-key="vehicles"]') || {}).value || '';

                // Step 2 - Days
                state.days = [];
                if (daysContainer) {
                    const cards = daysContainer.querySelectorAll('.day-row');
                    cards.forEach(function(card) {
                        const dayData = {};
                        dayData.date = (card.querySelector('.day-date-input') || {}).value || '';
                        dayData.city = (card.querySelector('.day-city-select') || {}).value || '';
                        dayData.hotel = (card.querySelector('select[data-admin-key="hotels"]') || {}).value || '';
                        dayData.roomType = (card.querySelector('select[data-admin-key="hotel-room-type"]') || {}).value || '';
                        dayData.roomCount = Math.max(1, parseInt((card.querySelector('.day-room-count-input') || {}).value || '1', 10) || 1);
                        dayData.rooms = collectDayRooms(card);
                        dayData.hotelExtras = collectDayHotelExtras(card);

                        const activitySel = card.querySelector('.day-activity-select') || card.querySelector('select[data-admin-key="tours"]');
                        const selectedActivityOpts = activitySel ? Array.from(activitySel.selectedOptions || []) : [];
                        const activityVals = selectedActivityOpts.map(function(o) {
                            return o.value || '';
                        }).filter(Boolean);
                        dayData.activities = selectedActivityOpts.map(function(o) {
                            const v = o.value || '';
                            if (!v) return null;
                            var pipeIdx = v.indexOf('|');
                            const rawTickets = (o.dataset && o.dataset.tickets) ? String(o.dataset.tickets).trim() : '';
                            let tickets = parseInt(rawTickets, 10);
                            if (!rawTickets || isNaN(tickets) || tickets < 1) tickets = null;
                            return {
                                value: v,
                                tour: pipeIdx >= 0 ? v.substring(0, pipeIdx) : v,
                                entrance: pipeIdx >= 0 ? v.substring(pipeIdx + 1) : '',
                                tickets: tickets
                            };
                        }).filter(Boolean);
                        dayData.tour = activityVals[0] ? (activityVals[0].indexOf('|') >= 0 ? activityVals[0].substring(0, activityVals[0].indexOf('|')) : activityVals[0]) : '';
                        dayData.entrance = activityVals[0] && activityVals[0].indexOf('|') >= 0 ? activityVals[0].substring(activityVals[0].indexOf('|') + 1) : '';

                        const dayTransfers = collectDayTransfers(card);
                        dayData.transfers = dayTransfers;
                        dayData.transferA = dayTransfers[0] ? dayTransfers[0].transferA : '';
                        dayData.transferB = dayTransfers[0] ? dayTransfers[0].transferB : '';
                        dayData.transferService = dayTransfers[0] ? dayTransfers[0].transferService : '';

                        dayData.vehicle = globalVehicle;
                        dayData.vehicles = globalVehicle ? [globalVehicle] : [];

                        const lunchSel = card.querySelector('select[data-admin-key="meals-lunch"]');
                        const dinnerSel = card.querySelector('select[data-admin-key="meals-dinner"]');
                        const lunchVal = lunchSel ? (lunchSel.value || '') : '';
                        const dinnerVal = dinnerSel ? (dinnerSel.value || '') : '';
                        dayData.meals = {
                            breakfast: {
                                checked: false,
                                option: ''
                            },
                            lunch: {
                                checked: !!lunchVal,
                                option: lunchVal,
                                options: lunchVal ? [lunchVal] : []
                            },
                            dinner: {
                                checked: !!dinnerVal,
                                option: dinnerVal,
                                options: dinnerVal ? [dinnerVal] : []
                            }
                        };

                        state.days.push(dayData);
                    });
                }

                // Step 3 - Extra services (multiple rows)
                state.extraServices = [];
                const extraRowsContainer = document.getElementById('extra-services-rows');
                if (extraRowsContainer) {
                    extraRowsContainer.querySelectorAll('.extra-service-row').forEach(function(row) {
                        const sel = row.querySelector('.extra-service-select');
                        const qtyInput = row.querySelector('.extra-service-qty');
                        const priceInput = row.querySelector('.extra-service-price');
                        const dateInput = row.querySelector('.extra-service-date');
                        const commentsInput = row.querySelector('.extra-service-comments');
                        const service = (sel && sel.value) || '';
                        const qty = (qtyInput && qtyInput.value) || '';
                        const price = (priceInput && priceInput.value) || '';
                        const date = (dateInput && dateInput.value) || '';
                        const comments = (commentsInput && commentsInput.value) || '';
                        if (service || price || date || comments || qty) {
                            state.extraServices.push({
                                service,
                                qty,
                                price,
                                date,
                                comments
                            });
                        }
                    });
                }

                return state;
            }

            function applyFormState(state) {
                if (!state) return;

                // Basics
                const b = state.basics || {};
                const countrySel = document.querySelector('select[data-admin-key="countries"]');
                if (countrySel && b.country) countrySel.value = b.country;

                const visaSel = document.querySelector('select[data-admin-key="visa"]');
                if (visaSel && b.visa) visaSel.value = b.visa;

                const vehicleSel = document.querySelector('select[data-admin-key="vehicles"]');
                if (vehicleSel && b.vehicle) vehicleSel.value = b.vehicle;

                const waterSel = document.querySelector('select[data-admin-key="water"]');
                if (waterSel && b.water) waterSel.value = b.water;

                const guideSel = document.querySelector('select[data-admin-key="guides"]');
                if (guideSel && b.guide) guideSel.value = b.guide;

                const [checkinInput, checkoutInput] = getCheckinCheckoutInputs();
                if (checkinInput && b.checkin) checkinInput.value = b.checkin;
                if (checkoutInput && b.checkout) checkoutInput.value = b.checkout;

                const adultsInput = document.querySelector('input[data-basics="adults"]');
                if (adultsInput && (b.adults || b.adults === 0 || b.adults === '0')) adultsInput.value = b.adults;

                const childrenInput = document.querySelector('input[data-basics="children"]');
                if (childrenInput && (b.children || b.children === 0 || b.children === '0')) childrenInput.value = b.children;

                const insuranceCb = document.getElementById('insurance');
                if (insuranceCb) insuranceCb.checked = !!b.insurance;

                // Extra service (Step 3)
                const e = state.extraService || {};
                const extraSel = document.getElementById('extra-service-select');
                if (extraSel && e.service) extraSel.value = e.service;
                const extraPriceInput = document.getElementById('extra-service-price');
                if (extraPriceInput && (e.price || e.price === 0 || e.price === '0')) extraPriceInput.value = e.price;
                const extraDateInput = document.getElementById('extra-service-date');
                if (extraDateInput && e.date) extraDateInput.value = e.date;
                const extraCommentsInput = document.getElementById('extra-service-comments');
                if (extraCommentsInput && e.comments) extraCommentsInput.value = e.comments;
                if (e.applyTo) {
                    const radio = document.querySelector('input[name="extra_service_day"][value="' + e.applyTo + '"]');
                    if (radio) radio.checked = true;
                }

                // Days
                const days = state.days || [];
                if (daysContainer) {
                    let cards = daysContainer.querySelectorAll('.day-row');

                    // Sətir sayını state-ə uyğunlaşdır
                    if (cards.length < days.length) {
                        for (let i = cards.length; i < days.length; i++) {
                            createDayCard(i + 1);
                        }
                    } else if (cards.length > days.length) {
                        for (let j = cards.length - 1; j >= days.length; j--) {
                            const card = cards[j];
                            if (card && card.parentNode === daysContainer) {
                                daysContainer.removeChild(card);
                            }
                        }
                    }

                    cards = daysContainer.querySelectorAll('.day-row');

                    days.forEach(function(dayData, idx) {
                        const card = cards[idx];
                        if (!card) return;

                        const dateInput = card.querySelector('.day-date-input');
                        if (dateInput && dayData.date) dateInput.value = dayData.date;

                        const citySel = card.querySelector('.day-city-select');
                        // Köhnə sifarişlər 'baku', 'qabala' saxlayır — koda çevrilir
                        if (citySel && dayData.city) citySel.value = cityCodeOf(dayData.city) || '';

                        const hotelSel = card.querySelector('select[data-admin-key="hotels"]');
                        if (hotelSel && dayData.hotel) hotelSel.value = dayData.hotel;

                        if (window._lastAdminData) doPopulateAllSelects(window._lastAdminData);
                        const roomTypeSel = card.querySelector('select[data-admin-key="hotel-room-type"]');
                        const savedRooms = Array.isArray(dayData.rooms) ? dayData.rooms : [];
                        const primaryRoom = savedRooms[0] || {};
                        const primaryRoomType = primaryRoom.roomType || dayData.roomType || '';
                        const primaryRoomCount = primaryRoom.roomCount != null ? primaryRoom.roomCount : dayData.roomCount;
                        if (roomTypeSel && primaryRoomType) roomTypeSel.value = primaryRoomType;
                        const roomCountInput = card.querySelector('.day-room-count-input');
                        if (roomCountInput) {
                            roomCountInput.value = String(Math.max(1, parseInt(primaryRoomCount || '1', 10) || 1));
                            if (primaryRoomCount != null) roomCountInput.dataset.manual = '1';
                        }
                        updateSuggestedRoomCount(card, false);
                        const additionalRoomsList = card.querySelector('.day-additional-rooms-list');
                        if (additionalRoomsList) additionalRoomsList.innerHTML = '';
                        savedRooms.slice(1).forEach(function(room) {
                            addAdditionalRoomRow(card, room);
                        });

                        applyHotelExtrasToDayCard(card, dayData);

                        const tourSel = card.querySelector('.day-activity-select') || card.querySelector('select[data-admin-key="tours"]');
                        if (tourSel) {
                            var acts = (Array.isArray(dayData.activities) && dayData.activities.length) ?
                                dayData.activities.map(function(a) {
                                    return a.value || '';
                                }).filter(Boolean) :
                                (dayData.tour ? [(dayData.entrance && (dayData.tour || '').indexOf('|') === -1) ? (dayData.tour + '|' + dayData.entrance) : dayData.tour] : []);
                            const ticketsByValue = {};
                            if (Array.isArray(dayData.activities)) {
                                dayData.activities.forEach(function(a) {
                                    if (!a || !a.value) return;
                                    if (a.tickets != null && a.tickets !== '' && !isNaN(a.tickets)) {
                                        ticketsByValue[a.value] = a.tickets;
                                    }
                                });
                            }
                            Array.from(tourSel.options || []).forEach(function(opt) {
                                const isSelected = acts.indexOf(opt.value) >= 0;
                                opt.selected = isSelected;
                                const v = opt.value || '';
                                if (v && ticketsByValue[v] != null && opt.dataset) {
                                    opt.dataset.tickets = String(ticketsByValue[v]);
                                }
                            });
                        }

                        if (window._lastAdminData) doPopulateAllSelects(window._lastAdminData);
                        applyTransfersToDayCard(card, dayData);

                        const meals = dayData.meals || {};
                        const lunchInfo = meals.lunch || {};
                        const dinnerInfo = meals.dinner || {};
                        const lunchVal = (Array.isArray(lunchInfo.options) && lunchInfo.options.length) ? lunchInfo.options[0] : (lunchInfo.option || '');
                        const dinnerVal = (Array.isArray(dinnerInfo.options) && dinnerInfo.options.length) ? dinnerInfo.options[0] : (dinnerInfo.option || '');
                        const lunchSel = card.querySelector('select[data-admin-key="meals-lunch"]');
                        if (lunchSel) lunchSel.value = lunchVal || '';
                        const dinnerSel = card.querySelector('select[data-admin-key="meals-dinner"]');
                        if (dinnerSel) dinnerSel.value = dinnerVal || '';

                        initActivityEntranceLinks(card);
                    });
                }
                // Checkboxları select-in yekun state-indən yenilə
                if (typeof enhanceMultiSelectsToCheckboxes === 'function') enhanceMultiSelectsToCheckboxes();
                if (typeof refreshAllDayTransferSummaries === 'function') refreshAllDayTransferSummaries();
                if (typeof refreshAllDayHotelExtrasSummaries === 'function') refreshAllDayHotelExtrasSummaries();
            }

            function saveFormState() {
                try {
                    const state = serializeForm();
                    localStorage.setItem(FORM_STATE_KEY, JSON.stringify(state));
                } catch (e) {
                    console.warn('Could not save form state', e);
                }
            }

            function loadFormState() {
                try {
                    const raw = localStorage.getItem(FORM_STATE_KEY);
                    if (!raw) return;
                    const state = JSON.parse(raw);
                    applyFormState(state);

                    // Əgər yaddaşda gün məlumatı yoxdursa, tarixdən auto-gün yarat
                    if (!state.days || !state.days.length) {
                        generateDaysFromDates();
                    }
                } catch (e) {
                    console.warn('Could not load form state', e);
                }
            }

            function syncExtraServiceDateLimits() {
                const [checkinInput, checkoutInput] = getCheckinCheckoutInputs();
                if (!checkinInput || !checkoutInput) return;
                const rowsContainer = document.getElementById('extra-services-rows');
                if (!rowsContainer) return;
                rowsContainer.querySelectorAll('.extra-service-date').forEach(function(input) {
                    syncExtraServiceDateLimitsForInput(input);
                });
            }

            function syncExtraServiceDateLimitsForInput(extraDateInput) {
                const [checkinInput, checkoutInput] = getCheckinCheckoutInputs();
                if (!extraDateInput || !checkinInput || !checkoutInput) return;
                const checkinVal = checkinInput.value;
                const checkoutVal = checkoutInput.value;
                if (!checkinVal || !checkoutVal) return;
                extraDateInput.min = checkinVal;
                extraDateInput.max = checkoutVal;
                if (!extraDateInput.value) {
                    extraDateInput.value = checkinVal;
                } else if (extraDateInput.value < checkinVal || extraDateInput.value > checkoutVal) {
                    extraDateInput.value = checkinVal;
                }
            }

            function getGuestCounts() {
                const adultsRaw = parseInt(document.querySelector('input[data-basics="adults"]')?.value || '1', 10);
                const childrenRaw = parseInt(document.querySelector('input[data-basics="children"]')?.value || '0', 10);
                const adults = isNaN(adultsRaw) ? 0 : Math.max(0, adultsRaw);
                const children = isNaN(childrenRaw) ? 0 : Math.max(0, childrenRaw);
                const totalPax = Math.max(1, adults + children);
                const adultMultiplier = Math.max(1, adults);
                const mealGuests = Math.max(1, adults + children);
                return {
                    adults,
                    children,
                    totalPax,
                    adultMultiplier,
                    mealGuests
                };
            }

            function normalizeLookupStr(s) {
                return String(s || '').trim().toLowerCase().replace(/\s+/g, ' ');
            }

            function isPerPersonActivity(label) {
                return /\bper\s*(?:person|pax)\b/i.test(String(label || ''));
            }

            function normalizeServiceKey(s) {
                const n = normalizeLookupStr(s).replace(/\t/g, '');
                if (n.indexOf('day trip') >= 0) return 'daytrip';
                if (n.indexOf('overnight') >= 0) return 'overnight';
                if (n.indexOf('private tour') >= 0) return 'privatetour';
                if (n.indexOf('private transfer') >= 0 || n.indexOf('privatetransfer') >= 0) return 'privatetransfer';
                if (n.indexOf('2 way') >= 0 || n.indexOf('2ways') >= 0) return 'twoway';
                if (n.indexOf('1 way') >= 0 || n.indexOf('1way') >= 0 || n.indexOf('airport transfer 1') >= 0) return 'oneway';
                if (n.indexOf('airport transfer 2') >= 0) return 'twoway';
                return n.replace(/[^a-z0-9]/g, '');
            }

            function servicesMatch(dbService, userService) {
                const a = normalizeServiceKey(dbService);
                const b = normalizeServiceKey(userService);
                return a !== '' && a === b;
            }

            /** Şəhər kodu/adı → transfers cədvəlindəki yer adı (QBL → Gabala). */
            function cityToTransferOrigin(city) {
                return cityCodeOf(city) ? cityNameOf(city) : 'Baku';
            }

            function buildPointACandidates(transA, city) {
                const candidates = [];
                const add = function(v) {
                    if (v && candidates.indexOf(v) < 0) candidates.push(v);
                };
                add(transA);
                const aNorm = normalizeLookupStr(transA);
                if (aNorm === 'hotel' || aNorm === 'hotel in region') {
                    add(cityToTransferOrigin(city));
                }
                if (aNorm.indexOf('airport') >= 0) {
                    add('Airport');
                }
                if (cityCodeOf(transA)) {
                    add(cityToTransferOrigin(transA));
                }
                return candidates;
            }

            function findRoutePriceMap(transferVehiclePrices, transA, transB, transSvc, city) {
                if (!transferVehiclePrices || !transA || !transB || !transSvc) return null;
                const bNorm = normalizeLookupStr(transB);
                const svcNorm = normalizeLookupStr(transSvc);
                const aCandidates = buildPointACandidates(transA, city);
                let i;
                let a;
                let exactKey;
                let want;
                let key;
                let parts;
                let got;

                for (i = 0; i < aCandidates.length; i++) {
                    a = aCandidates[i];
                    exactKey = a + '|' + transB + '|' + transSvc;
                    if (transferVehiclePrices[exactKey]) return transferVehiclePrices[exactKey];
                    want = normalizeLookupStr(a) + '|' + bNorm + '|' + svcNorm;
                    for (key in transferVehiclePrices) {
                        if (!Object.prototype.hasOwnProperty.call(transferVehiclePrices, key)) continue;
                        parts = key.split('|');
                        if (parts.length < 3) continue;
                        const ks = parts.slice(2).join('|');
                        if (!servicesMatch(ks, transSvc)) continue;
                        got = normalizeLookupStr(parts[0]) + '|' + normalizeLookupStr(parts[1]) + '|' + normalizeLookupStr(ks);
                        if (got === want) return transferVehiclePrices[key];
                    }
                }

                let best = null;
                let bestScore = 0;

                for (key in transferVehiclePrices) {
                    if (!Object.prototype.hasOwnProperty.call(transferVehiclePrices, key)) continue;
                    parts = key.split('|');
                    if (parts.length < 3) continue;
                    const ka = parts[0];
                    const kb = parts[1];
                    const ks = parts.slice(2).join('|');
                    if (normalizeLookupStr(kb) !== bNorm) continue;
                    if (!servicesMatch(ks, transSvc)) continue;

                    let score = 0;
                    const kaNorm = normalizeLookupStr(ka);
                    for (i = 0; i < aCandidates.length; i++) {
                        if (normalizeLookupStr(aCandidates[i]) === kaNorm) score += 100;
                    }
                    if (score > bestScore) {
                        bestScore = score;
                        best = transferVehiclePrices[key];
                    }
                }
                return bestScore >= 100 ? best : null;
            }

            function findVehiclePriceInMap(priceMap, vehicleName) {
                if (!priceMap || !vehicleName) return null;
                if (priceMap[vehicleName] != null && !isNaN(parseFloat(priceMap[vehicleName]))) {
                    return parseFloat(priceMap[vehicleName]);
                }
                const want = normalizeLookupStr(vehicleName);
                for (const name in priceMap) {
                    if (!Object.prototype.hasOwnProperty.call(priceMap, name)) continue;
                    if (normalizeLookupStr(name) === want && !isNaN(parseFloat(priceMap[name]))) {
                        return parseFloat(priceMap[name]);
                    }
                }
                return null;
            }

            function initVehicleChangeHandler() {
                const vehicleSel = document.querySelector('select[data-admin-key="vehicles"]');
                if (!vehicleSel || vehicleSel._vehicleHandler) return;
                vehicleSel._vehicleHandler = function() {
                    saveFormState();
                    updateSummary();
                };
                vehicleSel.addEventListener('change', vehicleSel._vehicleHandler);
            }

            function initGuestCountListeners() {
                ['adults', 'children'].forEach(function(key) {
                    const input = document.querySelector('input[data-basics="' + key + '"]');
                    if (!input) return;
                    const refresh = function() {
                        refreshSuggestedRoomCounts();
                        saveFormState();
                        updateSummary();
                    };
                    input.addEventListener('input', refresh);
                    input.addEventListener('change', refresh);
                });
            }

            function initAutoSave() {
                initGuestCountListeners();
                document.addEventListener('change', function() {
                    saveFormState();
                    updateSummary();
                    syncExtraServiceDateLimits();
                });
                document.addEventListener('input', function() {
                    saveFormState();
                    updateSummary();
                });
            }

            // === SUMMARY: seçimlərdən dinamik cədvəl və qiymət hesablaması ===
            function updateSummary() {
                const tbody = document.getElementById('summary-tbody');
                const perPersonEl = document.getElementById('summary-per-person');
                const grandTotalEl = document.getElementById('summary-grand-total');
                if (!tbody || !perPersonEl || !grandTotalEl) return;

                const data = window._lastAdminData || {};
                const activitiesByCity = data.activitiesByCity || (typeof ACTIVITIES_BY_CITY !== 'undefined' ? ACTIVITIES_BY_CITY : {});
                const mealsByCity = data.mealsByCity || {};
                const hotelPrices = data.hotelPrices || {};
                const hotelRoomPrices = data.hotelRoomPrices || {};
                const hotelExtrasMap = data.hotelExtras || {};
                const transferVehiclePrices = data.transferVehiclePrices || {};

                const [checkinInput, checkoutInput] = getCheckinCheckoutInputs();
                const checkin = (checkinInput && checkinInput.value) || '';
                const guestCounts = getGuestCounts();
                const adults = guestCounts.adults;
                const children = guestCounts.children;
                const totalPax = guestCounts.totalPax;
                const adultMultiplier = guestCounts.adultMultiplier;
                const mealGuests = guestCounts.mealGuests;
                const vehicleSel = document.querySelector('select[data-admin-key="vehicles"]');
                const visaSel = document.querySelector('select[data-admin-key="visa"]');
                const vehicleName = (vehicleSel && vehicleSel.value) || '';
                const visaName = (visaSel && visaSel.value) || '';
                const waterSel = document.querySelector('select[data-admin-key="water"]');
                const waterName = (waterSel && waterSel.value) || '';

                const numNights = (() => {
                    if (!checkin || !checkoutInput || !checkoutInput.value) return 0;
                    const s = new Date(checkin);
                    const e = new Date(checkoutInput.value);
                    if (isNaN(s.getTime()) || isNaN(e.getTime()) || e <= s) return 0;
                    return Math.round((e - s) / (1000 * 60 * 60 * 24));
                })();

                const rows = [];
                let totalAmount = 0;

                function addRow(cat, desc, qty, price) {
                    rows.push({
                        cat,
                        desc,
                        qty,
                        price
                    });
                    if (typeof price === 'number' && !isNaN(price)) totalAmount += price;
                }

                function findActivityPrice(value, city) {
                    const list = getActivitiesForCity(data, city);
                    const item = list.find(function(o) {
                        return o.value === value;
                    });
                    return (item && item.price != null && !isNaN(item.price)) ? parseFloat(item.price) : 0;
                }

                function findMealPrice(mealType, value, city) {
                    const byCity = mealsByCity[cityCodeOf(city) || DEFAULT_CITY_CODE] || mealsByCity[DEFAULT_CITY_CODE] || {};
                    const list = byCity[mealType] || [];
                    const item = Array.isArray(list) && list[0] && typeof list[0] === 'object' ? list.find(function(o) {
                        return (o.value || o.label) === value;
                    }) : null;
                    const p = item && (item.price != null || item.price2 != null) ? (parseFloat(item.price) || parseFloat(item.price2) || 0) : 0;
                    return p;
                }

                const hotelNights = {};
                const accommodationPerDay = [];
                const accommodationGroups = {};
                const transportLines = [];
                const activityItems = [];
                let activitiesTotal = 0;
                let mealsTotal = 0;
                let transportTotal = 0;

                function findHotelPrice(hotelName, roomType) {
                    if (!hotelName) return 0;
                    if (roomType && hotelRoomPrices[hotelName] && hotelRoomPrices[hotelName][roomType] != null) {
                        return parseFloat(hotelRoomPrices[hotelName][roomType]);
                    }
                    const exact = hotelPrices[hotelName];
                    if (exact != null && !isNaN(exact)) return parseFloat(exact);
                    const upper = hotelName.toUpperCase();
                    for (const k in hotelPrices) {
                        if (k.toUpperCase().indexOf(upper) >= 0 || upper.indexOf(k.toUpperCase()) >= 0) return parseFloat(hotelPrices[k]);
                    }
                    return 0;
                }

                function findHotelExtraUnitPrice(hotelName, extraType) {
                    if (!hotelName || !extraType) return 0;
                    const list = hotelExtrasMap[hotelName];
                    if (Array.isArray(list)) {
                        const item = list.find(function(o) {
                            return (o.value || o.label) === extraType;
                        });
                        if (item && item.price != null && !isNaN(item.price)) return parseFloat(item.price);
                    }
                    const upper = hotelName.toUpperCase();
                    for (const k in hotelExtrasMap) {
                        const ku = k.toUpperCase();
                        if (ku !== upper && ku.indexOf(upper) < 0 && upper.indexOf(ku) < 0) continue;
                        const match = (hotelExtrasMap[k] || []).find(function(o) {
                            return (o.value || o.label) === extraType;
                        });
                        if (match && match.price != null && !isNaN(match.price)) return parseFloat(match.price);
                    }
                    return 0;
                }

                function hotelExtraGuestMultiplier(extraType) {
                    const t = (extraType || '').toLowerCase();
                    if (/\bextra\s*bed\b/i.test(t)) return 1;
                    if (t.indexOf('child') >= 0 || t.indexOf('cnb') >= 0) return Math.max(0, children);
                    if (t.indexOf('adult') >= 0) return Math.max(0, adults);
                    return Math.max(1, totalPax);
                }

                (document.querySelectorAll('#days-container .day-row') || []).forEach(function(card) {
                    const city = getDayCityCode(card);
                    const mealCity = city;

                    const hotelSel = card.querySelector('select[data-admin-key="hotels"]');
                    const hotel = (hotelSel && hotelSel.value) || '';
                    if (hotel) {
                        hotelNights[hotel] = (hotelNights[hotel] || 0) + 1;
                        const dayRooms = collectDayRooms(card);
                        const pricedRooms = dayRooms.length ? dayRooms : [{
                            roomType: '',
                            roomCount: 1
                        }];
                        pricedRooms.forEach(function(room) {
                            const roomPrice = findHotelPrice(hotel, room.roomType);
                            accommodationPerDay.push(roomPrice * room.roomCount);
                            const groupKey = [hotel, room.roomType, room.roomCount].join('|');
                            if (!accommodationGroups[groupKey]) {
                                accommodationGroups[groupKey] = {
                                    hotel,
                                    roomType: room.roomType,
                                    roomCount: room.roomCount,
                                    nights: 0
                                };
                            }
                            accommodationGroups[groupKey].nights += 1;
                        });
                    }

                    collectDayHotelExtras(card).forEach(function(ex) {
                        const extraType = ex.type || '';
                        if (!extraType || !hotel) return;
                        let qty = parseInt(ex.qty || '0', 10);
                        if (!qty || isNaN(qty) || qty < 1) return;
                        const unit = findHotelExtraUnitPrice(hotel, extraType);
                        const mult = hotelExtraGuestMultiplier(extraType);
                        const lineTotal = unit * qty * mult;
                        if (lineTotal > 0) {
                            const isExtraBed = /\bextra\s*bed\b/i.test(extraType);
                            const quantityLabel = isExtraBed ? qty + ' bed(s)' : mult + ' guest(s)';
                            addRow('Hotel extras', hotel + ' – ' + extraType + ' x' + qty, quantityLabel, lineTotal);
                        }
                    });

                    updateDayActivitiesSummary(card);
                    const activitySel = card.querySelector('.day-activity-select') || card.querySelector('select[data-admin-key="activities"]');
                    const selectedActs = activitySel ? Array.from(activitySel.selectedOptions || []).filter(function(o) {
                        return !!o.value;
                    }) : [];
                    selectedActs.forEach(function(opt) {
                        const actVal = opt.value;
                        const label = opt.text || actVal;
                        const price = findActivityPrice(actVal, city);
                        const rawTickets = (opt.dataset && opt.dataset.tickets) ? String(opt.dataset.tickets).trim() : '';
                        let tickets = parseInt(rawTickets, 10);
                        if (!rawTickets || isNaN(tickets) || tickets < 1) {
                            tickets = isPerPersonActivity(label)
                                ? Math.max(1, totalPax)
                                : Math.max(1, adultMultiplier);
                        }
                        const lineTotal = price * tickets;
                        activityItems.push({
                            label,
                            price,
                            tickets: tickets,
                            lineTotal
                        });
                        activitiesTotal += lineTotal;
                    });

                    const lunchSel = card.querySelector('select[data-admin-key="meals-lunch"]');
                    const lunchVal = lunchSel ? (lunchSel.value || '') : '';
                    if (lunchVal) {
                        const p = findMealPrice('lunch', lunchVal, mealCity);
                        mealsTotal += p * mealGuests;
                    }
                    const dinnerSel = card.querySelector('select[data-admin-key="meals-dinner"]');
                    const dinnerVal = dinnerSel ? (dinnerSel.value || '') : '';
                    if (dinnerVal) {
                        const p = findMealPrice('dinner', dinnerVal, mealCity);
                        mealsTotal += p * mealGuests;
                    }

                    const dayDate = (card.querySelector('.day-date-input') || {}).value || '';
                    collectDayTransfers(card).forEach(function(t) {
                        const transA = t.transferA || '';
                        const transB = t.transferB || '';
                        const transSvc = t.transferService || '';
                        if (!hasTransferData(t)) return;
                        let unitPrice = 0;
                        const duty = t.dutyCode ? findTransferDuty(data, t.dutyCode) : null;
                        if (duty) {
                            // Duty kodu: qiymət birbaşa həmin duty-nin sətrindən
                            const found = transferDutyVehiclePrice(duty, vehicleName, data);
                            if (found != null) unitPrice = found;
                        } else if (transA && transB && transSvc && vehicleName) {
                            // Duty kodu olmayan köhnə sifarişlər: marşrut üzrə axtarış
                            const priceMap = findRoutePriceMap(transferVehiclePrices, transA, transB, transSvc, city);
                            const found = findVehiclePriceInMap(priceMap, vehicleName);
                            if (found != null && !isNaN(found)) unitPrice = found;
                        }
                        transportTotal += unitPrice;
                        transportLines.push({
                            desc: describeTransfer(t) + (vehicleName ? ' · ' + vehicleName : ''),
                            qty: dayDate || '1 Day',
                            price: unitPrice
                        });
                    });
                });

                let accommodationTotal = 0;
                if (accommodationPerDay.length > 0) {
                    accommodationTotal = accommodationPerDay.reduce(function(sum, p) {
                        return sum + (p || 0);
                    }, 0);
                } else {
                    for (const h in hotelNights) accommodationTotal += findHotelPrice(h) * hotelNights[h];
                }
                // Transfer qiymətləri maşın başına (transfers cədvəli) — böyüdülmür

                if (Object.keys(hotelNights).length > 0) {
                    const groups = Object.values(accommodationGroups);
                    const desc = groups.map(function(group) {
                        const roomLabel = group.roomType ? ' · ' + group.roomType : '';
                        return group.hotel + roomLabel + ' (' + group.roomCount + ' room(s) × ' + group.nights + ' night(s))';
                    }).join(', ');
                    const roomNights = groups.reduce(function(sum, group) {
                        return sum + (group.roomCount * group.nights);
                    }, 0);
                    addRow('Accommodation', desc, roomNights + ' room-night(s)', accommodationTotal);
                } else if (numNights > 0) {
                    addRow('Accommodation', '–', numNights + ' Nights', 0);
                }

                transportLines.forEach(function(line) {
                    addRow('Transportation', line.desc, line.qty, line.price);
                });

                if (activityItems.length > 0) {
                    const desc = activityItems.map(function(a) {
                        return a.label + (a.tickets > 1 ? ' (×' + a.tickets + ')' : '');
                    }).join('; ');
                    const totalTickets = activityItems.reduce(function(sum, a) {
                        return sum + (a.tickets || 1);
                    }, 0);
                    addRow('Activities', desc, totalTickets + ' ticket(s)', activitiesTotal);
                }

                if (mealsTotal > 0) addRow('Meals', 'Lunch & Dinner', numNights + ' Nights', mealsTotal);

                if (waterName && normalizeLookupStr(waterName) !== 'none') {
                    const selectedWaterOption = waterSel.options[waterSel.selectedIndex];
                    const waterPricePerPersonPerDay = selectedWaterOption && selectedWaterOption.dataset
                        ? parseFloat(selectedWaterOption.dataset.price || '0')
                        : 0;
                    const itineraryDays = Math.max(1, document.querySelectorAll('#days-container .day-row').length);
                    const waterTotal = (isNaN(waterPricePerPersonPerDay) ? 0 : waterPricePerPersonPerDay) * totalPax * itineraryDays;
                    addRow('Water Package', waterName, totalPax + ' guest(s) × ' + itineraryDays + ' day(s)', waterTotal);
                }

                const visaPrices = {
                    'Not Required': 0,
                    'Standard (3-5 Days)': 31,
                    'Urgent (3 Hours)': 65
                };
                const visaPricePer = visaPrices[visaName] != null ? visaPrices[visaName] : (visaName.toLowerCase().indexOf('urgent') >= 0 ? 65 : (visaName.toLowerCase().indexOf('standard') >= 0 ? 31 : 0));
                const visaTotal = visaPricePer * Math.max(0, adults);
                if (visaName && (visaTotal > 0 || visaName !== 'Not Required')) {
                    addRow('E-Visa', visaName, adults + ' Adults', visaTotal);
                }

                // Extra services (Step 3) - multiple rows
                (function() {
                    const rowsContainer = document.getElementById('extra-services-rows');
                    if (!rowsContainer) return;
                    rowsContainer.querySelectorAll('.extra-service-row').forEach(function(row) {
                        const sel = row.querySelector('.extra-service-select');
                        const qtyInput = row.querySelector('.extra-service-qty');
                        const priceInput = row.querySelector('.extra-service-price');
                        const dateInput = row.querySelector('.extra-service-date');
                        const commentsInput = row.querySelector('.extra-service-comments');
                        if (!sel || !priceInput) return;
                        const serviceVal = sel.value || '';
                        const serviceLabel = serviceVal ? (sel.options[sel.selectedIndex]?.text || serviceVal) : '';
                        const unitPrice = priceInput.value !== '' && !isNaN(parseFloat(priceInput.value)) ? parseFloat(priceInput.value) : 0;
                        let qtyVal = qtyInput ? parseInt(qtyInput.value || '1', 10) : 1;
                        if (!qtyVal || qtyVal < 1) qtyVal = 1;
                        const totalPrice = unitPrice * qtyVal;
                        // Xəta yalnız istifadəçi say/şərh yazıb, xidmət seçməyibsə göstərilir (tarix avtomatik doldurulur)
                        const errorEl = row.querySelector('.extra-service-error');
                        const rowTouched = !!((qtyInput && qtyInput.value) || (commentsInput && commentsInput.value));
                        if (errorEl) errorEl.classList.toggle('hidden', !!serviceVal || !rowTouched);
                        if (!serviceLabel || totalPrice <= 0) return;
                        const parts = [serviceLabel];
                        if (dateInput && dateInput.value) parts.push(dateInput.value);
                        if (commentsInput && commentsInput.value) parts.push(commentsInput.value);
                        addRow('Extra Services', parts.join(' — '), String(qtyVal), totalPrice);
                    });
                })();

                tbody.innerHTML = '';
                rows.forEach(function(r) {
                    // textContent: otel/aktivitet adları və istifadəçi şərhləri HTML kimi icra olunmasın
                    const tr = document.createElement('tr');
                    [['summary-td-cat', r.cat], ['summary-td-desc', r.desc], ['summary-td-qty', r.qty]].forEach(function(cell) {
                        const td = document.createElement('td');
                        td.className = cell[0];
                        td.textContent = cell[1] == null ? '' : String(cell[1]);
                        tr.appendChild(td);
                    });
                    tbody.appendChild(tr);
                });

                const perPerson = totalAmount / adultMultiplier;
                const grandTotal = totalAmount;
                perPersonEl.textContent = '$' + perPerson.toFixed(2);
                grandTotalEl.textContent = '$' + grandTotal.toFixed(2);
            }

            function initPdfExport() {
                const btnPdf = document.querySelector('.btn-pdf');
                if (!btnPdf) return;

                btnPdf.addEventListener('click', function() {
                    updateSummary();
                    const state = serializeForm();
                    const perPersonText = (document.getElementById('summary-per-person') && document.getElementById('summary-per-person').textContent) || '';
                    const grandTotalText = (document.getElementById('summary-grand-total') && document.getElementById('summary-grand-total').textContent) || '';

                    if (!state || !state.days || !state.days.length) {
                        alert('No days were found in the itinerary. Please fill in the check-in/check-out dates first.');
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'pdf.php';
                    form.target = '_blank';

                    const inputJson = document.createElement('input');
                    inputJson.type = 'hidden';
                    inputJson.name = 'order_data_json';
                    inputJson.value = JSON.stringify(state);

                    const inputPer = document.createElement('input');
                    inputPer.type = 'hidden';
                    inputPer.name = 'per_person';
                    inputPer.value = perPersonText;

                    const inputGrand = document.createElement('input');
                    inputGrand.type = 'hidden';
                    inputGrand.name = 'grand_total';
                    inputGrand.value = grandTotalText;

                    form.appendChild(inputJson);
                    form.appendChild(inputPer);
                    form.appendChild(inputGrand);
                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                });
            }

            // İlk yüklənmədə: avtomatik tarixlər, state bərpa, tarix handler
            setDefaultDates();
            initDateRangeHandler();
            loadFormState();
            // Yaddaşda gün yoxdursa və ya check-in/checkout dolu olduqda gün sətirlərini avtomatik yarat
            generateDaysFromDates();
            loadAdminDataWithCheckin();
            initActivitiesModal();
            refreshAllDayTransferSummaries();
            refreshAllDayHotelExtrasSummaries();
            initAutoSave();
            updateSummary();
            initPdfExport();

            // Sifariş yadda saxla (DB-ə)
            const btnSaveOrder = document.getElementById('btn-save-order');
            if (btnSaveOrder && typeof USER_LOGGED_IN !== 'undefined' && USER_LOGGED_IN) {
                btnSaveOrder.addEventListener('click', function() {
                    const state = serializeForm();
                    const grandTotalEl = document.getElementById('summary-grand-total');
                    let total = 0;
                    if (grandTotalEl && grandTotalEl.textContent) {
                        const m = grandTotalEl.textContent.replace(/[^0-9.]/g, '');
                        total = parseFloat(m) || 0;
                    }
                    const name = (state.basics && state.basics.checkin) ? 'Tour ' + state.basics.checkin : 'Order ' + new Date().toLocaleDateString('en-US');
                    btnSaveOrder.disabled = true;
                    btnSaveOrder.textContent = 'Saving...';
                    fetch('api/user-orders.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            name: name,
                            order_data: state,
                            total_amount: total
                        })
                    }).then(function(r) {
                        return r.json();
                    }).then(function(res) {
                        btnSaveOrder.disabled = false;
                        btnSaveOrder.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">cloud_upload</span> Save Order';
                        if (res.ok && res.price_mismatch) {
                            alert('Order saved. The total was recalculated with current prices: $' + Number(res.total_amount).toFixed(2) + '.');
                        } else if (res.ok) {
                            alert('Order saved successfully.');
                        } else {
                            alert(res.error || 'An error occurred.');
                        }
                    }).catch(function() {
                        btnSaveOrder.disabled = false;
                        btnSaveOrder.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px;">cloud_upload</span> Save Order';
                        alert('An error occurred.');
                    });
                });
            }
        });
    </script>

    <!-- Transfers – manage routes in modal -->
    <div id="transfers-modal" class="fixed inset-0 hidden items-center justify-center bg-black/50" style="z-index: 9999;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-4 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Transfers for this day</h3>
                    <p class="text-xs text-gray-500" id="transfers-modal-day-label"></p>
                </div>
                <button type="button" id="transfers-modal-close" class="p-2 rounded-full hover:bg-gray-100 text-gray-500">
                    <span class="material-symbols-outlined" style="font-size:18px;">close</span>
                </button>
            </div>
            <div class="px-6 py-4 overflow-y-auto flex-1">
                <div id="transfers-modal-list" class="space-y-3"></div>
                <button type="button" id="transfers-modal-add" class="btn-add-transfer mt-4">
                    <span class="material-symbols-outlined" style="font-size:14px;">add</span> ADD TRANSFER
                </button>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" id="transfers-modal-done" class="btn-primary">
                    Confirm transfers
                </button>
            </div>
        </div>
    </div>
    <div id="transfers-modal-row-template" class="hidden" aria-hidden="true">
        <div class="modal-transfer-row">
            <label class="block day-transfer-field">
                <span class="form-label" style="font-size:0.62rem;">Duty code</span>
                <input type="text" class="form-input-compact mt-1 day-transfer-duty-input" list="transfer-duty-options"
                    placeholder="e.g. 3 or Gobustan" autocomplete="off" spellcheck="false" />
            </label>
            <div class="day-transfer-duty-info" aria-live="polite"></div>
            <button type="button" class="day-transfer-remove text-xs text-red-500 self-end mb-1 hidden" title="Remove this transfer">Remove</button>
        </div>
    </div>
    <datalist id="transfer-duty-options"></datalist>

    <!-- Hotel extras – manage in modal -->
    <div id="hotel-extras-modal" class="fixed inset-0 hidden items-center justify-center bg-black/50" style="z-index: 9999;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Hotel extras for this day</h3>
                    <p class="text-xs text-gray-500" id="hotel-extras-modal-day-label"></p>
                    <p class="text-xs text-primary font-medium mt-0.5" id="hotel-extras-modal-hotel-label"></p>
                </div>
                <button type="button" id="hotel-extras-modal-close" class="p-2 rounded-full hover:bg-gray-100 text-gray-500">
                    <span class="material-symbols-outlined" style="font-size:18px;">close</span>
                </button>
            </div>
            <div class="px-6 py-4 overflow-y-auto flex-1">
                <p id="hotel-extras-modal-empty-hint" class="hidden text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3">
                    Please select a hotel for this day first. Extras are loaded per hotel from the price list.
                </p>
                <div id="hotel-extras-modal-list" class="space-y-3"></div>
                <button type="button" id="hotel-extras-modal-add" class="btn-add-transfer mt-4">
                    <span class="material-symbols-outlined" style="font-size:14px;">add</span> ADD HOTEL EXTRA
                </button>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" id="hotel-extras-modal-done" class="btn-primary">
                    Confirm hotel extras
                </button>
            </div>
        </div>
    </div>
    <div id="hotel-extras-modal-row-template" class="hidden" aria-hidden="true">
        <div class="modal-hotel-extra-row">
            <div class="block min-w-0">
                <span class="form-label" style="font-size:0.62rem;">Extra service</span>
                <select class="form-input-compact mt-1 w-full" data-admin-key="hotel-extras"></select>
            </div>
            <div class="block" style="width:72px;">
                <span class="form-label" style="font-size:0.62rem;">Qty</span>
                <input type="number" min="0" step="1" class="form-input-compact mt-1 w-full day-hotel-extra-qty" placeholder="1" />
            </div>
            <button type="button" class="day-hotel-extra-remove text-xs text-red-500 self-end mb-1 hidden whitespace-nowrap" title="Remove">Remove</button>
        </div>
    </div>

    <!-- Activities – extended selection modal (in front of the main page) -->
    <div id="activities-modal" class="fixed inset-0 hidden items-center justify-center bg-black/50" style="z-index: 9999;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-4 max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800">Activity – extended selection</h3>
                    <p class="text-xs text-gray-500" id="activities-modal-day-label"></p>
                </div>
                <button type="button" id="activities-modal-close" class="p-2 rounded-full hover:bg-gray-100 text-gray-500">
                    <span class="material-symbols-outlined" style="font-size:18px;">close</span>
                </button>
            </div>
            <div class="px-6 pt-4">
                <div class="modal-search">
                    <span class="material-symbols-outlined" aria-hidden="true">search</span>
                    <input type="search" id="activities-modal-search" placeholder="Search activity..." autocomplete="off" aria-label="Search activity" />
                    <span id="activities-modal-count" class="modal-search-count"></span>
                </div>
            </div>
            <div class="px-6 py-4 overflow-y-auto">
                <div id="activities-modal-list" class="space-y-2"></div>
                <p id="activities-modal-empty" class="hidden text-xs text-gray-500 py-4 text-center">No activities match your search.</p>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 flex justify-end">
                <button type="button" id="activities-modal-done" class="btn-primary">
                    Confirm selection
                </button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.__tourBuilderInitTransfersModal === 'function') {
                window.__tourBuilderInitTransfersModal();
            }
            if (typeof window.__tourBuilderInitHotelExtrasModal === 'function') {
                window.__tourBuilderInitHotelExtrasModal();
            }
            if (typeof window.__tourBuilderInitActivitiesModal === 'function') {
                window.__tourBuilderInitActivitiesModal();
            }
        });
    </script>
</body>

</html>
