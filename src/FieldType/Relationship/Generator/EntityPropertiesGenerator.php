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

use Doctrine\Inflector\InflectorFactory;
use Tardigrades\Entity\FieldInterface;
use Tardigrades\FieldType\Generator\GeneratorInterface;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;
use Tardigrades\SectionField\Generator\Loader\TemplateLoader;

class EntityPropertiesGenerator implements GeneratorInterface
{
    public static function generate(FieldInterface $field, TemplateDir $templateDir, ...$options): Template
    {
        $fieldConfig = $field->getConfig()->toArray();

        $toHandle = $fieldConfig['field']['as'] ?? $fieldConfig['field']['to'];

        $inflector = InflectorFactory::create()->build();

        return Template::create((string) TemplateLoader::load(
            $templateDir .
            '/GeneratorTemplate/entity.properties.php',
            [
                'kind' => $fieldConfig['field']['kind'],
                'pluralPropertyName' => $inflector->pluralize($toHandle),
                'entity' => ucfirst($fieldConfig['field']['to']),
                'propertyName' => $toHandle
            ]
        ));
    }
}
