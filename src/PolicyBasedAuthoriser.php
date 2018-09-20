<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth;


class PolicyBasedAuthoriser implements Authoriser
{
    /**
     * @var AccessControlEnforcer
     */
    protected $enforcer;

    /**
     * @var AccessControlPolicy[]
     */
    protected $policy_map = [];

    /**
     * @param AccessControlEnforcer $enforcer
     * @param AccessControlPolicy   $policy,...
     */
    public function __construct(AccessControlEnforcer $enforcer)
    {
        $args           = func_get_args();
        $this->enforcer = array_shift($args);

        foreach ($args as $policy) {
            if ( ! $policy instanceof AccessControlPolicy) {
                throw new \InvalidArgumentException('Invalid policy class '.get_class($policy));
            }
            $this->mapPolicyActions($policy);
        }
    }

    protected function mapPolicyActions(AccessControlPolicy $policy)
    {
        $actions = call_user_func([get_class($policy), 'listActions']);
        foreach ($actions as $action) {
            if (isset($this->policy_map[$action])) {
                throw new \DomainException(
                    'Duplicate action '.$action.' defined by '.get_class($policy).' and '.get_class(
                        $this->policy_map[$action]
                    )
                );
            }
            $this->policy_map[$action] = $policy;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function can($action, AccessControlResource $resource = NULL)
    {
        return $this->decide($action, $resource)->isAllowed();
    }

    /**
     * {@inheritdoc}
     */
    public function decide($action, AccessControlResource $resource = NULL)
    {
        if ( ! isset($this->policy_map[$action])) {
            throw new \OutOfBoundsException('Unknown access control action `'.$action.'`');
        }

        return $this->policy_map[$action]->decide($resource ?: new NullAccessControlResource, $action);
    }

    /**
     * {@inheritdoc}
     */
    public function enforce($action, AccessControlResource $resource = NULL)
    {
        $this->enforcer->enforce($this->decide($action, $resource));
    }


}
