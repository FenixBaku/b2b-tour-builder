# -*- coding: utf-8 -*-
"""
Bütün Excel fayllarından SQL import - AYRICA (batched) fayllar
"""

import os
import sys
import importlib.util

BASE = os.path.dirname(os.path.abspath(__file__))
OUTPUT_DIR = os.path.join(BASE, "sql_import")
BATCH_SIZE = 500

EXCEL_TRANSFERS = os.path.join(BASE, "Destinations Azerbaijan Transfers PVT 2026_31.12.2025.xlsx")
EXCEL_ACTIVITIES = os.path.join(BASE, "Destinations Azerbaijan Activities 2026_updated 31.12.2025.xlsx")
EXCEL_HOTELS = os.path.join(BASE, "Sembark Hotel Listing _Revise 10.12.2025 (1).xlsx")


def load_module(name, path):
    spec = importlib.util.spec_from_file_location(name, path)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


def write_batched_sql(files, table_name, columns, row_to_values, header_comment, use_ignore=True):
    created = []
    for i, (fname, rows) in enumerate(files):
        if not rows:
            continue

        path = os.path.join(OUTPUT_DIR, fname)
        os.makedirs(os.path.dirname(path), exist_ok=True)

        with open(path, "w", encoding="utf-8") as f:
            f.write("-- %s (part %d)\n" % (header_comment, i + 1))
            f.write("SET NAMES utf8mb4;\n\n")

            ins = "INSERT IGNORE INTO" if use_ignore else "INSERT INTO"
            f.write("%s `%s` (%s) VALUES\n" %
                    (ins, table_name, ",".join("`%s`" % c for c in columns)))

            vals = [row_to_values(r) for r in rows]
            f.write(",\n".join(vals))
            f.write(";\n")

        created.append((path, len(rows)))

    return created


# ============================================================
# TRANSFERS (region, location_type, duty_code YOXDUR)
# ============================================================

def run_transfers():
    mod = load_module("excel_transfer", os.path.join(BASE, "excel_transfer_to_sql.py"))

    if not os.path.isfile(EXCEL_TRANSFERS):
        print("Transfer Excel tapılmadı:", EXCEL_TRANSFERS)
        return []

    rows = mod.collect_rows(EXCEL_TRANSFERS)

    cols = [
        "point_a",
        "point_b",
        "route_description",
        "service",
        "distance_km",
        "start_time",
        "duration_min",
        "day_schedule",
        "valid_from",
        "valid_to",
        "vehicle",
        "price"
    ]

    def to_vals(r):
        dur = r.get("duration_min")
        if dur is not None:
            try:
                dur = int(float(dur))
            except:
                dur = None

        dist = r.get("distance_km")
        if dist is not None:
            try:
                dist = float(dist)
            except:
                dist = None

        v = (
            mod.to_sql_val(r["point_a"]),
            mod.to_sql_val(r["point_b"]),
            mod.to_sql_val(r["route_description"]),
            mod.to_sql_val(r["service"]),
            str(dist) if dist is not None else "NULL",
            mod.to_sql_val(r["start_time"]),
            str(dur) if dur is not None else "NULL",
            mod.to_sql_val(r["day_schedule"]),
            mod.to_date_val(r["valid_from"]),
            mod.to_date_val(r["valid_to"]),
            mod.to_sql_val(r["vehicle"]),
            str(r["price"]),
        )

        return "(" + ",".join(v) + ")"

    batches = []
    for i in range(0, len(rows), BATCH_SIZE):
        chunk = rows[i:i + BATCH_SIZE]
        batches.append(("transfer_%03d.sql" % (len(batches) + 1), chunk))

    return write_batched_sql(
        batches,
        "tour_transfer_prices",
        cols,
        to_vals,
        "Transfers PVT 2026",
        use_ignore=True
    )


# ============================================================
# ACTIVITIES (dəyişiklik yoxdur)
# ============================================================

