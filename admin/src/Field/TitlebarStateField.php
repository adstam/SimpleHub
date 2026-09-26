<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace StamPlusJ\Component\Simplehub\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use StamPlusJ\Component\Simplehub\Administrator\Repository\TitlebarModuleRepository;

final class TitlebarStateField extends FormField
{
    protected $type = 'TitlebarState';

    protected function getInput(): string
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $published = (new TitlebarModuleRepository($db))->isPublished();

        if ($published === null) {
            return '<span class="text-danger">'
                . Text::_('COM_SIMPLEHUB_ERROR_TITLEBAR_MODULE_NOT_FOUND')
                . '</span>';
        }

        /*
         * Dit veld wordt gerenderd binnen het Opties-scherm van com_config,
         * niet binnen SimpleHub zelf. joomla.asset.json van SimpleHub is op
         * die plek daardoor nog niet ingelezen — dat moet hier expliciet.
         */
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->getRegistry()->addExtensionRegistryFile('com_simplehub');
        $wa->useScript('com_simplehub.titlebar-config');

        $checked = $published ? ' checked' : '';

		$checked = $published ? ' checked' : '';

		return '<div class="form-check form-switch">'
			. '<input type="checkbox" role="switch" class="form-check-input"' . $checked
			. ' data-simplehub-titlebar-toggle="1">'
			. '</div>'
			. '<div id="titlebar_shortcut-feedback" class="small mt-1"></div>';
    }
}