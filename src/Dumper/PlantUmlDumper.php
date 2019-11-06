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

use InvalidArgumentException;
use MattyG\StateMachine\Definition;
use MattyG\StateMachine\Metadata\MetadataStoreInterface;
use MattyG\StateMachine\TransitionInterface;

/**
 * PlantUmlDumper dumps a state machine as a PlantUML file.
 *
 * You can convert the generated puml file with the plantuml.jar utility (http://plantuml.com/):
 *
 * php bin/console workflow:dump pull_request travis --dump-format=puml | java -jar plantuml.jar -p  > statemachine.png
 *
 * @author Sébastien Morel <morel.seb@gmail.com>
 */
class PlantUmlDumper implements DumperInterface
{
    private const INITIAL = '<<initial>>';
    private const MARKED = '<<marked>>';

    const DEFAULT_OPTIONS = [
        'skinparams' => [
            'titleBorderRoundCorner' => 15,
            'titleBorderThickness' => 2,
            'state' => [
                'BackgroundColor'.self::INITIAL => '#87b741',
                'BackgroundColor'.self::MARKED => '#3887C6',
                'BorderColor' => '#3887C6',
                'BorderColor'.self::MARKED => 'Black',
                'FontColor'.self::MARKED => 'White',
            ],
            'agent' => [
                'BackgroundColor' => '#ffffff',
                'BorderColor' => '#3887C6',
            ],
        ],
    ];

    public function dump(Definition $definition, ?string $state = null, array $options = []): string
    {
        /** @var array $options */
        $options = array_replace_recursive(self::DEFAULT_OPTIONS, $options);

        $stateMachineMetadata = $definition->getMetadataStore();

        $code = $this->initialize($options, $definition);

        foreach ($definition->getPlaces() as $place) {
            $code[] = $this->getState($place, $definition, $state);
        }
        foreach ($definition->getTransitions() as $transition) {
            $transitionEscaped = $this->escape($transition->getName());
            $fromEscaped = $this->escape($transition->getFrom());
            $toEscaped = $this->escape($transition->getTo());

            $transitionEscapedWithStyle = $this->getTransitionEscapedWithStyle($stateMachineMetadata, $transition, $transitionEscaped);

            $arrowColor = $stateMachineMetadata->getMetadata('arrow_color', $transition);

            $transitionColor = '';
            if ($arrowColor !== null) {
                $transitionColor = $this->getTransitionColor($arrowColor) ?? '';
            }

            $code[] = "$fromEscaped -${transitionColor}-> $toEscaped: $transitionEscapedWithStyle";
        }

        return $this->startPuml($options).$this->getLines($code).$this->endPuml($options);
    }

    private function startPuml(array $options): string
    {
        $start = '@startuml'.PHP_EOL;
        $start .= 'allow_mixing'.PHP_EOL;

        return $start;
    }

    private function endPuml(array $options): string
    {
        return PHP_EOL.'@enduml';
    }

    private function getLines(array $code): string
    {
        return implode(PHP_EOL, $code);
    }

    private function initialize(array $options, Definition $definition): array
    {
        $stateMachineMetadata = $definition->getMetadataStore();

        $code = [];
        if (isset($options['title'])) {
            $code[] = "title {$options['title']}";
        }
        if (isset($options['name'])) {
            $code[] = "title {$options['name']}";
        }

        // Add style from nodes
        foreach ($definition->getPlaces() as $place) {
            $backgroundColor = $stateMachineMetadata->getMetadata('bg_color', $place);
            if (null !== $backgroundColor) {
                $key = 'BackgroundColor<<'.$this->getColorId($backgroundColor).'>>';

                $options['skinparams']['state'][$key] = $backgroundColor;
            }
        }

        if (isset($options['skinparams']) && \is_array($options['skinparams'])) {
            foreach ($options['skinparams'] as $skinparamKey => $skinparamValue) {
                if ($skinparamKey === 'agent') {
                    continue;
                }
                if (!\is_array($skinparamValue)) {
                    $code[] = "skinparam {$skinparamKey} $skinparamValue";
                    continue;
                }
                $code[] = "skinparam {$skinparamKey} {";
                foreach ($skinparamValue as $key => $value) {
                    $code[] = "    {$key} $value";
                }
                $code[] = '}';
            }
        }

        return $code;
    }

    private function escape(string $string): string
    {
        // It's not possible to escape property double quote, so let's remove it
        return '"'.str_replace('"', '', $string).'"';
    }

    private function getState(string $place, Definition $definition, ?string $state = null): string
    {
        $stateMachineMetadata = $definition->getMetadataStore();

        $placeEscaped = $this->escape($place);

        $output = "state $placeEscaped".
            ($place === $definition->getInitialPlace() ? ' '.self::INITIAL : '').
            ($state === $place ? ' '.self::MARKED : '');

        $backgroundColor = $stateMachineMetadata->getMetadata('bg_color', $place);
        if (null !== $backgroundColor) {
            $output .= ' <<'.$this->getColorId($backgroundColor).'>>';
        }

        $description = $stateMachineMetadata->getMetadata('description', $place);
        if (null !== $description) {
            $output .= ' as '.$place.
                PHP_EOL.
                $place.' : '.$description;
        }

        return $output;
    }

    private function getTransitionEscapedWithStyle(MetadataStoreInterface $stateMachineMetadata, TransitionInterface $transition, string $to): string
    {
        $to = $stateMachineMetadata->getMetadata('label', $transition) ?? $to;

        $color = $stateMachineMetadata->getMetadata('color', $transition) ?? null;

        if (null !== $color) {
            $to = sprintf(
                '<font color=%1$s>%2$s</font>',
                $color,
                $to
            );
        }

        return $this->escape($to);
    }

    private function getTransitionColor(string $color): string
    {
        // PUML format requires that color in transition have to be prefixed with “#”.
        if ('#' !== substr($color, 0, 1)) {
            $color = '#'.$color;
        }

        return sprintf('[%s]', $color);
    }

    private function getColorId(string $color): string
    {
        // Remove “#“ from start of the color name so it can be used as an identifier.
        return ltrim($color, '#');
    }
}
