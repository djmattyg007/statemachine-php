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

namespace MattyG\StateMachine\Exception;

use MattyG\StateMachine\StateMachineInterface;

/**
 * @author Andrew Tch <andrew.tchircoff@gmail.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class TransitionException extends LogicException
{
    /**
     * @var object
     */
    private $subject;

    /**
     * @var string
     */
    private $transitionName;

    /**
     * @var StateMachineInterface
     */
    private $stateMachine;

    /**
     * @param object $subject
     * @param string $transitionName
     * @param StateMachineInterface $stateMachine
     * @param string $message
     */
    public function __construct(object $subject, string $transitionName, StateMachineInterface $stateMachine, string $message)
    {
        parent::__construct($message);

        $this->subject = $subject;
        $this->transitionName = $transitionName;
        $this->stateMachine = $stateMachine;
    }

    /**
     * @return object
     */
    public function getSubject(): object
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getTransitionName(): string
    {
        return $this->transitionName;
    }

    /**
     * @return StateMachineInterface
     */
    public function getStateMachine(): StateMachineInterface
    {
        return $this->stateMachine;
    }
}
