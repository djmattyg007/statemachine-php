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

namespace MattyG\StateMachine\Event;

final class BasicEventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, callable[]>
     */
    private $listeners = [];

    /**
     * @param string $eventName
     * @param callable $listener
     */
    public function addListener(string $eventName, callable $listener): void
    {
        if (isset($this->listeners[$eventName]) === false) {
            $this->listeners[$eventName] = [];
        }

        $this->listeners[$eventName][] = $listener;
    }

    /**
     * @param string $eventName
     * @param Event $event
     */
    public function dispatch(string $eventName, Event $event): void
    {
        if (isset($this->listeners[$eventName]) === false) {
            $this->listeners[$eventName] = [];
        }

        foreach ($this->listeners[$eventName] as $listener) {
            $listener($event);
        }
    }
}
