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
use MattyG\StateMachine\TransitionBlocker;
use MattyG\StateMachine\TransitionBlockerList;
use MattyG\StateMachine\StateMachineInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
final class GuardEvent extends Event
{
    /**
     * @var TransitionBlockerList
     */
    private $transitionBlockerList;

    /**
     * {@inheritdoc}
     */
    public function __construct(object $subject, TransitionInterface $transition, StateMachineInterface $stateMachine)
    {
        parent::__construct($subject, $transition, $stateMachine);

        $this->transitionBlockerList = new TransitionBlockerList();
    }

    /**
     * @return bool
     */
    public function isBlocked(): bool
    {
        return !$this->transitionBlockerList->isEmpty();
    }

    /**
     * @param bool $blocked
     */
    public function setBlocked(bool $blocked): void
    {
        if (!$blocked) {
            $this->transitionBlockerList->clear();

            return;
        }

        $this->transitionBlockerList->add(TransitionBlocker::createUnknown());
    }

    /**
     * @return TransitionBlockerList
     */
    public function getTransitionBlockerList(): TransitionBlockerList
    {
        return $this->transitionBlockerList;
    }

    /**
     * @param TransitionBlocker $transitionBlocker
     */
    public function addTransitionBlocker(TransitionBlocker $transitionBlocker): void
    {
        $this->transitionBlockerList->add($transitionBlocker);
    }
}
