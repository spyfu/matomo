<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Installation\Commands;

use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\Installation\Controller;
use Piwik\Plugins\Installation\FormDefaultSettings;
use Piwik\SettingsPiwik;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * setup:cleanup console command.
 */
class SetupCleanUp extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('setup:cleanup');
        $this->setDescription('Clean up new Matomo installation');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (SettingsPiwik::isPiwikInstalled()) {
            $output->writeln("Matomo already installed. Stopping setup clean up.");
            return;
        }

        $_POST = $input->getOptions();
        $form = new FormDefaultSettings();
        $controller = new Controller();

        /**
         * Triggered on initialization of the form to customize default Matomo settings (at the end of the installation process).
         *
         * @param \Piwik\Plugins\Installation\FormDefaultSettings $form
         */
        Piwik::postEvent('Installation.defaultSettingsForm.init', array($form));

        try {
            /**
             * Triggered on submission of the form to customize default Matomo settings (at the end of the installation process).
             *
             * @param \Piwik\Plugins\Installation\FormDefaultSettings $form
             */
            Piwik::postEvent('Installation.defaultSettingsForm.submit', array($form));
            $controller->markInstallationAsCompleted();
            $output->writeln("Matomo installation cleaned up");
        } catch (Exception $e) {
            $output->writeln(Common::sanitizeInputValue($e->getMessage()));
            return;
        }

        $output->writeln("Success");
    }
}
