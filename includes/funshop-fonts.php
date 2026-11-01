<?php
if (!defined('RB_ROOT')) {
    require_once __DIR__ . '/../config/app.php';
}

if (!empty($GLOBALS['rb_funshop_fonts_rendered'])) {
    return;
}
$GLOBALS['rb_funshop_fonts_rendered'] = true;

/*
 * Studio Funshop licensed web fonts.
 * Drop purchased WOFF2 files into assets/fonts/funshop/.
 * Matching ignores case, spaces, hyphens and underscores, so files such as
 * "Baddie-Regular.woff2", "HandmadeSans-Bold.woff2" or "kooky_cloud.woff2"
 * are detected automatically. No @font-face is emitted until a file exists.
 */
$funshopFonts = [
    'baddie' => [
        'family' => 'Baddie',
        'weights' => [
            400 => ['baddie', 'baddieregular', 'baddiedisplay'],
        ],
    ],
    'quirkie' => [
        'family' => 'Quirkie',
        'weights' => [
            400 => ['quirkie', 'quirkieregular'],
            700 => ['quirkiebold', 'quirkie700', 'quirkiesemibold', 'quirkie600'],
        ],
    ],
    'goops' => [
        'family' => 'Goops',
        'weights' => [
            400 => ['goops', 'goopsregular'],
            700 => ['goopsbold', 'goops700'],
        ],
    ],
    'handmade-sans' => [
        'family' => 'Handmade Sans',
        'weights' => [
            400 => ['handmadesans', 'handmadesansregular'],
            700 => ['handmadesansbold', 'handmadesans700', 'handmadesanssemibold', 'handmadesans600'],
        ],
    ],
    'scratchy' => [
        'family' => 'Scratchy',
        'weights' => [
            400 => ['scratchy', 'scratchyregular'],
        ],
    ],
    'kooky-cloud' => [
        'family' => 'Kooky Cloud',
        'weights' => [
            400 => ['kookycloud', 'kookycloudregular'],
        ],
    ],
    'oddball' => [
        'family' => 'Oddball',
        'weights' => [
            400 => ['oddball', 'oddballregular'],
        ],
    ],
    'puffie' => [
        'family' => 'Puffie',
        'weights' => [
            400 => ['puffie', 'puffieregular'],
        ],
    ],
    'dottie' => [
        'family' => 'Dottie',
        'weights' => [
            400 => ['dottie', 'dottieregular'],
        ],
    ],
    'wonderful' => [
        'family' => 'Wonderful',
        'weights' => [
            400 => ['wonderful', 'wonderfulregular'],
        ],
    ],
];

$fontDir = RB_ROOT . '/assets/fonts/funshop';
$available = [];
if (is_dir($fontDir)) {
    foreach (scandir($fontDir) ?: [] as $entry) {
        if (strtolower(pathinfo($entry, PATHINFO_EXTENSION)) !== 'woff2') {
            continue;
        }
        $token = preg_replace('/[^a-z0-9]/', '', strtolower(pathinfo($entry, PATHINFO_FILENAME)));
        if ($token !== '' && !isset($available[$token])) {
            $available[$token] = $entry;
        }
    }
}

$faces = [];
$status = ['found' => [], 'missing' => []];
foreach ($funshopFonts as $key => $font) {
    $familyFaces = [];
    foreach ($font['weights'] as $weight => $candidates) {
        foreach ($candidates as $candidate) {
            if (!isset($available[$candidate])) {
                continue;
            }
            $href = asset('fonts/funshop/' . $available[$candidate]);
            $familyFaces[] = [
                'family' => $font['family'],
                'file' => $available[$candidate],
                'weight' => (int) $weight,
                'href' => $href,
            ];
            break;
        }
    }
    if ($familyFaces) {
        $hasBold = false;
        foreach ($familyFaces as $face) {
            if ($face['weight'] === 700) {
                $hasBold = true;
                break;
            }
        }
        foreach ($familyFaces as &$face) {
            $face['weight_css'] = (!$hasBold && $face['weight'] === 400) ? '100 900' : (string) $face['weight'];
        }
        unset($face);
        $status['found'][] = $font['family'];
        array_push($faces, ...$familyFaces);
    } else {
        $status['missing'][] = $font['family'];
    }
}
$GLOBALS['rb_funshop_font_report'] = $status;

$preloadKeys = ['baddie', 'quirkie', 'handmade-sans', 'goops'];
$preloaded = [];
foreach ($faces as $face) {
    $key = strtolower(str_replace(' ', '-', $face['family']));
    if (!in_array($key, $preloadKeys, true) || $face['weight'] !== 400) {
        continue;
    }
    // Preload only the first 400 face per priority family.
    if (isset($preloaded[$face['href']])) {
        continue;
    }
    $preloaded[$face['href']] = true;
    echo '<link rel="preload" href="' . e($face['href']) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}

if ($faces) {
    echo '<style id="funshop-font-faces">' . "\n";
    foreach ($faces as $face) {
        $family = addcslashes($face['family'], "'\\");
        $href = addcslashes($face['href'], "'\\\n\r");
        echo "@font-face{font-family:'" . $family . "';src:url('" . $href . "') format('woff2');"
            . 'font-weight:' . $face['weight_css'] . ';font-style:normal;font-display:swap}' . "\n";
    }
    echo '</style>' . "\n";
}
