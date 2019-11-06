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

use MattyG\StateMachine\Transition;

class GuardExpression
{
    private $transition;
    private $expression;

    public function __construct(Transition $transition, string $expression)
    {
        $this->transition = $transition;
        $this->expression = $expression;
    }

    public function getTransition()
    {
        return $this->transition;
    }

    public function getExpression()
    {
        return $this->expression;
    }
}
