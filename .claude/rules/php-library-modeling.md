---
description: Library modeling rules — folder structure, public API boundary, naming, value objects, exceptions, enums, extension points, and complexity.
paths:
    - "src/**/*.php"
---

# Library modeling

Libraries are self-contained packages. The core has no dependency on frameworks, databases, or I/O. Refer to
`php-library-code-style.md` for the pre-output checklist applied to all PHP code.

## Folder structure

```
src/
├── <PublicInterface>.php         # Primary contract for consumers
├── <Implementation>.php          # Main implementation or extension point
├── <Enum>.php                    # Public enum
├── Contracts/                    # Interfaces for data returned to consumers
├── Internal/                     # Implementation details (not part of public API)
│   ├── <Collaborator>.php
│   └── Exceptions/               # Internal exception classes
├── <Feature>/                    # Feature-specific subdirectory when needed
└── Exceptions/                   # Public exception classes (when part of the API)
```

Never use `Models/`, `Entities/`, `ValueObjects/`, `Enums/`, or `Domain/` as folder names.

## Public API boundary

Only interfaces, extension points, enums, and thin orchestration classes live at the `src/` root. These classes
define the contract consumers interact with and delegate all real work to collaborators inside `src/Internal/`.
If a class contains substantial logic (algorithms, state machines, I/O), it belongs in `Internal/`, not at the root.

The `Internal/` namespace signals classes that are implementation details. Consumers must not depend on them.
Breaking changes inside `Internal/` are not semver-breaking for the library.

## Nomenclature

1. Every class, property, method, and exception name reflects the **concept** the library represents. A math library
   uses `Precision`, `RoundingMode`; a money library uses `Currency`, `Amount`; a collection library uses
   `Collectible`, `Order`.
2. Never use generic technical names as class suffixes, prefixes, or method names: `Manager`, `Helper`, `Processor`,
   `Handler`, `Service`, `Data`, `Info`, `Utils`, `Item`, `Record`, `Entity`, `Exception`, `process`, `handle`,
   `execute`, `mark`, `enforce`, `manage`, `ensure`, `validate`, `check`, `verify`, `assert`, `transform`, `parse`,
   `compute`, `sanitize`, or `normalize`.
3. Name classes after what they represent: `Money`, `Color`, `Pipeline` — not after what they do technically.
4. Name methods after the operation in its vocabulary: `add()`, `convertTo()`, `splitAt()`.

## Value objects

1. Are immutable: no setters, no mutation after construction. Operations return new instances.
2. Compare by value, not by reference.
3. Validate invariants in the constructor and throw on invalid input.
4. Have no identity field.
5. Use static factory methods (e.g., `from`, `of`, `zero`) with a private constructor when multiple creation paths
   exist. The factory name communicates the semantic intent.

## Exceptions

1. Every failure throws a **dedicated exception class** named after the invariant it guards — never
   `throw new DomainException('...')`, `throw new InvalidArgumentException('...')`,
   `throw new RuntimeException('...')`, or any other generic native exception with a string message. If the
   invariant is worth throwing for, it is worth a named class.
2. Dedicated exception classes **extend** the appropriate native PHP exception (`DomainException`,
   `InvalidArgumentException`, `OverflowException`, etc.) — the native class is the parent, never the thing that
   is thrown. Consumers that catch the broad standard types continue to work; consumers that need precise handling
   can catch the specific classes.
3. Exceptions are pure: no transport-specific fields (`code`, formatted `message`). Formatting to any transport
   happens at the consumer's boundary, not inside the library.
4. Exceptions signal invariant violations only, not control flow.
5. Name the class after the invariant violated, never after the technical type:
    - `PrecisionOutOfRange` — not `InvalidPrecisionException`.
    - `CurrencyMismatch` — not `BadCurrencyException`.
    - `ContainerWaitTimeout` — not `TimeoutException`.
6. No exception-formatting constructor, no custom message argument — the class name is the message.
7. Public exceptions live in `src/Exceptions/`. Internal exceptions live in `src/Internal/Exceptions/`.

**Prohibited** — throwing a native exception directly:

```php
if ($value < 0) {
    throw new InvalidArgumentException('Precision cannot be negative.');
}
```

**Correct** — dedicated class extending the native exception:

```php
// src/Exceptions/PrecisionOutOfRange.php
final class PrecisionOutOfRange extends InvalidArgumentException
{
}

// at the callsite
if ($value < 0) {
    throw new PrecisionOutOfRange();
}
```

## Enums

1. Are PHP backed enums.
2. Include methods when they carry vocabulary meaning (e.g., `Order::ASCENDING_KEY`, `RoundingMode::apply()`).
3. Live at the `src/` root when public. Enums used only by internals live in `src/Internal/`.

## Extension points

1. When a class is designed to be extended by consumers (e.g., `Collection`, `ValueObject`), it uses `class` instead
   of `final readonly class`. All other classes use `final readonly class`.
2. Extension point classes use a private constructor with static factory methods (`createFrom`, `createFromEmpty`)
   as the only creation path.
3. Internal state is injected via the constructor and stored in a `private readonly` property.

## Time and space complexity

1. Every public method has predictable, documented complexity. Document Big O in PHPDoc on the interface
   (see `php-library-code-style.md`, "PHPDoc" section).
2. Algorithms run in `O(N)` or `O(N log N)` unless the problem inherently requires worse. `O(N²)` or worse must
   be justified and documented.
3. Prefer lazy/streaming evaluation over materializing intermediate results. In pipeline-style libraries, fuse
   stages so a single pass suffices.
4. Memory usage is bounded and proportional to the output, not to the sum of intermediate stages.
5. Validate complexity claims with benchmarks against a reference implementation when optimizing critical paths.
   Parity testing against the reference library is the validation standard for optimization work.
