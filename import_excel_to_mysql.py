# -*- coding: utf-8 -*-
"""
Excel fayllarını MySQL-ə yükləyir (insert / skip / update).
Layihədəki mövcud Python parserlərdən istifadə edir.

İstifadə:
  python import_excel_to_mysql.py --scan
  python import_excel_to_mysql.py file1.xlsx file2.xlsx
  python import_excel_to_mysql.py --json file1.xlsx
"""
from __future__ import print_function

import argparse
import importlib.util
import json
import os
import re
import sys

BASE = os.path.dirname(os.path.abspath(__file__))

DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "bb_tour_builder",
    "charset": "utf8mb4",
}

SKIP_DIRS = {"vendor", "node_modules", ".git", "temp", "__pycache__", "pyex", "data"}

FILE_PATTERNS = {
    "activities": ["activit"],
    "transfers": ["transfer"],
    "hotels": ["hotel", "sembark"],
    "meals": ["meal"],
}


def load_module(name, path):
    spec = importlib.util.spec_from_file_location(name, path)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


def connect_db():
    try:
        import pymysql
    except ImportError:
        print(json.dumps({"ok": False, "error": "pymysql quraşdırın: pip install pymysql openpyxl"}))
        sys.exit(1)
    return pymysql.connect(**DB_CONFIG)


def detect_type(path):
    fn = os.path.basename(path).lower()
    for kind, keys in FILE_PATTERNS.items():
        for k in keys:
            if k in fn:
                return kind
    return "unknown"


def find_xlsx_files(root):
    out = []
    for dirpath, dirnames, filenames in os.walk(root):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
        for fn in filenames:
            if fn.lower().endswith(".xlsx") and not fn.startswith("~$"):
                out.append(os.path.join(dirpath, fn))
    return sorted(out)


def vals_equal(a, b):
    if a is None and b is None:
        return True
    if a is None or b is None:
        return False
    if isinstance(a, float) or isinstance(b, float):
        try:
            return abs(float(a) - float(b)) < 0.001
        except (TypeError, ValueError):
            return str(a).strip() == str(b).strip()
    return str(a).strip() == str(b).strip()


def import_activities(conn, path, stats):
    mod = load_module("excel_to_sql", os.path.join(BASE, "excel_to_sql.py"))
    rows = mod.collect_all_rows(path)
    cur = conn.cursor()
    cols = [
        "name", "city", "service", "description", "open_time", "close_time",
        "duration_mins", "slots", "season", "adult_price", "child_price_6_12",
    ]

    for row in rows:
        name = (row[0] if len(row) > 0 else None) or ""
        name = str(name).strip()
        if not name:
            stats["skipped"] += 1
            continue
        city = mod.normalize_city(row[1] if len(row) > 1 else None)
        service = str(row[2] if len(row) > 2 else "").strip() or name
        desc = row[3] if len(row) > 3 else None
        open_t = row[4] if len(row) > 4 else None
        close_t = row[5] if len(row) > 5 else None
        dur = row[6] if len(row) > 6 else None
        if dur is not None and isinstance(dur, (int, float)):
            try:
                dur = int(dur)
            except (ValueError, TypeError):
                dur = None
        slots = row[7] if len(row) > 7 else None
        season = row[8] if len(row) > 8 else None
        adult = row[9] if len(row) > 9 else None
        child = row[10] if len(row) > 10 else None

        cur.execute(
            "SELECT id, description, open_time, close_time, duration_mins, slots, season, "
            "adult_price, child_price_6_12 FROM tour_azerbaijan_activities_2026 "
            "WHERE name=%s AND city=%s AND service=%s LIMIT 1",
            (name, city, service),
        )
        ex = cur.fetchone()
        if ex:
            rid = ex[0]
            old = ex[1:]
            new = (desc, open_t, close_t, dur, slots, season, adult, child)
            if all(vals_equal(old[i], new[i]) for i in range(len(old))):
                stats["unchanged"] += 1
            else:
                cur.execute(
                    "UPDATE tour_azerbaijan_activities_2026 SET description=%s, open_time=%s, "
                    "close_time=%s, duration_mins=%s, slots=%s, season=%s, adult_price=%s, "
                    "child_price_6_12=%s WHERE id=%s",
                    (desc, open_t, close_t, dur, slots, season, adult, child, rid),
                )
                stats["updated"] += 1
        else:
            cur.execute(
                "INSERT INTO tour_azerbaijan_activities_2026 "
                "(name, city, service, description, open_time, close_time, duration_mins, "
                "slots, season, adult_price, child_price_6_12) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)",
                (name, city, service, desc, open_t, close_t, dur, slots, season, adult, child),
            )
            stats["inserted"] += 1
    conn.commit()
    cur.close()
    stats["total"] = len(rows)


