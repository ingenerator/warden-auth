<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace test\mock\Ingenerator\Warden\Auth;


use Ingenerator\Warden\Auth\AccessControlResource;

class DummyAccessControlResource implements AccessControlResource
{
    /**
     * @return string
     */
    public function getAccessControlResourceName()
    {
        return 'Dummy:resource';
    }

}
