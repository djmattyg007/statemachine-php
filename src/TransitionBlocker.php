<?php

/*
 * This file was forked from the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Matthew Gamble <git@matthewgamble.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MattyG\StateMachine;

/**
 * A reason why a transition cannot be performed for a subject.
 */
final class TransitionBlocker
{
    const BLOCKED_BY_STATE = '29beefc4-6b3e-4726-0d17-f38bd6d16e34';
    const BLOCKED_BY_AVAILIBILITY_GUARD = '1cdf608a-32df-40cb-b167-c555cee491ad';
    const BLOCKED_BY_EXPRESSION_GUARD_LISTENER = '326a1e9c-0c12-11e8-ba89-0ed5f89f718b';
    const UNKNOWN = 'e8b5bbb9-5913-4b98-bfa6-65dbd228a82a';

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $code;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param string $message
     * @param string $code       Code is a machine-readable string, usually an UUID
     * @param array  $parameters This is useful if you would like to pass around the condition values, that
     *                           blocked the transition. E.g. for a condition "distance must be larger than
     *                           5 miles", you might want to pass around the value of 5.
     */
    public function __construct(string $message, string $code, array $parameters = [])
    {
        $this->message = $message;
        $this->code = $code;
        $this->parameters = $parameters;
    }

    /**
     * Create a blocker that says the transition cannot be made because it is
     * not enabled.
     *
     * It means the subject is in the wrong place - i.e. not the 'from' place of the transition.
     */
    public static function createBlockedByState(string $state): self
    {
        return new static('The state does not enable the transition.', self::BLOCKED_BY_STATE, [
            'state' => $state,
        ]);
    }

    public static function createBlockedByAvailabilityGuard(): self
    {
        return new static('A transition availability guard blocks the transition.', self::BLOCKED_BY_AVAILIBILITY_GUARD);
    }

    /**
     * Creates a blocker that says the transition cannot be made because it has
     * been blocked by the expression guard listener.
     */
    public static function createBlockedByExpressionGuardListener(string $expression): self
    {
        return new static('The expression blocks the transition.', self::BLOCKED_BY_EXPRESSION_GUARD_LISTENER, [
            'expression' => $expression,
        ]);
    }

    /**
     * Creates a blocker that says the transition cannot be made because of an
     * unknown reason.
     *
     * This blocker code is chiefly for preserving backwards compatibility.
     */
    public static function createUnknown(): self
    {
        return new static('Unknown reason.', self::UNKNOWN);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
