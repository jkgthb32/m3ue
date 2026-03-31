<?php

$en = json_decode(file_get_contents(__DIR__.'/../lang/en.json'), true);
$de = json_decode(file_get_contents(__DIR__.'/../lang/de.json'), true);
$fr = json_decode(file_get_contents(__DIR__.'/../lang/fr.json'), true);
$es = json_decode(file_get_contents(__DIR__.'/../lang/es.json'), true);

// Confirm bad keys gone
$bad = array_filter($en, fn ($k) => str_starts_with($k, 'navigation.'), ARRAY_FILTER_USE_KEY);
echo 'Bad nav keys remaining in en.json: '.count($bad).PHP_EOL;

// Confirm nav group strings
$navGroups = ['Playlist', 'Integrations', 'Live Channels', 'VOD Channels', 'Series', 'EPG', 'Proxy', 'Tools'];
foreach (['de' => $de, 'fr' => $fr, 'es' => $es] as $locale => $data) {
    echo PHP_EOL."Nav groups {$locale}:".PHP_EOL;
    foreach ($navGroups as $g) {
        echo "  {$g} => ".($data[$g] ?? '(MISSING)').PHP_EOL;
    }
}

// Check escaped-quote strings - the actual JSON key has literal backslash-quote
$sampleKey = 'Returned as "server_info.http_port" in "player_api.php" responses. Leave empty to use APP_PORT (default).';
echo PHP_EOL.'Escaped-quote key (literal) in en.json: '.(isset($en[$sampleKey]) ? 'EXISTS' : 'MISSING').PHP_EOL;

// Check what the actual source PHP contains - it has \" which PHP treats as "
$sourceKey = 'Returned as \"server_info.http_port\" in \"player_api.php\" responses. Leave empty to use APP_PORT (default).';
echo 'Escaped-quote key (with backslash) in en.json: '.(isset($en[$sourceKey]) ? 'EXISTS => '.$en[$sourceKey] : 'MISSING').PHP_EOL;
echo 'In de.json: '.(isset($de[$sourceKey]) ? $de[$sourceKey] : '(MISSING)').PHP_EOL;
