<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth;


/**
 * Use when a policy does not require any specific resource (eg a list action with no criteria)
 */
class NullAccessControlResource implements AccessControlResource
{

    public function getAccessControlResourceName()
    {
        return '{unspecified resource}';
    }

}
