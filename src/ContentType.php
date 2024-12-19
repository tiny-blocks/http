<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * The Content-Type representation header is used to indicate the original media type
 * of the resource (prior to any content encoding applied for sending).
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Type
 */
final readonly class ContentType implements Headers
{
    private function __construct(private MimeType $mimeType, private ?Charset $charset)
    {
    }

    public static function textHtml(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::TEXT_HTML, charset: $charset);
    }

    public static function textPlain(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::TEXT_PLAIN, charset: $charset);
    }

    public static function applicationJson(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::APPLICATION_JSON, charset: $charset);
    }

    public static function applicationPdf(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::APPLICATION_PDF, charset: $charset);
    }

    public static function applicationOctetStream(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::APPLICATION_OCTET_STREAM, charset: $charset);
    }

    public static function applicationFormUrlencoded(?Charset $charset = null): ContentType
    {
        return new ContentType(mimeType: MimeType::APPLICATION_FORM_URLENCODED, charset: $charset);
    }

    public function toArray(): array
    {
        return [
            'Content-Type' => $this->charset
                ? sprintf('%s; %s', $this->mimeType->value, $this->charset->toString())
                : $this->mimeType->value
        ];
    }
}
