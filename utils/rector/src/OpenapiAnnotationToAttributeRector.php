<?php

declare (strict_types=1);

namespace Utils\Rector;

use OpenApi\Annotations\AbstractAnnotation;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\BetterPhpDocParser\ValueObject\PhpDocAttributeKey;
use Rector\Core\Rector\AbstractRector;
use Rector\Naming\Naming\UseImportsResolver;
use Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Rector\Php80\NodeFactory\AttrGroupsFactory;
use Rector\Php80\NodeManipulator\AttributeGroupNamedArgumentManipulator;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Rector\Php80\ValueObject\DoctrineTagAndAnnotationToAttribute;
use Rector\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class OpenapiAnnotationToAttributeRector extends AbstractRector
{
    /**
     * @readonly
     * @var OpenApiAttributeGroupFactory
     */
    private $attrGroupsFactory;
    /**
     * @readonly
     * @var \Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover
     */
    private $phpDocTagRemover;
    /**
     * @readonly
     * @var \Rector\Php80\NodeManipulator\AttributeGroupNamedArgumentManipulator
     */
    private $attributeGroupNamedArgumentManipulator;
    /**
     * @readonly
     * @var \Rector\Naming\Naming\UseImportsResolver
     */
    private $useImportsResolver;
    /**
     * @readonly
     * @var \Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer
     */
    private $phpAttributeAnalyzer;

    public function __construct(OpenApiAttributeGroupFactory $attrGroupsFactory, PhpDocTagRemover $phpDocTagRemover, AttributeGroupNamedArgumentManipulator $attributeGroupNamedArgumentManipulator, UseImportsResolver $useImportsResolver, PhpAttributeAnalyzer $phpAttributeAnalyzer)
    {
        $this->phpDocTagRemover = $phpDocTagRemover;
        $this->attributeGroupNamedArgumentManipulator = $attributeGroupNamedArgumentManipulator;
        $this->useImportsResolver = $useImportsResolver;
        $this->phpAttributeAnalyzer = $phpAttributeAnalyzer;
        $this->attrGroupsFactory = $attrGroupsFactory;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change openapi annotation to attribute', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
use OpenApi\Annotations as OA;
/**
 * @OA\Info(
 *   version="1.0.0",
 *   title="My API",
 *   @OA\License(name="MIT"),
 *   @OA\Attachable()
 * )
 */
class OpenApiSpec
{
}
CODE_SAMPLE
            , <<<'CODE_SAMPLE'
use OpenApi\Attributes as OA;
#[OA\Info(
    version: "1.0.0",
    title: "My API",
    license: new OA\License(name: "MIT"),
    attachables: [new OA\Attachable()]
)]
class OpenApiSpec
{
}
CODE_SAMPLE
            , [])]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class, Property::class, Param::class, ClassMethod::class, Function_::class, Closure::class, ArrowFunction::class, Interface_::class];
    }

    /**
     * @param Class_|Property|Param|ClassMethod|Function_|Closure|ArrowFunction|Interface_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $uses = $this->useImportsResolver->resolveBareUsesForNode($node);
        // 2. Doctrine annotation classes
        $annotationAttributeGroups = $this->processOpenApiAnnotations($phpDocInfo, $uses);
        $attributeGroups = \array_merge($annotationAttributeGroups);
        if ($attributeGroups === []) {
            return null;
        }
        $this->attributeGroupNamedArgumentManipulator->decorate($attributeGroups);
        $node->attrGroups = \array_merge($node->attrGroups, $attributeGroups);
        return $node;
    }

    /**
     * @param Use_[] $uses
     * @return AttributeGroup[]
     */
    private function processOpenApiAnnotations(PhpDocInfo $phpDocInfo, array $uses): array
    {
        if ($phpDocInfo->getPhpDocNode()->children === []) {
            return [];
        }
        $doctrineTagAndAnnotationToAttributes = [];
        $doctrineTagValueNodes = [];
        foreach ($phpDocInfo->getPhpDocNode()->children as $phpDocChildNode) {
            if (!$phpDocChildNode instanceof PhpDocTagNode) {
                continue;
            }
            if (!$phpDocChildNode->value instanceof DoctrineAnnotationTagValueNode) {
                continue;
            }
            $doctrineTagValueNode = $phpDocChildNode->value;
            $annotationToAttribute = $this->matchOpenApiAnnotation($doctrineTagValueNode);
            if ($annotationToAttribute === null) {
                continue;
            }
            $doctrineTagAndAnnotationToAttributes[] = new OpenApiTagAndAnnotationToAttribute(new OpenApiTagValueNode($doctrineTagValueNode), $annotationToAttribute);
            $doctrineTagValueNodes[] = $doctrineTagValueNode;
        }
        $attributeGroups = $this->attrGroupsFactory->create($doctrineTagAndAnnotationToAttributes, $uses);
        if ($this->phpAttributeAnalyzer->hasRemoveArrayState($attributeGroups)) {
            return [];
        }
        foreach ($doctrineTagValueNodes as $doctrineTagValueNode) {
            $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $doctrineTagValueNode);
        }
        return $attributeGroups;
    }

    private function matchOpenApiAnnotation(DoctrineAnnotationTagValueNode $doctrineAnnotationTagValueNode): ?\Rector\Php80\ValueObject\AnnotationToAttribute
    {
        $annotationClass = $doctrineAnnotationTagValueNode->identifierTypeNode->getAttribute(PhpDocAttributeKey::RESOLVED_CLASS);
        if ($annotationClass === null) {
            return null;
        }
        if (is_a($annotationClass, AbstractAnnotation::class, true)) {
            return new AnnotationToAttribute(
                $annotationClass,
                str_replace('OpenApi\\Annotations\\', 'OpenApi\\Attributes\\', $annotationClass)
            );
        }
        return null;
    }
}
