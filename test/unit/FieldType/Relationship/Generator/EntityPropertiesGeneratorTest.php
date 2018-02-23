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
 * @coversDefaultClass Tardigrades\FieldType\Relationship\Generator\EntityPropertiesGenerator
 */
final class EntityPropertiesGeneratorTest extends TestCase
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
                            'to' => 'you'
                        ]
                    ]
                )
            );

        $mockedSectionConfig->shouldReceive('getClassName')
            ->andReturn('PauloClass');

        $expected = <<<'EOT'
/** @var ArrayCollection */
protected $yous;


EOT;

        $options = ['sectionConfig' => $mockedSectionConfig];
        $generatedTemplate = EntityPropertiesGenerator::generate($mockedFieldInterface, $templateDir, $options);
        $this->assertInstanceOf(Template::class, $generatedTemplate);
        $this->assertSame($expected, (string)$generatedTemplate);
    }

    /**
     * @test
     * @covers ::generate
     */
    public function it_generates_and_uses_field_aliases()
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
                            'to' => 'you',
                            'as' => 'somethingElse'
                        ]
                    ]
                )
            );

        $mockedSectionConfig->shouldReceive('getClassName')
            ->andReturn('PauloClass');

        $expected = <<<'EOT'
/** @var ArrayCollection */
protected $somethingElses;


EOT;

        $options = ['sectionConfig' => $mockedSectionConfig];
        $generatedTemplate = EntityPropertiesGenerator::generate($mockedFieldInterface, $templateDir, $options);
        $this->assertInstanceOf(Template::class, $generatedTemplate);
        $this->assertSame($expected, (string)$generatedTemplate);
    }
}
