<?php

$composerJson = json_decode(file_get_contents('composer.json'), true);
foreach ($composerJson['autoload']['psr-4'] as $prefix => $dir) {
    if (str_starts_with($dir, 'src')) {
        $namespace = $prefix;
        break;
    }
}

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('src'), RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
    if (basename($file) === 'api-spec.php') {
        $lines = [];
        $namespaceSuffix = [];
        $parts = array_reverse(explode(DIRECTORY_SEPARATOR, dirname($file)));
        foreach ($parts as $dir) {
            if ($dir === 'src') {
                break;
            }
            $namespaceSuffix[] = $dir;
        }

        foreach (file($file) as $line) {
            if (str_starts_with($line, 'use ')) {
                $lines[] = 'namespace ' .  $namespace . (empty($namespaceSuffix) ? '' :  implode("\\", array_reverse($namespaceSuffix))) . ";\n";
            }
            $lines[] = $line;
        }
        $lines[] = "class ApiSpec {\n}\n";
        file_put_contents($file, implode('', $lines));
        rename($file, dirname($file) . '/ApiSpec.php');
    }
}

