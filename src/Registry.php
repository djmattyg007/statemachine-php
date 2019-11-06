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

use MattyG\StateMachine\Exception\InvalidArgumentException;
use MattyG\StateMachine\SupportStrategy\StateMachineSupportStrategyInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class Registry
{
    /**
     * @var array<int, array{0: StateMachineInterface, 1: StateMachineSupportStrategyInterface}>
     */
    private $stateMachines = [];

    /**
     * @param StateMachineInterface $stateMachine
     * @param StateMachineSupportStrategyInterface $supportStrategy
     */
    public function addStateMachine(StateMachineInterface $stateMachine, StateMachineSupportStrategyInterface $supportStrategy): void
    {
        $this->stateMachines[] = [$stateMachine, $supportStrategy];
    }

    /**
     * @param object $subject
     * @param string|null $stateMachineName
     * @return StateMachineInterface
     * @throws InvalidArgumentException
     */
    public function get(object $subject, ?string $stateMachineName = null): StateMachineInterface
    {
        /** @var StateMachineInterface[] $matched */
        $matched = [];

        foreach ($this->stateMachines as list($stateMachine, $supportStrategy)) {
            if ($this->supports($stateMachine, $supportStrategy, $subject, $stateMachineName)) {
                $matched[] = $stateMachine;
            }
        }

        if (!$matched) {
            throw new InvalidArgumentException(sprintf('Unable to find a state machine for class "%s".', \get_class($subject)));
        }

        if (\count($matched) >= 2) {
            $names = \array_map(static function (StateMachineInterface $stateMachine): string {
                return $stateMachine->getName();
            }, $matched);

            throw new InvalidArgumentException(sprintf(
                'Too many state machines (%s) match this subject (%s); set a different name on each and use the second (name) argument of this method.',
                \implode(', ', $names),
                \get_class($subject)
            ));
        }

        return $matched[0];
    }

    /**
     * @param object $subject
     * @return StateMachineInterface[]
     */
    public function all(object $subject): array
    {
        /** @var StateMachineInterface[] $matched */
        $matched = [];

        foreach ($this->stateMachines as list($stateMachine, $supportStrategy)) {
            if ($supportStrategy->supports($stateMachine, $subject)) {
                $matched[] = $stateMachine;
            }
        }

        return $matched;
    }

    /**
     * @param StateMachineInterface $stateMachine
     * @param StateMachineSupportStrategyInterface $supportStrategy
     * @param object $subject
     * @param string|null $stateMachineName
     */
    private function supports(StateMachineInterface $stateMachine, StateMachineSupportStrategyInterface $supportStrategy, object $subject, ?string $stateMachineName): bool
    {
        if ($stateMachineName !== null && $stateMachineName !== $stateMachine->getName()) {
            return false;
        }

        return $supportStrategy->supports($stateMachine, $subject);
    }
}
