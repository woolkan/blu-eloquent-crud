<?php

declare(strict_types=1);

namespace Blu\Generator\Exception;

use RuntimeException;

class GenerationException extends RuntimeException
{
    /**
     * GenerationException should be thrown when an error occurs during the code generation process,
     * such as file write failures or unexpected schema inconsistencies.
     */
}
