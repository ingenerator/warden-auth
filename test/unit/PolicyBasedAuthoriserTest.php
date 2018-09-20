<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace test\unit\Ingenerator\Warden\Auth;


use Ingenerator\Warden\Auth\AccessControlDecision;
use Ingenerator\Warden\Auth\AccessControlResource;
use Ingenerator\Warden\Auth\AccessDeniedException;
use Ingenerator\Warden\Auth\DefaultAccessControlEnforcer;
use Ingenerator\Warden\Auth\Policy\AbstractAccessPolicy;
use Ingenerator\Warden\Auth\PolicyBasedAuthoriser;
use Ingenerator\Warden\Auth\TestSupport\PolicyMocker;
use test\mock\Ingenerator\Warden\Auth\DummyAccessControlResource;

class PolicyBasedAuthoriserTest extends \PHPUnit\Framework\TestCase
{

    protected $enforcer;

    protected $policies = [];

    public function test_it_is_initialisable()
    {
        $this->assertInstanceOf(PolicyBasedAuthoriser::class, $this->newSubject());
    }

    /**
     * @expectedException \ErrorException
     */
    public function test_it_throws_if_provided_with_an_invalid_policy_class()
    {
        $this->policies = [new \stdClass];
        $this->newSubject();
    }

    /**
     * @expectedException \DomainException
     */
    public function test_it_throws_if_policies_define_non_unique_actions()
    {
        $this->policies = [
            PolicyMocker::stub(FirstPolicy::class)->getPolicy(),
            PolicyMocker::stub(ConflictPolicy::class)->getPolicy(),
        ];
        $this->newSubject();
    }

    /**
     * @expectedException \OutOfBoundsException
     */
    public function test_it_throws_if_asked_to_authorise_an_unknown_action()
    {
        $this->policies = [\Ingenerator\Warden\Auth\TestSupport\PolicyMocker::stub(FirstPolicy::class)->getPolicy()];
        $this->newSubject()->decide('do-some-junk');
    }

    public function provider_expected_decisions()
    {
        $res = new DummyAccessControlResource;

        return [
            [
                [PolicyMocker::stub(FirstPolicy::class)->getPolicy()],
                FirstPolicy::ACTION_FIRST,
                NULL,
                FALSE,
            ],
            [
                [PolicyMocker::stub(FirstPolicy::class)->allowAny(FirstPolicy::ACTION_FIRST)->getPolicy()],
                FirstPolicy::ACTION_FIRST,
                NULL,
                TRUE,
            ],
            [
                [PolicyMocker::stub(FirstPolicy::class)->allow($res, FirstPolicy::ACTION_SECOND)->getPolicy()],
                FirstPolicy::ACTION_FIRST,
                NULL,
                FALSE,
            ],
            [
                [PolicyMocker::stub(FirstPolicy::class)->allow($res, FirstPolicy::ACTION_SECOND)->getPolicy()],
                FirstPolicy::ACTION_SECOND,
                NULL,
                FALSE,
            ],
            [
                [PolicyMocker::stub(FirstPolicy::class)->allow($res, FirstPolicy::ACTION_SECOND)->getPolicy()],
                FirstPolicy::ACTION_SECOND,
                $res,
                TRUE,
            ],
            [
                [
                    PolicyMocker::stub(FirstPolicy::class)->getPolicy(),
                    \Ingenerator\Warden\Auth\TestSupport\PolicyMocker::stub(SecondPolicy::class)->allow($res, SecondPolicy::ACTION_FIRST)->getPolicy(),
                ],
                SecondPolicy::ACTION_FIRST,
                $res,
                TRUE,
            ],
        ];
    }

    /**
     * @dataProvider provider_expected_decisions
     */
    public function test_it_returns_decision_for_decide_action($policies, $action, $resource, $expect_ok)
    {
        $this->policies = $policies;
        $decision       = $this->newSubject()->decide($action, $resource);
        $this->assertInstanceOf(AccessControlDecision::class, $decision);
        $this->assertSame($expect_ok, $decision->isAllowed());
    }


    /**
     * @dataProvider provider_expected_decisions
     */
    public function test_it_returns_boolean_for_can_action($policies, $action, $resource, $expect_ok)
    {
        $this->policies = $policies;
        $this->assertSame($expect_ok, $this->newSubject()->can($action, $resource));
    }


    /**
     * @dataProvider provider_expected_decisions
     */
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

    public function setUp()
    {
        parent::setUp();
        $this->enforcer = new DefaultAccessControlEnforcer;
    }

    /**
     * @return PolicyBasedAuthoriser
     */
    protected function newSubject()
    {
        $refl = new \ReflectionClass(PolicyBasedAuthoriser::class);

        return $refl->newInstanceArgs(array_merge([$this->enforcer], $this->policies));
    }
}

class FirstPolicy extends AbstractAccessPolicy
{
    const ACTION_FIRST  = 'first.one';
    const ACTION_SECOND = 'first.two';

    protected function supportsResource(AccessControlResource $resource, $action)
    {
        return TRUE;
    }
}

class SecondPolicy extends AbstractAccessPolicy
{
    const ACTION_FIRST  = 'second.one';
    const ACTION_SECOND = 'second.two';

    protected function supportsResource(AccessControlResource $resource, $action)
    {
        return TRUE;
    }

}

class ConflictPolicy extends AbstractAccessPolicy
{
    const ACTION_SECOND = 'first.two';

    protected function supportsResource(AccessControlResource $resource, $action)
    {
        return TRUE;
    }

}

