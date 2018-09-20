<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth;

/**
 * Thrown by an AccessControlEnforcer when an access control decision prevents the requested action.
 */
class AccessDeniedException extends \RuntimeException
{
    /**
     * @var AccessControlDecision
     */
    protected $decision;

    public function __construct(AccessControlDecision $decision)
    {
        $this->decision = $decision;
        parent::__construct(
            'Access Denied for action '
            .$decision->getAction()
            .' - reason '.$decision->getReasonCode()
        );
    }

    /**
     * @return \Ingenerator\Warden\Auth\AccessControlDecision
     */
    public function getDecision()
    {
        return $this->decision;
    }

}
