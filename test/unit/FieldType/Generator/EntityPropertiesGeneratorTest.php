<?php
declare (strict_types=1);

namespace Tardigrades\FieldType\Generator;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tardigrades\Entity\Field;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;

/**
 * @coversDefaultClass Tardigrades\FieldType\Generator\EntityPropertiesGenerator
 */
final class EntityPropertiesGeneratorTest extends TestCase
{
    /**
     * @test
     * @covers ::generate
     */
    public function it_should_generate_when_property_is_not_nullable()
    {
        $body = <<<'EOT'
/** @var {{ nullable }}\DateTime */
protected ${{ propertyName }};

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.properties.php.template' => $body
            ]
        ];
        vfsStream::setup('root', null, $structure);

        $templateDir = TemplateDir::fromString('vfs://root');

        $config = [
            'field' => [
                'name' => 'foo',
                'handle' => 'bar',
                'generator' => [
                    'entity' => [
                        'validator' => [
                            'NotBlank' => null
                        ]
                    ]
                ]
            ]
        ];

        $templateString = <<<'EOT'
/** @var \DateTime */
protected $bar;

EOT;

        $field = new Field();
        $field->setConfig($config);

        $result = EntityPropertiesGenerator::generate($field, $templateDir);
        $expected = Template::create($templateString);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @covers ::generate
     */
    public function it_should_generate_when_property_is_nullable()
    {
        $body = <<<'EOT'
/** @var {{ nullable }}\DateTime */
protected ${{ propertyName }};

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.properties.php.template' => $body
            ]
        ];
        vfsStream::setup('root', null, $structure);

        $templateDir = TemplateDir::fromString('vfs://root');

        $config = [
            'field' => [
                'name' => 'foo',
                'handle' => 'bar'
            ]
        ];

        $templateString = <<<'EOT'
/** @var ?\DateTime */
protected $bar;

EOT;

        $field = new Field();
        $field->setConfig($config);

        $result = EntityPropertiesGenerator::generate($field, $templateDir);
        $expected = Template::create($templateString);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @covers ::generate
     */
    public function it_should_generate_when_property_is_nullable_and_generator_has_other_config()
    {
        $body = <<<'EOT'
/** @var {{ nullable }}\DateTime */
protected ${{ propertyName }};

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.properties.php.template' => $body
            ]
        ];
        vfsStream::setup('root', null, $structure);

        $templateDir = TemplateDir::fromString('vfs://root');

        $config = [
            'field' => [
                'name' => 'foo',
                'handle' => 'bar',
                'generator' => [
                    'entity' => [
                        'validator' => [
                            'Length' => [
                                'max' => 255
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $templateString = <<<'EOT'
/** @var ?\DateTime */
protected $bar;

EOT;

        $field = new Field();
        $field->setConfig($config);

        $result = EntityPropertiesGenerator::generate($field, $templateDir);
        $expected = Template::create($templateString);

        $this->assertEquals($expected, $result);
    }
}
