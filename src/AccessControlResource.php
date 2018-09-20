<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth;


/**
 * Marks any object that is expected to be used as an access control resource.
 */
interface AccessControlResource
{

    /**
     * Provide some sort of descriptive name for the resource, to be used in logging and other
     * usually developer-facing contexts.
     *
     * @return string
     */
    public function getAccessControlResourceName();
}
