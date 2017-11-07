<?php
declare (strict_types=1);

namespace Tardigrades\FieldType\Generator;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tardigrades\Entity\Field;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;

/**
 * @coversDefaultClass Tardigrades\FieldType\Generator\EntityPreUpdateGenerator
 */
final class EntityPreUpdateGeneratorTest extends TestCase
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
                'entity.preupdate.php.template' => $body
            ]
        ];
        vfsStream::setup('root', null, $structure);

        $templateDir = TemplateDir::fromString('vfs://root');

        $config = [
            'field' => [
                'name' => 'foo',
                'handle' => 'bar',
                'entityEvents' => ['preUpdate']

            ]
        ];

        $templateString = <<<'EOT'
$this->bar = new \DateTime('now');

EOT;

        $field = new Field();
        $field->setConfig($config);

        $result = EntityPreUpdateGenerator::generate($field, $templateDir);
        $expected = Template::create($templateString);

        $this->assertEquals($expected, $result);
    }

    /**
     * @test
     * @covers ::generate
     */
    public function it_should_throw_exception_when_wrong_config()
    {
        $this->expectException(NoPreUpdateEntityEventDefinedInFieldConfigException::class);
        $this->expectExceptionMessage('In the field config this key: entityEvents with this value: - preUpdate is not defined. Skipping pre update rendering for this field.');

        $body = <<<'EOT'
$this->{{ propertyName }} = new \DateTime('now');

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.preupdate.php.template' => $body
            ]
        ];
        vfsStream::setup('root', null, $structure);

        $templateDir = TemplateDir::fromString('vfs://root');

        $config = [
            'field' => [
                'name' => 'foo',
                'handle' => 'bar',
                'entityEvents' => []

            ]
        ];

        $field = new Field();
        $field->setConfig($config);

        EntityPreUpdateGenerator::generate($field, $templateDir);
    }
}
