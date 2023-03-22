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

class MaskPermissionsCommand extends Command
{
    protected MaskPermissions $maskPermissions;

    public function injectMaskPermissions(MaskPermissions $maskPermissions): void
    {
        $this->maskPermissions = $maskPermissions;
    }

    protected function configure()
    {
        $this->setDescription('Update mask permissions for backend user groups.');
        $this->setHelp('Specify BE User Group uid as first argument. Without arguments all groups will be updated.');
        $this->addArgument('group', InputArgument::OPTIONAL, 'Backend User Group Uid.', 0);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $group = (int)$input->getArgument('group');
        if (!$this->maskPermissions->updateNecessary($group)) {
            return self::SUCCESS;
        }
        $this->maskPermissions->update($group);
        return self::SUCCESS;
    }
}
