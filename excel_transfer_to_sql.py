# -*- coding: utf-8 -*-
"""
Destinations Azerbaijan Transfers PVT 2026 Excel -> SQL
Excel: A=Duty Code, B=Airport/Hotel, C=City, D=Service, E=Distance, F=Start Time,
       G=Duration(Min), H=Day Schedule, I=Pick-up From/To, J+=Vehicle columns (price)
Hər Excel sətri -> hər vehicle sütunu üçün ayrıca sətir (qiymət varsa)
"""
import openpyxl
import os
import sys
import re
from datetime import datetime

DEFAULT_EXCEL = os.path.join(os.path.dirname(__file__), "Destinations Azerbaijan Transfers PVT 2026_31.12.2025.xlsx")
DEFAULT_OUTPUT = os.path.join(os.path.dirname(__file__), "tour_transfer_prices_2026.sql")
EXCEL_PATH = sys.argv[1] if len(sys.argv) > 1 else DEFAULT_EXCEL
OUTPUT_SQL = sys.argv[2] if len(sys.argv) > 2 else DEFAULT_OUTPUT

# Sətir 1: Duty/A/B/Service..., sətir 4: vehicle başlıqları, məlumat sətir 6+
VEHICLE_HEADER_ROW = 4
DATA_START_ROW = 6
PRICE_COL_START = 9  # I sütunu (1-based)


def escape_sql(s):
    if s is None:
        return None
    return str(s).replace("\\", "\\\\").replace("'", "''").replace("\r", " ").replace("\n", " ").strip()


def to_sql_val(v):
    if v is None or v == "":
        return "NULL"
    if isinstance(v, (int, float)):
        try:
            if isinstance(v, float) and v == int(v):
                return str(int(v))
            return str(v)
        except (ValueError, TypeError):
            return "NULL"
    return "'" + escape_sql(str(v)) + "'"


def to_date_val(v):
    """'1 Jan 2026' və ya '31 Dec 2026' -> DATE format"""
    if v is None or str(v).strip() == "":
        return "NULL"
    s = str(v).strip()
    try:
        dt = datetime.strptime(s, "%d %b %Y")
        return f"'{dt.strftime('%Y-%m-%d')}'"
    except ValueError:
        return "NULL"


def normalize_region(v):
    if v is None or str(v).strip() == "":
        return "baku"
    c = str(v).strip().lower()
    m = {"shamakhi": "shamakhi", "shamaxı": "shamakhi", "qabala": "gabala", "qəbələ": "gabala",
         "lankaran": "lankaran", "lenkaran": "lankaran", "laqodek": "laqodek", "laqodexi": "laqodek",
         "ismayilli": "ismayilli", "zaqatala": "zaqatala", "zagatala": "zaqatala"}
    return m.get(c, c)


def parse_valid_dates(ws):
    """J2-dən '1 Jan 2026 - 31 Dec 2026' parse et"""
    j2 = ws.cell(row=2, column=10).value if ws.max_column >= 10 else None
    valid_from, valid_to = None, None
    if j2 and isinstance(j2, str) and "-" in j2:
        parts = j2.split("-", 1)
        if len(parts) == 2:
            valid_from = parts[0].strip()
            valid_to = parts[1].strip()
    return valid_from, valid_to


def get_vehicle_columns(ws):
    """Vehicle başlıq sətirindən sütun indekslərini al (0-based)"""
    vehicles = []
    for col in range(PRICE_COL_START, ws.max_column + 1):
        val = ws.cell(row=VEHICLE_HEADER_ROW, column=col).value
        if val and str(val).strip():
            vehicles.append((col - 1, str(val).strip()))
    return vehicles


