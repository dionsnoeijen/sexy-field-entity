<?php

/*
 * This file is part of the SexyField package.
 *
 * (c) Dion Snoeijen <hallo@dionsnoeijen.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare (strict_types=1);

namespace Tardigrades\FieldType\ConfigurationOverride\Generator;

use Assert\Assertion;
use Tardigrades\Entity\FieldInterface;
use Tardigrades\FieldType\Generator\GeneratorInterface;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;
use Tardigrades\SectionField\Generator\Loader\TemplateLoader;
use Tardigrades\SectionField\ValueObject\SectionConfig;

class EntityMethodsGenerator implements GeneratorInterface
{
    public static function generate(FieldInterface $field, TemplateDir $templateDir, ...$options): Template
    {
        $fieldConfig = $field->getConfig()->toArray();
        $handle = $field->getConfig()->getHandle();
        $nullable = '?';
        try {
            $fieldGeneratorConfig = $field->getConfig()->getGeneratorConfig()->toArray();
            if (array_key_exists('NotBlank', $fieldGeneratorConfig['entity']['validator'])) {
                $nullable = '';
            }
        } catch (\Throwable $e) {
        }
        try {
            /** @var SectionConfig $sectionConfig */
            $sectionConfig = $options[0]['sectionConfig'];
            $generatorConfig = $sectionConfig->getGeneratorConfig()->toArray();
            if (array_key_exists('NotBlank', $generatorConfig['entity'][(string)$field->getHandle()])) {
                $nullable = '';
            }
        } catch (\Throwable $e) {
        }

        try {
            $hierarchy = $fieldConfig['field']['hierarchy'];
        } catch (\Throwable $exception) {
        }
        Assertion::isArray($hierarchy, 'hierarchy must be an Array');

        $stringHandle = (string) $handle;
        try {
            $multiple = $fieldConfig['field']['form']['all']['multiple'];
        } catch (\Throwable $exception) {
            $multiple = false;
        }
        $returnType = $multiple? 'array': 'string';
        $sectionName = ucfirst((string) $sectionConfig->getHandle());
        $positionToLook = array_search($sectionName, $hierarchy) + 1;

        return Template::create((string)TemplateLoader::load(
            (string) $templateDir . '/GeneratorTemplate/entity.methods.php',
            [
                'hierarchy' => $hierarchy,
                'propertyName' => $stringHandle,
                'methodName' => ucfirst($stringHandle),
                'nullable' => $nullable,
                'sectionName' => $sectionName,
                'positionToLook' => $positionToLook,
                'hasParentConfig' => (count($hierarchy) > $positionToLook),
                'returnType' => $returnType
            ]
        ));
    }
}