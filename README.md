# Http

[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
    * [Request](#request)
    * [Response](#response)
* [License](#license)
* [Contributing](#contributing)

<div id='overview'></div> 

## Overview

Implements [PSR-7](https://www.php-fig.org/psr/psr-7) and [PSR-15](https://www.php-fig.org/psr/psr-15) HTTP primitives
for PHP, covering requests, responses, streams, cookies, headers, methods, status codes, and cache-control directives.
Ships with a fluent response builder that maps common outcomes to the correct HTTP semantics out of the box.
Interoperable with Slim, Laminas, and any PSR-compliant framework.

<div id='installation'></div>

## Installation

```bash
composer require tiny-blocks/http
```

<div id='how-to-use'></div>

## How to use

The library exposes interfaces like `Headers` and concrete implementations like `Request`, `Response`, `ContentType`,
and others, which facilitate construction.

<div id='request'></div>

### Request

#### Decoding a request

The library provides a small public API to decode a PSR-7 `ServerRequestInterface` into a typed structure, allowing you
to access route parameters and JSON body fields consistently.

- **Decode a request**: Use `Request::from(...)` to wrap the PSR-7 request and call `decode()`. The decoded object
  exposes `uri` and `body`.

  ```php
  use Psr\Http\Message\ServerRequestInterface;
  use TinyBlocks\Http\Request;

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
  use TinyBlocks\Http\Request;

  /** @var ServerRequestInterface $psrRequest */
  $request = Request::from(request: $psrRequest);

  $method = $request->method();        # Method::POST
  $method->value;                      # "POST"
  ```

- **Access the full URI**: Use `toString()` on the decoded `uri()` to retrieve the complete request URI as a string.

  ```php
  use TinyBlocks\Http\Request;

  $decoded = Request::from(request: $psrRequest)->decode();

  $fullUri = $decoded->uri()->toString(); # "https://api.example.com/v1/dragons?sort=name"
  ```

- **Access query parameters**: Use `queryParameters()` on the decoded `uri()` to retrieve typed access to query string
  values. Each value is returned as an `Attribute`, providing the same safe conversions and defaults as body fields.

  ```php
  use TinyBlocks\Http\Request;

  $decoded = Request::from(request: $psrRequest)->decode();

  $queryParams = $decoded->uri()->queryParameters()->toArray();                     # ['sort' => 'name', 'limit' => '50']
  $sort = $decoded->uri()->queryParameters()->get(key: 'sort')->toString();         # "name"
  $limit = $decoded->uri()->queryParameters()->get(key: 'limit')->toInteger();      # 50
  $active = $decoded->uri()->queryParameters()->get(key: 'active')->toBoolean();    # default: false
  ```

- **Typed access with defaults**: Each value is returned as an Attribute, which provides safe conversions and default
  values when the underlying value is missing or not compatible.

  ```php
  use TinyBlocks\Http\Request;
  
  $request = Request::from(request: $psrRequest);
  $decoded = $request->decode();
  
  $method = $request->method();                                                  # default: Method enum
  
  $id = $decoded->uri()->route()->get(key: 'id')->toInteger();                   # default: 0
  $uri = $decoded->uri()->toString();                                            # default: ""
  $sort = $decoded->uri()->queryParameters()->get(key: 'sort')->toString();      # default: ""
  $limit = $decoded->uri()->queryParameters()->get(key: 'limit')->toInteger();   # default: 0
  
  $note = $decoded->body()->get(key: 'note')->toString();                        # default: ""
  $tags = $decoded->body()->get(key: 'tags')->toArray();                         # default: []
  $price = $decoded->body()->get(key: 'price')->toFloat();                       # default: 0.00
  $active = $decoded->body()->get(key: 'active')->toBoolean();                   # default: false
  ```

- **Custom route attribute name**: If your framework stores route params in a different request attribute, you can
  specify it via `route()`.

  ```php
  use TinyBlocks\Http\Request;
  
  $decoded = Request::from(request: $psrRequest)->decode();

  $id = $decoded->uri()->route(name: '_route_params')->get(key: 'id')->toInteger();
  ```

#### How route parameters are resolved

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
   across frameworks:
    - `__route__`, `_route_params`, `route`, `routing`, `routeResult`, `routeInfo`

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

#### Manually injecting route parameters

If your framework or middleware does not automatically populate route attributes, you can inject them manually using
PSR-7's `withAttribute()`:

```php
use TinyBlocks\Http\Request;

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

### Response

#### Creating a response

The library provides an easy and flexible way to create HTTP responses, allowing you to specify the status code,
headers, and body. You can use the `Response` class to generate responses, and the result will always be a
`ResponseInterface` from the PSR, ensuring compatibility with any framework that adheres
to the [PSR-7](https://www.php-fig.org/psr/psr-7) standard.

- **Creating a response with a body**: To create an HTTP response, you can pass any type of data as the body.
  Optionally, you can also specify one or more headers. If no headers are provided, the response will default to
  `application/json` content type.

  ```php
  use TinyBlocks\Http\Response;
    
  Response::ok(body: ['message' => 'Resource created successfully.']);
  ```

- **Creating a response with a body and custom headers**: You can also add custom headers to the response. For instance,
  if you want to specify a custom content type or any other header, you can pass the headers as additional arguments.

  ```php
  use TinyBlocks\Http\Response;
  use TinyBlocks\Http\ContentType;
  use TinyBlocks\Http\CacheControl;
  use TinyBlocks\Http\ResponseCacheDirectives;
    
  $body = 'This is a plain text response';
  
  $contentType = ContentType::textPlain();
  
  $cacheControl = CacheControl::fromResponseDirectives(
      maxAge: ResponseCacheDirectives::maxAge(maxAgeInWholeSeconds: 10000),
      staleIfError: ResponseCacheDirectives::staleIfError()
  );
  
  Response::ok($body, $contentType, $cacheControl)
      ->withHeader(name: 'X-ID', value: 100)
      ->withHeader(name: 'X-NAME', value: 'Xpto');
  ```

#### Setting cookies

The library models the `Set-Cookie` HTTP response header through the `Cookie` value object, covering the full
[RFC 6265](https://datatracker.ietf.org/doc/html/rfc6265) attribute set plus modern additions such as `SameSite` and
`Partitioned`. Instances are immutable and fluent — every builder call returns a new `Cookie`. Like `ContentType` and
`CacheControl`, `Cookie` implements `Headers`, so it composes naturally with any `Response` factory via varargs.

- **Setting a session cookie**: Build a cookie with the required security flags and attach it to a response.

  ```php
  use TinyBlocks\Http\Cookie;
  use TinyBlocks\Http\Response;
  use TinyBlocks\Http\SameSite;
  
  $cookie = Cookie::create(name: 'refresh_token', value: $opaqueToken)
      ->httpOnly()
      ->secure()
      ->withSameSite(sameSite: SameSite::STRICT)
      ->withPath(path: '/v1/sessions')
      ->withMaxAge(seconds: 604800);
  
  Response::ok(body: ['ok' => true], $cookie);
  ```

- **Setting multiple cookies**: Pass each `Cookie` as an additional header argument. The response emits one
  `Set-Cookie` header per cookie, preserving all of them (this follows the PSR-7 multi-value header model).

  ```php
  use TinyBlocks\Http\Cookie;
  use TinyBlocks\Http\Response;
  use TinyBlocks\Http\SameSite;
  
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
  same `Path` (and `Domain`, if applicable) used when the cookie was issued; otherwise the browser will not match the
  cookie and the deletion will have no effect.

  ```php
  use TinyBlocks\Http\Cookie;
  use TinyBlocks\Http\Response;
  use TinyBlocks\Http\SameSite;
  
  $expired = Cookie::expire(name: 'refresh_token')
      ->httpOnly()
      ->secure()
      ->withSameSite(sameSite: SameSite::STRICT)
      ->withPath(path: '/v1/sessions');
  
  Response::noContent($expired);
  ```

- **Using an absolute expiration date**: When an explicit deletion moment is preferable over `Max-Age`, use
  `withExpires()`. The date is converted to UTC and rendered in
  the [RFC 7231](https://datatracker.ietf.org/doc/html/rfc7231)
  date format required by the `Expires` attribute. `Max-Age` and `Expires` are mutually exclusive — setting both
  throws `ConflictingLifetimeAttributes` when the response is serialized.

  ```php
  use DateTimeImmutable;
  use DateTimeZone;
  use TinyBlocks\Http\Cookie;
  
  Cookie::create(name: 'preference', value: 'dark-mode')->withExpires(
      expires: new DateTimeImmutable(datetime: '2030-01-15 12:00:00', timezone: new DateTimeZone(timezone: 'UTC'))
  );
  ```

- **Cross-site cookies**: `SameSite::NONE` requires the `Secure` flag — modern browsers reject `SameSite=None` cookies
  sent over insecure connections. The library enforces this invariant at serialization time and throws
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
  whitespace, or token separators (``( ) < > @ , ; : \ " / [ ] ? = { }``). Values cannot contain control characters,
  whitespace, double quotes, commas, semicolons, or backslashes. Encode the value (e.g., URL-encode or Base64) before
  passing it when it may contain arbitrary text.

  ```php
  use TinyBlocks\Http\Cookie;
  
  Cookie::create(name: 'user_id', value: (string)$userId);           # valid
  Cookie::create(name: 'payload', value: base64_encode($jsonBody));  # encode arbitrary values first
  ```

#### Using the status code

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

<div id='license'></div>

## License

Http is licensed under [MIT](LICENSE).

<div id='contributing'></div>

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
