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

namespace MattyG\StateMachine\Validator;

use MattyG\StateMachine\Definition;
use MattyG\StateMachine\Exception\InvalidDefinitionException;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class StateMachineValidator implements DefinitionValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(Definition $definition, string $name): void
    {
        $transitionFromNames = [];
        foreach ($definition->getTransitions() as $transition) {
            // Enforcing uniqueness of the names of transitions starting at each node
            $from = $transition->getFrom();
            if (isset($transitionFromNames[$from][$transition->getName()])) {
                throw new InvalidDefinitionException(sprintf('A transition from a place/state must have an unique name. Multiple transitions named "%s" from place/state "%s" were found in state machine "%s".', $transition->getName(), $from, $name));
            }

            $transitionFromNames[$from][$transition->getName()] = true;
        }
    }
}
