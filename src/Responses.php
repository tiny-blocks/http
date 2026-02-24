<?php

declare(strict_types=1);

namespace TinyBlocks\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Define standard HTTP response methods.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
 */
interface Responses
{
    /**
     * Creates a response with the specified status code, body, and headers.
     *
     * @param Code $code The HTTP status code for the response.
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated response with the specified status code, body, and headers.
     */
    public static function from(Code $code, mixed $body, Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 200 OK status.
     *
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 200 OK response.
     */
    public static function ok(mixed $body, Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 201 Created status.
     *
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 201 Created response.
     */
    public static function created(mixed $body, Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 202 Accepted status.
     *
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 202 Accepted response.
     */
    public static function accepted(mixed $body, Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 204 No Content status.
     *
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 204 No Content response.
     */
    public static function noContent(Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 400 Bad Request status.
     *
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 400 Bad Request response.
     */
    public static function badRequest(mixed $body, Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 401 Unauthorized status.
     *
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 401 Unauthorized response.
     */
    public static function unauthorized(mixed $body, Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 403 Forbidden status.
     *
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 403 Forbidden response.
     */
    public static function forbidden(mixed $body, Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 404 Not Found status.
     *
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 404 Not Found response.
     */
    public static function notFound(mixed $body, Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 409 Conflict status.
     *
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 409 Conflict response.
     */
    public static function conflict(mixed $body, Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 422 Unprocessable Entity status.
     *
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 422 Unprocessable Entity response.
     */
    public static function unprocessableEntity(mixed $body, Headers ...$headers): ResponseInterface;

    /**
     * Creates a response with a 500 Internal Server Error status.
     *
     * @param mixed $body The body of the response.
     * @param Headers ...$headers Optional additional headers for the response.
     * @return ResponseInterface The generated 500 Internal Server Error response.
     */
    public static function internalServerError(mixed $body, Headers ...$headers): ResponseInterface;
}
