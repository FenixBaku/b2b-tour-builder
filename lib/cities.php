<?php
/**
 * Şəhər kodları — sistemdə şəhər/region yalnız bu kodlarla saxlanılır (məs: QBL = Qəbələ).
 * Excel, köhnə qeydlər və istifadəçi girişindəki yazılışlar (gabala, qabala, qəbələ...) city_code() ilə koda çevrilir.
 * 'name' transfer cədvəlindəki yer adları ilə eynidir (Hotel → region uyğunlaşdırması üçün).
 */
const CITY_REGISTRY = [
    'BAK' => ['name' => 'Baku', 'az' => 'Bakı', 'aliases' => ['baku', 'baki', 'bakı', 'bakü']],
    'QBL' => ['name' => 'Gabala', 'az' => 'Qəbələ', 'aliases' => ['gabala', 'qabala', 'qebele', 'qəbələ', 'gebele']],
    'SHD' => ['name' => 'Shahdag', 'az' => 'Şahdağ', 'aliases' => ['shahdag', 'shahdagh', 'sahdag', 'şahdağ']],
    'SHK' => ['name' => 'Sheki', 'az' => 'Şəki', 'aliases' => ['sheki', 'shaki', 'seki', 'şəki', 'şeki']],
    'SHM' => ['name' => 'Shamakhi', 'az' => 'Şamaxı', 'aliases' => ['shamakhi', 'shamaxi', 'samaxi', 'şamaxı', 'shamakha']],
    'QBA' => ['name' => 'Guba', 'az' => 'Quba', 'aliases' => ['guba', 'quba', 'kuba']],
    'ISM' => ['name' => 'Ismayilli', 'az' => 'İsmayıllı', 'aliases' => ['ismayilli', 'ismayıllı', 'i̇smayıllı', 'ismailli', 'lahij', 'lahic', 'lahıc']],
    'GNJ' => ['name' => 'Ganja', 'az' => 'Gəncə', 'aliases' => ['ganja', 'gence', 'gəncə', 'ganca']],
    'LNK' => ['name' => 'Lankaran', 'az' => 'Lənkəran', 'aliases' => ['lankaran', 'lenkaran', 'lənkəran', 'lenkoran']],
];

const CITY_DEFAULT_CODE = 'BAK';

function city_lower($s) {
    $s = trim((string) $s);
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}

/** Kod, ad və ya yazılış → kod ('BAK', 'QBL', ...). Tanınmırsa ''. */
function city_code($value) {
    $v = trim((string) $value);
    if ($v === '') return '';
    $upper = strtoupper($v);
    if (isset(CITY_REGISTRY[$upper])) return $upper;
    $low = city_lower($v);
    foreach (CITY_REGISTRY as $code => $city) {
        if ($low === city_lower($city['name']) || $low === city_lower($city['az']) || in_array($low, $city['aliases'], true)) {
            return $code;
        }
    }
    return '';
}

/**
 * Sərbəst mətndən şəhər kodu (Excel vərəq adı, otel adı: "LakeSide Hotel - Gabala").
 * Excel vərəq adları 31 simvolda kəsilir, ona görə "- Gaba" kimi kəsik sonluq da tanınır.
 */
function city_code_from_text($text) {
    $low = city_lower($text);
    if ($low === '') return '';
    foreach (CITY_REGISTRY as $code => $city) {
        $words = array_merge([city_lower($city['name']), city_lower($city['az'])], $city['aliases']);
        foreach ($words as $w) {
            if ($w !== '' && preg_match('/(^|[^\p{L}])' . preg_quote($w, '/') . '($|[^\p{L}])/u', $low)) {
                return $code;
            }
        }
    }
    if (preg_match('/-\s*(\p{L}{4,})$/u', $low, $m)) {
        foreach (CITY_REGISTRY as $code => $city) {
            foreach (array_merge([city_lower($city['name'])], $city['aliases']) as $w) {
                if (strpos($w, $m[1]) === 0) return $code;
            }
        }
    }
    return '';
}

function city_name($code) {
    $code = city_code($code);
    return $code !== '' ? CITY_REGISTRY[$code]['name'] : '';
}

/** Açarları şəhər adı/yazılışı olan massivi kodlara çevirir; eyni koda düşənlər birləşdirilir. */
function city_rekey(array $byCity, $mergeLists = true) {
    $out = [];
    foreach ($byCity as $key => $value) {
        $code = city_code($key) ?: CITY_DEFAULT_CODE;
        if (!isset($out[$code])) {
            $out[$code] = $value;
        } elseif ($mergeLists && is_array($value)) {
            $out[$code] = array_merge_recursive($out[$code], $value);
        }
    }
    return $out;
}

/**
 * DB-də bu şəhər üçün ola biləcək dəyərlər (kod + köhnə yazılışlar) — miqrasiyadan əvvəlki qeydlər də tapılsın.
 * İstifadə: "city IN (" . city_sql_placeholders($code) . ")" + city_db_variants($code)
 */
function city_db_variants($code) {
    $code = city_code($code);
    if ($code === '') return [];
    $city = CITY_REGISTRY[$code];
    return array_values(array_unique(array_merge([$code, strtolower($code), city_lower($city['name'])], $city['aliases'])));
}

function city_sql_placeholders($code) {
    return implode(',', array_fill(0, max(1, count(city_db_variants($code))), '?'));
}

/** Builder və admin üçün: [{code, name, az, aliases}] */
function city_registry_for_js() {
    $out = [];
    foreach (CITY_REGISTRY as $code => $city) {
        $out[] = ['code' => $code, 'name' => $city['name'], 'az' => $city['az'], 'aliases' => $city['aliases']];
    }
    return $out;
}
