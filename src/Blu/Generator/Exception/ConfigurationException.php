<?php

declare(strict_types=1);

namespace Blu\Generator\Exception;

use RuntimeException;

class ConfigurationException extends RuntimeException
{
    /**
     * ConfigurationException should be thrown when required configuration parameters are missing
     * or invalid, providing a clear message to the developer.
     */
}
