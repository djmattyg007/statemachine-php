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

use function sprintf;

/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class GuardListener
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var AuthenticationTrustResolverInterface
     */
    private $trustResolver;

    /**
     * @var RoleHierarchyInterface|null
     */
    private $roleHierarchy;

    /**
     * @var ValidatorInterface|null
     */
    private $validator;

    public function __construct(
        array $configuration,
        ExpressionLanguage $expressionLanguage,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        AuthenticationTrustResolverInterface $trustResolver,
        RoleHierarchyInterface $roleHierarchy = null,
        ValidatorInterface $validator = null
    ) {
        $this->configuration = $configuration;
        $this->expressionLanguage = $expressionLanguage;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->trustResolver = $trustResolver;
        $this->roleHierarchy = $roleHierarchy;
        $this->validator = $validator;
    }

    /**
     * @param GuardEvent $event
     * @param string $eventName
     */
    public function onTransition(GuardEvent $event, string $eventName): void
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

    /**
     * @param GuardEvent $event
     * @param string $expression
     */
    private function validateGuardExpression(GuardEvent $event, string $expression): void
    {
        if (!$this->expressionLanguage->evaluate($expression, $this->getVariables($event))) {
            $blocker = TransitionBlocker::createBlockedByExpressionGuardListener($expression);
            $event->addTransitionBlocker($blocker);
        }
    }

    /**
     * This code should be sync with Symfony\Component\Security\Core\Authorization\Voter\ExpressionVoter
     *
     * @param GuardEvent $event
     * @return array
     */
    private function getVariables(GuardEvent $event): array
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            throw new InvalidTokenConfigurationException(
                sprintf('There are no tokens available for state machine %s.', $event->getStateMachineName())
            );
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
