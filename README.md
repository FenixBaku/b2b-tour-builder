# B2B Tour Builder — Destinations Azerbaijan

Tur agentlikləri (B2B tərəfdaşlar) üçün Azərbaycan turlarının qurulması və qiymətləndirilməsi sistemi:
günlük marşrut, otel, transfer (duty kodu ilə), aktivitet, yemək, viza və əlavə xidmətlər — avtomatik
qiymət hesabı, PDF proqram və sifariş/borc idarəetməsi.

## Funksiyalar

- **Tour Builder** (`tour-builder.php`) — günlər üzrə plan, axtarışlı seçimlər, canlı qiymət xülasəsi, PDF.
- **Transferlər duty kodu ilə** — Excel qiymət cədvəlindəki duty kodu transferin açarıdır; eyni marşrutun bir neçə duty-si ola bilər.
- **Şəhər kodları** — `BAK`, `QBL`, `SHD`, `SHK`, `SHM`, ... (`lib/cities.php`). Excel/köhnə yazılışlar avtomatik koda çevrilir.
- **Server tərəfində qiymət** — sifariş yadda saxlananda cəm `lib/order_pricing.php` ilə hesablanır (brauzerin göndərdiyi rəqəm yox).
- **Admin panel** (`tour-admin.php`) — otellər, aktivitetlər, transfer duty-ləri, yeməklər, Excel import, sifarişlər, ödənişlər/borclar, istifadəçilər.
- **İstifadəçi paneli** (`user-panel.php`) — sifarişlər, qəbz yükləmə, borclar.

## Texnologiya

PHP 8.1+ (tövsiyə: 8.3), MySQL/MariaDB, PhpSpreadsheet, PHPMailer, Tailwind (CDN), vanilla JS.

## Qovluq quruluşu

```
api/            JSON API-lər (konfiqurasiya, admin, sifarişlər, ödənişlər)
assets/         Ümumi frontend komponentləri (axtarışlı select)
auth/           Giriş, qeydiyyat (OTP), sessiya və email köməkçiləri
database/       Təmiz quraşdırma dump-ı (şəxsi məlumatsız)
lib/            Biznes məntiqi: şəhərlər, qiymət hesabı, Excel import
migrations/     SQL miqrasiyalar + run_migrations.php
tests/          CLI testlər (php tests/<ad>_test.php)
tools/          CLI alətlər (ilk admin yaratmaq)
uploads/        İstifadəçi qəbzləri (git-ə düşmür)
vendor/         Composer paketləri (repoya daxildir)
```

## Lokal quraşdırma (XAMPP)

1. Repo `C:\xampp\htdocs\bb` qovluğuna klonlanır (linklər `/bb/...` ilə yazılıb).
2. phpMyAdmin → `bb_tour_builder` bazası yaradın → `database/bb_tour_builder.sql` import edin.
3. `config.local.example.php` → `config.local.php` (lokal üçün default-lar kifayətdir, SMTP istəyə bağlıdır).
4. `php migrations/run_migrations.php`
5. Admin: `php tools/create_admin.php you@example.com`
6. Brauzer: `http://localhost/bb/`

## Deploy (cPanel hostinq)

### 1. Kodu serverə çəkmək — cPanel → **Git™ Version Control**
- **Create** → Clone URL: `https://github.com/FenixBaku/b2b-tour-builder.git`
- Repository Path: `public_html/bb` (sayt `https://domen/bb/` ünvanında açılacaq)
- Yeniləmə: həmin səhifədə **Pull or Deploy → Update from Remote**.

> Sayt `/bb/` alt qovluğunda işləyir. Kök domendə (`/`) işlətmək üçün koddakı `/bb/` yolları dəyişdirilməlidir.

### 2. Verilənlər bazası — cPanel → **MySQL Databases**
- Baza və istifadəçi yaradın, istifadəçiyə bazada **ALL PRIVILEGES** verin.
- **Yeni quraşdırma:** phpMyAdmin → `database/bb_tour_builder.sql` import.
- **Mövcud baza varsa:** dump import etməyin — yalnız 4-cü addımdakı miqrasiyaları işlədin.

### 3. Konfiqurasiya
`public_html/bb/config.local.php` yaradın (`config.local.example.php` nümunəsi ilə) — DB və SMTP məlumatları.
Bu fayl git-ə düşmür; parolları heç vaxt repoya yazmayın.

### 4. Miqrasiyalar və admin
- cPanel → **Terminal**: `cd public_html/bb && php migrations/run_migrations.php`
  (Terminal yoxdursa: admin kimi daxil olub `https://domen/bb/migrations/run_migrations.php` açın.)
- İlk admin: `php tools/create_admin.php admin@domen.az`

### 5. Yoxlama
- `uploads/receipts` qovluğu yazıla bilən olmalıdır (755).
- PHP versiyası: cPanel → **MultiPHP Manager** → 8.1+ (tövsiyə 8.3).
- `.htaccess` faylları (kök və `uploads/`) serverdə olmalıdır — daxili fayllar (SQL, Excel, `lib/`, `vendor/`, `.git`) URL ilə açılmır.
- Admin → Transferlər → 2026 Excel faylını **"Faylda olmayan duty kodlarını sil"** ilə yükləyin.

## Əsas qaydalar (inkişaf üçün)

- **Transfer = duty kodu.** Duty Code-u boş sətirlər import olunmur; eyni kod bir neçə vərəqdə varsa ilk vərəq əsasdır.
- **Şəhər = kod.** Yeni şəhər `lib/cities.php`-yə əlavə olunur.
- **Qiymət qaydası iki yerdədir:** `tour-builder.php` (`updateSummary`) və `lib/order_pricing.php`. Birini dəyişəndə digərini də dəyişin və testləri işlədin.
- **Axtarışlı select:** `assets/searchable-select.js` bütün `<select>`-ləri avtomatik genişləndirir; istisna üçün `data-no-search`.

## Testlər

```bash
for t in tests/*_test.php; do php "$t"; done
```

## Təhlükəsizlik

- Sirlər yalnız `config.local.php`-dədir (git-ə düşmür).
- Giriş cəhdləri limitlənir, sessiya cookie-si HttpOnly/SameSite, açıq yönləndirmə qadağandır.
- Sifariş cəmi serverdə hesablanır; Excel import yalnız tam admin üçündür.
