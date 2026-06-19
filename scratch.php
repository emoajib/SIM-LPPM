<?php

$str = "(225,'019e2c68-9809-7040-b650-039246931268',1,2,11,'','','Bahan habis pakai laboratorium ( Gelas ukur, pH strip, kabel, lem, dll)',1,500000.00,500000.00,'2026-05-18 08:48:49','2026-05-18 08:48:49')";
$regex = '/\(\s*(\d+)\s*,\s*(\'[0-9a-fA-F-]+\')\s*,\s*(\d+|NULL)\s*,\s*(\d+|NULL)\s*,\s*(?:\d+|NULL)\s*,/';
echo preg_replace($regex, '($1,$2,$3,$4,', $str);
