<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Installation\Commands;

use Piwik\Access;
use Piwik\Common;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\Installation\Controller;
use Piwik\Plugins\Installation\FormSuperUser;
use Piwik\Plugins\UsersManager\API as APIUsersManager;
use Piwik\SettingsPiwik;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * setup:superuser console command.
 */
class SetupSuperUser extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('setup:superuser');
        $this->setDescription('Setup Super User');
        $this->addOption('login', null, InputOption::VALUE_REQUIRED, 'Super User name');
        $this->addOption('email', null, InputOption::VALUE_REQUIRED, 'Super User email');
        $this->addOption('password', null, InputOption::VALUE_REQUIRED, 'Super User password');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (SettingsPiwik::isPiwikInstalled()) {
            $output->writeln("Matomo already installed. Stopping Super User setup.");
            return;
        }

        $superUserAlreadyExists = Access::doAsSuperUser(function () {
            return count(APIUsersManager::getInstance()->getUsersHavingSuperUserAccess()) > 0;
        });

        if ($superUserAlreadyExists) {
            $output->writeln("Matomo super user already exists. Stopping Super User setup.");
            return;
        }

        $_POST = $input->getOptions();
        $_POST['password_bis'] = $_POST['password'];
        $form = new FormSuperUser();
        $controller = new Controller();

        try {
            // Super User setup
            $controller->createSuperUser($form->getSubmitValue('login'),
                $form->getSubmitValue('password'),
                $form->getSubmitValue('email'));

            $output->writeln("Super User created");
        } catch (Exception $e) {
            $output->writeln(Common::sanitizeInputValue($e->getMessage()));
            return;
        }

        $output->writeln("Success");
    }
}
