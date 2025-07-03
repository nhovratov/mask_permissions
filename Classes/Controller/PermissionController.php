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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use HOV\MaskPermissions\Permissions\MaskPermissions;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Beuser\Domain\Repository\BackendUserGroupRepository;

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
            $this->addFlashMessage('Update successful!', '', ContextualFeedbackSeverity::OK);
        } else {
            $this->addFlashMessage('Update failed.', '', ContextualFeedbackSeverity::ERROR);
        }
        return $this->redirect('index');    
    }

    public function selectMasksAction(): ResponseInterface
    {
        $groupUid = (int)$this->request->getArgument('group');
        $group = $this->backendUserGroupRepository->findByUid($groupUid);
        if (!$group) {
            $this->addFlashMessage('Group not found', '', ContextualFeedbackSeverity::ERROR);
            return $this->redirect('index');
        }

        $maskElements = $this->getAvailableMaskCTypes();
        $selectedMaskElements = $this->permissionUpdater->getSelectedMasks($groupUid);

        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        if (method_exists($moduleTemplate, 'assign')) {
            $moduleTemplate->assignMultiple([
                'group' => $group,
                'maskElements' => $maskElements,
                'selectedMaskElements' => $selectedMaskElements,
            ]);
        }
        return $moduleTemplate->renderResponse('Permission/SelectMasks');
    }

    public function saveMasksAction(): ResponseInterface
    {
        $groupUid = (int)$this->request->getArgument('group');
        $selected = $this->request->hasArgument('selected') ? $this->request->getArgument('selected') : [];

        $success = $this->permissionUpdater->update($groupUid, $selected);
        if ($success) {
            $this->addFlashMessage('MASK permissions saved.', '', ContextualFeedbackSeverity::OK);
        } else {
            $this->addFlashMessage('MASK permissions save failed.', '', ContextualFeedbackSeverity::ERROR);
        }

        return $this->redirect('index');
    }

    protected function getAvailableMaskCTypes(): array
    {
        $maskElements = [];
        $cTypes = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
        foreach ($cTypes ?? [] as $type) {
            $label = $type[0] ?? $type['label'];
            $value = $type[1] ?? $type['value'];
            if ($value !== '--div--') {
                $maskElements[$value] = $this->getLanguageService()->sL($label);
            }
        }
        $maskElements = array_filter($maskElements, function ($key) {
            return strpos($key, 'mask_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        return $maskElements;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
