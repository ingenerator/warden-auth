<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace test\unit\Ingenerator\Warden\Auth;


use Ingenerator\Warden\Auth\AccessControlDecision;
use test\mock\Ingenerator\Warden\Auth\DummyAccessControlResource;

class AccessControlDecisionTest extends \PHPUnit\Framework\TestCase
{

    public function test_it_can_be_created_as_a_denied_decision()
    {
        $action   = uniqid();
        $resource = new DummyAccessControlResource;
        $reason   = uniqid();
        $decision = AccessControlDecision::denied($resource, $action, $reason);
        $this->assertFalse($decision->isAllowed());
        $this->assertSame($resource, $decision->getResource());
        $this->assertSame($action, $decision->getAction());
        $this->assertSame($reason, $decision->getReasonCode());
    }

    public function test_it_can_be_created_as_an_allowed_decision()
    {
        $action   = uniqid();
        $resource = new DummyAccessControlResource;
        $decision = AccessControlDecision::allowed($resource, $action);
        $this->assertTrue($decision->isAllowed());
        $this->assertSame($resource, $decision->getResource());
        $this->assertSame($action, $decision->getAction());
        $this->assertNull($decision->getReasonCode());
    }

    public function test_it_cannot_be_directly_created()
    {
        $reflection  = new \ReflectionClass('Ingenerator\Warden\Auth\AccessControlDecision');
        $constructor = $reflection->getMethod('__construct');
        $this->assertTrue($constructor->isProtected());
    }

}
