<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace test\unit\Ingenerator\Warden\Auth;

use Ingenerator\KohanaExtras\Logger\SpyingLoggerStub;
use Ingenerator\Warden\Auth\AccessControlDecision;
use Ingenerator\Warden\Auth\AccessDeniedException;
use Ingenerator\Warden\Auth\LoggingAccessControlEnforcer;
use Ingenerator\Warden\Core\UserSession\SimplePropertyUserSession;
use Ingenerator\Warden\Core\UserSession\UserSession;
use test\mock\Ingenerator\Warden\Auth\DummyAccessControlResource;
use test\mock\Ingenerator\Warden\Auth\Entity\UserStub;


class LoggingAccessControlEnforcerTest extends DefaultAccessControlEnforcerTest
{

    /**
     * @var SpyingLoggerStub
     */
    protected $log;

    /**
     * @var UserSession
     */
    protected $user_session;

    public function setUp()
    {
        parent::setUp();
        $this->log          = new SpyingLoggerStub;
        $this->user_session = new SimplePropertyUserSession;
    }

    public function test_it_is_initialisable()
    {
        $subject = $this->newSubject();
        $this->assertInstanceOf('Ingenerator\Warden\Auth\LoggingAccessControlEnforcer', $subject);
        $this->assertInstanceOf('Ingenerator\Warden\Auth\AccessControlEnforcer', $subject);
    }

    protected function newSubject()
    {
        return new LoggingAccessControlEnforcer($this->log, $this->user_session);
    }

    public function test_it_logs_nothing_when_decision_denied_because_user_not_authenticated()
    {
        $this->whenAccessDeniedEnforced(new DummyAccessControlResource, 'any.action', 'any.reason');
        $this->log->assertNothingLogged();
    }

    protected function whenAccessDeniedEnforced($resource, $action, $reason)
    {
        try {
            $this->newSubject()->enforce(AccessControlDecision::denied($resource, $action, $reason));
        } catch (AccessDeniedException $e) {
            // fine
        }
    }

    public function test_it_logs_nothing_when_decision_is_allowed()
    {
        $this->newSubject()->enforce(AccessControlDecision::allowed(new DummyAccessControlResource, 'any.action'));
        $this->log->assertNothingLogged();
    }

    public function test_it_logs_when_decision_denied_for_authenticated_user()
    {
        $this->user_session->login(UserStub::with(['id' => 12, 'email' => 'someone@bad.net']));
        $this->whenAccessDeniedEnforced(new DummyAccessControlResource, 'any.action', 'any.reason');
        $this->log->assertOneLog(
            \Log::WARNING,
            'Access denied: User someone@bad.net(#12) prevented from any.action on Dummy:resource because any.reason'
        );
    }

}
