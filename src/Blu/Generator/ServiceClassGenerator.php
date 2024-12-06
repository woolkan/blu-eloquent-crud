<?php

declare(strict_types=1);

namespace Blu\Generator;

use Blu\Generator\Exception\GenerationException;

class ServiceClassGenerator
{
    private Config $config;
    private array $schema;

    public function __construct(Config $config, array $schema)
    {
        $this->config = $config;
        $this->schema = $schema;
    }

    /**
     * Generate service classes based on the schema.
     *
     * @throws GenerationException
     */
    public function generateServices(): void
    {
        $servicesDir = $this->config->getOutputDirectory() . DIRECTORY_SEPARATOR . 'Services';
        if (!is_dir($servicesDir) && !mkdir($servicesDir, 0777, true)) {
            throw new GenerationException('Could not create services directory: ' . $servicesDir);
        }

        $stubPath = __DIR__ . '/stubs/service.stub';
        if (!file_exists($stubPath)) {
            throw new GenerationException('Service stub not found at: ' . $stubPath);
        }

        $stub = file_get_contents($stubPath);
        if ($stub === false) {
            throw new GenerationException('Could not read service stub.');
        }

        $modelNamespace = $this->config->getNamespace() . '\\Models';
        $serviceNamespace = $this->config->getNamespace() . '\\Services';

        foreach ($this->schema['tables'] as $table => $tableData) {
            $className = $this->convertToClassName($table) . 'Service';
            $modelClass = $this->convertToClassName($table);
            $relationshipsArray = $this->deriveRelationshipNames($tableData['relationships']);
            $relatedCreateLogic = $this->generateRelatedCreateLogic($tableData['relationships']);

            $serviceCode = str_replace(
                [
                    '{{namespace}}',
                    '{{modelNamespace}}',
                    '{{className}}',
                    '{{modelClass}}',
                    '{{relationshipsArray}}',
                    '{{relatedCreateLogic}}',
                    '{{relatedUpdateLogic}}',
                    '{{relatedDeleteLogic}}'
                ],
                [
                    $serviceNamespace,
                    $modelNamespace,
                    $className,
                    $modelClass,
                    implode(',', array_map(fn($r) => "'{$r}'", $relationshipsArray)),
                    $relatedCreateLogic,
                    $this->generateRelatedUpdateLogic($tableData['relationships']),
                    $this->generateRelatedDeleteLogic($tableData['relationships'])
                ],
                $stub
            );

            $serviceFile = $servicesDir . DIRECTORY_SEPARATOR . $className . '.php';
            if (file_put_contents($serviceFile, $serviceCode) === false) {
                throw new GenerationException('Could not write service file: ' . $serviceFile);
            }
        }
    }

    private function convertToClassName(string $table): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', rtrim($table, 's'))));
    }

    private function deriveRelationshipNames(array $relationships): array
    {
        $rels = [];
        foreach ($relationships['belongsTo'] as $rel) {
            $rels[] = lcfirst($this->convertToClassName($rel['target_table']));
        }
        foreach ($relationships['hasMany'] as $rel) {
            $rels[] = lcfirst($this->convertToClassName($rel['target_table'])) . 's';
        }
        return $rels;
    }

    private function generateRelatedCreateLogic(array $relationships): string
    {
        // Simple approach: for hasMany relationships, if data is provided (e.g. `product_images`),
        // we call $model->productImages()->createMany(...).
        // For belongsTo - typically we set foreign key in main model, so no extra logic needed here.

        $code = '';
        foreach ($relationships['hasMany'] as $rel) {
            $methodName = lcfirst($this->convertToClassName($rel['target_table'])) . 's';
            $code .= <<<PHP
if (isset(\$data['{$methodName}'])) {
    \$model->{$methodName}()->createMany(\$data['{$methodName}']);
}

PHP;
        }

        return $code;
    }

    private function generateRelatedUpdateLogic(array $relationships): string
    {
        // For simplicity, let's just recreate hasMany on update:
        // Delete old and create new. In real scenario, we might upsert or do more sophisticated logic.
        $code = '';
        foreach ($relationships['hasMany'] as $rel) {
            $methodName = lcfirst($this->convertToClassName($rel['target_table'])) . 's';
            $code .= <<<PHP
if (isset(\$data['{$methodName}'])) {
    \$model->{$methodName}()->delete();
    \$model->{$methodName}()->createMany(\$data['{$methodName}']);
}

PHP;
        }

        return $code;
    }

    private function generateRelatedDeleteLogic(array $relationships): string
    {
        // On delete, if we want cascade delete, we can delete hasMany relationships.
        // This depends on the desired logic. Let's assume we do cascade delete on hasMany.
        $code = '';
        foreach ($relationships['hasMany'] as $rel) {
            $methodName = lcfirst($this->convertToClassName($rel['target_table'])) . 's';
            $code .= <<<PHP
\$model->{$methodName}()->delete();

PHP;
        }

        return $code;
    }
}
