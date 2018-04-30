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

class EntityValidatorMetadataGenerator implements GeneratorInterface
{
    public static function generate(FieldInterface $field, TemplateDir $templateDir): Template
    {
        $asString = (string) Template::create(
            (string) TemplateLoader::load(
                (string) $templateDir . '/GeneratorTemplate/entity.validator-metadata.php.template'
            )
        );

        $generatorConfig = $field->getConfig()->getGeneratorConfig()->toArray();

        if (!empty($generatorConfig['entity']['validator'])) {
            $asString = str_replace(
                '{{ propertyName }}',
                $field->getConfig()->getPropertyName(),
                $asString
            );
            foreach ($generatorConfig['entity']['validator'] as $assertion => $assertionOptions) {
                $asString = str_replace('{{ assertion }}', $assertion, $asString);
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
                $asString = str_replace('{{ assertionOptions }}', $options, $asString);
            }
            return Template::create($asString);
        }

        return Template::create('');
    }
}