def collect_rows(path):
    if not os.path.isfile(path):
        raise FileNotFoundError(f"Excel tapılmadı: {path}")
    wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
    out = []
    for sheet_name in wb.sheetnames:
        ws = wb[sheet_name]
        valid_from, valid_to = parse_valid_dates(ws)
        vehicle_cols = get_vehicle_columns(ws)
        rows = list(ws.iter_rows(values_only=True))
        for row_idx in range(DATA_START_ROW - 1, len(rows)):
            row = rows[row_idx]
            if not row or (len(row) > 0 and row[0] is None and row[2] is None and row[3] is None):
                continue
            duty_code = row[0] if len(row) > 0 else None
            point_a_raw = row[1] if len(row) > 1 else None
            point_b_raw = row[2] if len(row) > 2 else None
            service = row[3] if len(row) > 3 else None
            distance = row[4] if len(row) > 4 else None
            start_time = row[5] if len(row) > 5 else None
            duration = row[6] if len(row) > 6 else None
            day_schedule = row[7] if len(row) > 7 else None
            route_desc = point_b_raw
            region = normalize_region(point_a_raw)
            point_a = str(point_a_raw).strip() if point_a_raw else "Hotel"
            point_b = str(point_b_raw).strip() if point_b_raw else (str(service).strip() if service else "Transfer")
            if not point_a:
                point_a = "Hotel"
            if not point_b:
                point_b = "Transfer"
            for col_idx, vehicle_name in vehicle_cols:
                if col_idx < len(row):
                    price = row[col_idx]
                    if price is not None and str(price).strip() != "":
                        try:
                            p = float(price)
                            if p >= 0:
                                out.append({
                                    "region": region,
                                    "duty_code": duty_code,
                                    "location_type": location_type,
                                    "point_a": point_a,
                                    "point_b": point_b,
                                    "route_description": route_desc,
                                    "service": service,
                                    "distance_km": distance,
                                    "start_time": start_time,
                                    "duration_min": duration,
                                    "day_schedule": day_schedule,
                                    "valid_from": valid_from,
                                    "valid_to": valid_to,
                                    "vehicle": vehicle_name,
                                    "price": p,
                                })
                        except (ValueError, TypeError):
                            pass
    wb.close()
    return out


def main():
    rows = collect_rows(EXCEL_PATH)
    d = os.path.dirname(OUTPUT_SQL)
    if d:
        os.makedirs(d, exist_ok=True)
    with open(OUTPUT_SQL, "w", encoding="utf-8") as f:
        f.write("-- Destinations Azerbaijan Transfers PVT 2026 - Excel-dan generasiya\n")
        f.write("-- Hədəf: tour_transfer_prices\n")
        f.write("-- Migration 012 əvvəlcə işlədilməlidir (yeni sütunlar üçün)\n\n")
        f.write("SET NAMES utf8mb4;\n\n")
        f.write("INSERT IGNORE INTO `tour_transfer_prices` ")
        f.write("(`region`,`duty_code`,`location_type`,`point_a`,`point_b`,`route_description`,")
        f.write("`service`,`distance_km`,`start_time`,`duration_min`,`day_schedule`,`valid_from`,`valid_to`,`vehicle`,`price`) ")
        f.write("VALUES\n")
        vals = []
        for r in rows:
            dur = r["duration_min"]
            if dur is not None:
                try:
                    dur = int(float(dur))
                except (ValueError, TypeError):
                    dur = None
            dist = r["distance_km"]
            if dist is not None:
                try:
                    dist = float(dist)
                except (ValueError, TypeError):
                    dist = None
            v = (
                to_sql_val(r["region"]),
                to_sql_val(r["duty_code"]),
                to_sql_val(r["location_type"]),
                to_sql_val(r["point_a"]),
                to_sql_val(r["point_b"]),
                to_sql_val(r["route_description"]),
                to_sql_val(r["service"]),
                str(dist) if dist is not None else "NULL",
                to_sql_val(r["start_time"]),
                str(dur) if dur is not None else "NULL",
                to_sql_val(r["day_schedule"]),
                to_date_val(r["valid_from"]),
                to_date_val(r["valid_to"]),
                to_sql_val(r["vehicle"]),
                str(r["price"]),
            )
            vals.append("(" + ",".join(v) + ")")
        f.write(",\n".join(vals))
        f.write(";\n")
    print(f"Yazıldı: {OUTPUT_SQL} ({len(rows)} sətir)")


if __name__ == "__main__":
    main()
