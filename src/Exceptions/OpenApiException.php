<?php

namespace Mdwheele\OpenApi\Exceptions;

use Exception;
use Throwable;

class OpenApiException extends Exception
{
    public static function wrapPrevious(string $message, Throwable $throwable)
    {
        return new static($message, $throwable->getCode(), $throwable);
    }
}
