# Http

[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
* [License](#license)
* [Contributing](#contributing)

<div id='overview'></div> 

## Overview

Common implementations for HTTP protocol.

<div id='installation'></div>

## Installation

```bash
composer require tiny-blocks/http
```

<div id='how-to-use'></div>

## How to use

The library exposes concrete implementations for the HTTP protocol, such as status codes, methods, etc.

### Using the HttpCode

The library exposes a concrete implementation through the `HttpCode` enum. You can get the status codes, and their
corresponding messages.

```php
$httpCode = HttpCode::CREATED;

echo $httpCode->name;      # CREATED
echo $httpCode->value;     # 201
echo $httpCode->message(); # 201 Created
```

### Using the HttpMethod

The library exposes a concrete implementation via the `HttpMethod` enum. You can get a set of HTTP methods.

```php
$method = HttpMethod::GET;

echo $method->name;  # GET
echo $method->value; # GET
```

## License

Math is licensed under [MIT](/LICENSE).

<div id='contributing'></div>

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.
