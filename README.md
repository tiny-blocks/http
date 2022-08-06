# Http

[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

* [Overview](#overview)
* [Installation](#installation)
* [How to use](#how-to-use)
* [License](#license)
* [Contributing](#contributing)

<div id='overview'></div> 

## Overview

HTTP response status codes.

<div id='installation'></div>

## Installation

```bash
composer require tiny-blocks/http
```

<div id='how-to-use'></div>

## How to use

The library exposes a concrete implementation through the `HttpCode` enum. You can get the status codes, and their
corresponding messages.

```php
echo $httpCode->name;      # CREATED
echo $httpCode->value;     # 201
echo $httpCode->message(); # 201 Created
```

## License

Math is licensed under [MIT](/LICENSE).

<div id='contributing'></div>

## Contributing

Please follow the [contributing guidelines](https://github.com/tiny-blocks/tiny-blocks/blob/main/CONTRIBUTING.md) to
contribute to the project.

