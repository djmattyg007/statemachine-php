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

/**
 * Thrown by GuardListener when there is no token set, but guards are placed on a transition.
 *
 * @author Matt Johnson <matj1985@gmail.com>
 */
class InvalidTokenConfigurationException extends LogicException
{
}
