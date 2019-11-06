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
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
interface DefinitionValidatorInterface
{
    /**
     * @param Definition $definition
     * @param string $name
     * @throws InvalidDefinitionException on invalid definition
     */
    public function validate(Definition $definition, string $name): void;
}
