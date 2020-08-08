<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace HOV\MaskPermissions\Command;

use HOV\MaskPermissions\Permissions\MaskPermissions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MaskPermissionsCommand extends Command
{
    protected function configure()
    {
        $this->setDescription('Update mask permissions for backend user groups.');
        $this->setHelp('Specify BE User Group uid as first argument. Without arguments all groups will be updated.');
        $this->addArgument(
            'group',
            InputArgument::OPTIONAL,
            'Backend User Group Uid.',
            0
        );
    }

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $maskPermissionUpdater = GeneralUtility::makeInstance(MaskPermissions::class);
        $group = $input->getArgument('group');
        if (!$maskPermissionUpdater->updateNecessary($group)) {
            return 0;
        }
        $maskPermissionUpdater->update($group);
        return 0;
    }
}
