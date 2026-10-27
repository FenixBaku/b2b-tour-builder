<?php

/**

 * Excel import mühiti testi (yalnız admin)

 */

header('Content-Type: application/json; charset=utf-8');



require_once __DIR__ . '/../auth/_auth_helper.php';

require_once __DIR__ . '/../lib/excel_import.php';



auth_require_admin_api();



$shellOk = excel_import_shell_available();

$usePhp = excel_import_use_php_engine() || !$shellOk;

$engine = $usePhp ? 'php' : 'python';



$out = [

    'engine' => $engine,

    'shell_available' => $shellOk,

    'exec_function_exists' => function_exists('exec'),

    'disable_functions' => ini_get('disable_functions') ?: '(boş)',

    'zip_extension' => extension_loaded('zip'),

    'vendor_autoload' => is_file(dirname(__DIR__) . '/vendor/autoload.php'),

];



if ($usePhp) {

    require_once __DIR__ . '/../lib/excel_xlsx.php';

    $out['php_spreadsheet'] = excel_xlsx_autoload() && class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory');

    $out['note'] = 'cPanel üçün PHP import aktiv (exec lazım deyil). vendor/ və ext-zip yoxlayın.';

} else {

    $python = excel_import_resolve_python();

    $cmdOut = [];

    $code = 1;

    if ($shellOk) {

        $cmd = escapeshellarg($python) . ' -c "import openpyxl, pymysql; print(\'OK\')" 2>&1';

        exec($cmd, $cmdOut, $code);

    }

    $out['python'] = $python;

    $out['python_modules_ok'] = $code === 0;

    $out['python_output'] = implode("\n", $cmdOut);

}



echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

