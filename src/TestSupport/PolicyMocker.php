<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth\TestSupport;

use Ingenerator\Warden\Auth\AccessControlDecision;
use Ingenerator\Warden\Auth\AccessControlPolicy;
use Ingenerator\Warden\Auth\AccessControlResource;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Mock any access control policy for use in unit tests. By default denies everything unless allowed
 */
class PolicyMocker
{

    /**
     * @var array
     */
    protected $allowed_actions = [];

    /**
     * @var string
     */
    protected $class;

    /**
     * @param string $class
     */
    protected function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * @param string $class
     *
     * @return static
     */
    public static function stub($class)
    {
        return new static($class);
    }

    public function _doDecide(AccessControlResource $resource, $action)
    {
        $allow = isset($this->allowed_actions[$action]) ? $this->allowed_actions[$action] : NULL;
        if ($allow === TRUE) {
            // Allow all
            return AccessControlDecision::allowed($resource, $action);
        } elseif (\is_array($allow) and \in_array($resource, $allow, TRUE)) {
            // Allow specific resource
            return AccessControlDecision::allowed($resource, $action);
        } else {
            return AccessControlDecision::denied($resource, $action, 'stub.not-allowed');
        }
    }

    /**
     * Allow a specific resource/action combo
     *
     * @param \Ingenerator\Warden\Auth\AccessControlResource   $resource
     * @param                                                  $action
     *
     * @return $this
     */
    public function allow(AccessControlResource $resource, $action)
    {
        $this->allowed_actions[$action][] = $resource;

        return $this;
    }

    public function allowAny($action)
    {
        $this->allowed_actions[$action] = TRUE;

        return $this;
    }

    /**
     * @return AccessControlPolicy
     */
    public function getPolicy(TestCase $test_case)
    {
        $builder = (new MockBuilder($test_case, $this->class))
            ->disableOriginalConstructor()
            ->enableOriginalClone()
            ->onlyMethods(['doDecide']);
        $mock    = $builder->getMock();
        $mock->method('doDecide')->will(
            new \PHPUnit\Framework\MockObject\Stub\ReturnCallback([$this, '_doDecide'])
        );

        return $mock;
    }

}
