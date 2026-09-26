<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace StamPlusJ\Component\Simplehub\Administrator\View\Group;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    /**
     * Het Joomla-formulier.
     */
    protected Form|false $form = false;

    public function display($tpl = null): void
    {
        $this->form = $this->get('Form');

        if ($this->form === false)
        {
            throw new \RuntimeException($this->getError());
        }

        $this->addToolbar();

        Factory::getApplication()
            ->getDocument()
            ->getWebAssetManager()
            ->useScript('com_simplehub.group');

        parent::display($tpl);
    }

    /**
     * Bouwt de toolbar op.
     */
    protected function addToolbar(): void
    {
        $isNew = ((int) $this->form->getValue('id')) === 0;

        ToolbarHelper::title(
            Text::_(
                $isNew
                    ? 'COM_SIMPLEHUB_GROUP_NEW'
                    : 'COM_SIMPLEHUB_GROUP_EDIT'
            ),
            'folder'
        );

        ToolbarHelper::apply('group.apply');
        ToolbarHelper::save('group.save');
        ToolbarHelper::save2new('group.save2new');
        ToolbarHelper::cancel('group.cancel');
    }
}