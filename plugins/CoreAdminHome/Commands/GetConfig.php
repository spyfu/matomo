<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CoreAdminHome\Commands;

use Piwik\Config;
use Piwik\Plugin\ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetConfig extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('config:get');
        $this->setDescription('Get config settings in the file config/config.ini.php');
        $this->addOption('section', null, InputOption::VALUE_REQUIRED, 'The section the INI config setting belongs to.');
        $this->addOption('key', null, InputOption::VALUE_REQUIRED, 'The name of the INI config setting.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $section = $input->getOption('section');
        $key = $input->getOption('key');

        $config = Config::getInstance();
        $output->write($config->$section[$key]);
    }
}