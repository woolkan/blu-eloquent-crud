<?php

declare(strict_types=1);

namespace Blu\Generator;

use Blu\Generator\Exception\GenerationException;

class ModelClassGenerator
{
    private Config $config;
    private array $schema;

    public function __construct(Config $config, array $schema)
    {
        $this->config = $config;
        $this->schema = $schema;
    }

    /**
     * Generate model classes from the schema.
     *
     * @throws GenerationException
     */
    public function generateModels(): void
    {
        $modelsDir = $this->config->getOutputDirectory() . DIRECTORY_SEPARATOR . 'Models';
        if (!is_dir($modelsDir) && !mkdir($modelsDir, 0777, true)) {
            throw new GenerationException('Could not create models directory: ' . $modelsDir);
        }

        $stubPath = __DIR__ . '/stubs/model.stub';
        if (!file_exists($stubPath)) {
            throw new GenerationException('Model stub not found at: ' . $stubPath);
        }

        $stub = file_get_contents($stubPath);
        if ($stub === false) {
            throw new GenerationException('Could not read model stub.');
        }

        $namespace = $this->config->getNamespace() . '\\Models';

        foreach ($this->schema['tables'] as $table => $tableData) {
            $className = $this->convertToClassName($table);
            $relationshipsCode = $this->generateRelationshipsCode($tableData['relationships'], $namespace);

            $modelCode = str_replace(
                ['{{namespace}}', '{{className}}', '{{tableName}}', '{{relationships}}'],
                [$namespace, $className, $table, $relationshipsCode],
                $stub
            );

            $modelFile = $modelsDir . DIRECTORY_SEPARATOR . $className . '.php';
            if (file_put_contents($modelFile, $modelCode) === false) {
                throw new GenerationException('Could not write model file: ' . $modelFile);
            }
        }
    }

    private function convertToClassName(string $table): string
    {
        // Simple conversion: singularize and capitalize.
        // For simplicity, assume table names are plural: 'products' -> 'Product'
        // A real implementation could be more sophisticated.
        return str_replace(' ', '', ucwords(str_replace('_', ' ', rtrim($table, 's'))));
    }

    private function generateRelationshipsCode(array $relationships, string $namespace): string
    {
        $code = '';

        // belongsTo
        foreach ($relationships['belongsTo'] as $rel) {
            $targetClass = $this->convertToClassName($rel['target_table']);
            $methodName = lcfirst($targetClass);
            $code .= <<<PHP

    public function {$methodName}()
    {
        return \$this->belongsTo({$namespace}\\{$targetClass}::class, '{$rel['foreign_key']}', '{$rel['owner_key']}');
    }

PHP;
        }

        // hasMany
        foreach ($relationships['hasMany'] as $rel) {
            $targetClass = $this->convertToClassName($rel['target_table']);
            // pluralize method name
            $methodName = lcfirst($targetClass) . 's';
            $code .= <<<PHP

    public function {$methodName}()
    {
        return \$this->hasMany({$namespace}\\{$targetClass}::class, '{$rel['foreign_key']}', '{$rel['local_key']}');
    }

PHP;
        }

        // belongsToMany - not derived in our simple logic, left empty for now

        return $code;
    }
}
