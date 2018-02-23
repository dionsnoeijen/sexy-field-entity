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
                            'to' => 'me'
                        ]
                    ]
                )
            );

        $mockedSectionConfig->shouldReceive('getClassName')
            ->andReturn('MyClass');

        $options = ['sectionConfig' => $mockedSectionConfig];
        $expected = <<<'EOT'
public function getMes(): Collection
{
    return $this->mes;
}

public function addMe(Me $me): {{ section }}
{
    if ($this->mes->contains($me)) {
        return $this;
    }
    $this->mes->add($me);
        $me->setMyClass($this);
        
    return $this;
}

public function removeMe(Me $me): {{ section }}
{
    if (!$this->mes->contains($me)) {
        return $this;
    }
    $this->mes->removeElement($me);
        $me->removeMyClass($this);
    
    return $this;
}


EOT;

        $generatedTemplate = EntityMethodsGenerator::generate($mockedFieldInterface, $templateDir, $options);
        $this->assertInstanceOf(Template::class, $generatedTemplate);
        $this->assertSame($expected, (string)$generatedTemplate);
    }

    /**
     * @test
     * @covers ::generate
     */
    public function it_generates_using_field_aliases_in_relationship()
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
                            'to' => 'me',
                            'as' => 'somethingElse'
                        ]
                    ]
                )
            );

        $mockedSectionConfig->shouldReceive('getClassName')
            ->andReturn('MyClass');

        $options = ['sectionConfig' => $mockedSectionConfig];
        $expected = <<<'EOT'
public function getSomethingElses(): Collection
{
    return $this->somethingElses;
}

public function addSomethingElse(Me $somethingElse): {{ section }}
{
    if ($this->somethingElses->contains($somethingElse)) {
        return $this;
    }
    $this->somethingElses->add($somethingElse);
        $somethingElse->setMyClass($this);
        
    return $this;
}

public function removeSomethingElse(Me $somethingElse): {{ section }}
{
    if (!$this->somethingElses->contains($somethingElse)) {
        return $this;
    }
    $this->somethingElses->removeElement($somethingElse);
        $somethingElse->removeMyClass($this);
    
    return $this;
}


EOT;

        $generatedTemplate = EntityMethodsGenerator::generate($mockedFieldInterface, $templateDir, $options);
        $this->assertInstanceOf(Template::class, $generatedTemplate);
        $this->assertSame($expected, (string)$generatedTemplate);
    }
}
