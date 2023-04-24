<?php

namespace Utils\Rector;

use Rector\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Rector\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;

class OpenApiTagValueNode
{

    private DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode;

    public function __construct(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode)
    {
        $this->doctrineAnnotationTagValueNode = $doctrineAnnotationTagValueNode;
    }

    public function __toString(): string
    {
        return $this->doctrineAnnotationTagValueNode->__toString();
    }

    public function getDoctrineAnnotationTagValueNode(): DoctrineAnnotationTagValueNode
    {
        return $this->doctrineAnnotationTagValueNode;
    }

    public function getValues(): array
    {
        $annotationClass = $this->doctrineAnnotationTagValueNode->identifierTypeNode->getAttribute(PhpDocAttributeKey::RESOLVED_CLASS);
        $reflectionAnnotationClass = new \ReflectionClass($annotationClass);
        $nestedClasses = [];
        if ($reflectionAnnotationClass->hasProperty('_nested')) {
            $property = $reflectionAnnotationClass->getProperty('_nested');
            if ($property->isPublic() && $property->isStatic()) {
                $nestedClasses = $annotationClass::$_nested;
            }
        }
        $attributeClass = $this->annotationToAttribute($annotationClass);
        $reflectionAttributeClass = new \ReflectionClass($attributeClass);
        $parameterTypes = [];
        foreach ($reflectionAttributeClass->getConstructor()->getParameters() as $parameter) {
            if ($parameter->getType() !== null) {
                $parameterTypes[$parameter->getName()] = $parameter->getType();
            }
        }
        $values = $this->doctrineAnnotationTagValueNode->getValuesWithSilentKey();
        $newValues = [];
        foreach ($values as $i => $item) {
            if (!$item instanceof ArrayItemNode) {
                continue;
            }
            if (!$item->value instanceof DoctrineAnnotationTagValueNode) {
                $newValues[$item->key] = $item;
                continue;
            }

            $itemClass = $item->value->identifierTypeNode->getAttribute(PhpDocAttributeKey::RESOLVED_CLASS);
            if (isset($nestedClasses[$itemClass])) {
                $key = $nestedClasses[$itemClass];
                if (is_array($key)) {
                    $key = $key[0];
                    if (isset($newValues[$key])) {
                        $newValues[$key]->value[] = new OpenApiTagValueNode($item->value);
                    } else {
                        $newValues[$key] = new ArrayItemNode(
                            [new OpenApiTagValueNode($item->value)],
                            $key,
                            $item->kindValueQuoted,
                            $item->kindKeyQuoted
                        );
                    }
                } else {
                    $newValues[$key] = new ArrayItemNode(
                        new OpenApiTagValueNode($item->value),
                        $key,
                        $item->kindValueQuoted,
                        $item->kindKeyQuoted
                    );
                }
            } else {
                $itemAttributeClass = $this->annotationToAttribute($itemClass);
                foreach ($parameterTypes as $name => $parameterType) {
                    if ($this->typeMatch($parameterType, $itemAttributeClass)) {
                        $newValues[$name] = new ArrayItemNode(
                            new OpenApiTagValueNode($item->value),
                            $name,
                            $item->kindValueQuoted,
                            $item->kindKeyQuoted
                        );
                        break;
                    }
                }
            }
        }
        return array_values($newValues);
    }

    private function annotationToAttribute(string $annotationClass): string
    {
        return str_replace('OpenApi\\Annotations\\', 'OpenApi\\Attributes\\', $annotationClass);
    }

    private function typeMatch(\ReflectionType $parameterType, string $class): bool
    {
        if ($parameterType instanceof \ReflectionNamedType) {
            return is_a($class, $parameterType->getName(), true);
        }

        foreach ($parameterType->getTypes() as $type) {
            if ($this->typeMatch($type, $class)) {
                return true;
            }
        }
        return false;
    }
}