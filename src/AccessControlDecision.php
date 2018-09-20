<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   BSD-3-Clause
 */

namespace Ingenerator\Warden\Auth;

/**
 * Represents an access control decision, which may be to allow or prevent the requested operation.
 */
class AccessControlDecision
{

    /**
     * @var string
     */
    protected $action;
    /**
     * @var bool
     */
    protected $is_allowed;
    /**
     * @var string
     */
    protected $reason_code;

    /**
     * @var AccessControlResource
     */
    protected $resource;

    /**
     * Protected constructor - do not use this, instead use the named constructors
     * for ::allowed and ::denied.
     *
     * @param AccessControlResource $resource
     * @param string                $action
     */
    protected function __construct(AccessControlResource $resource, $action)
    {
        $this->resource = $resource;
        $this->action   = $action;
    }

    /**
     * @param AccessControlResource $resource
     * @param string                $action
     *
     * @return static
     */
    public static function allowed(AccessControlResource $resource, $action)
    {
        $decision             = new static($resource, $action);
        $decision->is_allowed = TRUE;

        return $decision;
    }

    /**
     * @param AccessControlResource $resource
     * @param  string               $action
     * @param  string               $reason_code
     *
     * @return static
     */
    public static function denied(AccessControlResource $resource, $action, $reason_code)
    {
        $decision              = new static($resource, $action);
        $decision->is_allowed  = FALSE;
        $decision->reason_code = $reason_code;

        return $decision;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getReasonCode()
    {
        return $this->reason_code;
    }

    /**
     * @return AccessControlResource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return boolean
     */
    public function isAllowed()
    {
        return $this->is_allowed;
    }

}
