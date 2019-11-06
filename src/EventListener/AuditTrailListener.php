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

namespace MattyG\StateMachine\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MattyG\StateMachine\Event\Event;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class AuditTrailListener implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Event $event
     */
    public function onLeave(Event $event)
    {
        $this->logger->info(sprintf(
            'Leaving "%s" for subject of class "%s" in state machine "%s".',
            $event->getPreviousState(),
            \get_class($event->getSubject()),
            $event->getStateMachineName()
        ));
    }

    /**
     * @param Event $event
     */
    public function onTransition(Event $event)
    {
        $transition = $event->getTransition();
        $this->logger->info(sprintf(
            'Transition "%s" for subject of class "%s" in state machine "%s".',
            $transition->getName(),
            \get_class($event->getSubject()),
            $event->getStateMachineName()
        ));
    }

    /**
     * @param Event $event
     */
    public function onEnter(Event $event)
    {
        $this->logger->info(sprintf(
            'Entering "%s" for subject of class "%s" in state machine "%s".',
            $event->getNewState(),
            \get_class($event->getSubject()),
            $event->getStateMachineName()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'statemachine.leave' => ['onLeave'],
            'statemachine.transition' => ['onTransition'],
            'statemachine.enter' => ['onEnter'],
        ];
    }
}
