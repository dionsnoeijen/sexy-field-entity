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

use Doctrine\Common\Util\Inflector;
use ReflectionClass;
use Tardigrades\Entity\FieldInterface;
use Tardigrades\Entity\SectionInterface;
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
    private $templates;

    /** @var array */
    private $prePersistInfo;

    /** @var array */
    private $preUpdateInfo;

    /** @var array[] */
    private $metadata = [];

    const GENERATE_FOR = 'entity';

    const USE_TEMPLATE_VAR = 'use';
    const PROPERTIES_TEMPLATE_VAR = 'properties';
    const CONSTRUCTOR_TEMPLATE_VAR = 'constructor';
    const METHODS_TEMPLATE_VAR = 'methods';
    const PRE_PERSIST_TEMPLATE_VAR = 'prePersist';
    const PRE_UPDATE_TEMPLATE_VAR = 'preUpdate';

    public function generateBySection(
        SectionInterface $section
    ): Writable {

        $this->sectionConfig = $section->getConfig();

        // Make sure you start anew for every template
        $this->initializeTemplates();

        $fields = $this->fieldManager->readByHandles($this->sectionConfig->getFields());

        usort($fields, function(FieldInterface $a, FieldInterface $b) {
            return $a->getHandle() <=> $b->getHandle();
        });

        $this->metadata = [];
        $this->generateElements($fields);
        $this->orderPrePersist();
        $this->orderPreUpdate();

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

            // First see if this field is to be ignored by this generator
            try {
                $generatorConfig = $field->getConfig()->getGeneratorConfig()->toArray();
                if (!empty($generatorConfig[self::GENERATE_FOR]['ignore']) ||
                    $generatorConfig[self::GENERATE_FOR]['ignore']) {
                    continue;
                }
            } catch (\Exception $exception) {}

            $fieldTypeClass = (string)$field->getFieldType()->getFullyQualifiedClassName()->getClassName();
            $fieldConfig = $field->getConfig();
            if ($fieldTypeClass === 'Relationship') {
                $fieldConfigArray = $fieldConfig->toArray();
                $singularPropertyName = $fieldConfigArray['field']['as'] ?? $fieldConfigArray['field']['to'];

                $kind = $fieldConfigArray['field']['kind'];
                if ($kind === 'one-to-many' || $kind === 'many-to-many') {
                    $plural = true;
                    $propertyName = Inflector::pluralize($singularPropertyName);
                    $setter = 'add' . ucfirst($singularPropertyName);
                } else {
                    $plural = false;
                    $propertyName = $singularPropertyName;
                    $setter = 'set' . ucfirst($singularPropertyName);
                }

                $getter = 'get' . ucfirst($propertyName);

                $targetClass = $this->getNamespace() . '\\' . ucfirst($fieldConfigArray['field']['to']);

                $relationship = [
                    'class' => $targetClass,
                    'plural' => $plural,
                    'kind' => $kind,
                    'owner' => $fieldConfigArray['field']['owner']
                ];
            } else {
                $propertyName = (string)$fieldConfig->getPropertyName();
                $methodName = (string)$fieldConfig->getMethodName();
                $getter = "get$methodName";
                $setter = "set$methodName";
                $relationship = null;
            }

            $this->metadata[$propertyName] = [
                'handle' => (string)$field->getHandle(),
                'type' => (string)$field->getFieldType()->getType(),
                'getter' => $getter,
                'setter' => $setter,
                'relationship' => $relationship
            ];

            $parsed = $this->getFieldTypeGeneratorConfig($field, self::GENERATE_FOR);

            /**
             * @var string $item
             * @var \Tardigrades\FieldType\Generator\GeneratorInterface $generator
             */
            foreach ($parsed[self::GENERATE_FOR] as $item => $generator) {
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
                            'sexy-field-entity'
                        ));
                        $generated = $generator::generate($field, $templateDir, $options);

                        switch ($item) {
                            case self::PRE_PERSIST_TEMPLATE_VAR:
                                $this->prePersistInfo[] = [
                                    'generated' => $generated,
                                    'config' => $generatorConfig
                                ];
                                break;
                            case self::PRE_UPDATE_TEMPLATE_VAR:
                                $this->preUpdateInfo[] = [
                                    'generated' => $generated,
                                    'config' => $generatorConfig
                                ];
                                break;
                            default:
                                $this->templates[$item][] = $generated;
                                break;
                        }
                    } catch (\Exception $exception) {
                        $this->buildMessages[] = $exception->getMessage();
                    }
                }
            }

            $this->removeDoubles();
        }
    }

    private function orderPrePersist(): void
    {
        foreach ($this->prePersistInfo as &$info) {
            if (!empty($info['config'][self::GENERATE_FOR]) &&
                isset($info['config'][self::GENERATE_FOR]['prePersistOrder']) &&
                is_numeric($info['config'][self::GENERATE_FOR]['prePersistOrder'])
            ) {
                $info['config'][self::GENERATE_FOR]['prePersistOrder'] =
                    (int) $info['config'][self::GENERATE_FOR]['prePersistOrder'];
            } else {
                $info['config'][self::GENERATE_FOR]['prePersistOrder'] = 999999999;
            }
        }

        usort($this->prePersistInfo, function($a, $b) {
            return
                $a['config'][self::GENERATE_FOR]['prePersistOrder'] <=>
                $b['config'][self::GENERATE_FOR]['prePersistOrder'];
        });

        foreach ($this->prePersistInfo as $info) {
            $this->templates[self::PRE_PERSIST_TEMPLATE_VAR][] = $info['generated'];
        }
    }

    private function orderPreUpdate(): void
    {
        foreach ($this->preUpdateInfo as &$info) {
            if (!empty($info['config'][self::GENERATE_FOR]) &&
                isset($info['config'][self::GENERATE_FOR]['preUpdateOrder']) &&
                is_numeric($info['config'][self::GENERATE_FOR]['preUpdateOrder'])
            ) {
                $info['config'][self::GENERATE_FOR]['preUpdateOrder'] =
                    (int) $info['config'][self::GENERATE_FOR]['prePersistOrder'];
            } else {
                $info['config'][self::GENERATE_FOR]['preUpdateOrder'] = 999999999;
            }
        }

        usort($this->preUpdateInfo, function($a, $b) {
            return
                $a['config'][self::GENERATE_FOR]['preUpdateOrder'] <=>
                $b['config'][self::GENERATE_FOR]['preUpdateOrder'];
        });

        foreach ($this->preUpdateInfo as $info) {
            $this->templates[self::PRE_UPDATE_TEMPLATE_VAR][] = $info['generated'];
        }
    }

    private function initializeTemplates(): void
    {
        $this->prePersistInfo = [];
        $this->preUpdateInfo = [];

        $this->templates = [
            self::USE_TEMPLATE_VAR => [],
            self::PROPERTIES_TEMPLATE_VAR => [],
            self::CONSTRUCTOR_TEMPLATE_VAR => [],
            self::METHODS_TEMPLATE_VAR => [],
            self::PRE_PERSIST_TEMPLATE_VAR => [],
            self::PRE_UPDATE_TEMPLATE_VAR => []
        ];
    }

    private function removeDoubles()
    {
        foreach ($this->templates as $item => &$templates) {
            $templates = array_unique($templates);
        }
    }

    protected function generateSlugFieldGetMethod(SlugField $slugField): string
    {
        if ((string) $slugField !== 'slug') {
            return <<<EOT
public function getSlug(): Tardigrades\SectionField\ValueObject\Slug
{
    if (\$this->{$slugField} === null) {
        throw new \UnexpectedValueException('\$this->{$slugField} is null, no slug built');
    }
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
    if (\$this->{$defaultField} === null) {
        throw new \UnexpectedValueException('{$defaultField} is null, cannot get default value');
    }
    return \$this->{$defaultField};
}
EOT;
    }

    private function insertRenderedTemplates(string $template): string
    {
        foreach ($this->templates as $templateVariable => $templates) {
            $template = str_replace(
                '{{ ' . $templateVariable . ' }}',
                \implode($templates),
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

    private function getNamespace(): string
    {
        return (string) $this->sectionConfig->getNamespace() . '\\Entity';
    }

    private function insertNamespace(string $template): string
    {
        $template = str_replace(
            '{{ namespace }}',
            $this->getNamespace(),
            $template
        );

        return $template;
    }

    private function insertFieldMetadata(string $template): string
    {
        $content = var_export($this->metadata, true);

        // Modern array syntax
        $content = str_replace('array (', '[', $content);
        $content = str_replace(')', ']', $content);

        // Better indentation
        $content = preg_replace('/=> \\n */', '=> ', $content);
        $content = str_replace('  ', '    ', $content);
        $content = str_replace("\n", "\n    ", $content);

        // PSR2-conforming null constant
        $content = str_replace('=> NULL', '=> null', $content);

        return str_replace(
            '{{ metadata }}',
            "    const FIELDS = $content;\n",
            $template
        );
    }

    private function insertValidationMetadata(string $template): string
    {
        $generatorConfig = $this->sectionConfig->getGeneratorConfig()->toArray();
        $metadata = '';

        if (is_array($generatorConfig['entity'])) {
            foreach ($generatorConfig['entity'] as $handle => $options) {
                $field = $this->fieldManager->readByHandle(Handle::fromString($handle));
                $templateDirectory = $this->getFieldTypeTemplateDirectory(
                    $field,
                    'sexy-field-' . self::GENERATE_FOR
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
        $template = PhpFormatter::format($template);
        $template = $this->insertFieldMetadata($template);

        return Template::create($template);
    }
}
