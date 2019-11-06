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

namespace MattyG\StateMachine\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use MattyG\StateMachine\EventListener\AuditTrailListener;
use MattyG\StateMachine\Tests\Subject;
use MattyG\StateMachine\Tests\StateMachineBuilderTrait;
use MattyG\StateMachine\StateMachine;

class AuditTrailListenerTest extends TestCase
{
    use StateMachineBuilderTrait;

    public function testItWorks()
    {
        $definition = $this->createSimpleStateMachineDefinition();

        $object = new Subject('a');

        $logger = new Logger();

        $ed = new EventDispatcher();
        $ed->addSubscriber(new AuditTrailListener($logger));

        $stateMachine = new StateMachine($definition, null, $ed);

        $stateMachine->apply($object, 't1');

        $expected = [
            'Leaving "a" for subject of class "MattyG\StateMachine\Tests\Subject" in state machine "unnamed".',
            'Transition "t1" for subject of class "MattyG\StateMachine\Tests\Subject" in state machine "unnamed".',
            'Entering "b" for subject of class "MattyG\StateMachine\Tests\Subject" in state machine "unnamed".',
        ];

        $this->assertSame($expected, $logger->logs);
    }
}

class Logger extends AbstractLogger
{
    public $logs = [];

    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = $message;
    }
}
