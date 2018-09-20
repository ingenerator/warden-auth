<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth;

/**
 * Simplest possible implementation of an access control enforcer
 */
class DefaultAccessControlEnforcer implements AccessControlEnforcer
{
    /**
     * {@inheritdoc}
     */
    public function enforce(AccessControlDecision $decision)
    {
        if ( ! $decision->isAllowed()) {
            throw new AccessDeniedException($decision);
        }
    }

}
