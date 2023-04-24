<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $servicesConfigurator = $rectorConfig->services();
    $servicesConfigurator->defaults()->public()->autowire()->autoconfigure();
    $sourceRoot = __DIR__ . '/../../../src';
    $servicesConfigurator->load('Utils\\Rector\\', $sourceRoot)
        ->exclude([
            $sourceRoot . '/OpenApiTagValueNode.php',
            $sourceRoot . '/OpenApiTagAndAnnotationToAttribute.php'
        ]);
    $rectorConfig->rule(\Utils\Rector\OpenapiAnnotationToAttributeRector::class);
};