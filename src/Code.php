<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

/**
 * HTTP response status codes indicate whether a specific HTTP request has been successfully completed.
 * Responses are grouped in five classes:
 *
 * Informational (100 – 199)
 * Successful (200 – 299)
 * Redirection (300 – 399)
 * Client error (400 – 499)
 * Server error (500 – 599)
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status#information_responses
 */
enum Code: int
{
    # Informational 1xx
    case CONTINUE = 100;
    case SWITCHING_PROTOCOLS = 101;
    case PROCESSING = 102;
    case EARLY_HINTS = 103;

    # Successful 2xx
    case OK = 200;
    case CREATED = 201;
    case ACCEPTED = 202;
    case NON_AUTHORITATIVE_INFORMATION = 203;
    case NO_CONTENT = 204;
    case RESET_CONTENT = 205;
    case PARTIAL_CONTENT = 206;
    case MULTI_STATUS = 207;
    case ALREADY_REPORTED = 208;
    case IM_USED = 226;

    # Redirection 3xx
    case MULTIPLE_CHOICES = 300;
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case SEE_OTHER = 303;
    case NOT_MODIFIED = 304;
    case TEMPORARY_REDIRECT = 307;
    case PERMANENT_REDIRECT = 308;

    # Client error 4xx
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case PAYMENT_REQUIRED = 402;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case NOT_ACCEPTABLE = 406;
    case PROXY_AUTHENTICATION_REQUIRED = 407;
    case REQUEST_TIMEOUT = 408;
    case CONFLICT = 409;
    case GONE = 410;
    case LENGTH_REQUIRED = 411;
    case PRECONDITION_FAILED = 412;
    case PAYLOAD_TOO_LARGE = 413;
    case URI_TOO_LONG = 414;
    case UNSUPPORTED_MEDIA_TYPE = 415;
    case RANGE_NOT_SATISFIABLE = 416;
    case EXPECTATION_FAILED = 417;
    case IM_A_TEAPOT = 418;
    case MISDIRECTED_REQUEST = 421;
    case UNPROCESSABLE_ENTITY = 422;
    case LOCKED = 423;
    case FAILED_DEPENDENCY = 424;
    case TOO_EARLY = 425;
    case UPGRADE_REQUIRED = 426;
    case PRECONDITION_REQUIRED = 428;
    case TOO_MANY_REQUESTS = 429;
    case REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    case UNAVAILABLE_FOR_LEGAL_REASONS = 451;

    # Server error 5xx
    case INTERNAL_SERVER_ERROR = 500;
    case NOT_IMPLEMENTED = 501;
    case BAD_GATEWAY = 502;
    case SERVICE_UNAVAILABLE = 503;
    case GATEWAY_TIMEOUT = 504;
    case HTTP_VERSION_NOT_SUPPORTED = 505;
    case VARIANT_ALSO_NEGOTIATES = 506;
    case INSUFFICIENT_STORAGE = 507;
    case LOOP_DETECTED = 508;
    case NOT_EXTENDED = 510;
    case NETWORK_AUTHENTICATION_REQUIRED = 511;

    /**
     * Returns the HTTP status message associated with the enum's code.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Messages
     * @return string The formatted message with the status code and name.
     */
    public function message(): string
    {
        $subject = match ($this) {
            self::OK          => $this->name,
            self::IM_USED     => 'IM Used',
            self::IM_A_TEAPOT => "I'm a teapot",
            default           => mb_convert_case($this->name, MB_CASE_TITLE)
        };

        return str_replace('_', ' ', $subject);
    }

    /**
     * Determines if the given code is a valid HTTP status code represented by the enum.
     *
     * @param int $code The HTTP status code to check.
     * @return bool True if the code exists in the enum, otherwise false.
     */
    public static function isValidCode(int $code): bool
    {
        $mapper = fn(Code $code): int => $code->value;

        return in_array($code, array_map($mapper, self::cases()));
    }

    /**
     * Determines if the given code is in the error range (4xx or 5xx).
     *
     * @param int $code The HTTP status code to check.
     * @return bool True if the code is in the error range (4xx or 5xx), otherwise false.
     */
    public static function isErrorCode(int $code): bool
    {
        return $code >= self::BAD_REQUEST->value && $code <= self::NETWORK_AUTHENTICATION_REQUIRED->value;
    }

    /**
     * Determines if the given code is in the success range (2xx).
     *
     * @param int $code The HTTP status code to check.
     * @return bool True if the code is in the success range (2xx), otherwise false.
     */
    public static function isSuccessCode(int $code): bool
    {
        return $code >= self::OK->value && $code <= self::IM_USED->value;
    }
}
