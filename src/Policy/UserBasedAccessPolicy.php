<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth\Policy;

use Ingenerator\Warden\Auth\AccessControlDecision;
use Ingenerator\Warden\Auth\AccessControlResource;
use Ingenerator\Warden\Core\Entity\User;
use Ingenerator\Warden\Core\UserSession\UserSession;


/**
 * Base class for any access control policy that needs to operate based on the current user (which
 * is generally but not always going to be the case).
 *
 * By default requires an authenticated user for any action.
 */
abstract class UserBasedAccessPolicy extends AbstractAccessPolicy
{
    const REASON_NOT_AUTHENTICATED = 'user.not_authenticated';
    const REASON_NOT_PERMITTED     = 'user.not_permitted';
    /**
     * @var User;
     */
    protected $user;
    /**
     * @var UserSession
     */
    protected $user_session;

    /**
     * @param UserSession $user_session
     */
    public function __construct(UserSession $user_session = NULL)
    {
        $this->user_session = $user_session;
    }

    /**
     * Deny access if no user is logged in, before proceeding to implement any other rule for this
     * policy.
     *
     * @param AccessControlResource $resource
     * @param string                $action
     *
     * @return AccessControlDecision
     */
    protected function doDecide(AccessControlResource $resource, $action)
    {
        if ( ! $this->user_session->isAuthenticated()) {
            return AccessControlDecision::denied(
                $resource,
                $action,
                self::REASON_NOT_AUTHENTICATED
            );
        }

        $this->user = $this->user_session->getUser();

        return parent::doDecide($resource, $action);
    }

}
