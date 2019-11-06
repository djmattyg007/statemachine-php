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

namespace MattyG\StateMachine\Tests\Dumper;

use PHPUnit\Framework\TestCase;
use MattyG\StateMachine\Dumper\PlantUmlDumper;
use MattyG\StateMachine\Tests\StateMachineBuilderTrait;

class PlantUmlDumperTest extends TestCase
{
    use StateMachineBuilderTrait;

    /**
     * @dataProvider provideStateMachineDefinitionWithoutState
     */
    public function testDumpStateMachineWithoutState($definition, ?string $state, string $expectedFileName, string $title)
    {
        $dumper = new PlantUmlDumper();
        $dump = $dumper->dump($definition, $state, ['title' => $title]);
        // handle windows, and avoid to create more fixtures
        $dump = str_replace(PHP_EOL, "\n", $dump.PHP_EOL);
        $file = $this->getFixturePath($expectedFileName, 'arrow');
        $this->assertStringEqualsFile($file, $dump);
    }

    public function provideStateMachineDefinitionWithoutState()
    {
        yield [$this->createComplexStateMachineDefinition2(), null, 'complex-state-machine-nostate', 'SimpleDiagram'];
        yield [$this->createComplexStateMachineDefinition2(), 'c', 'complex-state-machine-state', 'SimpleDiagram'];
    }

    private function getFixturePath(string $name, string $transitionType)
    {
        return __DIR__.'/../fixtures/puml/'.$transitionType.'/'.$name.'.puml';
    }
}
