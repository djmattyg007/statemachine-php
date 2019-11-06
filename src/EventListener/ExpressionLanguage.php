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

/**
 * Adds some function to the default Symfony Security ExpressionLanguage.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExpressionLanguage extends BaseExpressionLanguage
{
    protected function registerFunctions()
    {
        parent::registerFunctions();

        $this->register('is_granted', function ($attributes, $object = 'null') {
            return sprintf('$auth_checker->isGranted(%s, %s)', $attributes, $object);
        }, function (array $variables, $attributes, $object = null) {
            return $variables['auth_checker']->isGranted($attributes, $object);
        });

        $this->register('is_valid', function ($object = 'null', $groups = 'null') {
            return sprintf('0 === count($validator->validate(%s, null, %s))', $object, $groups);
        }, function (array $variables, $object = null, $groups = null) {
            if (!$variables['validator'] instanceof ValidatorInterface) {
                throw new RuntimeException('"is_valid" cannot be used as the Validator component is not installed.');
            }

            $errors = $variables['validator']->validate($object, null, $groups);

            return 0 === \count($errors);
        });
    }
}
