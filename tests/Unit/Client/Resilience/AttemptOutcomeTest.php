<?php

declare(strict_types=1);

namespace Test\TinyBlocks\Http\Unit\Client\Resilience;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TinyBlocks\Http\Client\Resilience\AttemptOutcome;

final class AttemptOutcomeTest extends TestCase
{
    #[DataProvider('statusCodeScenarios')]
    public function testFromStatusCodeWhenStatusIsGivenThenClassifiesTheStatusFamily(
        ?AttemptOutcome $expected,
        int $statusCode
    ): void {
        /** @Given an HTTP status code */
        /** @When the status code is classified */
        $outcome = AttemptOutcome::fromStatusCode(statusCode: $statusCode);

        /** @Then the classification matches the status family */
        self::assertSame($expected, $outcome);
    }

    #[DataProvider('throwableScenarios')]
    public function testFromThrowableWhenMessageIsGivenThenClassifiesTheFailureNature(
        string $message,
        AttemptOutcome $expected
    ): void {
        /** @Given a transport failure carrying a message */
        $throwable = new RuntimeException(message: $message);

        /** @When the throwable is classified */
        $outcome = AttemptOutcome::fromThrowable(throwable: $throwable);

        /** @Then the classification matches the failure nature */
        self::assertSame($expected, $outcome);
    }

    #[DataProvider('retryableScenarios')]
    public function testIsRetryableWhenOutcomeIsGivenThenOnlyTheClientErrorIsNotRetryable(
        AttemptOutcome $outcome,
        bool $expected
    ): void {
        /** @Given a failure outcome */
        /** @When the outcome is asked whether a retry is worthwhile */
        $isRetryable = $outcome->isRetryable();

        /** @Then only the client error is not retryable */
        self::assertSame($expected, $isRetryable);
    }

    public static function retryableScenarios(): array
    {
        return [
            'Timeout is retryable'          => ['outcome' => AttemptOutcome::TIMEOUT, 'expected' => true],
            'Server error is retryable'     => ['outcome' => AttemptOutcome::SERVER_ERROR, 'expected' => true],
            'Connection reset is retryable' => ['outcome' => AttemptOutcome::CONNECTION_RESET, 'expected' => true],
            'Client error is never retried' => ['outcome' => AttemptOutcome::CLIENT_ERROR, 'expected' => false]
        ];
    }

    public static function throwableScenarios(): array
    {
        return [
            'Timed out message classifies a timeout'       => [
                'message'  => 'cURL error 28: Operation timed out after 10001 milliseconds',
                'expected' => AttemptOutcome::TIMEOUT
            ],
            'Timeout message classifies a timeout'         => [
                'message'  => 'Connection timeout reached',
                'expected' => AttemptOutcome::TIMEOUT
            ],
            'Uppercase timeout message classifies'         => [
                'message'  => 'OPERATION TIMED OUT',
                'expected' => AttemptOutcome::TIMEOUT
            ],
            'Any other message classifies a broken socket' => [
                'message'  => 'cURL error 56: Connection reset by peer',
                'expected' => AttemptOutcome::CONNECTION_RESET
            ]
        ];
    }

    public static function statusCodeScenarios(): array
    {
        return [
            'Success is not a failure'                  => ['expected' => null, 'statusCode' => 200],
            'Redirect is not a failure'                 => ['expected' => null, 'statusCode' => 399],
            'Client error lower bound classifies'       => [
                'expected'   => AttemptOutcome::CLIENT_ERROR,
                'statusCode' => 400
            ],
            'Client error upper bound classifies'       => [
                'expected'   => AttemptOutcome::CLIENT_ERROR,
                'statusCode' => 499
            ],
            'Server error lower bound classifies'       => [
                'expected'   => AttemptOutcome::SERVER_ERROR,
                'statusCode' => 500
            ],
            'Request timeout classifies a timeout'      => [
                'expected'   => AttemptOutcome::TIMEOUT,
                'statusCode' => 408
            ],
            'Gateway timeout classifies a timeout'      => [
                'expected'   => AttemptOutcome::TIMEOUT,
                'statusCode' => 504
            ],
            'Service unavailable is a server error'     => [
                'expected'   => AttemptOutcome::SERVER_ERROR,
                'statusCode' => 503
            ],
            'Non-RFC proxy code is still a server error' => [
                'expected'   => AttemptOutcome::SERVER_ERROR,
                'statusCode' => 599
            ]
        ];
    }
}
