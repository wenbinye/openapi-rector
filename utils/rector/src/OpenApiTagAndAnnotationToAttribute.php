<?php

namespace Utils\Rector;

use Rector\Php80\ValueObject\AnnotationToAttribute;

class OpenApiTagAndAnnotationToAttribute
{

    private OpenApiTagValueNode $openApiTagValueNode;
    private AnnotationToAttribute $annotationToAttribute;

    public function __construct(OpenApiTagValueNode $openApiTagValueNode, AnnotationToAttribute $annotationToAttribute)
    {
        $this->openApiTagValueNode = $openApiTagValueNode;
        $this->annotationToAttribute = $annotationToAttribute;
    }

    /**
     * @return OpenApiTagValueNode
     */
    public function getOpenApiTagValueNode(): OpenApiTagValueNode
    {
        return $this->openApiTagValueNode;
    }

    /**
     * @return AnnotationToAttribute
     */
    public function getAnnotationToAttribute(): AnnotationToAttribute
    {
        return $this->annotationToAttribute;
    }
}