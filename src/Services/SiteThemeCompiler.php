<?php

namespace App\Services;

use App\Model\ConsoleCommand;
use App\Model\NewClone;
use App\Process\ProcessAwareTrait;
use Symfony\Component\Process\Process;

/**
 * Class SiteThemeCompiler
 * @package AppBundle\Services
 */
class SiteThemeCompiler
{
    use ProcessAwareTrait;

    /**
     * Install node modules and required ruby packages.
     * @param string $pathToTheme
     */
    public function installThemeAssets($pathToTheme)
    {
        $commands = [
            'cd '. $pathToTheme,
            'bundle install',
            'npm install',
            'cd -- '
        ];
        $this->execute($commands);
    }

    /**
     * Compile theme assets.
     * @param string $pathToTheme
     */
    public function buildTheme($pathToTheme)
    {
        $commands = [
            'cd '. $pathToTheme,
            'grunt',
            'cd -- '
        ];
        $this->execute($commands);
    }

    /**
     * Run gfo-theme command.
     * @param string $pathToTheme
     */
    public function gfoTheme($pathToTheme)
    {
        $commands = [
            'cd '. $pathToTheme,
            'chmod -R a+rwx *',
            'gfo-theme',
            'chmod -R -x+X *',
            'cd -- '
        ];
        $this->execute($commands);
    }

    /**
     * Run grunt update-site command.
     * @param string $pathToTheme
     */
    public function updateSite($pathToTheme)
    {
        $commands = [
            'cd '. $pathToTheme,
            'grunt update-site',
            'cd -- '
        ];

        $this->execute($commands);
    }

    /**
     * Copy files from brand_theme to new theme.
     * @param string $pathToBrandTheme
     * @param string $pathToTheme
     */
    public function copyBrandTheme($pathToBrandTheme, $pathToTheme, $excludeContrib = TRUE)
    {
        if ($excludeContrib) {
            $command[] = sprintf(
                'rsync -a --ignore-existing --exclude "%s" --exclude "%s" %s/ %s/',
                NewClone::DEBUG_CONTRIB_IMAGES,
                NewClone::DEBUG_CONTRIB_FONTS,
                $pathToBrandTheme,
                $pathToTheme
            );
        }
        else {
            $command[] = sprintf(
                'rsync --delete -a %s %s/',
                $pathToBrandTheme,
                $pathToTheme
            );
        }

        $this->execute($command);
    }

    /**
     * @param array $commands
     */
    private function execute(array $commands)
    {
        $process = new Process(implode(' && ', $commands));
        $process->setTimeout(ConsoleCommand::PROCESS_TIMEOUT);
        $process->run($this->getRealTimeProcessCallback());
    }
}
