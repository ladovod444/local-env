<?php

namespace App\Process;

/**
 * Trait SshCheckTrait
 * @package AppBundle\Process
 */
trait SshCheckTrait
{
    /**
     * @return bool
     */
    protected function checkSshConnection($user, $host)
    {
        $connectionString = strtr('%user%@%host%', ['%user%' => $user, '%host%' => $host]);
        $createTestCommand = sprintf("ssh %s '%s'", $connectionString, 'ls;');
        exec($createTestCommand, $remoteOutput);
        if (!$remoteOutput) {
            return FALSE;
        }
        return TRUE;
    }
}
