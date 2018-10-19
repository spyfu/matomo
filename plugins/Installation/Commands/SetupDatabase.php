<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Installation\Commands;

use Piwik\Common;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\Installation\Controller;
use Piwik\Plugins\Installation\FormDatabaseSetup;
use Piwik\SettingsPiwik;
use Piwik\Updater;
use Piwik\Version;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * setup:database console command.
 */
class SetupDatabase extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('setup:database');
        $this->setDescription('Setup database and tables');
        $this->addOption('type', null, InputOption::VALUE_REQUIRED, 'DB Type');
        $this->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host name');
        $this->addOption('username', null, InputOption::VALUE_REQUIRED, 'DB user');
        $this->addOption('password', null, InputOption::VALUE_REQUIRED, 'DB password');
        $this->addOption('dbname', null, InputOption::VALUE_REQUIRED, 'DB name');
        $this->addOption('tables_prefix', null, InputOption::VALUE_REQUIRED, 'Tables prefix');
        $this->addOption('adapter', null, InputOption::VALUE_REQUIRED, 'PHP DB Adapter');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (SettingsPiwik::isPiwikInstalled()) {
            $output->writeln("Matomo already installed. Stopping DB setup.");
            return;
        }

        $_POST = $input->getOptions();
        $form = new FormDatabaseSetup();
        $controller = new Controller();

        try {
            // Database setup
            $dbInfos = $form->createDatabaseObject();

            $output->writeln("DB Created");

            DbHelper::checkDatabaseVersion();
            Db::get()->checkClientVersion();

            $output->writeln("DB Checked");

            $controller->createConfigFile($dbInfos);

            $output->writeln("Created Config File");

            // Tables setup
            if (!empty(DbHelper::getTablesInstalled())) {
                $output->writeln("Matomo tables already installed. Stopping DB setup.");
                return;
            }

            DbHelper::createTables();
            DbHelper::createAnonymousUser();

            $output->writeln("Created Tables");

            $controller->updateComponents();
            Updater::recordComponentSuccessfullyUpdated('core', Version::VERSION);
        } catch (Exception $e) {
            $output->writeln(Common::sanitizeInputValue($e->getMessage()));
            return;
        }

        $output->writeln("Success");
    }
}
