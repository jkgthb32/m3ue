<?php

/**
 * Fix escaped quotes inside __() single-quoted strings.
 *
 * In PHP single-quoted strings, \" is NOT an escape sequence.
 * It is literally the two chars \ and ".
 * So __('has \"quotes\"') looks up key 'has \"quotes\"' (with backslashes),
 * but the JSON key is 'has "quotes"' (plain quotes). They never match.
 *
 * Fix: replace \" with " inside __('...') calls.
 */
$dirs = [
    __DIR__.'/../app/Filament',
    __DIR__.'/../app/Providers',
];

$changed = [];

foreach ($dirs as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        $content = file_get_contents($path);
        $original = $content;

        // Match __('...') where the content contains \"
        // We use a callback to only replace \" → " inside the __() string argument
        $content = preg_replace_callback(
            "/__\('((?:[^'\\\\]|\\\\.)*)'\)/",
            function ($m) {
                $inner = $m[1];
                // Only process if it has backslash-quote
                if (strpos($inner, '\\"') !== false) {
                    $inner = str_replace('\\"', '"', $inner);

                    return "__('$inner')";
                }

                return $m[0];
            },
            $content
        );

        if ($content !== $original) {
            file_put_contents($path, $content);
            $changed[] = str_replace(__DIR__.'/../', '', $path);
        }
    }
}

echo 'Fixed escaped quotes in '.count($changed)." files:\n";
foreach ($changed as $f) {
    echo "  $f\n";
}
