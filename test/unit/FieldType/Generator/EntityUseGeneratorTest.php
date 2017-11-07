<?php
declare (strict_types=1);

namespace Tardigrades\FieldType\Generator;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tardigrades\Entity\Field;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;

/**
 * @coversDefaultClass Tardigrades\FieldType\Generator\EntityUseGenerator
 */
final class EntityUseGeneratorTest extends TestCase
{
    /**
     * @test
     * @covers ::generate
     */
    public function it_should_generate()
    {
        $body = <<<'EOT'
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

EOT;

        $structure = [
            'GeneratorTemplate' => [
                'entity.use.php.template' => $body
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

        $field = new Field();
        $field->setConfig($config);

        $result = EntityUseGenerator::generate($field, $templateDir);
        $expected = Template::create($body);

        $this->assertEquals($expected, $result);
    }
}
