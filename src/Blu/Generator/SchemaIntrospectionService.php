<?php

declare(strict_types=1);

namespace Blu\Generator;

use PDO;
use PDOException;
use Blu\Generator\Exception\ConfigurationException;
use Blu\Generator\Exception\GenerationException;

class SchemaIntrospectionService
{
    private Config $config;
    private PDO $pdo;

    /**
     * @throws ConfigurationException
     * @throws GenerationException
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->connect();
    }

    /**
     * Connect to the database using PDO.
     *
     * @throws ConfigurationException|GenerationException
     */
    private function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $this->config->getDBHost(),
            $this->config->getDBName(),
            $this->config->getDBOptions()['charset'] ?? 'utf8mb4'
        );

        try {
            $this->pdo = new PDO($dsn, $this->config->getDBUser(), $this->config->getDBPassword(), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } catch (PDOException $e) {
            throw new GenerationException('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve schema information for all tables in the database.
     *
     * @return array
     * @throws GenerationException
     */
    public function getSchema(): array
    {
        $schema = [
            'tables' => []
        ];

        $dbName = $this->config->getDBName();

        // Get tables
        $tablesQuery = $this->pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbName}'");
        $tables = $tablesQuery->fetchAll(PDO::FETCH_COLUMN);

        if (!$tables) {
            return $schema;
        }

        // For each table, get columns and primary key info
        foreach ($tables as $table) {
            $columnsData = $this->pdo->query("
                SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = '{$dbName}' AND TABLE_NAME = '{$table}'
            ")->fetchAll(PDO::FETCH_ASSOC);

            $primaryKey = $this->pdo->query("
                SELECT COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = '{$dbName}'
                AND TABLE_NAME = '{$table}'
                AND CONSTRAINT_NAME = 'PRIMARY'
            ")->fetchColumn();

            $foreignKeys = $this->pdo->query("
                SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = '{$dbName}'
                  AND TABLE_NAME = '{$table}'
                  AND REFERENCED_TABLE_NAME IS NOT NULL
            ")->fetchAll(PDO::FETCH_ASSOC);

            $columns = [];
            foreach ($columnsData as $col) {
                $columns[] = [
                    'name' => $col['COLUMN_NAME'],
                    'type' => $col['DATA_TYPE'],
                    'nullable' => $col['IS_NULLABLE'] === 'YES',
                    'default' => $col['COLUMN_DEFAULT']
                ];
            }

            $schema['tables'][$table] = [
                'columns' => $columns,
                'primaryKey' => $primaryKey ?: 'id',
                'foreignKeys' => array_map(function($fk) {
                    return [
                        'column' => $fk['COLUMN_NAME'],
                        'referenced_table' => $fk['REFERENCED_TABLE_NAME'],
                        'referenced_column' => $fk['REFERENCED_COLUMN_NAME']
                    ];
                }, $foreignKeys)
            ];
        }

        // Derive relationships
        $this->deriveRelationships($schema);

        return $schema;
    }

    /**
     * Derive relationships from foreign keys.
     *
     * This will add 'relationships' keys to each table definition
     * indicating hasMany, belongsTo, and belongsToMany where appropriate.
     */
    private function deriveRelationships(array &$schema): void
    {
        // Initialize relationships arrays
        foreach ($schema['tables'] as $table => $tableData) {
            $schema['tables'][$table]['relationships'] = [
                'belongsTo' => [],
                'hasMany' => [],
                'belongsToMany' => []
            ];
        }

        // Simple logic:
        // For each foreign key in table A pointing to table B:
        // A: belongsTo(B)
        // B: hasMany(A)
        foreach ($schema['tables'] as $table => &$tableData) {
            foreach ($tableData['foreignKeys'] as $fk) {
                $referencedTable = $fk['referenced_table'];
                $tableData['relationships']['belongsTo'][] = [
                    'target_table' => $referencedTable,
                    'foreign_key' => $fk['column'],
                    'owner_key' => $fk['referenced_column']
                ];

                // Add hasMany to the referenced table
                $schema['tables'][$referencedTable]['relationships']['hasMany'][] = [
                    'target_table' => $table,
                    'foreign_key' => $fk['column'],
                    'local_key' => $fk['referenced_column']
                ];
            }
        }
    }
}
