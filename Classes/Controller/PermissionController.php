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

namespace HOV\MaskPermissions\Controller;

use HOV\MaskPermissions\Permissions\MaskPermissions;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class PermissionController extends ActionController
{
    protected BackendUserGroupRepository $backendUserGroupRepository;
    protected MaskPermissions $permissionUpdater;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        BackendUserGroupRepository $backendUserGroupRepository,
        MaskPermissions $maskPermissions,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->backendUserGroupRepository = $backendUserGroupRepository;
        $this->permissionUpdater = $maskPermissions;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    public function indexAction(): ResponseInterface
    {
        $groups = $this->backendUserGroupRepository->findAll();
        $updatesNeeded = [];
        foreach ($groups as $group) {
            $uid = $group->getUid();
            $updatesNeeded[$uid] = $this->permissionUpdater->updateNecessary($uid);
        }

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        if (method_exists($moduleTemplate, 'assign')) {
            $moduleTemplate->assign('groups', $this->backendUserGroupRepository->findAll());
            $moduleTemplate->assign('canUpdate', $this->permissionUpdater->updateNecessary());
            $moduleTemplate->assign('updatesNeeded', $updatesNeeded);
        } else {
            $this->view->assign('groups', $this->backendUserGroupRepository->findAll());
            $this->view->assign('canUpdate', $this->permissionUpdater->updateNecessary());
            $this->view->assign('updatesNeeded', $updatesNeeded);
        }
        return $moduleTemplate->renderResponse('Permission/IndexNew');
    }

    public function updateAction(): ResponseInterface
    {
        if ($this->request->hasArgument('group')) {
            $success = $this->permissionUpdater->update((int)$this->request->getArgument('group'));
        } else {
            $success = $this->permissionUpdater->update();
        }
        if ($success) {
            $this->addFlashMessage('Update successful!');
        } else {
            $this->addFlashMessage('Update failed.', '', AbstractMessage::ERROR);
        }
        return $this->redirect('index');
    }
}
