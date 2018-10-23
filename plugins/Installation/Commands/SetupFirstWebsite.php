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
use Piwik\Plugins\Installation\FormFirstWebsiteSetup;
use Piwik\Plugins\Installation\ServerFilesGenerator;
use Piwik\Plugins\SitesManager\API as APISitesManager;
use Piwik\SettingsPiwik;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * setup:firstwebsite console command.
 */
class SetupFirstWebsite extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('setup:firstwebsite');
        $this->setDescription('Setup First Website');
        $this->addOption('siteName', null, InputOption::VALUE_REQUIRED, 'Website name');
        $this->addOption('url', null, InputOption::VALUE_REQUIRED, 'Website url');
        $this->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'Website timezone');
        $this->addOption('ecommerce', null, InputOption::VALUE_REQUIRED, 'Is Ecommerce website');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (SettingsPiwik::isPiwikInstalled()) {
            $output->writeln("Matomo already installed. Stopping First Website setup.");
            return;
        }

        ServerFilesGenerator::createFilesForSecurity();

        $siteIdsCount = Access::doAsSuperUser(function () {
            return count(APISitesManager::getInstance()->getAllSitesId());
        });

        if ($siteIdsCount > 0) {
            $output->writeln("Website already exists. Stopping First Website setup.");
            return;
        }

        $_POST = $input->getOptions();
        $form = new FormFirstWebsiteSetup();
        $controller = new Controller();

        // Fetch parameters
        $name = Common::sanitizeInputValue($form->getSubmitValue('siteName'));
        $url = Common::unsanitizeInputValue($form->getSubmitValue('url'));
        $ecommerce = (int)$form->getSubmitValue('ecommerce');
        $timezone = Common::sanitizeInputValue($form->getSubmitValue('timezone'));

        try {
            // Setup First Website
            Access::doAsSuperUser(function () use ($name, $url, $ecommerce, $timezone) {
                return APISitesManager::getInstance()->addSite($name, $url, $ecommerce, null,
                    null, null, null, null, $timezone);
            });
            $controller->addTrustedHosts($url);
            $output->writeln("First Website created");
        } catch (Exception $e) {
            $output->writeln(Common::sanitizeInputValue($e->getMessage()));
            return;
        }

        $output->writeln("Success");
    }
}
