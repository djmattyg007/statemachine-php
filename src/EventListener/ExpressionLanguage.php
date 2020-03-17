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

namespace MattyG\StateMachine\EventListener;

use Symfony\Component\Security\Core\Authorization\ExpressionLanguage as BaseExpressionLanguage;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use MattyG\StateMachine\Exception\RuntimeException;

use function count;
use function sprintf;

/**
 * Adds some function to the default Symfony Security ExpressionLanguage.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExpressionLanguage extends BaseExpressionLanguage
{
    protected function registerFunctions(): void
    {
        parent::registerFunctions();

        $this->register('is_granted', function ($attributes, $object = 'null'): string {
            return sprintf('$auth_checker->isGranted(%s, %s)', $attributes, $object);
        }, function (array $variables, $attributes, $object = null): bool {
            return $variables['auth_checker']->isGranted($attributes, $object);
        });

        $this->register('is_valid', function ($object = 'null', $groups = 'null'): string {
            return sprintf('count($validator->validate(%s, null, %s)) === 0', $object, $groups);
        }, function (array $variables, $object = null, $groups = null): bool {
            if (!$variables['validator'] instanceof ValidatorInterface) {
                throw new RuntimeException('"is_valid" cannot be used as the Validator component is not installed.');
            }

            $errors = $variables['validator']->validate($object, null, $groups);

            return count($errors) === 0;
        });
    }
}
