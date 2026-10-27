<?php
/**
 * Excel → MySQL import köməkçiləri (Python import_excel_to_mysql.py)
 */
require_once __DIR__ . '/../config.php';

/**
 * Shell əmrləri (exec və s.) icazəlidirmi — cPanel-də çox vaxt deyil.
 */
function excel_import_shell_available() {
    if (!function_exists('exec')) {
        return false;
    }
    $disabled = strtolower((string) ini_get('disable_functions'));
    if ($disabled === '') {
        return true;
    }
    $list = array_map('trim', explode(',', $disabled));
    foreach (['exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen'] as $fn) {
        if (in_array($fn, $list, true)) {
            return false;
        }
    }
    return true;
}

/**
 * Default: PHP import (duty kodları, şəhər kodları və miqrasiyalar yalnız burada tam dəstəklənir).
 * Köhnə Python skripti yalnız config-də define('EXCEL_IMPORT_ENGINE', 'python'); olduqda işlədilir.
 */
function excel_import_use_php_engine() {
    return !defined('EXCEL_IMPORT_ENGINE') || EXCEL_IMPORT_ENGINE !== 'python';
}

/**
 * İşləyən python.exe tam yolunu tap (Apache/XAMPP PATH-dən asılı deyil).
 */
function excel_import_resolve_python() {
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    if (defined('PYTHON_BIN') && PYTHON_BIN !== '' && is_file(PYTHON_BIN)) {
        $resolved = PYTHON_BIN;
        return $resolved;
    }

    $candidates = [];

    if (defined('PHP_OS_FAMILY') && PHP_OS_FAMILY === 'Windows') {
        $localApp = getenv('LOCALAPPDATA');
        if ($localApp) {
            $glob = glob($localApp . '\\Python\\pythoncore-*\\python.exe') ?: [];
            rsort($glob);
            foreach ($glob as $p) {
                $candidates[] = $p;
            }
        }
        $userProfile = getenv('USERPROFILE');
        if ($userProfile) {
            foreach (['Python314', 'Python313', 'Python312', 'Python311'] as $ver) {
                $p = $userProfile . '\\AppData\\Local\\Programs\\Python\\' . $ver . '\\python.exe';
                if (is_file($p)) {
                    $candidates[] = $p;
                }
            }
        }
        $progFiles = getenv('ProgramFiles');
        if ($progFiles) {
            foreach (['Python314', 'Python313', 'Python312'] as $ver) {
                $p = $progFiles . '\\' . $ver . '\\python.exe';
                if (is_file($p)) {
                    $candidates[] = $p;
                }
            }
        }
        $candidates[] = 'C:\\Python314\\python.exe';
        $candidates[] = 'C:\\Python313\\python.exe';
    } else {
        $candidates = ['/usr/bin/python3', '/usr/local/bin/python3', 'python3', 'python'];
    }

    $candidates = array_values(array_unique($candidates));

    foreach ($candidates as $py) {
        if (!is_string($py) || $py === '') {
            continue;
        }
        if (strpos($py, DIRECTORY_SEPARATOR) !== false || strpos($py, '/') !== false) {
            if (!is_file($py)) {
                continue;
            }
        }
        if (!excel_import_shell_available()) {
            break;
        }
        $testCmd = escapeshellarg($py) . ' -c "import sys" 2>&1';
        $out = [];
        $code = 1;
        @exec($testCmd, $out, $code);
        if ($code === 0) {
            $resolved = $py;
            return $resolved;
        }
    }

    $resolved = (defined('PHP_OS_FAMILY') && PHP_OS_FAMILY === 'Windows') ? 'python' : 'python3';
    return $resolved;
}

function excel_import_script_path() {
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'import_excel_to_mysql.py';
}

