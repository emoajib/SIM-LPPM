<?php

$segments = [
    'top' => [[12, 12], [48, 12]],
    'top-right' => [[50, 14], [50, 58]],
    'bottom-right' => [[50, 64], [50, 108]],
    'bottom' => [[12, 110], [48, 110]],
    'bottom-left' => [[10, 64], [10, 108]],
    'top-left' => [[10, 14], [10, 58]],
    'middle' => [[12, 61], [48, 61]],
];

$digits = [
    '0' => ['top', 'top-left', 'top-right', 'bottom-left', 'bottom-right', 'bottom'],
    '1' => ['top-right', 'bottom-right'],
    '2' => ['top', 'top-right', 'middle', 'bottom-left', 'bottom'],
    '3' => ['top', 'top-right', 'middle', 'bottom-right', 'bottom'],
    '4' => ['top-left', 'middle', 'top-right', 'bottom-right'],
    '5' => ['top', 'top-left', 'middle', 'bottom-right', 'bottom'],
    '6' => ['top', 'top-left', 'middle', 'bottom-left', 'bottom-right', 'bottom'],
    '7' => ['top', 'top-right', 'bottom-right'],
    '8' => ['top', 'top-left', 'top-right', 'middle', 'bottom-left', 'bottom-right', 'bottom'],
    '9' => ['top', 'top-left', 'top-right', 'middle', 'bottom-right', 'bottom'],
];

$glyphs = [];

foreach ($digits as $char => $keys) {
    foreach ($keys as $key) {
        $glyphs[$char][] = [$segments[$key][0], $segments[$key][1]];
    }
}

$glyphs['+'] = [[[12, 61], [48, 61]], [[30, 35], [30, 87]]];
$glyphs['-'] = [[[12, 61], [48, 61]]];
$glyphs['×'] = [[[14, 30], [46, 92]], [[46, 30], [14, 92]]];
$glyphs['='] = [[[12, 48], [48, 48]], [[12, 74], [48, 74]]];

return $glyphs;
