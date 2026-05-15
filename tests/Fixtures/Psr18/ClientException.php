<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Fixtures\Psr18;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

final class ClientException extends RuntimeException implements ClientExceptionInterface
{
}
