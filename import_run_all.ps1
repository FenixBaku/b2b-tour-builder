# Excel SQL fayllarini MySQL-a import et
# Evvelce: migrations/013_excel_pricing_structure.sql run olunmalidir
# Istifade: .\import_run_all.ps1

$DB = "bb_tour_builder"
$USER = "root"
$PASS = ""

$dir = Join-Path $PSScriptRoot "sql_import"
if (-not (Test-Path $dir)) {
    Write-Host "sql_import qovlugu yoxdur. Evvelce run edin: python import_all_excel_batched.py" -ForegroundColor Red
    exit 1
}

$order = @(
    "hotels_list.sql",
    (Get-ChildItem $dir -Filter "hotel_prices_*.sql" | Sort-Object Name),
    (Get-ChildItem $dir -Filter "transfer_*.sql" | Sort-Object Name),
    (Get-ChildItem $dir -Filter "activities_*.sql" | Sort-Object Name)
)

$args = "-u", $USER
if ($PASS) { $args += "-p$PASS" }
$args += $DB

foreach ($item in $order) {
    if ($item -is [System.IO.FileInfo]) {
        $path = $item.FullName
    } else {
        $path = Join-Path $dir $item
        if (-not (Test-Path $path)) { continue }
    }
    Write-Host "[$($item.Name)]"
    Get-Content $path -Raw | mysql @args
}
Write-Host "`nTamamlandi."