def table_exists(cur, name):
    cur.execute("SHOW TABLES LIKE %s", (name,))
    return cur.fetchone() is not None


def import_transfers(conn, path, stats):
    mod = load_module(
        "excel_to_transfers_sql",
        os.path.join(BASE, "pyex", "excel_to_transfers_sql.py"),
    )
    skipped = {}
    rows = mod.collect_wide_rows(path, skipped)
    if not rows and skipped.get("no_duty"):
        raise RuntimeError("Transfer faylında Duty Code sütunu boşdur. Hər sətrin A sütununda duty kodu olmalıdır.")
    stats["skipped_no_duty"] = skipped.get("no_duty", 0)
    stats["skipped_duplicate_duty"] = skipped.get("duplicate_duty", 0)
    cur = conn.cursor()
    if not table_exists(cur, "transfers"):
        cur.close()
        raise RuntimeError("transfers cədvəli yoxdur. migrations/020_excel_import_schema.sql işlədin.")

    price_cols = mod.OUTPUT_COLUMNS
    all_cols = [
        "duty_code", "a", "b", "service", "distance", "start_time",
        "duration_mins", "day_schedule",
    ] + price_cols

    for r in rows:
        duty = int(r["duty_code"])
        dist = r.get("distance")
        if dist is not None:
            dist = str(dist).strip() if dist is not None else None
        dur = r.get("duration")
        if dur is not None:
            try:
                dur = str(int(float(dur)))
            except (ValueError, TypeError):
                dur = str(dur) if dur else None
        start_t = r.get("start_time")
        if start_t is not None:
            start_t = str(start_t)
        vals = [
            duty, r["a"], r["b"], r["service"], dist, start_t, dur,
            r.get("day_schedule"),
        ]
        for c in price_cols:
            vals.append(r["prices"].get(c))

        # Duty kodu açardır: eyni kod varsa yenilənir, yoxdursa əlavə olunur.
        cur.execute(
            "SELECT " + ", ".join(all_cols[1:]) + " FROM transfers WHERE duty_code=%s LIMIT 1",
            (duty,),
        )
        ex = cur.fetchone()
        if ex:
            if all(vals_equal(ex[i], vals[i + 1]) for i in range(len(ex))):
                stats["unchanged"] += 1
            else:
                sets = ", ".join("%s=%%s" % c for c in all_cols[1:])
                cur.execute(
                    "UPDATE transfers SET " + sets + " WHERE duty_code=%s",
                    vals[1:] + [duty],
                )
                stats["updated"] += 1
        else:
            ph = ", ".join(["%s"] * len(all_cols))
            cur.execute(
                "INSERT INTO transfers (" + ", ".join(all_cols) + ") VALUES (" + ph + ")",
                vals,
            )
            stats["inserted"] += 1
    conn.commit()
    cur.close()
    stats["total"] = len(rows)


