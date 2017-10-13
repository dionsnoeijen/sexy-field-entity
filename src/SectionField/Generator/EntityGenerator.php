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

namespace Tardigrades\SectionField\Generator;

use ReflectionClass;
use Tardigrades\Entity\FieldInterface;
use Tardigrades\Entity\SectionInterface;
use Tardigrades\FieldType\FieldTypeInterface;
use Tardigrades\FieldType\ValueObject\Template;
use Tardigrades\FieldType\ValueObject\TemplateDir;
use Tardigrades\SectionField\Generator\Loader\TemplateLoader;
use Tardigrades\SectionField\Generator\Writer\Writable;
use Tardigrades\SectionField\ValueObject\Handle;
use Tardigrades\SectionField\ValueObject\SectionConfig;
use Tardigrades\SectionField\ValueObject\SlugField;

class EntityGenerator extends Generator implements GeneratorInterface
{
    /** @var SectionConfig */
    private $sectionConfig;

    /** @var array */
    private $templates = [
        'use' => [],
        'properties' => [],
        'constructor' => [],
        'methods' => [],
        'prePersist' => [],
        'preUpdate' => []
    ];

    const GENERATE_FOR = 'entity';

    public function generateBySection(
        SectionInterface $section
    ): Writable {

        $this->sectionConfig = $section->getConfig();

        $fields = $this->fieldManager->readByHandles($this->sectionConfig->getFields());
        $fields = $this->addOpposingRelationships($section, $fields);

        $this->generateElements($fields);

        return Writable::create(
            (string) $this->generateEntity(),
            $this->sectionConfig->getNamespace() . '\\Entity\\',
            $this->sectionConfig->getClassName() . '.php'
        );
    }

    private function generateElements(array $fields): void
    {
        /** @var FieldInterface $field */
        foreach ($fields as $field) {

            $parsed = $this->getFieldTypeGeneratorConfig($field, self::GENERATE_FOR);

            /**
             * @var string $item
             * @var \Tardigrades\FieldType\Generator\GeneratorInterface $generator
             */
            foreach ($parsed[self::GENERATE_FOR] as $item=>$generator) {
                if (!key_exists($item, $this->templates)) {
                    $this->templates[$item] = [];
                }
                if (class_exists($generator)) {
                    $interfaces = class_implements($generator);
                } else {
                    $this->buildMessages[] = 'Generators ' . get_class($generator) . ': Generators not found.';
                    break;
                }
                if (key($interfaces) === \Tardigrades\FieldType\Generator\GeneratorInterface::class) {
                    try {
                        $reflector = new ReflectionClass($generator);
                        $method = $reflector->getMethod('generate');
                        $options = [];
                        if (isset($method->getParameters()[1])) {
                            $options = [
                                'sectionManager' => $this->sectionManager,
                                'sectionConfig' => $this->sectionConfig
                            ];
                        }
                        $templateDir = TemplateDir::fromString($this->getFieldTypeTemplateDirectory(
                            $field,
                            'sexy-field-field-types-base',
                            'sexy-field-entity'
                        ));
                        $this->templates[$item][] = $generator::generate($field, $templateDir, $options);
                    } catch (\Exception $exception) {
                        $this->buildMessages[] = $exception->getMessage();
                    }
                }
            }

            $this->removeDoubles();
        }
    }

    private function removeDoubles()
    {
        foreach ($this->templates as $item=>&$templates) {
            $templates = array_unique($templates);
        }
    }

    protected function generateSlugFieldGetMethod(SlugField $slugField): string
    {
        if ((string) $slugField !== 'slug') {
            return <<<EOT
public function getSlug(): Tardigrades\SectionField\ValueObject\Slug
{
    return Tardigrades\SectionField\ValueObject\Slug::fromString(\$this->{$slugField});
}
EOT;
        }

        return '';
    }

    protected function generateDefaultFieldGetMethod(string $defaultField): string
    {
        return <<<EOT
public function getDefault(): string
{
    return \$this->{$defaultField};
}
EOT;
    }

