<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth;

/**
 * Throws an exception if an access control decision prevents a particular action
 */
interface AccessControlEnforcer
{

    /**
     * @param \Ingenerator\Warden\Auth\AccessControlDecision $decision
     *
     * @return void
     * @throws \Ingenerator\Warden\Auth\AccessDeniedException
     */
    public function enforce(AccessControlDecision $decision);

}
