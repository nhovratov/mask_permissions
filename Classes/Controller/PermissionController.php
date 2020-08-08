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
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class PermissionController extends ActionController
{
    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var BackendUserGroupRepository
     */
    protected $backendUserGroupRepository;

    /**
     * @var MaskPermissions
     */
    protected $permissionUpdater;

    public function injectBackendUserGroupRepository(BackendUserGroupRepository $backendUserGroupRepository)
    {
        $this->backendUserGroupRepository = $backendUserGroupRepository;
    }

    public function injectMaskPermissions(MaskPermissions $maskPermissions)
    {
        $this->permissionUpdater = $maskPermissions;
    }

    public function indexAction()
    {
        $groups = $this->backendUserGroupRepository->findAll();
        $updatesNeeded = [];
        foreach ($groups as $group) {
            $uid = $group->getUid();
            $updatesNeeded[$uid] = $this->permissionUpdater->updateNecessary($uid);
        }
        $this->view->assign('groups', $this->backendUserGroupRepository->findAll());
        $this->view->assign('canUpdate', $this->permissionUpdater->updateNecessary());
        $this->view->assign('updatesNeeded', $updatesNeeded);
    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function updateAction()
    {
        if ($this->request->hasArgument('group')) {
            $success = $this->permissionUpdater->update($this->request->getArgument('group'));
        } else {
            $success = $this->permissionUpdater->update();
        }
        if ($success) {
            $this->addFlashMessage('Update successful!');
        } else {
            $this->addFlashMessage('Update failed.', '', AbstractMessage::ERROR);
        }
        $this->redirect('index');
    }
}
