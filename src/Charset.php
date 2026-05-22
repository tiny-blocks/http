<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * Character encoding declared in an HTTP Content-Type header.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Type
 */
enum Charset: string
{
    case BIG5 = 'big5';
    case ASCII = 'ascii';
    case UTF_8 = 'utf-8';
    case EUC_KR = 'euc-kr';
    case GB2312 = 'gb2312';
    case KOI8_R = 'koi8-r';
    case UTF_16 = 'utf-16';
    case SHIFT_JIS = 'shift_jis';
    case ISO_8859_1 = 'iso-8859-1';
    case WINDOWS_1252 = 'windows-1252';

    /**
     * Returns the Charset as a Content-Type charset parameter.
     *
     * @return string The header fragment in the form <code>charset={value}</code>.
     */
    public function toString(): string
    {
        $template = 'charset=%s';

        return sprintf($template, $this->value);
    }
}
