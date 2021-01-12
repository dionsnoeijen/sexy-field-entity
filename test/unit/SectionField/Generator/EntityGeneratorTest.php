<?php
declare (strict_types=1);

namespace Tardigrades\SectionField\Generator;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tardigrades\Entity\Field;
use Tardigrades\Entity\FieldType;
use Tardigrades\Entity\Section;
use Mockery;
use Tardigrades\SectionField\Generator\Writer\Writable;
use Tardigrades\SectionField\Service\FieldManagerInterface;
use Tardigrades\SectionField\Service\FieldTypeManagerInterface;
use Tardigrades\SectionField\Service\SectionManagerInterface;
use Tardigrades\SectionField\ValueObject\FieldTypeGeneratorConfig;
use Tardigrades\SectionField\ValueObject\FullyQualifiedClassName;
use Tardigrades\SectionField\ValueObject\Type;

/**
 * @coversDefaultClass Tardigrades\SectionField\Generator\EntityGenerator
 * @covers ::<private>
 */
final class EntityGeneratorTest extends TestCase
{
    /**
     * @test
     * @covers ::generateBySection
     * @dataProvider configProvider
     */
    public function it_generates_by_section_with_and_without_entity_constraints($configArrayForSection)
    {
        $container = Mockery::mock(ContainerInterface::class);
        $sectionManager = Mockery::mock(SectionManagerInterface::class);
        $fieldTypeManager = Mockery::mock(FieldTypeManagerInterface::class);
        $mockedFieldManager = Mockery::mock(FieldManagerInterface::class);

        $result = Mockery::mock(Writable::class);

        $section = new Section();
        $section->setConfig($configArrayForSection);
        $section->setHandle('sexyhandle');

        $fieldTypeMocka = Mockery::mock(new FieldType())->makePartial();
        $fieldTypeMocka->shouldReceive('getFullyQualifiedClassName')->andReturn(
            FullyQualifiedClassName::fromString(\Foo\Bar::class)
        );
        $fieldTypeMocka->shouldReceive('directory')->andReturn('one/two');
        $fieldTypeMocka->shouldReceive('getType')->andReturn(Type::fromString('typoe'));

        $fieldTypeMockb = Mockery::mock(new FieldType())->makePartial();
        $fieldTypeMockb->shouldReceive('getFullyQualifiedClassName')->andReturn(
            FullyQualifiedClassName::fromString(\Tardigrades\FieldType\Relationship\Relationship::class)
        );
        $fieldTypeMockb->shouldReceive('directory')->andReturn('one/two');
        $fieldTypeMockb->shouldReceive('getType')->andReturn(Type::fromString('typoe'));

        $fieldTypeMockc = Mockery::mock(new FieldType())->makePartial();
        $fieldTypeMockc->shouldReceive('getFullyQualifiedClassName')->andReturn(
            FullyQualifiedClassName::fromString(\Tardigrades\FieldType\Relationship\ExtendedRelationship::class)
        );
        $fieldTypeMockc->shouldReceive('directory')->andReturn('one/two');
        $fieldTypeMockc->shouldReceive('getType')->andReturn(Type::fromString('typoe'));

        $aField = new Field();
        $aField->setHandle('one');
        $aField->setName('one');
        $aField->setFieldType($fieldTypeMocka);
        $aField->setConfig(['field' => [
            'name' => 'one',
            'handle' => 'one'
        ]]);
        $section->addField($aField);

        $bField = new Field();
        $bField->setHandle('two');
        $bField->setName('two');
        $bField->setFieldType($fieldTypeMockb);
        $bField->setConfig(['field' => [
            'name' => 'two',
            'handle' => 'two',
            'to' => 'two',
            'kind' => 'many-to-one',
            'owner' => true
        ]]);
        $section->addField($bField);

        $cField = new Field();
        $cField->setHandle('two');
        $cField->setName('two');
        $cField->setFieldType($fieldTypeMockc);
        $cField->setConfig(['field' => [
            'name' => 'ten',
            'handle' => 'ten',
            'to' => 'ten',
            'kind' => 'many-to-one',
            'owner' => true
        ]]);
        $section->addField($cField);

        $sectionConfigForWritable = $section->getConfig();

        $mockedFieldManager->shouldReceive('readByHandles')->once()
            ->with($section->getConfig()->getFields())
            ->andReturn($section->getFields()->toArray());

        $returnValueRelations = [
            'sexyhandle' => [
                'one' => [
                    'kind' => 'one-to-one',
                    'to' => 'new-sexion',
                    'from' => 'sexyhandle',
                    'relationship-type' => 'unidirectional'
                ]
            ]
        ];

        $sectionManager->shouldReceive('getRelationshipsOfAll')->once()
            ->andReturn($returnValueRelations);


        $result->shouldReceive('create')->once()->with(
            $sectionConfigForWritable->getNamespace() . '\\Entity\\',
            $sectionConfigForWritable->getClassName() . '.php'
        );

        $fieldConfig = FieldTypeGeneratorConfig::fromArray(
            [
                'entity' => ['entiteit' => 'generator']
            ]
        );

        $fieldTypeMocka->shouldReceive('getFieldTypeGeneratorConfig')->once()
            ->andReturn($fieldConfig);

        $container->shouldReceive('get')->once()
            ->andReturn($fieldTypeMocka);

        $returnField = new Field();
        $returnField->setFieldType($fieldTypeMocka);

        $mockedFieldManager->shouldReceive('readByHandle')
            ->andReturn($returnField);

        $generator = new EntityGenerator($mockedFieldManager, $fieldTypeManager, $sectionManager, $container);
        $generated = $generator->generateBySection($section);

        $this->assertInstanceOf(Writable::class, $generated);
        $this->assertEquals('My\\Sexy\\Namespace\\Entity\\', $generated->getNamespace());
        $this->assertEquals('Sexyhandle.php', $generated->getFilename());

        if (!empty($configArrayForSection['section']['entityInterfaces'])) {
            $this->assertEquals($this->getTemplateOutputWithInterfaces(), $generated->getTemplate());
        } else {
            $this->assertEquals($this->getTemplateOutputWithoutInterfaces(), $generated->getTemplate());
        }
    }

