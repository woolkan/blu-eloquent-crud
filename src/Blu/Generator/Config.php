<?php

declare(strict_types=1);

namespace Blu\Generator;

use Blu\Generator\Exception\ConfigurationException;

class Config
{
    /** @var string|null */
    private ?string $dbHost = null;

    /** @var string|null */
    private ?string $dbName = null;

    /** @var string|null */
    private ?string $dbUser = null;

    /** @var string|null */
    private ?string $dbPassword = null;

    /** @var array */
    private array $dbOptions = [];

    /** @var string|null */
    private ?string $outputDirectory = null;

    /** @var string|null */
    private ?string $namespace = null;

    /**
     * Set the database configuration parameters.
     *
     * @param string $host
     * @param string $dbname
     * @param string $user
     * @param string $password
     * @param array  $options
     * @return self
     */
    public function setDBConfiguration(
        string $host,
        string $dbname,
        string $user,
        string $password,
        array $options = []
    ): self {
        $this->dbHost = $host;
        $this->dbName = $dbname;
        $this->dbUser = $user;
        $this->dbPassword = $password;
        $this->dbOptions = $options;
        return $this;
    }

    /**
     * Set the output directory where the generated files will be stored.
     *
     * @param string $directory
     * @return self
     */
    public function setOutputDirectory(string $directory): self
    {
        $this->outputDirectory = rtrim($directory, DIRECTORY_SEPARATOR);
        return $this;
    }

    /**
     * Set the PHP namespace that will be applied to the generated classes.
     *
     * @param string $namespace
     * @return self
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = trim($namespace, '\\');
        return $this;
    }

    /**
     * Validate the configuration and throw an exception if something is missing or invalid.
     *
     * @throws ConfigurationException
     */
    public function validate(): void
    {
        if ($this->dbHost === null ||
            $this->dbName === null ||
            $this->dbUser === null ||
            $this->dbPassword === null) {
            throw new ConfigurationException('Database configuration is incomplete.');
        }

        if ($this->outputDirectory === null) {
            throw new ConfigurationException('Output directory is not set.');
        }

        if ($this->namespace === null) {
            throw new ConfigurationException('Namespace is not set.');
        }
    }

    /**
     * Getters for internal usage (not strictly required but may be useful).
     */
    public function getDBHost(): string
    {
        return $this->dbHost ?? '';
    }

    public function getDBName(): string
    {
        return $this->dbName ?? '';
    }

    public function getDBUser(): string
    {
        return $this->dbUser ?? '';
    }

    public function getDBPassword(): string
    {
        return $this->dbPassword ?? '';
    }

    public function getDBOptions(): array
    {
        return $this->dbOptions;
    }

    public function getOutputDirectory(): string
    {
        return $this->outputDirectory ?? '';
    }

    public function getNamespace(): string
    {
        return $this->namespace ?? '';
    }
}
