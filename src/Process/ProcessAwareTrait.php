<?php

namespace App\Process;

/**
 * Trait ProcessAwareTrait
 * @package AppBundle\Process
 */
trait ProcessAwareTrait
{
    /**
     * @return \Closure
     */
    protected function getRealTimeProcessCallback()
    {
        return function ($type, $buffer) {
            print $buffer;
        };
    }
}
