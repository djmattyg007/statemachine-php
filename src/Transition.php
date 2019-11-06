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
class Transition implements TransitionInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $to;

    /**
     * @var TransitionGuardManager|null
     */
    private $guardManager;

    /**
     * @param string $name
     * @param string $from
     * @param string $to
     * @param TransitionGuardManager|null $guardManager
     */
    public function __construct(string $name, string $from, string $to, ?TransitionGuardManager $guardManager = null)
    {
        $this->name = $name;
        $this->from = $from;
        $this->to = $to;
        $this->guardManager = $guardManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * {@inheritdoc}
     */
    public function getTo(): string
    {
        return $this->to;
    }

    /**
     * {@inheritdoc}
     */
    public function checkIsAvailable(object $subject, StateMachineInterface $stateMachine): bool
    {
        if ($this->guardManager === null) {
            return true;
        }

        try {
            $result = $this->guardManager->runAvailabilityGuards($subject, $this, $stateMachine);
        } catch (LogicException $e) {
            return false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function checkCanLeave(object $subject, StateMachineInterface $stateMachine): void
    {
        if ($this->guardManager === null) {
            return;
        }

        $this->guardManager->runLeaveGuards($subject, $this, $stateMachine);
    }

    /**
     * {@inheritdoc}
     */
    public function checkCanEnter(object $subject, StateMachineInterface $stateMachine): void
    {
        if ($this->guardManager === null) {
            return;
        }

        $this->guardManager->runEnterGuards($subject, $this, $stateMachine);
    }
}
