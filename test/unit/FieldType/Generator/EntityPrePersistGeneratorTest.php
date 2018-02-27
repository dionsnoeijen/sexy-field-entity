<?php
declare (strict_types=1);

namespace Tardigrades\FieldType\Generator;

use Assert\InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tardigrades\Entity\Field;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;

/**
 * @coversDefaultClass Tardigrades\FieldType\Generator\EntityPrePersistGenerator
 */
final class EntityPrePersistGeneratorTest extends TestCase
{
    /**
     * @test
     * @covers ::generate
     */
    public function it_should_generate()
    {
        $body = <<<'EOT'
$this->{{ propertyName }} = new \DateTime('now');

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.prepersist.php.template' => $body
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
                        'event' => ['prePersist']
                    ]
                ]
            ]
        ];

        $templateString = <<<'EOT'
$this->bar = new \DateTime('now');

EOT;

        $field = new Field();
        $field->setConfig($config);

        $result = EntityPrePersistGenerator::generate($field, $templateDir);
        $expected = Template::create($templateString);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @covers ::generate
     */
    public function it_should_throw_error_if_config_is_wrong()
    {
        $this->expectException(InvalidArgumentException::class);
        // @codingStandardsIgnoreStart
        $this->expectExceptionMessage('Entity events should be an array of events you want a generator to run for.');
        // @codingStandardsIgnoreEnd

        $body = <<<'EOT'
$this->{{ propertyName }} = new \DateTime('now');

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.prepersist.php.template' => $body
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
                        'event' => 'nothingandwrong'
                    ]
                ]
            ]
        ];

        $field = new Field();
        $field->setConfig($config);

        EntityPrePersistGenerator::generate($field, $templateDir);
    }
}
