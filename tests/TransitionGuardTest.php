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

namespace MattyG\StateMachine\Tests;

use MattyG\StateMachine\Exception\LogicException;
use MattyG\StateMachine\TransitionGuardManager;
use MattyG\StateMachine\TransitionGuardManagerBuilder;
use MattyG\StateMachine\TransitionInterface;
use MattyG\StateMachine\StateMachineInterface;
use PHPUnit\Framework\TestCase;

class TransitionGuardTest extends TestCase
{
    /**
     * @var Subject
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new Subject('a');
    }

    public function testBuilder()
    {
        $builder = new TransitionGuardManagerBuilder();
        $builder->addAvailabilityGuard(function (): bool {
            return true;
        });
        $builder->addLeaveGuard(function (): void {
            throw new LogicException();
        });
        $builder->addEnterGuard(function (): void {
            throw new LogicException();
        });

        $manager = $builder->build();
        $this->assertInstanceOf(TransitionGuardManager::class, $manager);
    }
}
