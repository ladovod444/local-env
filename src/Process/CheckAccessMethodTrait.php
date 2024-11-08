<?php

namespace App\Process;

/**
 * Trait CheckAccessMethodTrait
 * @package AppBundle\Process
 */
trait CheckAccessMethodTrait
{
    /**
     * @return bool
     */
    protected function isSshUsed($url)
    {
        if (preg_match('#^ssh:#', $url)) {
            return TRUE;
        }
        return FALSE;
    }
}
