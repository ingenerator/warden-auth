<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace Ingenerator\Warden\Auth\TestSupport;


use Ingenerator\Warden\Auth\AccessControlDecision;
use Ingenerator\Warden\Auth\AccessControlResource;
use Ingenerator\Warden\Auth\Authoriser;
use Ingenerator\Warden\Auth\DefaultAccessControlEnforcer;
use Ingenerator\Warden\Auth\NullAccessControlResource;
use Ingenerator\Warden\Auth\Policy\AbstractAccessPolicy;
use OutOfRangeException;

/**
 * @psalm-type StubDecision = array{0: ?AccessControlResource, 1: string}
 * @psalm-type StubDecisionMap = array<string, StubDecision>
 */
class ArrayAuthoriserMock implements Authoriser
{

    protected readonly DefaultAccessControlEnforcer $enforcer;

    /**
     * @param StubDecisionMap $explicit_decisions
     */
    protected function __construct(
        protected readonly array   $explicit_decisions,
        protected readonly ?string $default_decision,
    ) {
        $this->enforcer = new DefaultAccessControlEnforcer;
    }

    public static function allowAnything(): self
    {
        return static::allowByDefaultOr([]);
    }

    /**
     * @param StubDecisionMap $expect_decisions
     */
    public static function allowByDefaultOr(array $expect_decisions): self
    {
        return new self($expect_decisions, AbstractAccessPolicy::ALLOW);
    }

    /**
     * @param StubDecisionMap $expect_decisions
     */
    public static function denyByDefaultOr(array $expect_decisions, string $reason = 'denied'): self
    {
        return new self($expect_decisions, $reason);
    }

    /**
     * Configure with an explicit list of expected checks - note that it does not validate all of these are called.
     *
     *   $this->authoriser = ArrayAuthoriserMock::willDecideOnly([
     *     MyPolicy::DELETE => [
     *       [$resource1, AbstractAccessPolicy::ALLOW],
     *       [$resource2, MyPolicy::REASON_BAD_STATE],
     *     ],
     *     MyPolicy::ADD_THING => [
     *       [$resource1, MyPolicy::REASON_USER_LOCKED],
     *     ],
     *   ]);
     *
     *
     * @param StubDecisionMap $expect_decisions
     */
    public static function willDecideOnly(array $expect_decisions): self
    {
        return new self($expect_decisions, NULL);
    }

    public static function willDecideSingle(string $action, ?AccessControlResource $resource, string $result): self
    {
        return self::willDecideOnly([$action => [[$resource, $result]]]);
    }

    public function can(string $action, AccessControlResource $resource = NULL): bool
    {
        return $this->decide($action, $resource)->isAllowed();
    }

    public function decide(string $action, AccessControlResource $resource = NULL): AccessControlDecision
    {
        $reason   = $this->findDecisionReasonFor($action, $resource);
        $resource ??= new NullAccessControlResource;

        return match ($reason) {
            AbstractAccessPolicy::ALLOW => AccessControlDecision::allowed($resource, $action),
            default => AccessControlDecision::denied($resource, $action, $reason)
        };
    }

    private function findDecisionReasonFor(string $action, ?AccessControlResource $resource): string
    {
        foreach ($this->explicit_decisions[$action] ?? [] as [$match_resource, $reason_code]) {
            if ($match_resource === $resource) {
                return $reason_code;
            }
        }

        if ($this->default_decision !== NULL) {
            return $this->default_decision;
        }

        throw new OutOfRangeException('Unexpected access control check for '.$action);
    }

    public function enforce(string $action, ?AccessControlResource $resource = NULL): void
    {
        $this->enforcer->enforce($this->decide($action, $resource));
    }

}
