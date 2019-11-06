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

namespace MattyG\StateMachine\Tests\Validator;

use PHPUnit\Framework\TestCase;
use MattyG\StateMachine\Definition;
use MattyG\StateMachine\Exception\InvalidDefinitionException;
use MattyG\StateMachine\Tests\StateMachineBuilderTrait;
use MattyG\StateMachine\Transition;
use MattyG\StateMachine\Validator\WorkflowValidator;

class WorkflowValidatorTest extends TestCase
{
    //use StateMachineBuilderTrait;

    public function testWorkflowWithInvalidNames()
    {
        $this->expectException(InvalidDefinitionException::class);
        $this->expectExceptionMessage('All transitions for a place must have a unique name. Multiple transitions named "t1" were found for place "a" in workflow "foo".');
        $places = range('a', 'c');

        $transitions = [];
        $transitions[] = new Transition('t0', 'c', 'b');
        $transitions[] = new Transition('t1', 'a', 'b');
        $transitions[] = new Transition('t1', 'a', 'c');

        $definition = new Definition($places, $transitions);

        (new WorkflowValidator())->validate($definition, 'foo');
    }

    public function testSameTransitionNameButNotSamePlace()
    {
        $places = range('a', 'd');

        $transitions = [];
        $transitions[] = new Transition('t1', 'a', 'b');
        $transitions[] = new Transition('t1', 'b', 'c');
        $transitions[] = new Transition('t1', 'd', 'c');

        $definition = new Definition($places, $transitions);

        (new WorkflowValidator())->validate($definition, 'foo');

        // the test ensures that the validation does not fail (i.e. it does not throw any exceptions)
        $this->addToAssertionCount(1);
    }
}