def import_hotels(conn, path, stats):
    hotels_mod = load_module("hotels_excel_to_sql", os.path.join(BASE, "hotels_excel_to_sql.py"))
    sembark_mod = load_module(
        "sembark_hotel_excel_to_full_sql",
        os.path.join(BASE, "sembark_hotel_excel_to_full_sql.py"),
    )

    hotels, prices = hotels_mod.collect_hotels_and_prices(path)
    cur = conn.cursor()
    has_tour_hotels = table_exists(cur, "tour_hotels")
    has_tour_prices = table_exists(cur, "tour_hotel_prices")
    has_listing = table_exists(cur, "hotel_listing_full")

    for i, name in enumerate(hotels, 1):
        if not has_tour_hotels:
            break
        region = "baku"
        if " - Gabala" in name or " - gabala" in name:
            region = "gabala"
        elif " - Shahdag" in name or " - shahdag" in name:
            region = "shahdag"
        cur.execute(
            "INSERT IGNORE INTO tour_hotels (name, region, sort_order) VALUES (%s, %s, %s)",
            (name, region, i),
        )
        if cur.rowcount:
            stats["hotels_inserted"] += 1
        else:
            stats["hotels_unchanged"] += 1

    for h, region, room, season, price in prices:
        if not has_tour_prices:
            break
        cur.execute(
            "SELECT id, price FROM tour_hotel_prices WHERE hotel_name=%s AND region=%s "
            "AND room_type=%s AND season_type=%s",
            (h, region, room, season),
        )
        ex = cur.fetchone()
        if ex:
            if vals_equal(ex[1], price):
                stats["prices_unchanged"] += 1
            else:
                cur.execute(
                    "UPDATE tour_hotel_prices SET price=%s WHERE id=%s",
                    (price, ex[0]),
                )
                stats["prices_updated"] += 1
        else:
            cur.execute(
                "INSERT INTO tour_hotel_prices (hotel_name, region, room_type, season_type, price) "
                "VALUES (%s,%s,%s,%s,%s)",
                (h, region, room, season, price),
            )
            stats["prices_inserted"] += 1

    if not has_listing:
        conn.commit()
        cur.close()
        stats["note"] = "hotel_listing_full cədvəli yoxdur; yalnız tour_hotels import edildi"
        stats["total_hotels"] = len(hotels) if has_tour_hotels else 0
        stats["total_prices"] = len(prices) if has_tour_prices else 0
        return

    sheets = sembark_mod.collect_all_data(path)
    import json as _json

    def sheet_to_region(sn):
        s = str(sn).strip().lower()
        if "gabala" in s or "qabala" in s:
            return "gabala"
        if "shahdag" in s:
            return "shahdag"
        if "sheki" in s or "shaki" in s:
            return "sheki"
        if "shamakhi" in s or "shamaxi" in s:
            return "shamakhi"
        return "baku"

    for sheet in sheets:
        name = sheet["name"]
        headers = sheet["headers"]
        rows = sheet["rows"]
        region = sheet_to_region(name)
        hotel_col = room_col = None
        price_cols = []
        for i, h in enumerate(headers):
            hl = h.lower()
            if "hotel" in hl or hl == "name" or "otel" in hl:
                hotel_col = i
            if "room" in hl or "otaq" in hl:
                room_col = i
            if "peak" in hl:
                price_cols.append((i, "peak"))
            elif "low" in hl:
                price_cols.append((i, "low"))
            elif re.match(r"^season\s*_?\s*2$", hl):
                price_cols.append((i, "low"))
            elif hl in ("price", "rate"):
                price_cols.append((i, "standard"))

        for row_idx, row in enumerate(rows):
            row_num = row_idx + 2
            hotel_name = ""
            if hotel_col is not None and hotel_col < len(row) and row[hotel_col]:
                hotel_name = str(row[hotel_col]).strip()
            room_type = ""
            if room_col is not None and room_col < len(row) and row[room_col]:
                room_type = str(row[room_col]).strip()
            price_val = None
            season_type = "peak"
            for pc, st in price_cols:
                if pc < len(row):
                    p = sembark_mod.parse_num(row[pc])
                    if p is not None and p > 0:
                        price_val = p
                        season_type = st
                        break
            extra = {}
            for i, h in enumerate(headers):
                if i < len(row) and row[i] is not None and str(row[i]).strip():
                    if (hotel_col is None or i != hotel_col) and (room_col is None or i != room_col):
                        if not any(pc == i for pc, _ in price_cols):
                            extra[h] = row[i]
            extra_json = _json.dumps(extra, ensure_ascii=False) if extra else None

            if hotel_name and room_type:
                cur.execute(
                    "SELECT id, hotel_name, room_type, season_type, price, col_extra "
                    "FROM hotel_listing_full WHERE hotel_name=%s AND room_type=%s "
                    "AND region=%s AND season_type=%s LIMIT 1",
                    (hotel_name, room_type, region, season_type),
                )
            else:
                cur.execute(
                    "SELECT id, hotel_name, room_type, season_type, price, col_extra "
                    "FROM hotel_listing_full WHERE sheet_name=%s AND region=%s AND row_num=%s LIMIT 1",
                    (name, region, row_num),
                )
            ex = cur.fetchone()
            if ex:
                old = (ex[1], ex[2], ex[3], ex[4], ex[5])
                new = (hotel_name or None, room_type or None, season_type, price_val, extra_json)
                if all(vals_equal(old[i], new[i]) for i in range(len(old))):
                    stats["listing_unchanged"] += 1
                else:
                    cur.execute(
                        "UPDATE hotel_listing_full SET hotel_name=%s, room_type=%s, season_type=%s, "
                        "price=%s, col_extra=%s WHERE id=%s",
                        (hotel_name or None, room_type or None, season_type, price_val, extra_json, ex[0]),
                    )
                    stats["listing_updated"] += 1
            else:
                cur.execute(
                    "INSERT INTO hotel_listing_full "
                    "(sheet_name, region, row_num, hotel_name, room_type, season_type, price, col_extra) "
                    "VALUES (%s,%s,%s,%s,%s,%s,%s,%s)",
                    (name, region, row_num, hotel_name or None, room_type or None, season_type, price_val, extra_json),
                )
                stats["listing_inserted"] += 1

    conn.commit()
    cur.close()
    stats["total_hotels"] = len(hotels)
    stats["total_prices"] = len(prices)


