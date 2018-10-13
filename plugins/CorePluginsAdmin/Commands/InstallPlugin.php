<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CorePluginsAdmin\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugin\Manager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * plugin:install console command.
 */
class InstallPlugin extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('plugin:install');
        $this->setDescription('Install a plugin.');
        $this->addArgument('plugin', InputArgument::IS_ARRAY, 'The plugin name you want to install. Multiple plugin names can be specified separated by a space.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pluginManager = Manager::getInstance();

        $plugins = $input->getArgument('plugin');

        foreach ($plugins as $plugin) {
            if ($pluginManager->isPluginInstalled($plugin)) {
                $output->writeln(sprintf('<comment>The plugin %s is already installed.</comment>', $plugin));
                continue;
            }

            if ($dependencies = $pluginManager->loadPlugin($plugin)->getMissingDependenciesAsString()) {
                $output->writeln("<error>$dependencies</error>");
                continue;
            }

            $output->writeln("Installed plugin <info>$plugin</info>");
        }

        $pluginManager->installLoadedPlugins();
    }
}
