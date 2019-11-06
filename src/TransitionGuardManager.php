<?php

/*
 * This file was developed after the fork from Symfony framework.
 *
 * (c) Matthew Gamble <git@matthewgamble.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MattyG\StateMachine;

use MattyG\StateMachine\Exception\LogicException;

final class TransitionGuardManager
{
    /**
     * @var callable[]
     */
    private $availabilityGuards;

    /**
     * @var callable[]
     */
    private $leaveGuards;

    /**
     * @var callable[]
     */
    private $enterGuards;

    /**
     * @param callable[] $availabilityGuards
     * @param callable[] $leaveGuards
     * @param callable[] $enterGuards
     */
    public function __construct(array $availabilityGuards, array $leaveGuards, array $enterGuards)
    {
        $this->availabilityGuards = $availabilityGuards;
        $this->leaveGuards = $leaveGuards;
        $this->enterGuards = $enterGuards;
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     * @param StateMachineInterface $stateMachine
     * @return bool
     * @throws LogicException
     */
    public function runAvailabilityGuards(object $subject, TransitionInterface $transition, StateMachineInterface $stateMachine): bool
    {
        foreach ($this->availabilityGuards as $guard) {
            $available = $guard($subject, $transition, $stateMachine);
            if ($available === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     * @param StateMachineInterface $stateMachine
     * @throws LogicException
     */
    public function runLeaveGuards(object $subject, TransitionInterface $transition, StateMachineInterface $stateMachine): void
    {
        foreach ($this->leaveGuards as $guard) {
            $guard($subject, $transition, $stateMachine);
        }
    }

    /**
     * @param object $subject
     * @param TransitionInterface $transition
     * @param StateMachineInterface $stateMachine
     * @throws LogicException
     */
    public function runEnterGuards(object $subject, TransitionInterface $transition, StateMachineInterface $stateMachine): void
    {
        foreach ($this->enterGuards as $guard) {
            $guard($subject, $transition, $stateMachine);
        }
    }
}
