<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

spl_autoload_register(static function ($class) {
    if (str_starts_with($class, 'Utils\\Rector\\')) {
        $parts = explode('\\', $class);
        $file = __DIR__ . '/utils/rector/src/' . implode('/', array_slice($parts, 2)) . '.php';
        echo $file, "\n";
        if (file_exists($file)) {
            include $file;
            return true;
        }
    }
    return false;
});

return static function (RectorConfig $rectorConfig): void {
    $servicesConfigurator = $rectorConfig->services();
    $servicesConfigurator->defaults()->public()->autowire()->autoconfigure();
    $sourceRoot = __DIR__ . '/utils/rector/src';
    $servicesConfigurator->load('Utils\\Rector\\', $sourceRoot)
        ->exclude([
            $sourceRoot . '/OpenApiTagValueNode.php',
            $sourceRoot . '/OpenApiTagAndAnnotationToAttribute.php'
        ]);
    $rectorConfig->rule(\Utils\Rector\OpenapiAnnotationToAttributeRector::class);
};