def import_file(conn, path):
    kind = detect_type(path)
    result = {"file": path, "type": kind, "ok": False, "error": None, "stats": {}}
    if kind == "unknown":
        result["error"] = "Fayl növü tanınmadı (adında activity, transfer, hotel və ya meal olmalıdır)"
        return result
    if not os.path.isfile(path):
        result["error"] = "Fayl tapılmadı"
        return result
    try:
        if kind == "activities":
            stats = {"inserted": 0, "updated": 0, "unchanged": 0, "skipped": 0, "total": 0}
            import_activities(conn, path, stats)
            result["stats"] = stats
        elif kind == "transfers":
            stats = {"inserted": 0, "updated": 0, "unchanged": 0, "total": 0}
            import_transfers(conn, path, stats)
            result["stats"] = stats
        elif kind == "hotels":
            stats = {
                "hotels_inserted": 0, "hotels_unchanged": 0,
                "prices_inserted": 0, "prices_updated": 0, "prices_unchanged": 0,
                "listing_inserted": 0, "listing_updated": 0, "listing_unchanged": 0,
            }
            import_hotels(conn, path, stats)
            result["stats"] = stats
        elif kind == "meals":
            result["error"] = "Yemək Excel importu hələ dəstəklənmir (excel_to_meals_sql.py ilə SQL generasiya edin)"
            return result
        result["ok"] = True
    except Exception as e:
        result["error"] = str(e)
    return result


def ensure_schema(conn):
    sql_path = os.path.join(BASE, "migrations", "020_excel_import_schema.sql")
    if not os.path.isfile(sql_path):
        return
    with open(sql_path, "r", encoding="utf-8") as f:
        sql = f.read()
    cur = conn.cursor()
    for stmt in sql.split(";"):
        s = stmt.strip()
        if s and not s.startswith("--"):
            try:
                cur.execute(s)
            except Exception:
                pass
    conn.commit()
    cur.close()


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("files", nargs="*", help="Excel faylları")
    parser.add_argument("--scan", action="store_true", help="Layihədəki bütün .xlsx faylları")
    parser.add_argument("--json", action="store_true", help="JSON çıxış")
    args = parser.parse_args()

    files = list(args.files)
    if args.scan:
        files = find_xlsx_files(BASE)

    if not files:
        msg = {"ok": False, "error": "Heç bir Excel faylı verilməyib. Faylları layihə qovluğuna qoyun və ya --scan istifadə edin."}
        print(json.dumps(msg, ensure_ascii=False) if args.json else msg["error"])
        sys.exit(1)

    conn = connect_db()
    ensure_schema(conn)
    results = []
    for path in files:
        results.append(import_file(conn, path))
    conn.close()

    out = {"ok": all(r.get("ok") for r in results), "results": results, "files_found": len(files)}
    if args.json:
        print(json.dumps(out, ensure_ascii=False))
    else:
        for r in results:
            print(r["file"], "->", r["type"], "OK" if r["ok"] else r["error"], r.get("stats"))


if __name__ == "__main__":
    main()
