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

namespace MattyG\StateMachine\Tests;

final class Subject
{
    private $state;
    private $context;

    public function __construct($state = null)
    {
        $this->state = $state;
        $this->context = [];
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state, array $context = [])
    {
        $this->state = $state;
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
