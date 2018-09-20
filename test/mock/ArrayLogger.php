<?php
/**
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @licence   proprietary
 */

namespace test\mock\Ingenerator\Warden\Auth;

use Psr\Log\AbstractLogger;

class ArrayLogger extends AbstractLogger
{
    protected $logs = [];

    public function log($level, $message, array $context = [])
    {
        $this->logs[] = [$level, $message, $context];
    }

    public function assertNothingLogged()
    {
        \PHPUnit\Framework\Assert::assertEmpty($this->logs);
    }

    public function assertOneLog($level, $message, array $context = [])
    {
        \PHPUnit\Framework\Assert::assertSame(
            [
                [$level, $message, $context],
            ],
            $this->logs
        );
    }
}
