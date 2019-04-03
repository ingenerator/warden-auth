<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth\Policy;


use Ingenerator\Warden\Auth\AccessControlDecision;
use Ingenerator\Warden\Auth\AccessControlPolicy;
use Ingenerator\Warden\Auth\AccessControlResource;

/**
 * Base implementation for all access control policies. Provides some helpful magic to get common
 * policies up and running quickly:
 *
 *  * Provide a `supportsResource` method to verify the resource is something your policy can work
 *    with.
 *  * Define available actions as class constants like `ACTION_VIEW` and they will be automatically
 *    detected and validated.
 *  * Define a method for each action, like `canView`. This method must return either the special
 *    self::ALLOW constant or the string reason code for denying access.
 */
abstract class AbstractAccessPolicy implements AccessControlPolicy
{
    const ALLOW = 'allow';

    /**
     * Decide whether a given action on a resource is permitted
     *
     * @param AccessControlResource $resource
     * @param string                $action
     *
     * @return AccessControlDecision
     * @throws \InvalidArgumentException if a resource or action type is invalid
     */
    public function decide(AccessControlResource $resource, $action)
    {
        if ( ! $this->supportsResource($resource, $action)) {
            $res_class = \get_class($resource);
            throw new \InvalidArgumentException(
                $res_class.' is not a valid resource type for '.$action.' on '.static::class
            );
        }

        if ( ! \in_array($action, static::listActions())) {
            throw new \InvalidArgumentException(
                $action.' is not a valid action for '.static::class
            );
        }

        return $this->doDecide($resource, $action);
    }

    /**
     * Test whether the provided resource is valid for this policy and action
     *
     * @param AccessControlResource $resource
     * @param string                $action
     *
     * @return bool
     */
    abstract protected function supportsResource(AccessControlResource $resource, $action);

    /**
     * List all available actions for this policy, using the `ACTION_XXX` constants defined on the
     * implementing class.
     *
     * @return string[]
     */
    public static function listActions()
    {
        static $actions = [];

        if ( ! $actions) {
            $reflection = new \ReflectionClass(static::class);
            foreach ($reflection->getConstants() as $name => $constant) {
                if (\strncmp($name, 'ACTION_', 7) === 0) {
                    $actions[\substr($name, 7)] = $constant;
                }
            }
        }

        return $actions;
    }

    /**
     * Process the decision, assuming that the resource and action are valid
     *
     * @param AccessControlResource $resource
     * @param string                $action
     *
     * @return AccessControlDecision
     */
    protected function doDecide(AccessControlResource $resource, $action)
    {
        $action_key = \array_flip(static::listActions())[$action];
        $method     = 'can'.\str_replace('_', '', $action_key);
        $decision   = $this->$method($resource);

        if ( ! \is_string($decision)) {
            throw new \UnexpectedValueException(
                static::class.'::'.$method.' must return a string decision'
            );
        } elseif ($decision === self::ALLOW) {
            return AccessControlDecision::allowed($resource, $action);
        } else {
            return AccessControlDecision::denied($resource, $action, $decision);
        }
    }
}
