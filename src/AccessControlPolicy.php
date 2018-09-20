<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth;


/**
 * Access control policies control a particular operation on a particular
 * resource. Usually you will want to extend one of the policy framework
 * classes that take a lot of the boilerplate out of implementing a policy.
 *
 * @see     \Ingenerator\Warden\Auth\Policy\AbstractAccessPolicy
 * @see     \Ingenerator\Warden\Auth\Policy\UserBasedAccessPolicy
 */
interface AccessControlPolicy
{
    /**
     * All available actions for this policy
     *
     * @return string[]
     */
    public static function listActions();

    /**
     * Check if the requested action is permitted on the requested resource
     *
     * @param AccessControlResource $resource
     * @param string                $action
     *
     * @return AccessControlDecision
     */
    public function decide(AccessControlResource $resource, $action);
}
