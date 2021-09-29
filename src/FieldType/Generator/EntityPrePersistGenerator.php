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

class EntityPrePersistGenerator implements GeneratorInterface
{
    public static function generate(FieldInterface $field, TemplateDir $templateDir, ...$options): Template
    {
        if (in_array('prePersist', $field->getConfig()->getEntityEvents())) {
            $asString = (string) TemplateLoader::load(
                (string) $templateDir . '/GeneratorTemplate/entity.prepersist.php.template'
            );

            $asString = str_replace(
                '{{ propertyName }}',
                (string) $field->getConfig()->getPropertyName(),
                $asString
            );

            return Template::create(
                $asString
            );
        }

        throw new NoPrePersistEntityEventDefinedInFieldConfigException();
    }
}
