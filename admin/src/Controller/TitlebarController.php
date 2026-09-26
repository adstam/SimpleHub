<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


namespace StamPlusJ\Component\Simplehub\Administrator\Controller;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Response\JsonResponse;
use Joomla\Database\DatabaseInterface;
use StamPlusJ\Component\Simplehub\Administrator\Repository\TitlebarModuleRepository;

final class TitlebarController extends BaseController
{
    /**
     * Zet de titelbalk-snelkoppeling aan of uit — rechtstreeks op de
     * module zelf, aangeroepen vanuit het schuifje in de SimpleHub-Opties
     * (media/js/titlebar-config.js). Zie ARCHITECTURE_APPENDIX.md, ADR-6.
     */
    public function toggle(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();

        if (!$app->getIdentity()->authorise('core.admin', 'com_simplehub')) {
            echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true);
            $app->close();
        }

        $published = $app->input->post->getBool('published');

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $repository = new TitlebarModuleRepository($db);

        if (!$repository->setPublished($published)) {
            echo new JsonResponse(
                null,
                Text::_('COM_SIMPLEHUB_ERROR_TITLEBAR_MODULE_NOT_FOUND'),
                true
            );

            $app->close();
        }

        echo new JsonResponse(['published' => $published]);
        $app->close();
    }
}