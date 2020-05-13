<?php

/*
 * This file is part of the SexyField package.
 *
 * (c) Dion Snoeijen <hallo@dionsnoeijen.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tardigrades\SectionField\Generator;

use Symfony\Component\Yaml\Yaml;
use Tardigrades\Entity\SectionInterface;
use Tardigrades\SectionField\Generator\Loader\TemplateLoader;
use Tardigrades\SectionField\Generator\Writer\Writable;
use Tardigrades\SectionField\Service\NoJmsConfigurationException;
use Tardigrades\SectionField\ValueObject\FullyQualifiedClassName;

class JmsSerializerConfigGenerator extends Generator implements GeneratorInterface
{
    public function generateBySection(SectionInterface $section): Writable
    {
        $sectionConfig = $section->getConfig();
        $location = $sectionConfig->getNamespace() . '\\Resources\\config\\serializer\\';
        $class = 'Model.' . $sectionConfig->getClassName();
        $fqcEntity = FullyQualifiedClassName::fromNamespaceAndClassName(
            $sectionConfig->getNamespace(),
            $sectionConfig->getClassName()
        );

        $sectionConfig = $sectionConfig->toArray();
        if (empty($sectionConfig['section']) ||
            empty($sectionConfig['section']['serializer'])
        ) {
            throw new NoJmsConfigurationException();
        }

        $configuration = Yaml::dump($sectionConfig['section']['serializer']);
        $template = TemplateLoader::load(
            __DIR__ . '/GeneratorTemplate/jmsserializer.yml.template'
        );
        $template = str_replace(
            ['{{ fcqEntity }}', '{{ configuration }}'],
            [ (string) $fqcEntity, $configuration ],
            $template
        );

        return Writable::create(
            $template,
            $location,
            "$class.yml",
            false
        );
    }
}
