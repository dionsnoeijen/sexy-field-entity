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
    const TEMPLATE_BODY = <<<'EOT'
public function get{{ methodName }}(): {{ nullable }}\DateTime
{
<?php if (!$nullable) { ?>
    if (is_null($this->{{ propertyName }})) {
        throw new \UnexpectedValueException("Property {{ propertyName }} can not be null");
    }
<?php } ?>
    return $this->{{ propertyName }};
}

public function set{{ methodName }}({{ nullable }}\DateTime ${{ propertyName }}): {{ section }}
{
    $this->{{ propertyName }} = ${{ propertyName }};
    return $this;
}

EOT;

    /**
     * @test
     * @covers ::generate
     */
    public function it_should_generate_when_property_is_not_nullable()
    {
        $structure = [
            'GeneratorTemplate' => [
                'entity.methods.php' => static::TEMPLATE_BODY
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
public function getBar(): \DateTime
{
    if (is_null($this->bar)) {
        throw new \UnexpectedValueException("Property bar can not be null");
    }
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
        $structure = [
            'GeneratorTemplate' => [
                'entity.methods.php' => static::TEMPLATE_BODY
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
        $structure = [
            'GeneratorTemplate' => [
                'entity.methods.php' => static::TEMPLATE_BODY
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
