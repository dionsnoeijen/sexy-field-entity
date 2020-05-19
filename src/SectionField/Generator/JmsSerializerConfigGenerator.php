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

use Doctrine\Common\Inflector\Inflector;
use Symfony\Component\Yaml\Yaml;
use Tardigrades\Entity\FieldInterface;
use Tardigrades\Entity\SectionInterface;
use Tardigrades\SectionField\Generator\Loader\TemplateLoader;
use Tardigrades\SectionField\Generator\Writer\Writable;
use Tardigrades\SectionField\ValueObject\FullyQualifiedClassName;

class JmsSerializerConfigGenerator extends Generator implements GeneratorInterface
{
    public function generateBySection(SectionInterface $section): Writable
    {
        $sectionConfig = $section->getConfig();

        $location = $sectionConfig->getNamespace() . '\\Resources\\config\\serializer\\';
        $fqcnEntity = FullyQualifiedClassName::fromNamespaceAndClassName(
            $sectionConfig->getNamespace(),
            $sectionConfig->getClassName()
        );
        $class = str_replace('\\', '.', (string) $fqcnEntity);

        $fields = $this->fieldManager->readByHandles($sectionConfig->getFields());
        $fieldsSerializerConfig = $this->parseFieldProperties($fields);
        $sectionConfig = $sectionConfig->toArray();
        $sectionSerializerConfig = [];
        if (!empty($sectionConfig['section']) &&
            !empty($sectionConfig['section']['serializer'])
        ) {
            $sectionSerializerConfig = $sectionConfig['section']['serializer'];
        }

        // If no configuration for this section,
        // don't generate a file
        if (empty($sectionSerializerConfig) &&
            empty($fieldsSerializerConfig['properties'])
        ) {
            throw new NoJmsConfigurationException();
        }

        $configuration = array_replace_recursive($fieldsSerializerConfig, $sectionSerializerConfig);

        // Make a properly indented yaml
        $configuration = Yaml::dump($configuration);
        $parsedConfiguration = '';
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $configuration) as $line) {
            $parsedConfiguration .= '    ' . $line . "\n";
        }

        $template = TemplateLoader::load(__DIR__ . '/GeneratorTemplate/jmsserializer.yml.template');
        $template = str_replace(
            ['{{ fqcnEntity }}', '{{ configuration }}'],
            [ (string) $fqcnEntity, $parsedConfiguration ],
            $template
        );

        return Writable::create(
            $template,
            $location,
            "$class.yml"
        );
    }

    private function parseFieldProperties(array $fields): array
    {
        $result = [ 'properties' => [] ];
        /** @var FieldInterface $field */
        foreach ($fields as $field) {
            $fieldTypeClass = (string) $field->getFieldType()->getFullyQualifiedClassName()->getClassName();
            $fieldConfig = $field->getConfig()->toArray();
            $propertyName = (string) $field->getHandle();
            if ($fieldTypeClass === 'Relationship') {
                $singularPropertyName = $fieldConfig['field']['as'] ?? $fieldConfig['field']['to'];
                $kind = $fieldConfig['field']['kind'];
                if ($kind === 'one-to-many' || $kind === 'many-to-many') {
                    $propertyName = Inflector::pluralize($singularPropertyName);
                } else {
                    $propertyName = $singularPropertyName;
                }
            }
            if (!empty($fieldConfig['field']) &&
                !empty($fieldConfig['field']['serializer'])
            ) {
                $result['properties'][$propertyName] = $fieldConfig['field']['serializer'];
            }
        }

        return $result;
    }
}