    private function combine(array $templates): string
    {
        $combined = '';
        foreach ($templates as $template) {
            $combined .= $template;
        }
        return $combined;
    }

    private function insertRenderedTemplates(string $template): string
    {
        foreach ($this->templates as $templateVariable=>$templates) {
            $template = str_replace(
                '{{ ' . $templateVariable . ' }}',
                $this->combine($templates),
                $template
            );
        }

        return $template;
    }

    private function insertSlug(string $template): string
    {
        try {
            if ($this->sectionConfig->getSlugField() !== 'slug') {
                $template = str_replace(
                    '{{ getSlug }}',
                    $this->generateSlugFieldGetMethod($this->sectionConfig->getSlugField()),
                    $template
                );
            }
        } catch (\Exception $exception) {
            $template = str_replace(
                '{{ getSlug }}',
                '',
                $template
            );
            $this->buildMessages[] = 'There is no slug field available, skipping generic method.';
        }

        return $template;
    }

    private function insertDefaultFieldMethod(string $template): string
    {
        $template = str_replace(
            '{{ getDefault }}',
            $this->generateDefaultFieldGetMethod($this->sectionConfig->getDefault()),
            $template
        );

        return $template;
    }

    private function insertSection(string $template): string
    {
        $template = str_replace(
            '{{ section }}',
            $this->sectionConfig->getClassName(),
            $template
        );

        return $template;
    }

    private function insertNamespace(string $template): string
    {
        $template = str_replace(
            '{{ namespace }}',
            (string) $this->sectionConfig->getNamespace() . '\\Entity',
            $template
        );

        return $template;
    }

    private function insertValidationMetadata(string $template): string
    {
        $generatorConfig = $this->sectionConfig->getGeneratorConfig()->toArray();
        $metadata = '';

        foreach ($generatorConfig['entity'] as $handle => $options) {

            $field = $this->fieldManager->readByHandle(Handle::fromString($handle));
            /** @var FieldTypeInterface $fieldType */
            $fieldType = $this->container->get((string) $field->getFieldType()->getFullyQualifiedClassName());
            $templateDirectory = str_replace(
                'sexy-field-field-types-base',
                'sexy-field-entity',
                $fieldType->directory()
            );

            foreach ($options as $assertion => $assertionOptions) {
                try {
                    $asString = (string) Template::create(
                        (string) TemplateLoader::load(
                            $templateDirectory . '/GeneratorTemplate/entity.validator-metadata.php.template'
                        )
                    );
                    $asString = str_replace(
                        '{{ propertyName }}',
                        $field->getHandle(),
                        $asString
                    );

                    $asString = str_replace(
                        '{{ assertion }}',
                        $assertion,
                        $asString
                    );
                    $arguments = '';
                    if (is_array($assertionOptions)) {
                        foreach ($assertionOptions as $optionKey => $optionValue) {
                            $arguments .= "'{$optionKey}' => '{$optionValue}',";
                        }
                        if (!empty($arguments)) {
                            $arguments = rtrim($arguments, ',');
                            $arguments = "[{$arguments}]";
                        }
                    }
                    $asString = str_replace(
                        '{{ assertionOptions }}',
                        $arguments,
                        $asString
                    );
                    if (strpos($template, $asString) === false) {
                        // Add to metadata
                        $metadata .= $asString;
                    }
                } catch (\Exception $exception) {
                    $this->buildMessages[] = $exception->getMessage();
                }
            }
        }

        // Insert
        $template = str_replace(
            '{{ validatorMetadataSectionPhase }}',
            $metadata,
            $template
        );

        return $template;
    }

    private function generateEntity(): Template
    {
        $template = TemplateLoader::load(__DIR__ . '/GeneratorTemplate/entity.php.template');

        $template = $this->insertRenderedTemplates($template);
        $template = $this->insertSlug($template);
        $template = $this->insertDefaultFieldMethod($template);
        $template = $this->insertSection($template);
        $template = $this->insertNamespace($template);
        $template = $this->insertValidationMetadata($template);

        return Template::create(PhpFormatter::format($template));
    }
}
