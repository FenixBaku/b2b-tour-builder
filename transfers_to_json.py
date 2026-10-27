# -*- coding: utf-8 -*-
"""Transfers Excel-dan A, B, Service və Vehicles siyahılarını JSON fayla export edir.
   A, B, SERVICE regiona əsaslanır - hər sheet region kimi istifadə olunur."""
import openpyxl
import json
import os
import sys

DEFAULT_EXCEL = os.path.join(os.path.dirname(__file__), "Destinations Azerbaijan Transfers PVT 2026_31.12.2025.xlsx")
DEFAULT_JSON = os.path.join(os.path.dirname(__file__), "transfers_data.json")
EXCEL_PATH = sys.argv[1] if len(sys.argv) > 1 else DEFAULT_EXCEL
OUTPUT_JSON = sys.argv[2] if len(sys.argv) > 2 else DEFAULT_JSON

# Region adlarının normalizasiyası (sheet adı -> db region)
REGIONS = ['baku', 'gabala', 'shahdag', 'shamakhi', 'sheki', 'qabala', 'lahic']
DEFAULT_REGION = 'baku'


def sheet_to_region(sheet_name):
    """Sheet adından region təyin et. Əks halda DEFAULT_REGION."""
    s = str(sheet_name).strip().lower()
    for r in REGIONS:
        if r in s:
            return r
    return DEFAULT_REGION


def infer_region_from_ab(a_val, b_val, sheet_region):
    """A və B dəyərlərindən region əmələ gətir. Excel-də Region sütunu yoxdursa sheet_region istifadə olunur."""
    return sheet_region


def collect_transfers_data(path):
    """Excel-dan A, B, service və vehicles siyahılarını topla - regiona görə."""
    if not os.path.isfile(path):
        return {"byRegion": {}, "a": [], "b": [], "services": [], "vehicles": []}

    wb = openpyxl.load_workbook(path, read_only=True, data_only=True)
    a_set = set()
    b_set = set()
    services_set = set()
    vehicles_set = set()
    # region -> { a: set(), b: set(), services: set() }
    by_region = {}

    for sheet_name in wb.sheetnames:
        region = sheet_to_region(sheet_name)
        if region not in by_region:
            by_region[region] = {'a': set(), 'b': set(), 'services': set()}

        ws = wb[sheet_name]
        rows = list(ws.iter_rows(values_only=True))
        if len(rows) < 4:
            continue

        # Sətir 3 (index 3): vehicles sütun 8-dən (regiondan asılı deyil)
        row3 = rows[3] if len(rows) > 3 else []
        for c in range(8, len(row3)):
            v = row3[c] if c < len(row3) else None
            if v and str(v).strip():
                vehicles_set.add(str(v).strip())

        # Sətir 5-dən: A (sütun 1), B (sütun 2), Service (sütun 3) - regiona aid
        for row in rows[5:]:
            if row and len(row) >= 4:
                a_val = row[1]
                b_val = row[2]
                service_val = row[3]
                if a_val and str(a_val).strip():
                    val = str(a_val).strip()
                    a_set.add(val)
                    by_region[region]['a'].add(val)
                if b_val and str(b_val).strip():
                    val = str(b_val).strip()
                    b_set.add(val)
                    by_region[region]['b'].add(val)
                if service_val and str(service_val).strip():
                    val = str(service_val).strip()
                    services_set.add(val)
                    by_region[region]['services'].add(val)

    wb.close()

    # byRegion: region -> { a: [], b: [], services: [] } (sorted)
    by_region_out = {}
    for r, data in by_region.items():
        by_region_out[r] = {
            "a": sorted(data['a']),
            "b": sorted(data['b']),
            "services": sorted(data['services'])
        }

    return {
        "byRegion": by_region_out,
        "a": sorted(a_set),
        "b": sorted(b_set),
        "services": sorted(services_set),
        "vehicles": sorted(vehicles_set)
    }


def escape_sql(s):
    if s is None:
        return ""
    return str(s).replace("\\", "\\\\").replace("'", "''").replace("\r", " ").replace("\n", " ")


def write_seed_sql(data, sql_path):
    """A, B, services, vehicles üçün INSERT SQL yazır (veritabanına seed). Regiona görə."""
    lines = ["SET NAMES utf8mb4;", "-- A, B, SERVICE regiondan asılıdır", ""]
    by_region = data.get("byRegion", {})
    if not by_region:
        # Köhnə format - bütün məlumatı baku regionuna yaz
        by_region = {"baku": {"a": data.get("a", []), "b": data.get("b", []), "services": data.get("services", [])}}

    # A - regiona görə
    for region, rdata in sorted(by_region.items()):
        if rdata.get("a"):
            lines.append("-- A (region: %s)" % region)
            lines.append("INSERT IGNORE INTO `tour_transfer_point_a` (`name`, `region`, `sort_order`) VALUES")
            vals = []
            for i, name in enumerate(rdata["a"], 1):
                vals.append("('%s', '%s', %d)" % (escape_sql(name), escape_sql(region), i))
            lines.append(",\n".join(vals) + ";")
            lines.append("")

    # B - regiona görə
    for region, rdata in sorted(by_region.items()):
        if rdata.get("b"):
            lines.append("-- B (region: %s)" % region)
            lines.append("INSERT IGNORE INTO `tour_transfer_point_b` (`name`, `region`, `sort_order`) VALUES")
            vals = []
            for i, name in enumerate(rdata["b"], 1):
                vals.append("('%s', '%s', %d)" % (escape_sql(name), escape_sql(region), i))
            lines.append(",\n".join(vals) + ";")
            lines.append("")

    # Services - regiona görə
    for region, rdata in sorted(by_region.items()):
        if rdata.get("services"):
            lines.append("-- Services (region: %s)" % region)
            lines.append("INSERT IGNORE INTO `tour_transfer_services` (`name`, `region`, `sort_order`) VALUES")
            vals = []
            for i, name in enumerate(rdata["services"], 1):
                vals.append("('%s', '%s', %d)" % (escape_sql(name), escape_sql(region), i))
            lines.append(",\n".join(vals) + ";")
            lines.append("")
    # Vehicles -> tour_vehicles
    if data.get("vehicles"):
        lines.append("INSERT IGNORE INTO `tour_vehicles` (`name`, `sort_order`) VALUES")
        vals = []
        for i, name in enumerate(data["vehicles"], 1):
            vals.append("('%s', %d)" % (escape_sql(name), i))
        lines.append(",\n".join(vals) + ";")
    with open(sql_path, "w", encoding="utf-8") as f:
        f.write("\n".join(lines))
    print(f"Seed SQL: {sql_path}")


def main():
    data = collect_transfers_data(EXCEL_PATH)
    with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"Yazıldı: {OUTPUT_JSON} | A: {len(data['a'])}, B: {len(data['b'])}, Services: {len(data['services'])}, Vehicles: {len(data['vehicles'])}")

    sql_dir = sys.argv[3] if len(sys.argv) > 3 else os.path.join(os.path.dirname(__file__), "migrations")
    sql_path = os.path.join(sql_dir, "007_seed_transfer_points_by_region.sql")
    os.makedirs(os.path.dirname(sql_path), exist_ok=True)
    write_seed_sql(data, sql_path)


if __name__ == "__main__":
    main()
