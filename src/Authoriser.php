<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 */

namespace Ingenerator\Warden\Auth;


interface Authoriser
{
    /**
     * @param string                     $action
     * @param AccessControlResource|NULL $resource
     *
     * @return boolean
     */
    public function can(string $action, AccessControlResource $resource = NULL);

    /**
     * @param string                     $action
     * @param AccessControlResource|NULL $resource
     *
     * @return AccessControlDecision
     */
    public function decide(string $action, AccessControlResource $resource = NULL);

    /**
     * @param string                     $action
     * @param AccessControlResource|NULL $resource
     *
     * @return void
     * @throws AccessDeniedException
     */
    public function enforce(string $action, AccessControlResource $resource = NULL);

}
