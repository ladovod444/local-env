<?php

namespace App\Command\Platform;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use App\Model\Workspace;

/**
 * Represents a command to update JnJ credentials.
 */
class PlatformChangeCredsCommand extends Command
{

  /**
   * {@inheritdoc}
   */
    protected function configure()
    {
        $this
          ->setName('app:platform:change-creds')
          ->setDescription('Update JnJ credentials')
          ->addOption('login', 'l', InputOption::VALUE_REQUIRED)
          ->addOption('password', 'p', InputOption::VALUE_REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        // Validate the required input values.
        foreach (['login', 'password'] as $value) {
            if (empty($input->getOption($value))) {
                $output->writeln("--{$value} option is required.");
                exit;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $netrc = fopen("/home/developer/.netrc", "w");
        $login = trim($input->getOption('login'));
        $password = trim($input->getOption('password'));

        //dump($netrc); die();

        if ($netrc) {
            fwrite($netrc, "machine sourcecode.jnj.com\n");
            fwrite($netrc, "login {$login}\n");
            fwrite($netrc, "password {$password}\n");
            fclose($netrc);
        }

        $parameters = fopen (Workspace::CONFIG_DIR . "/parameters.yml", "r");
        $lines = [];
        if ($parameters) {
            while (!feof ($parameters)) {
                $lines[] = fgets($parameters);
            }
            fclose ($parameters);
        }

        $new_parameters = fopen(Workspace::CONFIG_DIR . "/parameters.yml","w");
        if ($new_parameters) {
            foreach ($lines as $line) {
                if (strstr($line, 'app_jnj_user:')){
                    fwrite($new_parameters,"    app_jnj_user: {$login}\n");
                } elseif (strstr($line, 'app_jnj_pwd:')) {
                    fwrite($new_parameters,"    app_jnj_pwd: {$password}\n");
                } else {
                    fwrite($new_parameters,$line);
                }
            }
            fclose ($new_parameters);
            $output->writeln('Your credentials were updated');
        }
    }
}
