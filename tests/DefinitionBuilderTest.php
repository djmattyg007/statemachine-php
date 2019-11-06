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

namespace MattyG\StateMachine\Tests;

use PHPUnit\Framework\TestCase;
use MattyG\StateMachine\DefinitionBuilder;
use MattyG\StateMachine\Metadata\InMemoryMetadataStore;
use MattyG\StateMachine\Transition;

class DefinitionBuilderTest extends TestCase
{
    public function testSetInitialPlace()
    {
        $builder = new DefinitionBuilder(['a', 'b'], [new Transition('a_to_b', 'a', 'b')]);
        $builder->setInitialPlace('b');
        $definition = $builder->build();

        $this->assertEquals('b', $definition->getInitialPlace());
    }

    public function testAddTransition()
    {
        $places = range('a', 'b');

        $transition0 = new Transition('name0', $places[0], $places[1]);
        $transition1 = new Transition('name1', $places[0], $places[1]);
        $builder = new DefinitionBuilder($places, [$transition0]);
        $builder->addTransition($transition1);

        $definition = $builder->build();

        $this->assertCount(2, $definition->getTransitions());
        $this->assertSame($transition0, $definition->getTransitions()[0]);
        $this->assertSame($transition1, $definition->getTransitions()[1]);
    }

    public function testAddPlace()
    {
        $builder1 = new DefinitionBuilder(['a'], []);
        $builder1->addPlace('b');
        $builder1->addTransition(new Transition('a_to_b', 'a', 'b'));

        $definition1 = $builder1->build();

        $this->assertCount(2, $definition1->getPlaces());
        $this->assertEquals('a', $definition1->getPlaces()['a']);
        $this->assertEquals('b', $definition1->getPlaces()['b']);

        $builder2 = new DefinitionBuilder(['a'], [new Transition('a_to_b', 'a', 'b')]);
        $builder2->addPlace('b');

        $definition2 = $builder2->build();

        $this->assertCount(2, $definition2->getPlaces());
        $this->assertEquals('a', $definition2->getPlaces()['a']);
        $this->assertEquals('b', $definition2->getPlaces()['b']);
    }

    public function testSetMetadataStore()
    {
        $builder = new DefinitionBuilder(['a', 'b'], [new Transition('a_to_b', 'a', 'b')]);
        $metadataStore = new InMemoryMetadataStore();
        $builder->setMetadataStore($metadataStore);
        $definition = $builder->build();

        $this->assertSame($metadataStore, $definition->getMetadataStore());
    }
}
