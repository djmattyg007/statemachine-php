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

use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use MattyG\StateMachine\Event\GuardEvent;
use MattyG\StateMachine\Exception\InvalidTokenConfigurationException;
use MattyG\StateMachine\TransitionBlocker;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class GuardListener
{
    private $configuration;
    private $expressionLanguage;
    private $tokenStorage;
    private $authorizationChecker;
    private $trustResolver;
    private $roleHierarchy;
    private $validator;

    public function __construct(array $configuration, ExpressionLanguage $expressionLanguage, TokenStorageInterface $tokenStorage, AuthorizationCheckerInterface $authorizationChecker, AuthenticationTrustResolverInterface $trustResolver, RoleHierarchyInterface $roleHierarchy = null, ValidatorInterface $validator = null)
    {
        $this->configuration = $configuration;
        $this->expressionLanguage = $expressionLanguage;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->trustResolver = $trustResolver;
        $this->roleHierarchy = $roleHierarchy;
        $this->validator = $validator;
    }

    public function onTransition(GuardEvent $event, string $eventName)
    {
        if (!isset($this->configuration[$eventName])) {
            return;
        }

        $eventConfiguration = (array) $this->configuration[$eventName];
        foreach ($eventConfiguration as $guard) {
            if ($guard instanceof GuardExpression) {
                if ($guard->getTransition() !== $event->getTransition()) {
                    continue;
                }
                $this->validateGuardExpression($event, $guard->getExpression());
            } else {
                $this->validateGuardExpression($event, $guard);
            }
        }
    }

    private function validateGuardExpression(GuardEvent $event, string $expression)
    {
        if (!$this->expressionLanguage->evaluate($expression, $this->getVariables($event))) {
            $blocker = TransitionBlocker::createBlockedByExpressionGuardListener($expression);
            $event->addTransitionBlocker($blocker);
        }
    }

    // code should be sync with Symfony\Component\Security\Core\Authorization\Voter\ExpressionVoter
    private function getVariables(GuardEvent $event): array
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token) {
            throw new InvalidTokenConfigurationException(sprintf('There are no tokens available for state machine %s.', $event->getStateMachineName()));
        }

        $variables = [
            'token' => $token,
            'user' => $token->getUser(),
            'subject' => $event->getSubject(),
            'role_names' => $this->roleHierarchy->getReachableRoleNames($token->getRoleNames()),
            // needed for the is_granted expression function
            'auth_checker' => $this->authorizationChecker,
            // needed for the is_* expression function
            'trust_resolver' => $this->trustResolver,
            // needed for the is_valid expression function
            'validator' => $this->validator,
        ];

        return $variables;
    }
}
