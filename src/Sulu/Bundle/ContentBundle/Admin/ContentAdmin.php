<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Routing\Route;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Component\Webspace\Webspace;

class ContentAdmin extends Admin
{
    /**
     * The prefix for the security context, the key of the webspace has to be appended.
     *
     * @var string
     */
    const SECURITY_CONTEXT_PREFIX = 'sulu.webspaces.';

    /**
     * The prefix for the settings security context, the key of the webspace has to be appended.
     *
     * @var string
     */
    const SECURITY_SETTINGS_CONTEXT_PREFIX = 'sulu.webspace_settings.';

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    public function __construct(
        WebspaceManagerInterface $webspaceManager,
        SecurityCheckerInterface $securityChecker,
        SessionManagerInterface $sessionManager,
        $title
    ) {
        $this->webspaceManager = $webspaceManager;
        $this->securityChecker = $securityChecker;
        $this->sessionManager = $sessionManager;

        $rootNavigationItem = new NavigationItem($title);

        $section = new NavigationItem('navigation.webspaces');
        $section->setPosition(10);

        $rootNavigationItem->addChild($section);

        $position = 10;

        /** @var Webspace $webspace */
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            if ($this->securityChecker->hasPermission(self::SECURITY_CONTEXT_PREFIX . $webspace->getKey(), PermissionTypes::VIEW)) {
                $webspaceItem = new NavigationItem($webspace->getName());
                $webspaceItem->setPosition($position++);
                $webspaceItem->setIcon('bullseye');

                $indexUuid = $this->sessionManager->getContentNode($webspace->getKey())->getIdentifier();

                $indexPageItem = new NavigationItem('navigation.webspaces.index-page');
                $indexPageItem->setPosition(10);
                $indexPageItem->setAction(
                    'content/contents/' . $webspace->getKey() . '/edit:' . $indexUuid . '/content'
                );
                $webspaceItem->addChild($indexPageItem);

                $contentItem = new NavigationItem('navigation.webspaces.content');
                $contentItem->setPosition(20);
                $contentItem->setAction('content/contents/' . $webspace->getKey());
                $webspaceItem->addChild($contentItem);

                $webspaceSettingsItem = new NavigationItem('navigation.webspaces.settings');
                $webspaceSettingsItem->setPosition(30);
                $webspaceSettingsItem->setAction(sprintf('content/webspace/settings:%s/general', $webspace->getKey()));
                $webspaceItem->addChild($webspaceSettingsItem);

                $section->addChild($webspaceItem);
            }
        }

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'sulucontent';
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        /** @var Webspace $firstWebspace */
        $firstWebspace = current($this->webspaceManager->getWebspaceCollection()->getWebspaces());

        return [
            (new Route('sulu_content.webspaces', '/webspaces/:webspace/:locale', 'sulu_content.webspace_overview'))
                ->addAttributeDefault('webspace', $firstWebspace->getKey())
                ->addAttributeDefault('locale', $firstWebspace->getDefaultLocalization()->getLocale())
                ->addRerenderAttribute('webspace'),
            // add form
            (new Route('sulu_content.page_add_form', '/webspaces/:webspace/:locale/add/:parentId', 'sulu_admin.resource_tabs'))
                ->addOption('locales', true)
                ->addOption('resourceKey', 'pages'),
            (new Route('sulu_content.page_add_form.detail', '/details', 'sulu_content.page_form'))
                ->addOption('tabTitle', 'sulu_content.page_form_detail')
                ->addOption('editRoute', 'sulu_content.page_edit_form.detail')
                ->setParent('sulu_content.page_add_form'),
            // edit form
            (new Route('sulu_content.page_edit_form', '/webspaces/:webspace/:locale/:id', 'sulu_admin.resource_tabs'))
                ->addOption('locales', true)
                ->addOption('preview', true)
                ->addOption('resourceKey', 'pages'),
            (new Route('sulu_content.page_edit_form.detail', '/details', 'sulu_content.page_form'))
                ->addOption('tabTitle', 'sulu_content.page_form_detail')
                ->setParent('sulu_content.page_edit_form'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSecurityContexts()
    {
        $webspaceContexts = [];
        foreach ($this->webspaceManager->getWebspaceCollection() as $webspace) {
            /* @var Webspace $webspace */
            $webspaceContexts[self::SECURITY_CONTEXT_PREFIX . $webspace->getKey()] = [
                PermissionTypes::VIEW,
                PermissionTypes::ADD,
                PermissionTypes::EDIT,
                PermissionTypes::DELETE,
                PermissionTypes::LIVE,
                PermissionTypes::SECURITY,
            ];
        }

        return [
            'Sulu' => [
                'Webspaces' => $webspaceContexts,
            ],
        ];
    }
}
