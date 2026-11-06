<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace test\unit\Ingenerator\Warden\Auth;


use PHPUnit\Framework\TestCase;
use stdClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use DomainException;
use Ingenerator\Warden\Auth\AccessControlDecision;
use Ingenerator\Warden\Auth\AccessControlResource;
use Ingenerator\Warden\Auth\AccessDeniedException;
use Ingenerator\Warden\Auth\DefaultAccessControlEnforcer;
use Ingenerator\Warden\Auth\Policy\AbstractAccessPolicy;
use Ingenerator\Warden\Auth\PolicyBasedAuthoriser;
use InvalidArgumentException;
use OutOfBoundsException;
use test\mock\Ingenerator\Warden\Auth\DummyAccessControlResource;

class PolicyBasedAuthoriserTest extends TestCase
{

    protected $enforcer;

    protected $policies = [];

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(PolicyBasedAuthoriser::class, $this->newSubject());
    }

    public function test_it_throws_if_provided_with_an_invalid_policy_class()
    {
        $this->policies = [new stdClass];
        $this->expectException(InvalidArgumentException::class);
        $this->newSubject();
    }

    public function test_it_throws_if_policies_define_non_unique_actions()
    {
        $this->policies = [
            new FirstPolicy,
            new ConflictPolicy,
        ];
        $this->expectException(DomainException::class);
        $this->expectExceptionMessageIsOrContains('Duplicate action');
        $this->newSubject();
    }

    public function test_it_throws_if_asked_to_authorise_an_unknown_action()
    {
        $this->policies = [new FirstPolicy];
        $this->expectException(OutOfBoundsException::class);
        $this->newSubject()->decide('do-some-junk');
    }

    public static function provider_expected_decisions()
    {
        $res = new DummyAccessControlResource;

        return [
            [
                [new FirstPolicy],
                FirstPolicy::ACTION_FIRST,
                NULL,
                FALSE,
            ],
            [
                [(new FirstPolicy)->allowAny(FirstPolicy::ACTION_FIRST)],
                FirstPolicy::ACTION_FIRST,
                NULL,
                TRUE,
            ],
            [
                [(new FirstPolicy)->allow($res, FirstPolicy::ACTION_SECOND)],
                FirstPolicy::ACTION_FIRST,
                NULL,
                FALSE,
            ],
            [
                [(new FirstPolicy)->allow($res, FirstPolicy::ACTION_SECOND)],
                FirstPolicy::ACTION_SECOND,
                NULL,
                FALSE,
            ],
            [
                [(new FirstPolicy)->allow($res, FirstPolicy::ACTION_SECOND)],
                FirstPolicy::ACTION_SECOND,
                $res,
                TRUE,
            ],
            [
                [
                    new FirstPolicy,
                    (new SecondPolicy)->allow($res, SecondPolicy::ACTION_FIRST),
                ],
                SecondPolicy::ACTION_FIRST,
                $res,
                TRUE,
            ],
        ];
    }

    #[DataProvider('provider_expected_decisions')]
    public function test_it_returns_decision_for_decide_action($policies, $action, $resource, $expect_ok)
    {
        $this->policies = $policies;
        $decision       = $this->newSubject()->decide($action, $resource);
        $this->assertInstanceOf(AccessControlDecision::class, $decision);
        $this->assertSame($expect_ok, $decision->isAllowed());
    }


    #[DataProvider('provider_expected_decisions')]
    public function test_it_returns_boolean_for_can_action($policies, $action, $resource, $expect_ok)
    {
        $this->policies = $policies;
        $this->assertSame($expect_ok, $this->newSubject()->can($action, $resource));
    }


    #[DataProvider('provider_expected_decisions')]
    public function test_it_enforces_decisions_with_enforcer($policies, $action, $resource, $expect_ok)
    {
        $this->policies = $policies;
        try {
            $this->newSubject()->enforce($action, $resource);
            $denied_exception = NULL;
        } catch (AccessDeniedException $denied_exception) {

        }
        if ($expect_ok) {
            $this->assertNull($denied_exception, 'Should not have thrown');
        } else {
            $this->assertInstanceOf(AccessDeniedException::class, $denied_exception, 'Should have thrown');
            $this->assertSame($action, $denied_exception->getDecision()->getAction());
        }
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->enforcer = new DefaultAccessControlEnforcer;
    }

    /**
     * @return PolicyBasedAuthoriser
     */
    protected function newSubject()
    {
        $refl = new ReflectionClass(PolicyBasedAuthoriser::class);

        return $refl->newInstanceArgs(\array_merge([$this->enforcer], $this->policies));
    }
}

abstract class StubPolicy extends AbstractAccessPolicy
{
    protected array $allowed_actions = [];

    public function allow(AccessControlResource $resource, string $action): static
    {
        $this->allowed_actions[$action][] = $resource;
        return $this;
    }

    public function allowAny(string $action): static
    {
        $this->allowed_actions[$action] = true;
        return $this;
    }

    protected function supportsResource(AccessControlResource $resource, string $action): bool
    {
        return true;
    }

    protected function doDecide(AccessControlResource $resource, string $action): AccessControlDecision
    {
        $allow = $this->allowed_actions[$action] ?? null;
        if ($allow === true) {
            return AccessControlDecision::allowed($resource, $action);
        } elseif (\is_array($allow) && \in_array($resource, $allow, true)) {
            return AccessControlDecision::allowed($resource, $action);
        } else {
            return AccessControlDecision::denied($resource, $action, 'stub.not-allowed');
        }
    }
}

class FirstPolicy extends StubPolicy
{
    const ACTION_FIRST  = 'first.one';
    const ACTION_SECOND = 'first.two';
}

class SecondPolicy extends StubPolicy
{
    const ACTION_FIRST  = 'second.one';
    const ACTION_SECOND = 'second.two';
}

class ConflictPolicy extends StubPolicy
{
    const ACTION_SECOND = 'first.two';
}

