<?php

namespace TinyBlocks\Http\Internal;

interface Header
{
    public function key(): string;

    public function value(): string;
}
