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

interface EventDispatcherInterface
{
    /**
     * @param string $eventName
     * @param Event $event
     */
    public function dispatch(string $eventName, Event $event): void;
}
