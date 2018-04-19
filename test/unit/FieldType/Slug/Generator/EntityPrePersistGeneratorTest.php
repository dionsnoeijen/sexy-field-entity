<?php
declare (strict_types=1);

namespace Tardigrades\FieldType\Slug\Generator;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tardigrades\Entity\Field;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;
use Tardigrades\SectionField\ValueObject\FieldConfig;

/**
 * @coversDefaultClass Tardigrades\FieldType\Slug\Generator\EntityPrePersistGenerator
 */
class EntityPrePersistGeneratorTest extends TestCase
{
    /**
     * @test
     * @covers ::generate
     * @covers ::<private>
     */
    public function it_should_generate()
    {
        $mockedFieldInterface = Mockery::mock(new Field())->makePartial();
        $templateDir = TemplateDir::fromString(__DIR__ . '/../../../../../src/FieldType/Slug');

        $mockedFieldInterface->shouldReceive('getConfig')
            ->andReturn(
                FieldConfig::fromArray(
                    [
                        'field' => [
                            'name' => 'iets',
                            'handle' => 'niets',
                            'kind' => 'one-to-many',
                            'entityEvents' => ['1', '2'],
                            'to' => 'you',
                            'generator' => [
                                'entity' => [
                                    'slugFields' => ['snail', 'sexy|DateTime|Y-m-d']
                                ]
                            ]
                        ]
                    ]
                )
            );

        $generatedTemplate = EntityPrePersistGenerator::generate($mockedFieldInterface, $templateDir);
        $this->assertInstanceOf(Template::class, $generatedTemplate);
        $this->assertFalse((string)$generatedTemplate === '');

        $expected = <<<'EOT'
// phpcs:ignore Generic.Files.LineLength
if ($this->getSnail() === null) {
    throw new \UnexpectedValueException('snail is null, cannot build slug');
}
if ($this->getSexy() === null) {
    throw new \UnexpectedValueException('sexy is null, cannot build slug');
}

$this->niets = Tardigrades\Helper\StringConverter::toSlug($this->getSnail() . '-' . $this->getSexy()->format('Y-m-d'));

EOT;

        $this->assertSame($expected, (string)$generatedTemplate);
    }
}
