<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace StamPlusJ\Component\Simplehub\Administrator\View\Item;

\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
	/**
     * Formulier.
     *
     * @var \Joomla\CMS\Form\Form
     */
    protected $form;

    /**
     * Item.
     *
     * @var object
     */
    protected $item;

    /**
     * Display the view.
     *
     * @param   string  $tpl  Template.
     *
     * @return  void
     */
    public function display($tpl = null): void
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        if ($this->form === false)
        {
            throw new \RuntimeException(
                Text::_('COM_SIMPLEHUB_ERROR_FORM_LOAD')
            );
        }

        $isNew = empty($this->item->id);
				
		ToolbarHelper::apply('item.apply');
		ToolbarHelper::save('item.save');
		ToolbarHelper::save2new('item.save2new');
		ToolbarHelper::cancel('item.cancel');

        $this->document->setTitle(
            $isNew
                ? Text::_('COM_SIMPLEHUB_ITEM_NEW')
                : Text::_('COM_SIMPLEHUB_ITEM_EDIT')
        );


		$this->document
			->getWebAssetManager()
			->useScript('com_simplehub.item');

		Text::script('COM_SIMPLEHUB_EXTERNAL_URL_CHECKING');
	
        parent::display($tpl);
    }
}