function excel_import_find_xlsx($root = null) {
    $root = $root ?: dirname(__DIR__);
    $skip = ['vendor', 'node_modules', '.git', 'temp', '__pycache__', 'pyex', 'data'];
    $files = [];
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        foreach ($skip as $s) {
            if (strpos($path, DIRECTORY_SEPARATOR . $s . DIRECTORY_SEPARATOR) !== false) {
                continue 2;
            }
        }
        if (preg_match('/\.xlsx$/i', $file->getFilename()) && strpos($file->getFilename(), '~$') !== 0) {
            $files[] = $path;
        }
    }
    sort($files);
    return $files;
}

function excel_import_detect_type($filename) {
    $fn = strtolower($filename);
    if (strpos($fn, 'activit') !== false) {
        return 'activities';
    }
    if (strpos($fn, 'transfer') !== false) {
        return 'transfers';
    }
    if (strpos($fn, 'hotel') !== false || strpos($fn, 'sembark') !== false) {
        return 'hotels';
    }
    if (strpos($fn, 'meal') !== false) {
        return 'meals';
    }
    return 'unknown';
}

/**
 * @param string[] $paths
 * @return array{ok:bool, results?:array, error?:string, log?:string, python?:string}
 */
function excel_import_to_mysql(array $paths, array $options = []) {
    $needsNativeMeals = false;
    foreach ($paths as $candidatePath) {
        if (excel_import_detect_type(basename((string) $candidatePath)) === 'meals') {
            $needsNativeMeals = true;
            break;
        }
    }
    if ($needsNativeMeals || excel_import_use_php_engine() || !excel_import_shell_available() || !empty($options['remove_missing_duties'])) {
        require_once __DIR__ . '/excel_import_native.php';
        return excel_import_native_to_mysql($paths, $options);
    }

    $script = excel_import_script_path();
    if (!is_file($script)) {
        require_once __DIR__ . '/excel_import_native.php';
        return excel_import_native_to_mysql($paths);
    }

    $valid = [];
    foreach ($paths as $p) {
        $p = realpath($p);
        if ($p && is_file($p) && preg_match('/\.xlsx$/i', $p)) {
            $valid[] = $p;
        }
    }
    if ($valid === []) {
        return ['ok' => false, 'error' => 'Keçərli .xlsx faylı yoxdur'];
    }

    $python = excel_import_resolve_python();
    $parts = [escapeshellarg($python), escapeshellarg($script), '--json'];
    foreach ($valid as $p) {
        $parts[] = escapeshellarg($p);
    }
    $cmd = implode(' ', $parts) . ' 2>&1';
    $cwd = dirname(__DIR__);

    $output = [];
    $exitCode = 1;
    $oldCwd = getcwd();
    if (is_dir($cwd)) {
        chdir($cwd);
    }
    exec($cmd, $output, $exitCode);
    if ($oldCwd !== false) {
        chdir($oldCwd);
    }

    $log = implode("\n", $output);
    if ($log === '' && $exitCode !== 0) {
        $log = 'Exit code: ' . $exitCode . '. Python: ' . $python;
        if (strpos($python, DIRECTORY_SEPARATOR) === false && strpos($python, '/') === false) {
            $log .= ' (tam yol tapılmadı — config.php-də PYTHON_BIN təyin edin)';
        }
    }

    $jsonLine = '';
    foreach (array_reverse($output) as $line) {
        $line = trim($line);
        if ($line !== '' && isset($line[0]) && $line[0] === '{') {
            $jsonLine = $line;
            break;
        }
    }

    if ($jsonLine === '') {
        return [
            'ok' => false,
            'error' => 'Import skripti cavab vermədi. Python/openpyxl/pymysql quraşdırılıb?',
            'log' => $log,
            'python' => $python,
        ];
    }

    $data = json_decode($jsonLine, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'JSON parse xətası', 'log' => $log, 'python' => $python];
    }
    $data['log'] = $log;
    $data['python'] = $python;
    $data['engine'] = 'python';
    return $data;
}

function excel_import_scan_project() {
    return excel_import_to_mysql(excel_import_find_xlsx());
}
