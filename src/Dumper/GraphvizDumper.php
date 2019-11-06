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

namespace MattyG\StateMachine\Dumper;

use MattyG\StateMachine\Definition;

/**
 * GraphvizDumper dumps a state machine as a graphviz file.
 *
 * You can convert the generated dot file with the dot utility (https://graphviz.org/):
 *
 *   dot -Tpng statemachine.dot > statemachine.png
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class GraphvizDumper implements DumperInterface
{
    protected static $defaultOptions = [
        'graph' => ['ratio' => 'compress', 'rankdir' => 'LR'],
        'node' => ['fontsize' => 9, 'fontname' => 'Arial', 'color' => '#333333', 'fillcolor' => 'lightblue', 'fixedsize' => 'false', 'width' => 1],
        'edge' => ['fontsize' => 9, 'fontname' => 'Arial', 'color' => '#333333', 'arrowhead' => 'normal', 'arrowsize' => 0.5],
    ];

    /**
     * {@inheritdoc}
     *
     * Dumps the state machine as a graphviz graph.
     *
     * Available options:
     *
     *  * graph: The default options for the whole graph
     *  * node: The default options for nodes (places + transitions)
     *  * edge: The default options for edges
     */
    public function dump(Definition $definition, ?string $state = null, array $options = []): string
    {
        $places = $this->findPlaces($definition, $state);
        $transitions = $this->findTransitions($definition);
        $edges = $this->findEdges($definition);

        /** @var array $options */
        $options = array_replace_recursive(self::$defaultOptions, $options);

        return $this->startDot($options)
            .$this->addPlaces($places)
            .$this->addTransitions($transitions)
            .$this->addEdges($edges)
            .$this->endDot();
    }

    /**
     * @param Definition $definition
     * @param string|null $state
     * @return array<string, array{attributes: array}>
     */
    protected function findPlaces(Definition $definition, ?string $state = null): array
    {
        $stateMachineMetadata = $definition->getMetadataStore();

        $places = [];

        foreach ($definition->getPlaces() as $place) {
            $attributes = [];
            if ($place === $definition->getInitialPlace()) {
                $attributes['style'] = 'filled';
            }
            if ($state && $state === $place) {
                $attributes['color'] = '#FF0000';
                $attributes['shape'] = 'doublecircle';
            }
            $backgroundColor = $stateMachineMetadata->getMetadata('bg_color', $place);
            if (null !== $backgroundColor) {
                $attributes['style'] = 'filled';
                $attributes['fillcolor'] = $backgroundColor;
            }
            $label = $stateMachineMetadata->getMetadata('label', $place);
            if (null !== $label) {
                $attributes['name'] = $label;
            }
            $places[$place] = [
                'attributes' => $attributes,
            ];
        }

        return $places;
    }

    /**
     * @return array<int, array{attributes: array, name: string}>
     */
    protected function findTransitions(Definition $definition): array
    {
        $stateMachineMetadata = $definition->getMetadataStore();

        $transitions = [];

        foreach ($definition->getTransitions() as $transition) {
            $attributes = ['shape' => 'box', 'regular' => true];

            $backgroundColor = $stateMachineMetadata->getMetadata('bg_color', $transition);
            if (null !== $backgroundColor) {
                $attributes['style'] = 'filled';
                $attributes['fillcolor'] = $backgroundColor;
            }
            $name = $stateMachineMetadata->getMetadata('label', $transition) ?? $transition->getName();

            $transitions[] = [
                'attributes' => $attributes,
                'name' => $name,
            ];
        }

        return $transitions;
    }

    /**
     * @param array<string, array{attributes: array}> $places
     * @return string
     */
    protected function addPlaces(array $places): string
    {
        $code = '';

        foreach ($places as $id => $place) {
            if (isset($place['attributes']['name'])) {
                $placeName = $place['attributes']['name'];
                unset($place['attributes']['name']);
            } else {
                $placeName = $id;
            }

            $code .= sprintf("  place_%s [label=\"%s\", shape=circle%s];\n", $this->dotize($id), $this->escape($placeName), $this->addAttributes($place['attributes']));
        }

        return $code;
    }

    /**
     * @param array<int, array{attributes: array, name: string}> $transitions
     * @return string
     */
    protected function addTransitions(array $transitions): string
    {
        $code = '';

        foreach ($transitions as $i => $place) {
            $code .= sprintf("  transition_%s [label=\"%s\",%s];\n", $this->dotize((string) $i), $this->escape($place['name']), $this->addAttributes($place['attributes']));
        }

        return $code;
    }

    /**
     * @param Definition $definition
     * @return array<int, array{from: string, to: string, direction: string, transition_number: int}>
     */
    protected function findEdges(Definition $definition): array
    {
        $stateMachineMetadata = $definition->getMetadataStore();

        $dotEdges = [];

        foreach ($definition->getTransitions() as $i => $transition) {
            $transitionName = $stateMachineMetadata->getMetadata('label', $transition) ?? $transition->getName();

            $dotEdges[] = [
                'from' => $transition->getFrom(),
                'to' => $transitionName,
                'direction' => 'from',
                'transition_number' => $i,
            ];
            $dotEdges[] = [
                'from' => $transitionName,
                'to' => $transition->getTo(),
                'direction' => 'to',
                'transition_number' => $i,
            ];
        }

        return $dotEdges;
    }

    /**
     * @param array<int, array{from: string, to: string, direction: string, transition_number: int}> $edges
     * @return string
     */
    protected function addEdges(array $edges): string
    {
        $code = '';

        foreach ($edges as $edge) {
            if ('from' === $edge['direction']) {
                $code .= sprintf("  place_%s -> transition_%s [style=\"solid\"];\n",
                    $this->dotize($edge['from']),
                    $this->dotize((string) $edge['transition_number'])
                );
            } else {
                $code .= sprintf("  transition_%s -> place_%s [style=\"solid\"];\n",
                    $this->dotize((string) $edge['transition_number']),
                    $this->dotize($edge['to'])
                );
            }
        }

        return $code;
    }

    /**
     * @internal
     */
    protected function startDot(array $options): string
    {
        return sprintf("digraph workflow {\n  %s\n  node [%s];\n  edge [%s];\n\n",
            $this->addOptions($options['graph']),
            $this->addOptions($options['node']),
            $this->addOptions($options['edge'])
        );
    }

    /**
     * @internal
     */
    protected function endDot(): string
    {
        return "}\n";
    }

    /**
     * @internal
     */
    protected function dotize(string $id): string
    {
        return hash('sha1', $id);
    }

    /**
     * @internal
     */
    protected function escape($value): string
    {
        return \is_bool($value) ? ($value ? '1' : '0') : addslashes($value);
    }

    protected function addAttributes(array $attributes): string
    {
        $code = [];

        foreach ($attributes as $k => $v) {
            $code[] = sprintf('%s="%s"', $k, $this->escape($v));
        }

        return $code ? ' '.implode(' ', $code) : '';
    }

    private function addOptions(array $options): string
    {
        $code = [];

        foreach ($options as $k => $v) {
            $code[] = sprintf('%s="%s"', $k, $v);
        }

        return implode(' ', $code);
    }
}
