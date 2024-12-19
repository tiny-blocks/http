<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

enum Charset: string
{
    case BIG5 = 'big5';
    case ASCII = 'ascii';
    case UTF_8 = 'utf-8';
    case UTF_16 = 'utf-16';
    case EUC_KR = 'euc-kr';
    case GB2312 = 'gb2312';
    case KOI8_R = 'koi8-r';
    case SHIFT_JIS = 'shift_jis';
    case ISO_8859_1 = 'iso-8859-1';
    case WINDOWS_1252 = 'windows-1252';

    public function toString(): string
    {
        return sprintf('charset=%s', $this->value);
    }
}
