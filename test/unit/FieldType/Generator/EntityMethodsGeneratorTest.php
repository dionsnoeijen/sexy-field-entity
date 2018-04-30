<?php
declare (strict_types=1);

namespace Tardigrades\FieldType\Generator;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tardigrades\Entity\Field;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;

/**
 * @coversDefaultClass Tardigrades\FieldType\Generator\EntityMethodsGenerator
 */
final class EntityMethodsGeneratorTest extends TestCase
{
    /**
     * @test
     * @covers ::generate
     */
    public function it_should_generate_when_property_is_not_nullable()
    {
        $body = <<<'EOT'
public function get{{ methodName }}(): ?\DateTime
{
    return $this->{{ propertyName }};
}

public function set{{ methodName }}({{ nullable }}\DateTime ${{ propertyName }}): {{ section }}
{
    $this->{{ propertyName }} = ${{ propertyName }};
    return $this;
}

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.methods.php.template' => $body
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
public function getBar(): ?\DateTime
{
    return $this->bar;
}

public function setBar(\DateTime $bar): {{ section }}
{
    $this->bar = $bar;
    return $this;
}

EOT;

        $field = new Field();
        $field->setConfig($config);

        $result = EntityMethodsGenerator::generate($field, $templateDir);
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
public function get{{ methodName }}(): ?\DateTime
{
    return $this->{{ propertyName }};
}

public function set{{ methodName }}({{ nullable }}\DateTime ${{ propertyName }}): {{ section }}
{
    $this->{{ propertyName }} = ${{ propertyName }};
    return $this;
}

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.methods.php.template' => $body
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
public function getBar(): ?\DateTime
{
    return $this->bar;
}

public function setBar(?\DateTime $bar): {{ section }}
{
    $this->bar = $bar;
    return $this;
}

EOT;

        $field = new Field();
        $field->setConfig($config);

        $result = EntityMethodsGenerator::generate($field, $templateDir);
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
public function get{{ methodName }}(): ?\DateTime
{
    return $this->{{ propertyName }};
}

public function set{{ methodName }}({{ nullable }}\DateTime ${{ propertyName }}): {{ section }}
{
    $this->{{ propertyName }} = ${{ propertyName }};
    return $this;
}

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.methods.php.template' => $body
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
public function getBar(): ?\DateTime
{
    return $this->bar;
}

public function setBar(?\DateTime $bar): {{ section }}
{
    $this->bar = $bar;
    return $this;
}

EOT;

        $field = new Field();
        $field->setConfig($config);

        $result = EntityMethodsGenerator::generate($field, $templateDir);
        $expected = Template::create($templateString);

        $this->assertEquals($expected, $result);
    }
}
