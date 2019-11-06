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

namespace MattyG\StateMachine\Tests\SupportStrategy;

use PHPUnit\Framework\TestCase;
use MattyG\StateMachine\SupportStrategy\InstanceOfSupportStrategy;
use MattyG\StateMachine\StateMachine;

class InstanceOfSupportStrategyTest extends TestCase
{
    public function testSupportsIfClassInstance()
    {
        $strategy = new InstanceOfSupportStrategy(Subject1::class);

        $this->assertTrue($strategy->supports($this->createStateMachine(), new Subject1()));
    }

    public function testSupportsIfNotClassInstance()
    {
        $strategy = new InstanceOfSupportStrategy(Subject2::class);

        $this->assertFalse($strategy->supports($this->createStateMachine(), new Subject1()));
    }

    private function createStateMachine()
    {
        return $this->getMockBuilder(StateMachine::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}

class Subject1
{
}
class Subject2
{
}
