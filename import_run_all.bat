@echo off
REM Excel SQL fayllarini MySQL-a import et
REM Evvəlce: migrations/013_excel_pricing_structure.sql run olunmalidir
REM MySQL: db=bb_tour_builder, user=root, pass= (bos)

set DB=bb_tour_builder
set USER=root
set PASS=

cd /d "%~dp0"

if not exist "sql_import" (
    echo sql_import qovlugu yoxdur. Evvelce run edin: python import_all_excel_batched.py
    pause
    exit /b 1
)

echo Import baslayir...
echo.

for %%f in (sql_import\hotels_list.sql) do (
    echo [%%f]
    mysql -u %USER% %PASS% %DB% < "%%f"
)
for %%f in (sql_import\hotel_prices_*.sql) do (
    echo [%%f]
    mysql -u %USER% %PASS% %DB% < "%%f"
)
for %%f in (sql_import\transfer_*.sql) do (
    echo [%%f]
    mysql -u %USER% %PASS% %DB% < "%%f"
)
for %%f in (sql_import\activities_*.sql) do (
    echo [%%f]
    mysql -u %USER% %PASS% %DB% < "%%f"
)

echo.
echo Tamamlandi.
pause
