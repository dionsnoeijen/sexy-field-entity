<?php
declare(strict_types=1);

namespace Tardigrades\SectionField\Generator;

use Tardigrades\Entity\SectionInterface;
use Tardigrades\SectionField\Generator\Loader\TemplateLoader;
use Tardigrades\SectionField\Generator\Writer\Writable;

class EntityTraitGenerator extends Generator implements GeneratorInterface
{
    public function generateBySection(SectionInterface $section): Writable
    {
        $sectionConfig = $section->getConfig();
        $namespace = $sectionConfig->getNamespace() . '\\Entity\\Extra';
        $class = $sectionConfig->getClassName() . 'Trait';

        $template = TemplateLoader::load(__DIR__ . '/GeneratorTemplate/entitytrait.php.template');
        $template = str_replace(
            ['{{ namespace }}', '{{ section }}'],
            [$namespace, $class],
            $template
        );

        return Writable::create(
            $template,
            $namespace . '\\',
            "$class.php",
            false
        );
    }
}
