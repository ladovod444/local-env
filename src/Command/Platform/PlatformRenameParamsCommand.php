<?php

namespace App\Command\Platform;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use App\Model\Workspace;

/**
 * Represents a command to rename app parameters.
 */
class PlatformRenameParamsCommand extends Command
{

    /**
     * @var array
     */
    private $paramsToRename = [
      'app_stash_user:' => 'app_jnj_user:',
      'app_stash_pwd:' => 'app_jnj_pwd:',
      'app_ssh_path_to_keys:' => 'app_acquia_ssh_path_to_keys:',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
          ->setName('app:platform:rename-params')
          ->setDescription('Rename app parameters');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parameters = fopen(Workspace::CONFIG_DIR."/parameters.yml", "r");
        $lines = [];
        if ($parameters) {
            while (!feof($parameters)) {
                $lines[] = fgets($parameters);
            }
            fclose($parameters);
        }

        $parameters_rename = fopen(Workspace::CONFIG_DIR."/parameters.yml","w");

        if ($parameters_rename) {
            foreach ($lines as $line) {
                $renamed_line = str_replace(array_keys($this->paramsToRename), array_values($this->paramsToRename), $line);
                fwrite($parameters_rename, $renamed_line);
            }
            fclose($parameters_rename);
            $output->writeln('Parameters were renamed');
        }
    }
}
