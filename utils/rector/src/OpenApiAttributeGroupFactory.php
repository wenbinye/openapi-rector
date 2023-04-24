<?php

namespace Utils\Rector;

use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\Use_;
use Rector\BetterPhpDocParser\PhpDoc\ArrayItemNode;
use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Rector\Php80\ValueObject\DoctrineTagAndAnnotationToAttribute;
use Rector\PhpAttribute\AnnotationToAttributeMapper;
use Rector\PhpAttribute\AttributeArrayNameInliner;
use Rector\PhpAttribute\NodeAnalyzer\ExprParameterReflectionTypeCorrector;

class OpenApiAttributeGroupFactory
{
    /**
     * @readonly
     * @var \Rector\PhpAttribute\AnnotationToAttributeMapper
     */
    private $annotationToAttributeMapper;
    /**
     * @readonly
     * @var \Rector\PhpAttribute\NodeFactory\AttributeNameFactory
     */
    private $attributeNameFactory;
    /**
     * @readonly
     * @var \Rector\PhpAttribute\NodeFactory\NamedArgsFactory
     */
    private $namedArgsFactory;
    /**
     * @readonly
     * @var \Rector\PhpAttribute\NodeAnalyzer\ExprParameterReflectionTypeCorrector
     */
    private $exprParameterReflectionTypeCorrector;
    /**
     * @readonly
     * @var \Rector\PhpAttribute\AttributeArrayNameInliner
     */
    private $attributeArrayNameInliner;
    public function __construct(AnnotationToAttributeMapper $annotationToAttributeMapper, \Rector\PhpAttribute\NodeFactory\AttributeNameFactory $attributeNameFactory, \Rector\PhpAttribute\NodeFactory\NamedArgsFactory $namedArgsFactory, ExprParameterReflectionTypeCorrector $exprParameterReflectionTypeCorrector, AttributeArrayNameInliner $attributeArrayNameInliner)
    {
        $this->annotationToAttributeMapper = $annotationToAttributeMapper;
        $this->attributeNameFactory = $attributeNameFactory;
        $this->namedArgsFactory = $namedArgsFactory;
        $this->exprParameterReflectionTypeCorrector = $exprParameterReflectionTypeCorrector;
        $this->attributeArrayNameInliner = $attributeArrayNameInliner;
    }
    /**
     * @param OpenApiTagAndAnnotationToAttribute[] $openapiTagAndAnnotationToAttributes
     * @param Use_[] $uses
     * @return AttributeGroup[]
     */
    public function create(array $openapiTagAndAnnotationToAttributes, array $uses) : array
    {
        $attributeGroups = [];
        foreach ($openapiTagAndAnnotationToAttributes as $openapiTagAndAnnotationToAttribute) {
            // add attributes
            $attributeGroups[] = $this->createAttributeGroup(
                $openapiTagAndAnnotationToAttribute,
                $uses
            );
        }
        return $attributeGroups;
    }


    /**
     * @param Use_[] $uses
     */
    public function createAttributeGroup(OpenApiTagAndAnnotationToAttribute $openApiTagAndAnnotationToAttribute, array $uses) : AttributeGroup
    {
        $annotationToAttribute = $openApiTagAndAnnotationToAttribute->getAnnotationToAttribute();
        $openapiTagValueNode = $openApiTagAndAnnotationToAttribute->getOpenApiTagValueNode();
        $values = $openapiTagValueNode->getValues();

        $args = $this->createArgsFromItems($values, $annotationToAttribute->getAttributeClass());
        $args = $this->attributeArrayNameInliner->inlineArrayToArgs($args);
        $attributeName = $this->attributeNameFactory->create($annotationToAttribute, $openapiTagValueNode->getDoctrineAnnotationTagValueNode(), $uses);
        // keep FQN in the attribute, so it can be easily detected later
        $attributeName->setAttribute(AttributeKey::PHP_ATTRIBUTE_NAME, $annotationToAttribute->getAttributeClass());
        $attribute = new Attribute($attributeName, $args);
        return new AttributeGroup([$attribute]);
    }

    /**
     * @api tests
     *
     * @param ArrayItemNode[]|mixed[] $items
     * @return Arg[]
     */
    public function createArgsFromItems(array $items, string $attributeClass) : array
    {
        /** @var Expr[]|Expr\Array_ $mappedItems */
        $mappedItems = $this->annotationToAttributeMapper->map($items);
        $mappedItems = $this->exprParameterReflectionTypeCorrector->correctItemsByAttributeClass($mappedItems, $attributeClass);
        // the key here should contain the named argument
        return $this->namedArgsFactory->createFromValues($mappedItems);
    }
}