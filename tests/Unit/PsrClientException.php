<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

final class PsrClientException extends RuntimeException implements ClientExceptionInterface
{
}