    public function configProvider()
    {
        return [
            [
                [
                    'section' => [
                        'name' => 'sexyon',
                        'handle' => 'sexyhandle',
                        'fields' => ['one', 'two', 'ten'],
                        'slug' => ['these'],
                        'default' => 'these',
                        'namespace' => 'My\Sexy\Namespace',
                        'entityInterfaces' => ['Namespace\OneInterface', 'Namespace\SecondInterface'],
                        'generator' =>
                            ['entity' =>
                                [
                                    'name' =>
                                        [
                                            'NotBlank' => null
                                        ]
                                ]
                            ]
                    ]
                ]
            ],
            [
                [
                    'section' => [
                        'name' => 'sexyon',
                        'handle' => 'sexyhandle',
                        'fields' => ['one', 'two', 'ten'],
                        'slug' => ['these'],
                        'default' => 'these',
                        'namespace' => 'My\Sexy\Namespace',
                        'generator' =>
                            ['entity' => null]
                    ]
                ]
            ]
        ];
    }

    private function getTemplateOutputWithInterfaces()
    {
        return "<?php
declare (strict_types=1);

namespace My\Sexy\Namespace\Entity;

use Tardigrades;
use Tardigrades\SectionField\Generator\CommonSectionInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class Sexyhandle implements CommonSectionInterface, Namespace\OneInterface, Namespace\SecondInterface
{
    use Extra\SexyhandleTrait;

    const FIELDS = [
        'one' => [
            'handle' => 'one',
            'type' => 'typoe',
            'parent' => null,
            'getter' => 'getOne',
            'setter' => 'setOne',
            'relationship' => null,
        ],
        'two' => [
            'handle' => 'two',
            'type' => 'typoe',
            'parent' => null,
            'getter' => 'getTwo',
            'setter' => 'setTwo',
            'relationship' => [
                'class' => 'My\\\Sexy\\\Namespace\\\Entity\\\Two',
                'plural' => false,
                'kind' => 'many-to-one',
                'owner' => true,
            ],
        ],
        'ten' => [
            'handle' => 'two',
            'type' => 'typoe',
            'parent' => null,
            'getter' => 'getTen',
            'setter' => 'setTen',
            'relationship' => [
                'class' => 'My\\\Sexy\\\Namespace\\\Entity\\\Ten',
                'plural' => false,
                'kind' => 'many-to-one',
                'owner' => true,
            ],
        ],
    ];

    /** @var ?int */
    private \$id;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return \$this->id;
    }

    public function getDefault(): string
    {
        if (\$this->these === null) {
            throw new \UnexpectedValueException('these is null, cannot get default value');
        }
        return \$this->these;
    }

    public static function loadValidatorMetadata(ClassMetadata \$metadata): void
    {
    {{ validatorMetadata }}
    }

    public function onPrePersist(): void
    {
    }

    public function onPreUpdate(): void
    {
    }

    public static function fieldInfo(): array
    {
        return static::FIELDS;
    }
}
";

    }

    private function getTemplateOutputWithoutInterfaces()
    {
        return "<?php
declare (strict_types=1);

namespace My\Sexy\Namespace\Entity;

use Tardigrades;
use Tardigrades\SectionField\Generator\CommonSectionInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

class Sexyhandle implements CommonSectionInterface
{
    use Extra\SexyhandleTrait;

    const FIELDS = [
        'one' => [
            'handle' => 'one',
            'type' => 'typoe',
            'parent' => null,
            'getter' => 'getOne',
            'setter' => 'setOne',
            'relationship' => null,
        ],
        'two' => [
            'handle' => 'two',
            'type' => 'typoe',
            'parent' => null,
            'getter' => 'getTwo',
            'setter' => 'setTwo',
            'relationship' => [
                'class' => 'My\\\Sexy\\\Namespace\\\Entity\\\Two',
                'plural' => false,
                'kind' => 'many-to-one',
                'owner' => true,
            ],
        ],
        'ten' => [
            'handle' => 'two',
            'type' => 'typoe',
            'parent' => null,
            'getter' => 'getTen',
            'setter' => 'setTen',
            'relationship' => [
                'class' => 'My\\\Sexy\\\Namespace\\\Entity\\\Ten',
                'plural' => false,
                'kind' => 'many-to-one',
                'owner' => true,
            ],
        ],
    ];

    /** @var ?int */
    private \$id;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return \$this->id;
    }

    public function getDefault(): string
    {
        if (\$this->these === null) {
            throw new \UnexpectedValueException('these is null, cannot get default value');
        }
        return \$this->these;
    }

    public static function loadValidatorMetadata(ClassMetadata \$metadata): void
    {
    {{ validatorMetadata }}
    }

    public function onPrePersist(): void
    {
    }

    public function onPreUpdate(): void
    {
    }

    public static function fieldInfo(): array
    {
        return static::FIELDS;
    }
}
";

    }
}

namespace Foo;

class Bar
{
    public static function getCofields(string $handle): array
    {
        return [];
    }
}

namespace Tardigrades\FieldType\Relationship;

class Relationship
{
    public static function getCofields(string $handle): array
    {
        return [];
    }
}

class ExtendedRelationship extends Relationship
{
    public static function getCofields(string $handle): array
    {
        return [];
    }
}
