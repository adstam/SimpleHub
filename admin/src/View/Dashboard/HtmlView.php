<?php

	/**
	 * @package     Joomla.Administrator
	 * @subpackage  com_simplehub
	 *
	 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
	 * @license     GNU General Public License version 2 or later; see LICENSE.txt
	 */

	namespace StamPlusJ\Component\Simplehub\Administrator\View\Dashboard;

	\defined('_JEXEC') or die;

	use Joomla\CMS\Factory;
	use Joomla\CMS\Language\Text;
	use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
	use Joomla\CMS\Toolbar\ToolbarHelper;
	use StamPlusJ\Component\Simplehub\Administrator\Repository\HubRepository;

	final class HtmlView extends BaseHtmlView
	{
		/**
		 * Hubgroepen.
		 *
		 * @var array<int,array<string,mixed>>
		 */
		protected array $groups = [];

		/**
		 * Toont het dashboard.
		 *
		 * @param   string|null  $tpl  Template.
		 *
		 * @return void
		 */
		public function display($tpl = null): void
		{
			$repository = new HubRepository();

			$this->groups = $repository->getGroups();

			$wa = Factory::getApplication()
				->getDocument()
				->getWebAssetManager();

			$wa->useStyle('com_simplehub.dashboard.css');
			$wa->useScript('com_simplehub.dashboard');

			/*
			 * Voor de verwijdermodal bij Hubgroepen met items (Sprint 19).
			 * Joomla-eigen JoomlaDialog-script (Bootstrap-modals zijn sinds
			 * Joomla 5.1 vervangen/afgebouwd, zie ARCHITECTURE_APPENDIX.md,
			 * ADR-1); de autocreate-variant bindt de modal declaratief via
			 * het data-joomla-dialog-attribuut, zonder eigen JavaScript.
			 */
			$wa->useScript('joomla.dialog-autocreate');

			$this->addToolbar();

			parent::display($tpl);
		}

		/**
		 * Bouwt de toolbar op.
		 *
		 * @return void
		 */
		protected function addToolbar(): void
		{
			ToolbarHelper::title(
				Text::_('COM_SIMPLEHUB'),
				'grid'
			);

			ToolbarHelper::addNew(
				'group.add',
				'COM_SIMPLEHUB_NEW_GROUP'
			);

			ToolbarHelper::preferences('com_simplehub');
		}
	}