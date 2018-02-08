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

namespace Tardigrades\FieldType\Relationship\Generator;

use Doctrine\Common\Util\Inflector;
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

        /** @var SectionConfig $sectionConfig */
        $sectionConfig = $options[0]['sectionConfig'];

        return Template::create((string) TemplateLoader::load(
            (string) $templateDir .
            '/GeneratorTemplate/entity.methods.php',
            [
                'kind' => $fieldConfig['field']['kind'],
                'pluralMethodName' => ucfirst(Inflector::pluralize($fieldConfig['field']['to'])),
                'pluralPropertyName' => Inflector::pluralize($fieldConfig['field']['to']),
                'methodName' => ucfirst($fieldConfig['field']['to']),
                'entity' => ucfirst($fieldConfig['field']['to']),
                'propertyName' => $fieldConfig['field']['to'],
                'thatMethodName' => $sectionConfig->getClassName()
            ]
        ));
    }
}
