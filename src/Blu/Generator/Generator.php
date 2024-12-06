<?php

declare(strict_types=1);

namespace Blu\Generator;

use Blu\Generator\Exception\ConfigurationException;
use Blu\Generator\Exception\GenerationException;

class Generator
{
    /** @var Config */
    private Config $config;

    public function __construct()
    {
        // Initialize the config object.
        $this->config = new Config();
    }

    public function setDBConfiguration(
        string $host,
        string $dbname,
        string $user,
        string $password,
        array $options = []
    ): self {
        $this->config->setDBConfiguration($host, $dbname, $user, $password, $options);
        return $this;
    }

    public function setOutputDirectory(string $directory): self
    {
        $this->config->setOutputDirectory($directory);
        return $this;
    }

    public function setNamespace(string $namespace): self
    {
        $this->config->setNamespace($namespace);
        return $this;
    }

    /**
     * @throws ConfigurationException|GenerationException
     */
    public function generate(): void
    {
        // Validate configuration
        $this->config->validate();

        // 1. Get schema info
        $schemaService = new SchemaIntrospectionService($this->config);
        $schema = $schemaService->getSchema();

        // 2. Generate models
        $modelGen = new ModelClassGenerator($this->config, $schema);
        $modelGen->generateModels();

        // 3. Generate services
        $serviceGen = new ServiceClassGenerator($this->config, $schema);
        $serviceGen->generateServices();

        // W przyszłości można dodać kolejne kroki, np. generowanie innych klas.
    }
}
