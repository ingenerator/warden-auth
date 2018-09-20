<?php
/**
 * @author    Craig Gosman <craig@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace test\mock\Ingenerator\Warden\Auth\Entity;


use Ingenerator\Warden\Core\Entity\SimpleUser;

class UserStub extends SimpleUser
{

    public static function with(array $values)
    {
        static $user_id = 1;

        $values = array_merge(
            [
                'id'    => $user_id++,
                'email' => '',
            ],
            $values
        );

        $user        = new static;
        $user->id    = $values['id'];
        $user->email = $values['email'];

        return $user;
    }

}
