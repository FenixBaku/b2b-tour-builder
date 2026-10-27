# -*- coding: utf-8 -*-
"""
Sembark Hotel Listing Excel-dan BÜTÜN məlumatı SQL faylına export edir.
Her bir sheet, her bir sütun və sətir SQL-də görünəcək.

İstifadə: python sembark_hotel_excel_to_full_sql.py [excel_path] [output.sql]
"""
import re
import os
import sys

try:
    import openpyxl
except ImportError:
    print("openpyxl quraşdırın: pip install openpyxl")
    sys.exit(1)

DEFAULT_EXCEL = os.path.join(os.path.dirname(__file__), "Sembark Hotel Listing _Revise 10.12.2025 xx.xlsx")
OUTPUT_SQL = os.path.join(os.path.dirname(__file__), "migx", "sembark_hotel_listing_full.sql")

EXCEL_PATH = sys.argv[1] if len(sys.argv) > 1 else DEFAULT_EXCEL
OUT_PATH = sys.argv[2] if len(sys.argv) > 2 else OUTPUT_SQL


def escape_sql(s):
    if s is None:
        return "NULL"
    t = str(s).strip()
    if t == "":
        return "NULL"
    escaped = t.replace("\\", "\\\\").replace("'", "''").replace("\r", " ").replace("\n", " ")
    return "'%s'" % escaped


def col_name_to_sql(name):
    """Excel sütun adını SQL sütun adına çevir"""
    if not name:
        return "col_unknown"
    s = re.sub(r'[^a-zA-Z0-9_]', '_', str(name).strip())
    s = re.sub(r'_+', '_', s).strip('_')
    if not s or s[0].isdigit():
        s = "col_" + s
    return (s[:64] or "col") if s else "col"


def parse_num(val):
    """Yalnız əsl qiymət ədədlərini parse et (tarixlər, il 2026 və s. istisna)"""
    if val is None:
        return None
    s = str(val).strip().replace(',', '.')
    nums = re.findall(r'\d+\.?\d*', s)
    if not nums:
        return None
    # Tarix/il - skip (2020-2030)
    if len(nums) >= 3 and any(len(n) >= 4 for n in nums):
        return None
    try:
        f = float(nums[0])
        if 2020 <= f <= 2030:  # İl kimi yoxla
            return None
        if 1 <= f <= 10000:  # Otaq qiyməti diapazonu
            return f
    except (ValueError, TypeError):
        pass
    return None


def collect_all_data(path):
    """Excel-dan bütün sheet, sütun və sətirləri topla"""
    if not os.path.isfile(path):
        print("Fayl tapılmadı: %s" % path)
        return []

    wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
    all_sheets = []

    for sheet_name in wb.sheetnames:
        ws = wb[sheet_name]
        rows = list(ws.iter_rows(values_only=True))
        if not rows:
            all_sheets.append({"name": sheet_name, "headers": [], "rows": []})
            continue

        header_row = rows[0]
        headers = [col_name_to_sql(cell) if cell else ("col_%d" % i) for i, cell in enumerate(header_row)]
        seen = {}
        for i, h in enumerate(headers):
            if h in seen:
                seen[h] += 1
                headers[i] = h + "_" + str(seen[h])
            else:
                seen[h] = 0

        data_rows = []
        for row in rows[1:]:
            if not row:
                continue
            data_rows.append(row)

        all_sheets.append({"name": sheet_name, "headers": headers, "rows": data_rows})

    wb.close()
    return all_sheets


