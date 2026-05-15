<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

final readonly class ContentType implements Headerable
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
        $value = $this->charset
            ? sprintf('%s; %s', $this->mimeType->value, $this->charset->toString())
            : $this->mimeType->value;

        return ['Content-Type' => [$value]];
    }
}
