<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth;

use Ingenerator\Warden\Core\UserSession\UserSession;
use Psr\Log\LoggerInterface;


/**
 * Enforces access control decisions and logs a warning when access is denied.
 */
class LoggingAccessControlEnforcer extends DefaultAccessControlEnforcer
{

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var UserSession
     */
    protected $user_session;

    public function __construct(LoggerInterface $log, UserSession $user_session = NULL)
    {
        $this->log          = $log;
        $this->user_session = $user_session;
    }

    /**
     * {@inheritdoc}
     */
    public function enforce(AccessControlDecision $decision)
    {
        try {
            parent::enforce($decision);
        } catch (AccessDeniedException $e) {

            $this->logAccessDenied($decision);

            throw $e;
        }
    }

    protected function logAccessDenied(AccessControlDecision $decision)
    {
        if ( ! $this->user_session->isAuthenticated()) {
            // Don't log action by unauthenticated user, most likely it is session expiry
            return;
        }

        $entry = sprintf(
            'Access denied: User %s(#%s) prevented from %s on %s because %s',
            $this->user_session->getUser()->getEmail(),
            $this->user_session->getUser()->getId(),
            $decision->getAction(),
            $decision->getResource()->getAccessControlResourceName(),
            $decision->getReasonCode()
        );

        $this->log->warning($entry);
    }

}