def write_full_sql(sheets_data, sql_path):
    """Bütün məlumatı bir SQL faylına yaz"""
    lines = [
        "-- =============================================================================",
        "-- Sembark Hotel Listing _Revise 10.12.2025 - BÜTÜN məlumat Excel-dan",
        "-- python sembark_hotel_excel_to_full_sql.py ile yaradılıb",
        "-- =============================================================================",
        "SET NAMES utf8mb4;",
        "",
        "CREATE TABLE IF NOT EXISTS `sembark_hotel_listing_full` (",
        "  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,",
        "  `sheet_name` varchar(100) NOT NULL,",
        "  `region` varchar(50) NOT NULL DEFAULT 'baku',",
        "  `row_num` int(11) NOT NULL DEFAULT 0,",
        "  `hotel_name` varchar(255) DEFAULT NULL,",
        "  `room_type` varchar(255) DEFAULT NULL,",
        "  `season_type` varchar(50) DEFAULT 'peak',",
        "  `price` decimal(12,2) DEFAULT NULL,",
        "  `col_extra` text DEFAULT NULL COMMENT 'JSON: digər sütunlar',",
        "  PRIMARY KEY (`id`),",
        "  KEY `sheet_region` (`sheet_name`, `region`),",
        "  KEY `hotel_name` (`hotel_name`(100))",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "TRUNCATE TABLE `sembark_hotel_listing_full`;",
        ""
    ]

    def sheet_to_region(sn):
        s = str(sn).strip().lower()
        if 'gabala' in s or 'qabala' in s:
            return 'gabala'
        if 'shahdag' in s:
            return 'shahdag'
        if 'sheki' in s or 'shaki' in s:
            return 'sheki'
        if 'shamakhi' in s or 'shamaxi' in s:
            return 'shamakhi'
        return 'baku'

    total_inserts = 0
    for sheet in sheets_data:
        name = sheet["name"]
        headers = sheet["headers"]
        rows = sheet["rows"]
        region = sheet_to_region(name)

        hotel_col = None
        room_col = None
        price_cols = []
        for i, h in enumerate(headers):
            hl = h.lower()
            if 'hotel' in hl or 'name' in hl or 'otel' in hl:
                hotel_col = i
            if 'room' in hl or 'otaq' in hl:
                room_col = i
            if 'peak' in hl:
                price_cols.append((i, 'peak'))
            elif 'low' in hl:
                price_cols.append((i, 'low'))
            elif re.match(r'^season\s*_?\s*2$', hl):
                price_cols.append((i, 'low'))
            elif hl in ('price', 'rate'):
                price_cols.append((i, 'standard'))

        for row_idx, row in enumerate(rows):
            hotel_name = str(row[hotel_col]).strip() if hotel_col is not None and hotel_col < len(row) and row[hotel_col] else ""
            room_type = str(row[room_col]).strip() if room_col is not None and room_col < len(row) and row[room_col] else ""
            price_val = None
            season_type = 'peak'
            for pc, st in price_cols:
                if pc < len(row):
                    p = parse_num(row[pc])
                    if p is not None and p > 0:
                        price_val = p
                        season_type = st
                        break

            extra = {}
            for i, h in enumerate(headers):
                if i < len(row) and row[i] is not None and str(row[i]).strip():
                    if (hotel_col is None or i != hotel_col) and (room_col is None or i != room_col):
                        if not (price_cols and any(pc == i for pc, _ in price_cols)):
                            extra[h] = row[i]

            import json
            extra_json = json.dumps(extra, ensure_ascii=False) if extra else "NULL"
            if extra_json != "NULL":
                extra_json = "'" + extra_json.replace("\\", "\\\\").replace("'", "''") + "'"

            hotel_esc = escape_sql(hotel_name) if hotel_name else "NULL"
            room_esc = escape_sql(room_type) if room_type else "NULL"
            price_esc = "%.2f" % price_val if price_val is not None else "NULL"

            lines.append("INSERT INTO `sembark_hotel_listing_full` (`sheet_name`, `region`, `row_num`, `hotel_name`, `room_type`, `season_type`, `price`, `col_extra`) VALUES (%s, '%s', %d, %s, %s, '%s', %s, %s);"
                % (escape_sql(name), region, row_idx + 2, hotel_esc, room_esc, season_type, price_esc, extra_json))
            total_inserts += 1

        if rows:
            lines.append("")

    lines.append("-- Cəmi %d sətir" % total_inserts)
    lines.append("")
    lines.append("-- tour_hotels və tour_hotel_prices üçün: python hotels_excel_to_sql.py işlədin")
    lines.append("-- =============================================================================")

    os.makedirs(os.path.dirname(sql_path) if os.path.dirname(sql_path) else ".", exist_ok=True)
    with open(sql_path, "w", encoding="utf-8") as f:
        f.write("\n".join(lines))

    print("SQL yazıldı: %s | %d sətir" % (sql_path, total_inserts))
    return total_inserts


def write_raw_wide_sql(sheets_data, sql_path):
    """Əlavə: Geniş format - hər Excel sütunu ayrı SQL sütunu kimi (max 30 sütun)"""
    base, ext = os.path.splitext(sql_path)
    wide_path = base + "_raw_wide" + ext

    lines = [
        "-- Excel-in RAW exportu - hər sütun ayrıca",
        "SET NAMES utf8mb4;",
        ""
    ]

    for sheet in sheets_data:
        name = sheet["name"]
        headers = sheet["headers"]
        rows = sheet["rows"]
        safe_name = re.sub(r'[^a-z0-9_]', '_', str(name).lower())[:50] or "sheet"
        table_name = "sembark_raw_%s" % safe_name

        cols = ["`id` int AUTO_INCREMENT PRIMARY KEY", "`sheet` varchar(100)", "`row_num` int"]
        for i, h in enumerate(headers[:25]):
            cols.append("`%s` varchar(500)" % (h[:60] or "col_%d" % i))

        lines.append("DROP TABLE IF EXISTS `%s`;" % table_name)
        lines.append("CREATE TABLE `%s` (" % table_name)
        lines.append(",\n  ".join(cols))
        lines.append(") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;")
        lines.append("")

        col_list = "`sheet`, `row_num`"
        for h in headers[:25]:
            col_list += ", `%s`" % (h.replace("`", ""))
        for ri, row in enumerate(rows):
            vals = [escape_sql(name), str(ri + 2)]
            for i in range(min(len(headers), 25)):
                v = row[i] if i < len(row) else None
                vals.append(escape_sql(v))
            lines.append("INSERT INTO `%s` (%s) VALUES (%s);" % (table_name, col_list, ", ".join(vals)))
        lines.append("")

    with open(wide_path, "w", encoding="utf-8") as f:
        f.write("\n".join(lines))
    print("RAW wide SQL: %s" % wide_path)


def main():
    print("Excel: %s" % EXCEL_PATH)
    print("Output: %s" % OUT_PATH)
    data = collect_all_data(EXCEL_PATH)
    if not data:
        print("Heç bir məlumat tapılmadı.")
        return

    total = sum(len(s["rows"]) for s in data)
    print("Sheet sayı: %d, cəmi sətir: %d" % (len(data), total))

    write_full_sql(data, OUT_PATH)
    write_raw_wide_sql(data, OUT_PATH)


if __name__ == "__main__":
    main()
