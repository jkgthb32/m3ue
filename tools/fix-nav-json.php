<?php

/**
 * 1. Remove bad navigation.groups.* and navigation.labels.* identity entries from all JSON files.
 * 2. Add missing nav group translations.
 * 3. Fix "Proxy" — it's a technical term, keep it as "Proxy" in all locales.
 */
$langDir = __DIR__.'/../lang';

// Correct translations for nav group strings
$corrections = [
    'de' => [
        'Integrations' => 'Integrationen',
        'Tools' => 'Tools',
        'Proxy' => 'Proxy',
        'Series' => 'Serien',
    ],
    'fr' => [
        'Integrations' => 'Intégrations',
        'Tools' => 'Outils',
        'Proxy' => 'Proxy',
    ],
    'es' => [
        'Integrations' => 'Integraciones',
        'Tools' => 'Herramientas',
        'Proxy' => 'Proxy',
    ],
];

foreach (['en', 'de', 'fr', 'es'] as $locale) {
    $path = "{$langDir}/{$locale}.json";
    $data = json_decode(file_get_contents($path), true);
    $original = $data;

    // 1. Remove all bad navigation.groups.* and navigation.labels.* keys
    foreach (array_keys($data) as $key) {
        if (str_starts_with($key, 'navigation.groups.') || str_starts_with($key, 'navigation.labels.')) {
            unset($data[$key]);
        }
    }

    // 2. Apply corrections for non-EN locales
    if (isset($corrections[$locale])) {
        foreach ($corrections[$locale] as $key => $value) {
            $data[$key] = $value;
        }
    }

    if ($data !== $original) {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
        $removed = count($original) - count($data) + count($corrections[$locale] ?? []);
        echo "{$locale}.json: removed bad keys, applied corrections\n";
    } else {
        echo "{$locale}.json: no changes needed\n";
    }
}

echo "\nDone.\n";
