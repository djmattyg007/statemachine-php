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

namespace MattyG\StateMachine\Tests\StateAccessor;

use MattyG\StateMachine\Exception\LogicException;
use MattyG\StateMachine\StateAccessor\MethodStateAccessor;
use MattyG\StateMachine\Tests\Subject;
use PHPUnit\Framework\TestCase;

class MethodStateAccessorTest extends TestCase
{
    public function testValidGetter()
    {
        $subject = new Subject('a');
        $accessor = new MethodStateAccessor('getState', 'notused');

        $this->assertSame('a', $accessor->getState($subject));
    }

    public function testValidSetter()
    {
        $subject = new Subject('a');
        $accessor = new MethodStateAccessor('notused', 'setState');

        $accessor->setState($subject, 'b');

        $this->assertSame('b', $subject->getState());
    }

    public function testInvalidGetter()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Getter method "MattyG\\StateMachine\\Tests\\Subject::getStatus()" does not exist.');

        $subject = new Subject('a');
        $accessor = new MethodStateAccessor('getStatus', 'notused');

        $accessor->getState($subject);
    }

    public function testInvalidSetter()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Setter method "MattyG\\StateMachine\\Tests\\Subject::setStatus()" does not exist.');

        $subject = new Subject('a');
        $accessor = new MethodStateAccessor('notused', 'setStatus');

        $accessor->setState($subject, 'b');
    }

    public function testNamedConstructorFromProperty()
    {
        $subject = new Subject('a');
        $accessor = MethodStateAccessor::fromProperty('state');

        $this->assertSame('a', $accessor->getState($subject));

        $accessor->setState($subject, 'b');

        $this->assertSame('b', $subject->getState());
    }
}
