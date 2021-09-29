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

use Tardigrades\Entity\FieldInterface;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;
use Tardigrades\SectionField\Generator\Loader\TemplateLoader;
use Tardigrades\SectionField\ValueObject\SectionConfig;

class EntityMethodsGenerator implements GeneratorInterface
{
    public static function generate(FieldInterface $field, TemplateDir $templateDir, ...$options): Template
    {
        $nullable = true;
        try {
            $generatorConfig = $field->getConfig()->getGeneratorConfig()->toArray();
            if (array_key_exists('NotBlank', $generatorConfig['entity']['validator']) &&
                $generatorConfig['entity']['validator']['NotBlank']
            ) {
                $nullable = false;
            }
        } catch (\Throwable $e) {
            // The key did't exist at all
        }

        try {
            /** @var SectionConfig $sectionConfig */
            $sectionConfig = $options[0]['sectionConfig'];
            $generatorConfig = $sectionConfig->getGeneratorConfig()->toArray();
            // If the key exists, it means it's overridden in the section config.
            // So let's do it again, set it to true.
            if (array_key_exists('NotBlank', $generatorConfig['entity'][(string)$field->getHandle()])) {
                $nullable = true;
                // unless it's set to false
                if ($generatorConfig['entity'][(string)$field->getHandle()]['NotBlank']) {
                    $nullable = false;
                }
            }
        } catch (\Throwable $e) {
            // The key did't exist at all
        }

        $asString = (string) TemplateLoader::load(
            (string) $templateDir . '/GeneratorTemplate/entity.methods.php.template'
        );

        $asString = str_replace(
            '{{ nullable }}',
            $nullable ? '?' : '',
            $asString
        );

        $asString = str_replace(
            '{{ methodName }}',
            (string) $field->getConfig()->getMethodName(),
            $asString
        );
        $asString = str_replace(
            '{{ propertyName }}',
            (string) $field->getConfig()->getPropertyName(),
            $asString
        );

        return Template::create($asString);
    }
}
