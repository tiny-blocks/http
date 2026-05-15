<?php

declare(strict_types=1);

namespace TinyBlocks\Http\Internal\Client;

final class Cursor
{
    private int $position = 0;

    public function advance(): int
    {
        $current = $this->position;
        $this->position++;

        return $current;
    }
}
