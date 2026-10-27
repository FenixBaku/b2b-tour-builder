# -*- coding: utf-8 -*-
"""
tour_transfer_prices_2026.sql faylını kiçik klasterlərə bölür.
Hər klaster: max ROWS_PER_PART sətir (MySQL/phpMyAdmin limitləri üçün).
İstifadə: python split_transfer_sql.py [input.sql] [rows_per_part]
"""
import os
import sys

DEFAULT_INPUT = os.path.join(os.path.dirname(__file__), "tour_transfer_prices_2026.sql")
ROWS_PER_PART = 500  # MySQL max_allowed_packet və phpMyAdmin limitləri üçün təhlükəsiz

INPUT_SQL = sys.argv[1] if len(sys.argv) > 1 else DEFAULT_INPUT
ROWS_LIMIT = int(sys.argv[2]) if len(sys.argv) > 2 else ROWS_PER_PART


def extract_insert_parts(path):
    """INSERT IGNORE... faylı parse edib value sətirlərini ayırır."""
    with open(path, "r", encoding="utf-8") as f:
        lines = f.readlines()

    rows = []
    for line in lines:
        line = line.strip()
        if line.startswith("("):
            # Son vergül və ya nöqtə-vergülü çıxar, amma içəridəki məzmunu saxla
            s = line.rstrip(",;").strip()
            if s.startswith("(") and s.endswith(")"):
                rows.append(s)
    return rows


def main():
    if not os.path.isfile(INPUT_SQL):
        print(f"Fayl tapılmadı: {INPUT_SQL}")
        sys.exit(1)

    rows = extract_insert_parts(INPUT_SQL)
    total = len(rows)
    if total == 0:
        print("Heç bir value sətiri tapılmadı.")
        sys.exit(1)

    base_dir = os.path.dirname(INPUT_SQL)
    base_name = os.path.splitext(os.path.basename(INPUT_SQL))[0]
    num_parts = (total + ROWS_LIMIT - 1) // ROWS_LIMIT

    created = []
    for p in range(num_parts):
        start = p * ROWS_LIMIT
        end = min(start + ROWS_LIMIT, total)
        batch = rows[start:end]
        part_num = p + 1
        out_path = os.path.join(base_dir, f"{base_name}_part_{part_num:02d}.sql")

        with open(out_path, "w", encoding="utf-8") as f:
            f.write("-- Destinations Azerbaijan Transfers PVT 2026 - Klaster ")
            f.write(f"{part_num}/{num_parts} (sətir {start + 1}-{end})\n")
            f.write("-- Hədəf: tour_transfer_prices\n")
            f.write("-- Migration 012 əvvəlcə işlədilməlidir\n\n")
            f.write("SET NAMES utf8mb4;\n\n")
            f.write("INSERT IGNORE INTO `tour_transfer_prices` ")
            f.write("(`region`,`duty_code`,`location_type`,`point_a`,`point_b`,`route_description`,")
            f.write("`service`,`distance_km`,`start_time`,`duration_min`,`day_schedule`,`valid_from`,`valid_to`,`vehicle`,`price`) ")
            f.write("VALUES\n")
            f.write(",\n".join(batch))
            f.write(";\n")

        created.append((out_path, len(batch)))
        print(f"Yaradıldı: {out_path} ({len(batch)} sətir)")

    print(f"\nCəmi: {total} sətir -> {num_parts} klaster (hər biri max {ROWS_LIMIT} sətir)")


if __name__ == "__main__":
    main()
