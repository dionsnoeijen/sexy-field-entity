<?php

/*
 * This file is part of the SexyField package.
 *
 * (c) Dion Snoeijen <hallo@dionsnoeijen.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types = 1);

namespace Tardigrades\FieldType\Generator;

use Doctrine\Inflector\InflectorFactory;
use Tardigrades\Entity\FieldInterface;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;
use Tardigrades\SectionField\Generator\Loader\TemplateLoader;
use Tardigrades\SectionField\ValueObject\SectionConfig;

class EntityValidatorMetadataGenerator implements GeneratorInterface
{
    public static function generate(FieldInterface $field, TemplateDir $templateDir, ...$options): Template
    {
        $asString = (string) Template::create(
            (string) TemplateLoader::load(
                (string) $templateDir . '/GeneratorTemplate/entity.validator-metadata.php.template'
            )
        );

        $generatorConfig = $field->getConfig()->getGeneratorConfig()->toArray();
        $fieldConfig = $field->getConfig()->toArray();

        // @todo: Move to value object...
        $propertyName =
            !empty($fieldConfig['field']['as']) ?
                $fieldConfig['field']['as'] :
                (!empty($fieldConfig['field']['to']) ?
                    $fieldConfig['field']['to'] :
                    $field->getConfig()->getPropertyName());

        if (!empty($fieldConfig['field']['kind'])) {
            switch ($fieldConfig['field']['kind']) {
                case 'many-to-many':
                case 'one-to-many':
                    $inflector = InflectorFactory::create()->build();
                    $propertyName = $inflector->pluralize((string) $propertyName);
                    break;
            }
        }

        // See if there's stuff in the section config that overrides the field config
        /** @var SectionConfig $sectionConfig */
        $sectionConfig = $options[0]['sectionConfig'];
        try {
            $sectionConfig = $sectionConfig->getGeneratorConfig()->toArray();
            $generatorConfig = self::checkSectionOverrides($sectionConfig, $generatorConfig, (string) $propertyName);
        } catch (\InvalidArgumentException $exception) {
            //
        }

        if (!empty($generatorConfig['entity']['validator'])) {
            $asString = str_replace(
                '{{ propertyName }}',
                (string) $propertyName,
                $asString
            );
            $strings = [];
            foreach ($generatorConfig['entity']['validator'] as $assertion => $assertionOptions) {
                $path = strpos($assertion,"\\") === false? 'new Assert\\': 'new \\';
                $stringPiece = $asString;
                $stringPiece = str_replace('{{ path }}', $path, $stringPiece);
                $stringPiece = str_replace('{{ assertion }}', $assertion, $stringPiece);
                $options = '';
                if (is_array($assertionOptions)) {
                    foreach ($assertionOptions as $optionKey => $optionValue) {
                        $options .= "'{$optionKey}' => '{$optionValue}',";
                    }
                }

                if (!empty($options)) {
                    $options = rtrim($options, ',');
                    $options = "[{$options}]";
                }
                $stringPiece = str_replace('{{ assertionOptions }}', $options, $stringPiece);
                $strings[] = $stringPiece;
            }
            return Template::create(implode("\n", $strings));
        }

        return Template::create('');
    }

    private static function checkSectionOverrides(
        array $sectionConfig,
        array $generatorConfig,
        string $propertyName
    ): array {

        if (isset($sectionConfig['entity']) &&
            array_key_exists($propertyName, $sectionConfig['entity'])
        ) {
            foreach ($sectionConfig['entity'][$propertyName] as $key => $value) {
                if (array_key_exists($key, $generatorConfig['entity']['validator'])) {
                    if (!$sectionConfig['entity'][$propertyName][$key]) {
                        unset($generatorConfig['entity']['validator'][$key]);
                    } else {
                        $generatorConfig['entity']['validator'][$key] =
                            $sectionConfig['entity'][$propertyName][$key];
                    }
                }
            }
        }

        return $generatorConfig;
    }
}
