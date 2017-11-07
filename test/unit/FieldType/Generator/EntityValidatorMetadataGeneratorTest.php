<?php
declare (strict_types=1);

namespace Tardigrades\FieldType\Generator;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tardigrades\Entity\Field;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;

/**
 * @coversDefaultClass Tardigrades\FieldType\Generator\EntityValidatorMetadataGenerator
 */
final class EntityValidatorMetadataGeneratorTest extends TestCase
{
    /**
     * @test
     * @covers ::generate
     */
    public function it_should_generate()
    {
        $body = <<<'EOT'
$metadata->addPropertyConstraint('{{ propertyName }}', new Assert\{{ assertion }}({{ assertionOptions }}));

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.validator-metadata.php.template' => $body
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
                                'min' => '2',
                                'max' => '255'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $templateString = <<<'EOT'
$metadata->addPropertyConstraint('bar', new Assert\Length(['min' => '2','max' => '255']));

EOT;

        $field = new Field();
        $field->setConfig($config);

        $result = EntityValidatorMetadataGenerator::generate($field, $templateDir);
        $expected = Template::create($templateString);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @covers ::generate
     */
    public function it_should_create_template_from_empty_string_when_wrong_config()
    {
        $body = <<<'EOT'
$metadata->addPropertyConstraint('{{ propertyName }}', new Assert\{{ assertion }}({{ assertionOptions }}));

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.validator-metadata.php.template' => $body
            ]
        ];
        vfsStream::setup('root', null, $structure);

        $templateDir = TemplateDir::fromString('vfs://root');

        $config = [
            'field' => [
                'name' => 'foo',
                'handle' => 'bar',
                'generator' => []
            ]
        ];

        $field = new Field();
        $field->setConfig($config);

        $result = EntityValidatorMetadataGenerator::generate($field, $templateDir);
        $expected = Template::create('');

        $this->assertEquals($expected, $result);
    }
}
