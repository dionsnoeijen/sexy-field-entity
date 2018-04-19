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
        $this->compareOutput(
            [
                'field' => [
                    'name' => 'iets',
                    'handle' => 'niets',
                    'kind' => 'one-to-many',
                    'relationship-type' => 'bidirectional',
                    'entityEvents' => ['1', '2'],
                    'to' => 'me'
                ]
            ],
            <<<'EOT'
public function getMes(): ?Collection
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
    $me->removeMyClass();

    return $this;
}

EOT
        );
    }

    /**
     * @test
     * @covers ::generate
     */
    public function it_generates_using_field_aliases_in_relationship()
    {
        $this->compareOutput(
            [
                'field' => [
                    'name' => 'iets',
                    'handle' => 'niets',
                    'kind' => 'many-to-many',
                    'relationship-type' => 'bidirectional',
                    'entityEvents' => ['1', '2'],
                    'to' => 'me',
                    'as' => 'somethingElse'
                ]
            ],
            <<<'EOT'
public function getSomethingElses(): ?Collection
{
    return $this->somethingElses;
}

public function addSomethingElse(Me $somethingElse): {{ section }}
{
    if ($this->somethingElses->contains($somethingElse)) {
        return $this;
    }
    $this->somethingElses->add($somethingElse);
    $somethingElse->addMyClass($this);

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

EOT
        );
    }

    /**
     * @test
     * @covers ::generate
     */
    public function it_generates_other_relationship_kinds()
    {
        $this->compareOutput(
            [
                'field' => [
                    'name' => 'iets',
                    'handle' => 'niets',
                    'kind' => 'many-to-one',
                    'relationship-type' => 'bidirectional',
                    'entityEvents' => ['1', '2'],
                    'to' => 'me',
                    'from-handle' => 'ParticularMyClass'
                ]
            ],
            <<<'EOT'
public function getMe(): ?Me
{
    return $this->me;
}

public function hasMe(): bool
{
    return !empty($this->me);
}

public function setMe(Me $me): {{ section }}
{
    if ($this->me === $me) {
        return $this;
    }
    $this->me = $me;
    $me->addParticularMyClass($this);

    return $this;
}

public function removeMe(): {{ section }}
{
    if ($this->me === null) {
        return $this;
    }
    $me = $this->me;
    $this->me = null;
    $me->removeParticularMyClass($this);

    return $this;
}

EOT
        );

        $this->compareOutput(
            [
                'field' => [
                    'name' => 'iets',
                    'handle' => 'niets',
                    'kind' => 'one-to-one',
                    'relationship-type' => 'unidirectional',
                    'entityEvents' => ['1', '2'],
                    'to' => 'me'
                ]
            ],
            <<<'EOT'
public function getMe(): ?Me
{
    return $this->me;
}

public function hasMe(): bool
{
    return !empty($this->me);
}

public function setMe(Me $me): {{ section }}
{
    if ($this->me === $me) {
        return $this;
    }
    $this->me = $me;

    return $this;
}

public function removeMe(): {{ section }}
{
    if ($this->me === null) {
        return $this;
    }
    $this->me = null;

    return $this;
}

EOT
        );
    }

    private function compareOutput($config, $expected)
    {
        $mockedFieldInterface = Mockery::mock(new Field())->makePartial();
        $mockedSectionConfig = Mockery::mock('alias:SectionConfig')->makePartial();
        $templateDir = TemplateDir::fromString(__DIR__ . '/../../../../../src/FieldType/Relationship');

        $mockedFieldInterface->shouldReceive('getConfig')
            ->andReturn(FieldConfig::fromArray($config));

        $mockedSectionConfig->shouldReceive('getClassName')
            ->andReturn('MyClass');

        $options = ['sectionConfig' => $mockedSectionConfig];

        $generatedTemplate = EntityMethodsGenerator::generate($mockedFieldInterface, $templateDir, $options);
        $this->assertInstanceOf(Template::class, $generatedTemplate);
        $this->assertSame($expected, (string)$generatedTemplate);
    }
}
