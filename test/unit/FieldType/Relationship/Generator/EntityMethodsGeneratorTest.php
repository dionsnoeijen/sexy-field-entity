<?php
declare (strict_types=1);

namespace Tardigrades\FieldType\Relationship\Generator;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tardigrades\Entity\Field;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;
use Tardigrades\SectionField\ValueObject\FieldConfig;

/**
 * @coversDefaultClass Tardigrades\FieldType\Relationship\Generator\EntityMethodsGenerator
 */
final class EntityMethodsGeneratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @test
     * @covers ::generate
     */
    public function it_generates()
    {
        $mockedFieldInterface = Mockery::mock(new Field())->makePartial();
        $mockedSectionConfig = Mockery::mock('alias:SectionConfig')->makePartial();
        $templateDir = TemplateDir::fromString('src/FieldType/Relationship');

        $mockedFieldInterface->shouldReceive('getConfig')
            ->andReturn(
                FieldConfig::fromArray(
                    [
                        'field' => [
                            'name' => 'iets',
                            'handle' => 'niets',
                            'kind' => 'one-to-many',
                            'entityEvents' => ['1', '2'],
                            'to' => 'me!'
                        ]
                    ]
                )
            );

        $mockedSectionConfig->shouldReceive('getClassName')
            ->andReturn('this one');

        $options = ['sectionConfig' => $mockedSectionConfig];
        $generatedTemplate = EntityMethodsGenerator::generate($mockedFieldInterface, $templateDir, $options);
        $this->assertInstanceOf(Template::class, $generatedTemplate);
        $this->assertFalse((string)$generatedTemplate === '');
    }
}
