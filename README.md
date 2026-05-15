# Http

[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [Upgrading from 1.x to 2.x](#upgrading-from-1x-to-2x)
* [How to use](#how-to-use)
    * [Server](#server)
        * [Request](#request)
        * [Response](#response)
        * [Status codes](#status-codes)
    * [Client](#client)
        * [Configuring Http](#configuring-http)
        * [Making a request](#making-a-request)
        * [Reading the response](#reading-the-response)
        * [Query parameters](#query-parameters)
        * [Custom headers and content type](#custom-headers-and-content-type)
        * [Error handling](#error-handling)
        * [Configuring timeouts](#configuring-timeouts)
* [License](#license)
* [Contributing](#contributing)

<div id='overview'></div>

## Overview

Implements [PSR-7](https://www.php-fig.org/psr/psr-7) and [PSR-15](https://www.php-fig.org/psr/psr-15) HTTP primitives
for PHP, covering requests, responses, streams, cookies, headers, methods, status codes, and cache-control directives.
Ships with a fluent response builder that maps common outcomes to the correct HTTP semantics out of the box.
Interoperable with Slim, Laminas, and any PSR-compliant framework.

In addition, the library provides a thin [PSR-18](https://www.php-fig.org/psr/psr-18) outbound HTTP client façade
(`Http`) that composes any PSR-18 client with PSR-17 factories, handles URL construction, serializes JSON bodies,
and maps transport exceptions to typed exceptions — without bundling any HTTP client implementation.

The public API is organized by role:

| Namespace                 | Purpose                                                                                       |
|---------------------------|-----------------------------------------------------------------------------------------------|
| `TinyBlocks\Http\`        | Shared primitives: `Method`, `Code`, `Headers`, `ContentType`, `CacheControl`, `Cookie`, etc. |
| `TinyBlocks\Http\Server\` | PSR-7 / PSR-15 server-side request decoding and response building                             |
| `TinyBlocks\Http\Client\` | Outbound HTTP: `Request`, `Response`, and typed exceptions                                    |

<div id='installation'></div>

## Installation

```bash
composer require tiny-blocks/http
```

To make outbound HTTP requests, also require a [PSR-18](https://www.php-fig.org/psr/psr-18) client and
[PSR-17](https://www.php-fig.org/psr/psr-17) factories. For example, using Guzzle and Nyholm:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
```

<div id='upgrading-from-1x-to-2x'></div>

## Upgrading from 1.x to 2.x

Version 2.x moves the server-side `Request` and `Response` classes into the `Server\` sub-namespace. The shared
primitives (`Method`, `Code`, `ContentType`, `CacheControl`, `Cookie`, `Headers`, etc.) stay at the root and are
unchanged.

`Response::from(...)` and `Responses::from(...)` now take `$body` before `$code`. Update every call site:

```bash
# Before
Response::from(code: Code::OK, body: $payload)

# After
Response::from(body: $payload, code: Code::OK)
```

Run the following find/replace commands in your project:

```bash
# Request
grep -rn 'TinyBlocks\\Http\\Request' .
sed -i 's/use TinyBlocks\\Http\\Request;/use TinyBlocks\\Http\\Server\\Request;/g' $(grep -rl 'use TinyBlocks\\Http\\Request;' .)

# Response
grep -rn 'TinyBlocks\\Http\\Response' .
sed -i 's/use TinyBlocks\\Http\\Response;/use TinyBlocks\\Http\\Server\\Response;/g' $(grep -rl 'use TinyBlocks\\Http\\Response;' .)

# Responses interface
grep -rn 'TinyBlocks\\Http\\Responses' .
sed -i 's/use TinyBlocks\\Http\\Responses;/use TinyBlocks\\Http\\Server\\Responses;/g' $(grep -rl 'use TinyBlocks\\Http\\Responses;' .)
```

<div id='how-to-use'></div>

## How to use

<div id='server'></div>

### Server

<div id='request'></div>

#### Request

##### Decoding a request

The library provides a small public API to decode a PSR-7 `ServerRequestInterface` into a typed structure, allowing you
to access route parameters and JSON body fields consistently.

- **Decode a request**: Use `Request::from(...)` to wrap the PSR-7 request and call `decode()`. The decoded object
  exposes `uri` and `body`.

  ```php
  use Psr\Http\Message\ServerRequestInterface;
  use TinyBlocks\Http\Server\Request;

  /** @var ServerRequestInterface $psrRequest */
  $decoded = Request::from(request: $psrRequest)->decode();

  $name = $decoded->body()->get(key: 'name')->toString();
  $payload = $decoded->body()->toArray();

  $id = $decoded->uri()->route()->get(key: 'id')->toInteger();
  ```

- **Access the HTTP method**: Use `method()` directly on the `Request` to retrieve the HTTP verb as a typed `Method`
  enum.

  ```php
  use Psr\Http\Message\ServerRequestInterface;
  use TinyBlocks\Http\Server\Request;

  /** @var ServerRequestInterface $psrRequest */
  $request = Request::from(request: $psrRequest);

  $method = $request->method();        # Method::POST
  $method->value;                      # "POST"
  ```

- **Access the full URI**: Use `toString()` on the decoded `uri()` to retrieve the complete request URI as a string.

  ```php
  use TinyBlocks\Http\Server\Request;

  $decoded = Request::from(request: $psrRequest)->decode();

  $fullUri = $decoded->uri()->toString(); # "https://api.example.com/v1/dragons?sort=name"
  ```

- **Access query parameters**: Use `queryParameters()` on the decoded `uri()` to retrieve typed access to query string
  values. Each value is returned as an `Attribute`, providing safe conversions and defaults.

  ```php
  use TinyBlocks\Http\Server\Request;

  $decoded = Request::from(request: $psrRequest)->decode();

  $queryParams = $decoded->uri()->queryParameters()->toArray();                     # ['sort' => 'name', 'limit' => '50']
  $sort = $decoded->uri()->queryParameters()->get(key: 'sort')->toString();         # "name"
  $limit = $decoded->uri()->queryParameters()->get(key: 'limit')->toInteger();      # 50
  $active = $decoded->uri()->queryParameters()->get(key: 'active')->toBoolean();    # default: false
  ```

- **Typed access with defaults**: Each value is returned as an `Attribute`, which provides safe conversions and default
  values when the underlying value is missing or not compatible.

  ```php
  use TinyBlocks\Http\Server\Request;

  $request = Request::from(request: $psrRequest);
  $decoded = $request->decode();

  $method = $request->method();                                                  # Method enum

  $id = $decoded->uri()->route()->get(key: 'id')->toInteger();                   # default: 0
  $uri = $decoded->uri()->toString();                                            # default: ""
  $sort = $decoded->uri()->queryParameters()->get(key: 'sort')->toString();      # default: ""
  $limit = $decoded->uri()->queryParameters()->get(key: 'limit')->toInteger();   # default: 0

  $note = $decoded->body()->get(key: 'note')->toString();                        # default: ""
  $tags = $decoded->body()->get(key: 'tags')->toArray();                         # default: []
  $price = $decoded->body()->get(key: 'price')->toFloat();                       # default: 0.00
  $active = $decoded->body()->get(key: 'active')->toBoolean();                   # default: false
  ```

- **Custom route attribute name**: If your framework stores route params in a different request attribute, specify it
  via `route()`.

  ```php
  use TinyBlocks\Http\Server\Request;

  $decoded = Request::from(request: $psrRequest)->decode();

  $id = $decoded->uri()->route(name: '_route_params')->get(key: 'id')->toInteger();
  ```

##### How route parameters are resolved

The library resolves route parameters from the PSR-7 `ServerRequestInterface` using a **multistep fallback strategy**,
designed to work across different frameworks without importing any framework-specific code.

**Resolution order** (when using the default `route()` or `route(name: '...')`):

1. **Specified attribute lookup** — Reads the attribute from the request using the configured name (default:
   `__route__`).
    - If the value is an **array**, the key is looked up directly.
    - If the value is an **object**, the resolver tries known accessor methods (`getArguments()`,
      `getMatchedParams()`, `getParameters()`, `getParams()`) and then public properties (`arguments`, `params`,
      `vars`, `parameters`).
    - If the value is a **scalar** (e.g., a string), it is returned as-is.

2. **Known attribute scan** (only when using the default `__route__` name) — Scans all commonly used attribute keys
   across frameworks: `__route__`, `_route_params`, `route`, `routing`, `routeResult`, `routeInfo`.

3. **Direct attribute fallback** — As a last resort, tries `$request->getAttribute($key)` directly, which supports
   frameworks like Laravel that store route params as individual request attributes.

4. **Safe default** — If nothing is found, returns `Attribute::from(null)`, which provides safe conversions:
   `toInteger()` → `0`, `toString()` → `""`, `toFloat()` → `0.00`, `toBoolean()` → `false`, `toArray()` → `[]`.

**Supported frameworks and attribute formats:**

| Framework               | Attribute Key   | Format                                        |
|-------------------------|-----------------|-----------------------------------------------|
| **Slim 4**              | `__route__`     | Object with `getArguments()`                  |
| **Mezzio / Expressive** | `routeResult`   | Object with `getMatchedParams()`              |
| **Symfony**             | `_route_params` | `array<string, mixed>`                        |
| **Laravel**             | *(direct)*      | `getAttribute('id')` directly on the request  |
| **FastRoute (generic)** | `routeInfo`     | Array with route parameters                   |
| **Manual injection**    | Any custom key  | `$request->withAttribute('__route__', [...])` |

##### Manually injecting route parameters

If your framework or middleware does not automatically populate route attributes, inject them manually using
PSR-7's `withAttribute()`:

```php
use TinyBlocks\Http\Server\Request;

$psrRequest = $psrRequest->withAttribute('__route__', [
    'id'    => '42',
    'email' => 'user@example.com'
]);

$decoded = Request::from(request: $psrRequest)->decode();
$id = $decoded->uri()->route()->get(key: 'id')->toInteger(); # 42

$psrRequest = $psrRequest->withAttribute('my_params', ['slug' => 'hello-world']);
$slug = Request::from(request: $psrRequest)
    ->decode()
    ->uri()
    ->route(name: 'my_params')
    ->get(key: 'slug')
    ->toString(); # "hello-world"
```

<div id='response'></div>

#### Response

##### Creating a response

The library provides an easy and flexible way to create HTTP responses, allowing you to specify the status code,
headers, and body. You can use the `Response` class to generate responses, and the result will always be a
`ResponseInterface` from the PSR, ensuring compatibility with any framework that adheres
to the [PSR-7](https://www.php-fig.org/psr/psr-7) standard.

- **Creating a response with a body**: To create an HTTP response, you can pass any type of data as the body.
  Optionally, you can also specify one or more headers. If no headers are provided, the response will default to
  `application/json` content type.

  ```php
  use TinyBlocks\Http\Server\Response;

  Response::ok(body: ['message' => 'Resource created successfully.']);
  ```

- **Creating a response with a body and custom headers**: You can also add custom headers to the response. For instance,
  if you want to specify a custom content type or any other header, you can pass the headers as additional arguments.

  ```php
  use TinyBlocks\Http\CacheControl;
  use TinyBlocks\Http\ContentType;
  use TinyBlocks\Http\ResponseCacheDirectives;
  use TinyBlocks\Http\Server\Response;

  $contentType = ContentType::textPlain();

  $cacheControl = CacheControl::fromResponseDirectives(
      maxAge: ResponseCacheDirectives::maxAge(maxAgeInWholeSeconds: 10000),
      staleIfError: ResponseCacheDirectives::staleIfError()
  );

  Response::ok('This is a plain text response', $contentType, $cacheControl)
      ->withHeader(name: 'X-ID', value: 100)
      ->withHeader(name: 'X-NAME', value: 'Xpto');
  ```

##### Setting cookies

The library models the `Set-Cookie` HTTP response header through the `Cookie` value object, covering the full
[RFC 6265](https://datatracker.ietf.org/doc/html/rfc6265) attribute set plus modern additions such as `SameSite` and
`Partitioned`. Instances are immutable and fluent — every builder call returns a new `Cookie`. Like `ContentType` and
`CacheControl`, `Cookie` implements `Headers`, so it composes naturally with any `Response` factory via varargs.

- **Setting a session cookie**: Build a cookie with the required security flags and attach it to a response.

  ```php
  use TinyBlocks\Http\Cookie;
  use TinyBlocks\Http\SameSite;
  use TinyBlocks\Http\Server\Response;

  $cookie = Cookie::create(name: 'refresh_token', value: $opaqueToken)
      ->httpOnly()
      ->secure()
      ->withSameSite(sameSite: SameSite::STRICT)
      ->withPath(path: '/v1/sessions')
      ->withMaxAge(seconds: 604800);

  Response::ok(body: ['ok' => true], $cookie);
  ```

- **Setting multiple cookies**: Pass each `Cookie` as an additional header argument. The response emits one
  `Set-Cookie` header per cookie, preserving all of them.

  ```php
  use TinyBlocks\Http\Cookie;
  use TinyBlocks\Http\SameSite;
  use TinyBlocks\Http\Server\Response;

  $accessCookie = Cookie::create(name: 'access_token', value: $accessToken)
      ->httpOnly()
      ->secure()
      ->withPath(path: '/');

  $refreshCookie = Cookie::create(name: 'refresh_token', value: $refreshToken)
      ->httpOnly()
      ->secure()
      ->withSameSite(sameSite: SameSite::STRICT)
      ->withPath(path: '/v1/sessions')
      ->withMaxAge(seconds: 604800);

  Response::ok(body: ['ok' => true], $accessCookie, $refreshCookie);
  ```

- **Expiring a cookie**: Use `Cookie::expire()` to instruct the browser to delete a previously set cookie. Chain the
  same `Path` (and `Domain`, if applicable) used when the cookie was issued.

  ```php
  use TinyBlocks\Http\Cookie;
  use TinyBlocks\Http\SameSite;
  use TinyBlocks\Http\Server\Response;

  $expired = Cookie::expire(name: 'refresh_token')
      ->httpOnly()
      ->secure()
      ->withSameSite(sameSite: SameSite::STRICT)
      ->withPath(path: '/v1/sessions');

  Response::noContent($expired);
  ```

- **Using an absolute expiration date**: When an explicit deletion moment is preferable over `Max-Age`, use
  `withExpires()`. `Max-Age` and `Expires` are mutually exclusive — setting both throws
  `ConflictingLifetimeAttributes` when the response is serialized.

  ```php
  use DateTimeImmutable;
  use DateTimeZone;
  use TinyBlocks\Http\Cookie;

  Cookie::create(name: 'preference', value: 'dark-mode')->withExpires(
      expires: new DateTimeImmutable(datetime: '2030-01-15 12:00:00', timezone: new DateTimeZone(timezone: 'UTC'))
  );
  ```

- **Cross-site cookies**: `SameSite::NONE` requires the `Secure` flag — modern browsers reject `SameSite=None`
  cookies sent over insecure connections. The library enforces this invariant at serialization time and throws
  `SameSiteNoneRequiresSecure` when the combination is incomplete.

  ```php
  use TinyBlocks\Http\Cookie;
  use TinyBlocks\Http\SameSite;

  Cookie::create(name: 'embed_session', value: $token)
      ->withSameSite(sameSite: SameSite::NONE)
      ->secure();
  ```

- **Validation at construction time**: Cookie names and values are validated against
  [RFC 6265](https://datatracker.ietf.org/doc/html/rfc6265). Names cannot be empty nor contain control characters,
  whitespace, or token separators. Values cannot contain control characters, whitespace, double quotes, commas,
  semicolons, or backslashes. Encode the value before passing it when it may contain arbitrary text.

  ```php
  use TinyBlocks\Http\Cookie;

  Cookie::create(name: 'user_id', value: (string)$userId);           # valid
  Cookie::create(name: 'payload', value: base64_encode($jsonBody));  # encode arbitrary values first
  ```

<div id='status-codes'></div>

#### Status codes

The library exposes a concrete implementation through the `Code` enum. You can retrieve the status codes, their
corresponding messages, and check for various status code ranges using the methods provided.

- **Get message**: Returns the [HTTP status message](https://developer.mozilla.org/en-US/docs/Web/HTTP/Messages)
  associated with the enum's code.

  ```php
  use TinyBlocks\Http\Code;

  Code::OK->value;                        # 200
  Code::OK->message();                    # OK
  Code::IM_A_TEAPOT->message();           # I'm a teapot
  Code::INTERNAL_SERVER_ERROR->message(); # Internal Server Error
  ```

- **Check if the code is valid**: Determines if the given code is a valid HTTP status code represented by the enum.

  ```php
  use TinyBlocks\Http\Code;

  Code::isValidCode(code: 200); # true
  Code::isValidCode(code: 999); # false
  ```

- **Check if the code is an error**: Determines if the given code is in the error range (**4xx** or **5xx**).

  ```php
  use TinyBlocks\Http\Code;

  Code::isErrorCode(code: 500); # true
  Code::isErrorCode(code: 200); # false
  ```

- **Check if the code is a success**: Determines if the given code is in the success range (**2xx**).

  ```php
  use TinyBlocks\Http\Code;

  Code::isSuccessCode(code: 500); # false
  Code::isSuccessCode(code: 200); # true
  ```

<div id='client'></div>

### Client

<div id='configuring-http'></div>

#### Configuring Http

`Http` is a thin, immutable façade over any [PSR-18](https://www.php-fig.org/psr/psr-18) HTTP client. It does not
bundle any transport implementation — bring your own.

```php
use GuzzleHttp\Client as GuzzleClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use TinyBlocks\Http\Http;

$factory = new Psr17Factory();

$http = Http::from(
    client: new GuzzleClient(),
    requestFactory: $factory,
    streamFactory: $factory
);
```

Pass an optional `baseUrl` to avoid repeating the host on every request:

```php
$http = Http::from(
    client: new GuzzleClient(),
    requestFactory: $factory,
    streamFactory: $factory,
    baseUrl: 'https://api.example.com'
);
```

<div id='making-a-request'></div>

#### Making a request

Build a `Client\Request` with `Request::create()` and pass it to `Http::send()`.

```php
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Http;
use TinyBlocks\Http\Method;

$response = $http->send(
    request: Request::create(
        url: '/dragons',
        method: Method::GET
    )
);
```

For requests with a JSON body:

```php
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Http;
use TinyBlocks\Http\Method;

$response = $http->send(
    request: Request::create(
        url: '/dragons',
        method: Method::POST,
        body: ['name' => 'Hydra', 'type' => 'water']
    )
);
```

<div id='reading-the-response'></div>

#### Reading the response

`Http::send()` returns an immutable `Client\Response`. It provides typed access to the status code, headers, and
JSON body.

```php
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

$response = $http->send(
    request: Request::create(url: '/dragons/42', method: Method::GET)
);

$response->statusCode();              # 200
$response->code();                    # Code::OK  (null if not in the Code enum)
$response->isSuccess();               # true
$response->isError();                 # false

$response->body()->get(key: 'id')->toInteger();     # 42
$response->body()->get(key: 'name')->toString();    # "Hydra"
$response->body()->toArray();                       # ['id' => 42, 'name' => 'Hydra']

$response->headers();                 # ['Content-Type' => 'application/json', ...]
$response->raw();                     # the underlying PSR-7 ResponseInterface
```

<div id='query-parameters'></div>

#### Query parameters

Pass an associative array to `query`. Values are encoded
using [RFC 3986](https://datatracker.ietf.org/doc/html/rfc3986).

```php
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

$http->send(
    request: Request::create(
        url: '/dragons',
        method: Method::GET,
        query: ['sort' => 'name', 'order' => 'asc', 'limit' => 50]
    )
);
# Sends: GET /dragons?sort=name&order=asc&limit=50
```

<div id='custom-headers-and-content-type'></div>

#### Custom headers and content type

By default, requests with a body are sent with `Content-Type: application/json` and `Accept: application/json`.
Pass one or more `Headers` instances to override or extend the defaults.

```php
use TinyBlocks\Http\Charset;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\ContentType;
use TinyBlocks\Http\Method;

$http->send(
    request: Request::create(
        url: '/dragons',
        method: Method::POST,
        body: ['name' => 'Hydra'],
        headers: ContentType::applicationJson(charset: Charset::UTF_8)
    )
);
# Sends: Content-Type: application/json; charset=utf-8
```

<div id='error-handling'></div>

#### Error handling

`Http::send()` maps PSR-18 transport exceptions to three typed exceptions. All three extend `HttpRequestFailed`,
which carries the target `$url`, the `Method`, and the `$reason` string.

| Exception            | Cause                                                                  |
|----------------------|------------------------------------------------------------------------|
| `HttpNetworkFailed`  | `NetworkExceptionInterface` — connection refused, timeout, DNS failure |
| `HttpRequestInvalid` | `RequestExceptionInterface` — malformed request object                 |
| `HttpRequestFailed`  | Any other `ClientExceptionInterface`                                   |

```php
use TinyBlocks\Http\Client\Exceptions\HttpNetworkFailed;
use TinyBlocks\Http\Client\Exceptions\HttpRequestFailed;
use TinyBlocks\Http\Client\Exceptions\HttpRequestInvalid;
use TinyBlocks\Http\Client\Request;
use TinyBlocks\Http\Method;

try {
    $response = $http->send(
        request: Request::create(url: '/dragons', method: Method::GET)
    );
} catch (HttpNetworkFailed $exception) {
    # connection-level failure — retry is often appropriate
    echo $exception->url;    # "https://api.example.com/dragons"
    echo $exception->method; # Method::GET
    echo $exception->reason; # "connection refused"
} catch (HttpRequestInvalid $exception) {
    # malformed request — do not retry
} catch (HttpRequestFailed $exception) {
    # catch-all for any other PSR-18 client exception
}
```

The original PSR-18 exception is always preserved as the previous exception (`$exception->getPrevious()`).

<div id='configuring-timeouts'></div>

#### Configuring timeouts

Timeouts are not part of this library's public API. Configure them directly on the PSR-18 client you inject.

**Guzzle:**

```php
use GuzzleHttp\Client as GuzzleClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use TinyBlocks\Http\Http;

$factory = new Psr17Factory();

$http = Http::from(
    client: new GuzzleClient([
        'timeout'         => 5.0,
        'connect_timeout' => 2.0
    ]),
    requestFactory: $factory,
    streamFactory: $factory,
    baseUrl: 'https://api.example.com'
);
```

**Symfony HttpClient:**

```php
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;
use TinyBlocks\Http\Http;

$factory = new Psr17Factory();

$http = Http::from(
    client: new Psr18Client(
        \Symfony\Component\HttpClient\HttpClient::create([
            'timeout' => 5.0
        ])
    ),
    requestFactory: $factory,
    streamFactory: $factory,
    baseUrl: 'https://api.example.com'
);
```

## License

Http is licensed under [MIT](LICENSE).

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
