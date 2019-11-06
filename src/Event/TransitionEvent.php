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

namespace MattyG\StateMachine\Event;

use MattyG\StateMachine\TransitionInterface;
use MattyG\StateMachine\StateMachineInterface;

final class TransitionEvent extends Event
{
    /**
     * @var array
     */
    private $context;

    /**
     * {@inheritdoc}
     * @param array $context
     */
    public function __construct(object $subject, TransitionInterface $transition, StateMachineInterface $stateMachine, array $context = [])
    {
        parent::__construct($subject, $transition, $stateMachine);

        $this->context = $context;
    }

    /**
     * @param array $context
     */
    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
