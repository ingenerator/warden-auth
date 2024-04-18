<?php

namespace test\unit\Ingenerator\Warden\Auth\TestSupport;

use Ingenerator\Warden\Auth\AccessControlResource;
use Ingenerator\Warden\Auth\AccessDeniedException;
use Ingenerator\Warden\Auth\NullAccessControlResource;
use Ingenerator\Warden\Auth\Policy\AbstractAccessPolicy;
use Ingenerator\Warden\Auth\TestSupport\ArrayAuthoriserMock;
use PHPUnit\Framework\TestCase;
use test\mock\Ingenerator\Warden\Auth\DummyAccessControlResource;

class ArrayAuthoriserMockTest extends TestCase
{

    public function test_it_can_allow_anything()
    {
        $subject = ArrayAuthoriserMock::allowAnything();
        $this->assertAllows($subject, 'whatever', new DummyAccessControlResource);
        $this->assertAllows($subject, 'things', NULL);
    }

    public function test_it_can_allow_by_default_or_return_explicit_decision()
    {
        $locked_resource_1 = new DummyAccessControlResource();
        $locked_resource_2 = new DummyAccessControlResource();
        $safe_resource     = new DummyAccessControlResource();

        $subject = ArrayAuthoriserMock::allowByDefaultOr(
            [
                'delete'  => [
                    [$locked_resource_1, 'not-permitted'],
                    [$locked_resource_2, 'locked'],
                ],
                'refresh' => [
                    [$safe_resource, AbstractAccessPolicy::ALLOW],
                ],
            ]
        );

        $this->assertAllows($subject, 'update', $locked_resource_1);
        $this->assertAllows($subject, 'update', $locked_resource_2);
        $this->assertAllows($subject, 'update', NULL);
        $this->assertAllows($subject, 'delete', new DummyAccessControlResource);
        $this->assertAllows($subject, 'refresh', $safe_resource);

        $this->assertDenies($subject, 'delete', $locked_resource_1, 'not-permitted');
        $this->assertDenies($subject, 'delete', $locked_resource_2, 'locked');
    }

    public function test_it_can_deny_by_default_or_return_explicit_decision()
    {
        $safe_resource_1 = new DummyAccessControlResource();
        $locked_resource = new DummyAccessControlResource();
        $safe_resource_2 = new DummyAccessControlResource();
        $subject         = ArrayAuthoriserMock::denyByDefaultOr(
            [
                'delete'  => [
                    [$safe_resource_1, AbstractAccessPolicy::ALLOW],
                    [$locked_resource, 'locked'],
                ],
                'refresh' => [
                    [$safe_resource_2, AbstractAccessPolicy::ALLOW],
                ],
            ],
            'some-reason'
        );

        $this->assertAllows($subject, 'delete', $safe_resource_1);
        $this->assertAllows($subject, 'refresh', $safe_resource_2);

        $this->assertDenies($subject, 'delete', $locked_resource, 'locked');
        $this->assertDenies($subject, 'update', $locked_resource, 'some-reason');
        $this->assertDenies($subject, 'update', NULL, 'some-reason');
        $this->assertDenies($subject, 'delete', new DummyAccessControlResource, 'some-reason');
        $this->assertDenies($subject, 'refresh', $safe_resource_1, 'some-reason');
    }

    public function test_it_can_enforce_only_explicit_decisions_requested()
    {
        $safe_resource_1 = new DummyAccessControlResource();
        $locked_resource = new DummyAccessControlResource();
        $subject         = ArrayAuthoriserMock::willDecideOnly(
            [
                'delete'   => [
                    [$safe_resource_1, AbstractAccessPolicy::ALLOW],
                    [$locked_resource, 'locked'],
                ],
                'refresh'  => [
                    [$safe_resource_1, 'nope'],
                ],
                'list'     => [
                    [NULL, AbstractAccessPolicy::ALLOW],
                ],
                'truncate' => [
                    [NULL, 'not-safe'],
                ],
            ],
        );

        $this->assertAllows($subject, 'delete', $safe_resource_1);
        $this->assertAllows($subject, 'list', NULL);
        $this->assertDenies($subject, 'delete', $locked_resource, 'locked');
        $this->assertDenies($subject, 'refresh', $safe_resource_1, 'nope');
        $this->assertDenies($subject, 'truncate', NULL, 'not-safe');

        // NB can do the expected decisions any number of times
        $this->assertAllows($subject, 'delete', $safe_resource_1);
        $this->assertDenies($subject, 'delete', $locked_resource, 'locked');

        $this->expectException(\OutOfRangeException::class);
        $subject->decide('anything', $safe_resource_1);
    }

    public function test_it_can_enforce_only_single_decision_requested()
    {
        $locked_resource = new DummyAccessControlResource();
        $subject         = ArrayAuthoriserMock::willDecideSingle('delete', $locked_resource, 'not-allowed');

        $this->assertDenies($subject, 'delete', $locked_resource, 'not-allowed');

        // NB can do the expected decisions any number of times
        $this->assertDenies($subject, 'delete', $locked_resource, 'not-allowed');

        $this->expectException(\OutOfRangeException::class);
        $subject->decide('anything', new DummyAccessControlResource());
    }

    private function assertAllows(
        ArrayAuthoriserMock    $subject,
        string                 $action,
        ?AccessControlResource $resource
    ): void {
        $this->assertDecisionMatches(
            is_allowed: TRUE,
            reason:     NULL,
            action:     $action,
            resource:   $resource,
            decision:   $subject->decide($action, $resource)
        );

        // No throw
        $subject->enforce($action, $resource);
        $this->assertTrue($subject->can($action, $resource));
    }

    private function assertDenies(
        ArrayAuthoriserMock    $subject,
        string                 $action,
        ?AccessControlResource $resource,
        string                 $expect_reason
    ) {
        $this->assertDecisionMatches(
            is_allowed: FALSE,
            reason:     $expect_reason,
            action:     $action,
            resource:   $resource,
            decision:   $subject->decide($action, $resource)
        );

        $this->assertFalse($subject->can($action, $resource));

        try {
            $subject->enforce($action, $resource);
            $this->fail('Expected to throw an access denied exception');
        } catch (AccessDeniedException $e) {
            $this->assertSame($expect_reason, $e->getDecision()->getReasonCode());
        }
    }

    private function assertDecisionMatches(
        bool                                           $is_allowed,
        ?string                                        $reason,
        string                                         $action,
        ?DummyAccessControlResource                    $resource,
        \Ingenerator\Warden\Auth\AccessControlDecision $decision,
    ): void {
        $this->assertSame(
            [
                'is_allowed' => $is_allowed,
                'reason'     => $reason,
                'action'     => $action,
            ],
            [
                'is_allowed' => $decision->isAllowed(),
                'reason'     => $decision->getReasonCode(),
                'action'     => $decision->getAction(),
            ]
        );
        if ($resource) {
            $this->assertSame($resource, $decision->getResource());
        } else {
            $this->assertInstanceOf(NullAccessControlResource::class, $decision->getResource());
        }
    }

}