def run_activities():
    mod = load_module("excel_activities", os.path.join(BASE, "excel_to_sql.py"))

    if not os.path.isfile(EXCEL_ACTIVITIES):
        print("Activities Excel tapılmadı:", EXCEL_ACTIVITIES)
        return []

    rows = mod.collect_all_rows(EXCEL_ACTIVITIES)

    cols = [
        "name", "city", "service", "description",
        "open_time", "close_time",
        "duration_mins", "slots",
        "season", "adult_price", "child_price_6_12"
    ]

    def to_vals(row):
        dur = row[6] if len(row) > 6 else None
        if isinstance(dur, (int, float)):
            try:
                dur = int(dur)
            except:
                dur = None

        v = (
            mod.to_sql_val(row[0] if len(row) > 0 else None),
            mod.to_sql_val(mod.normalize_city(row[1]) if len(row) > 1 else None),
            mod.to_sql_val(row[2] if len(row) > 2 else None),
            mod.to_sql_val(row[3] if len(row) > 3 else None),
            mod.to_sql_val(row[4] if len(row) > 4 else None),
            mod.to_sql_val(row[5] if len(row) > 5 else None),
            str(dur) if dur is not None else "NULL",
            mod.to_sql_val(row[7] if len(row) > 7 else None),
            mod.to_sql_val(row[8] if len(row) > 8 else None),
            mod.to_sql_val(row[9] if len(row) > 9 else None),
            mod.to_sql_val(row[10] if len(row) > 10 else None),
        )

        return "(" + ",".join(v) + ")"

    batches = []
    for i in range(0, len(rows), BATCH_SIZE):
        chunk = rows[i:i + BATCH_SIZE]
        batches.append(("activities_%03d.sql" % (len(batches) + 1), chunk))

    return write_batched_sql(
        batches,
        "tour_azerbaijan_activities_2026",
        cols,
        to_vals,
        "Activities 2026",
        use_ignore=False
    )


# ============================================================
# HOTELS (region silinib)
# ============================================================

def run_hotels():
    mod = load_module("excel_hotels", os.path.join(BASE, "hotels_excel_to_sql.py"))

    if not os.path.isfile(EXCEL_HOTELS):
        print("Hotel Excel tapılmadı:", EXCEL_HOTELS)
        return []

    hotels, prices = mod.collect_hotels_and_prices(EXCEL_HOTELS)
    created = []

    # Hotels list
    if hotels:
        hp = os.path.join(OUTPUT_DIR, "hotels_list.sql")
        os.makedirs(OUTPUT_DIR, exist_ok=True)

        with open(hp, "w", encoding="utf-8") as f:
            f.write("-- Hotel Listing\nSET NAMES utf8mb4;\n\n")
            f.write("INSERT IGNORE INTO `tour_hotels` (`name`, `sort_order`) VALUES\n")

            vals = []
            for i, name in enumerate(hotels, 1):
                vals.append("('%s', %d)" % (mod.escape_sql(name), i))

            f.write(",\n".join(vals) + ";\n")

        created.append((hp, len(hotels)))

    # Hotel prices
    if prices:
        def to_vals(t):
            h, r, room, season, p = t
            return "('%s', '%s', '%s', %.2f)" % (
                mod.escape_sql(h),
                mod.escape_sql(room),
                mod.escape_sql(season),
                p
            )

        batches = []
        for i in range(0, len(prices), BATCH_SIZE):
            chunk = prices[i:i + BATCH_SIZE]
            batches.append(("hotel_prices_%03d.sql" % (len(batches) + 1), chunk))

        for i, (fname, chunk) in enumerate(batches):
            path = os.path.join(OUTPUT_DIR, fname)
            with open(path, "w", encoding="utf-8") as f:
                f.write("-- Hotel prices (part %d)\nSET NAMES utf8mb4;\n\n" % (i + 1))
                f.write("INSERT INTO `tour_hotel_prices` (`hotel_name`, `room_type`, `season_type`, `price`) VALUES\n")
                vals = [to_vals(t) for t in chunk]
                f.write(",\n".join(vals))
                f.write("\nON DUPLICATE KEY UPDATE `price` = VALUES(`price`);\n")

            created.append((path, len(chunk)))

    return created


# ============================================================
# MAIN
# ============================================================

def main():
    os.makedirs(OUTPUT_DIR, exist_ok=True)

    print("Output:", OUTPUT_DIR)
    print("Batch size:", BATCH_SIZE)
    print()

    all_created = []
    all_created.extend(run_hotels())
    all_created.extend(run_transfers())
    all_created.extend(run_activities())

    print()
    for path, count in all_created:
        print("  ", path, "(%d sətir)" % count)

    print("\nCəmi %d fayl yaradıldı." % len(all_created))


if __name__ == "__main__":
    main()