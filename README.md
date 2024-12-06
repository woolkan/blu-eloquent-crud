# Blu Generator

This package generates Eloquent models and related service classes (CRUD) based on your MySQL database schema.

## Requirements
- PHP >= 8.0
- Composer
- MySQL database

## Installation
After publishing on Packagist, you will be able to install this package with:
```bash
composer require blu/eloquent-crud-generator
```

## Usage
Create a PHP file (e.g., `generate.php`) with the following content:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Blu\Generator\Generator;
use Blu\Generator\Exception\ConfigurationException;
use Blu\Generator\Exception\GenerationException;

$generator = new Generator();
$generator->setDBConfiguration('localhost', 'dynamo', 'user', 'user_pass', ['charset' => 'utf8mb4'])
    ->setOutputDirectory(__DIR__ . '/output')
    ->setNamespace('Blu\\Generated');

try {
    $generator->generate();
    echo "Code generation completed successfully.\n";
} catch (ConfigurationException | GenerationException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

Then, run the script from the terminal:
```bash
php generate.php
```
