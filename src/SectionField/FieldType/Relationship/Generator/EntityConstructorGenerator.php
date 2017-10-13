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
use Tardigrades\SectionField\Generator\Loader\TemplateLoader;

class EntityConstructorGenerator implements GeneratorInterface
{
    public static function generate(FieldInterface $field, ...$options): Template
    {
        $fieldConfig = $field->getConfig()->toArray();

        return Template::create((string) TemplateLoader::load(
            $field->getFieldType()->getInstance()->directory() .
            '/GeneratorTemplate/entity.constructor.php', [
                'kind' => $fieldConfig['field']['kind'],
                'pluralPropertyName' => Inflector::pluralize($fieldConfig['field']['to'])
            ]
        ));
    }
}
