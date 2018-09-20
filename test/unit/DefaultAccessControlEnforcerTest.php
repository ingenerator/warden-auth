<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace test\unit\Ingenerator\Warden\Auth;

use Ingenerator\Warden\Auth\AccessControlDecision;
use Ingenerator\Warden\Auth\AccessDeniedException;
use Ingenerator\Warden\Auth\DefaultAccessControlEnforcer;
use test\mock\Ingenerator\Warden\Auth\DummyAccessControlResource;

class DefaultAccessControlEnforcerTest extends \PHPUnit\Framework\TestCase
{

    public function test_it_does_nothing_when_decision_is_allowed()
    {
        $this->assertNull(
            $this->newSubject()->enforce(
                AccessControlDecision::allowed(new DummyAccessControlResource, 'any_action')
            )
        );
    }

    protected function newSubject()
    {
        return new DefaultAccessControlEnforcer;
    }

    public function test_it_is_initialisable()
    {
        $subject = $this->newSubject();
        $this->assertInstanceOf('Ingenerator\Warden\Auth\DefaultAccessControlEnforcer', $subject);
        $this->assertInstanceOf('Ingenerator\Warden\Auth\AccessControlEnforcer', $subject);
    }

    public function test_it_throws_when_decision_is_not_allowed()
    {
        $decision = AccessControlDecision::denied(
            new DummyAccessControlResource,
            'any_action',
            'any_reason'
        );

        try {
            $this->newSubject()->enforce($decision);
            $this->fail('Expected AccessDeniedException, none got');
        } catch (AccessDeniedException $e) {
            $this->assertSame($decision, $e->getDecision());
        }
    }
}
