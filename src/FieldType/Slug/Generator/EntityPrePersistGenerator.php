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

namespace Tardigrades\FieldType\Slug\Generator;

use Doctrine\Common\Util\Inflector;
use Tardigrades\Entity\FieldInterface;
use Tardigrades\FieldType\Generator\GeneratorInterface;
use Tardigrades\FieldType\ValueObject\PrePersistTemplate;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;
use Tardigrades\SectionField\ValueObject\Slug as SlugValueObject;
use Tardigrades\SectionField\Generator\Loader\TemplateLoader;

class EntityPrePersistGenerator implements GeneratorInterface
{
    public static function generate(FieldInterface $field, TemplateDir $templateDir, ...$options): Template
    {
        $template = PrePersistTemplate::create(
            TemplateLoader::load(
                $templateDir . '/GeneratorTemplate/entity.prepersist.php.template'
            )
        );

        $asString = (string) $template;
        $asString = str_replace(
            '{{ propertyName }}',
            $field->getConfig()->getPropertyName(),
            $asString
        );

        $slug = SlugValueObject::create(
            $field->getConfig()->getGeneratorConfig()->toArray()['entity']['slugFields']
        );

        $asString = str_replace(
            '{{ verification }}',
            self::makeSlugVerification($slug),
            $asString
        );

        $asString = str_replace(
            '{{ assignment }}',
            self::makeSlugAssignment($slug),
            $asString
        );

        return Template::create($asString);
    }

    private static function makeSlugAssignment(SlugValueObject $slug): string
    {
        $assignment = [];
        foreach ($slug->toArray() as $element) {
            $element = explode('|', $element);
            $value = "\$$element[0]";
            if (count($element) > 1) {
                switch ($element[1]) {
                    case 'DateTime':
                        $value .= "->format('$element[2]')";
                        break;
                }
            }
            $assignment[] = $value;
        }
        return 'Tardigrades\Helper\StringConverter::toSlug(' . implode(" . '-' . ", $assignment) . ');';
    }

    private static function makeSlugVerification(SlugValueObject $slug): string
    {
        $body = '';
        foreach ($slug->toArray() as $element) {
            $element = explode('|', $element);
            $getter = '$this->get' . Inflector::classify($element[0]) . '()';
            $body .= <<<EOT
\$$element[0] = $getter;
if (\$$element[0] === null) {
    throw new \UnexpectedValueException('$element[0] is null, cannot build slug');
}

EOT;
        }
        return $body;
    }
}
