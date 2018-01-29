<?php
declare (strict_types=1);

namespace Tardigrades\FieldType\Generator;

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
                'entityEvents' => ['prePersist']
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
        $this->expectException(NoPrePersistEntityEventDefinedInFieldConfigException::class);
        // @codingStandardsIgnoreStart
        $this->expectExceptionMessage('In the field config this key: entityEvents with this value: - prePersist is not defined. Skipping pre update rendering for this field.');
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
                'entityEvents' => []
            ]
        ];

        $field = new Field();
        $field->setConfig($config);

        EntityPrePersistGenerator::generate($field, $templateDir);
    }
}
