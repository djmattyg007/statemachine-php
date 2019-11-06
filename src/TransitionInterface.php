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

use MattyG\StateMachine\Exception\LogicException;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
interface TransitionInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getFrom(): string;

    /**
     * @return string
     */
    public function getTo(): string;

    /**
     * @param object $subject
     * @param StateMachineInterface $stateMachine
     * @return bool False if the transition is not available to the subject.
     */
    public function checkIsAvailable(object $subject, StateMachineInterface $stateMachine): bool;

    /**
     * @param object $subject
     * @param StateMachineInterface $stateMachine
     * @throws LogicException If the subject is not eligible to leave its current state.
     */
    public function checkCanLeave(object $subject, StateMachineInterface $stateMachine): void;

    /**
     * @param object $subject
     * @param StateMachineInterface $stateMachine
     * @throws LogicException If the subject is not eligible to enter the new state.
     */
    public function checkCanEnter(object $subject, StateMachineInterface $stateMachine): void;
}
