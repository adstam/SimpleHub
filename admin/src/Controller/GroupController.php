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

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use StamPlusJ\Component\Simplehub\Administrator\Repository\HubRepository;

final class GroupController extends FormController
{
    /**
     * View waarnaar wordt teruggekeerd.
     *
     * @var string
     */
    protected $view_list = 'dashboard';

    /**
     * Slaat een Hubgroep op.
     *
     * Hiermee wordt de standaard FormController::save() omzeild.
     * De standaard workflow verliest binnen SimpleHub de primaire
     * sleutel waardoor altijd een INSERT wordt uitgevoerd.
     */
    public function save($key = null, $urlVar = null): void
    {
        $this->checkToken();

        $data = $this->input->post->get('jform', [], 'array');

        /** @var \StamPlusJ\Component\Simplehub\Administrator\Model\GroupModel $model */
        $model = $this->getModel();

        if (!$model->save($data))
        {
            /*
             * Bewaar de al ingevulde formulierwaarden in de user state,
             * zodat het formulier bij het opnieuw tonen (na de redirect
             * hieronder) NIET wordt leeggemaakt. Dezelfde context-sleutel
             * als GroupModel::loadFormData() gebruikt
             * ('com_simplehub.edit.group.data'). Analoog aan
             * ItemController::save() (Sprint 17.5); zie ROADMAP_INTERN.md,
             * item #1.
             */
            $this->app->setUserState(
                'com_simplehub.edit.group.data',
                (object) $data
            );

            $this->setRedirect(
                'index.php?option=com_simplehub&view=group&layout=edit&id=' . (int) ($data['id'] ?? 0),
                $model->getError(),
                'error'
            );

            return;
        }

        /*
         * Bij een geslaagde opslag mag er geen "oude" formulierstate
         * blijven hangen, anders zou een volgend Add/Edit-scherm per
         * ongeluk deze data weer tonen.
         */
        $this->app->setUserState(
            'com_simplehub.edit.group.data',
            null
        );

        $task = $this->getTask();

        if ($task === 'apply')
        {
            $this->setRedirect(
                'index.php?option=com_simplehub&task=group.edit&id=' . (int) $data['id'],
                Text::_('COM_SIMPLEHUB_GROUP_SAVED'),
                'message'
            );

            return;
        }

        if ($task === 'save2new')
        {
            $this->setRedirect(
                'index.php?option=com_simplehub&task=group.add',
                Text::_('COM_SIMPLEHUB_GROUP_SAVED'),
                'message'
            );

            return;
        }

        $this->setRedirect(
            'index.php?option=com_simplehub&view=dashboard',
            Text::_('COM_SIMPLEHUB_GROUP_SAVED'),
            'message'
        );
    }

	/**
	 * Verplaatst de items van een Hubgroep naar een andere Hubgroep en
	 * verwijdert vervolgens de (inmiddels lege) bronproep.
	 *
	 * Dit is de serverside afhandeling van "Keuze B" uit de
	 * verwijdermodal op het Dashboard (zie admin/tmpl/dashboard/default.php):
	 * eerst worden de items van de bronproep verplaatst naar de gekozen
	 * doelgroep (HubRepository::moveItemsToGroup()), en pas daarna wordt
	 * de lege bronproep via het bestaande delete-pad verwijderd. Zie
	 * ARCHITECTURE_APPENDIX.md, ADR-2, voor de architectuurmotivatie om
	 * deze verplaatslogica in HubRepository te plaatsen in plaats van in
	 * GroupModel/GroupController.
	 */
	public function moveItemsAndDelete(): void
	{
		$this->checkToken();

		$id            = $this->input->post->getInt('id');
		$targetGroupId = $this->input->post->getInt('target_group_id');

		if ($id < 1)
		{
			$this->setRedirect(
				'index.php?option=com_simplehub&view=dashboard',
				Text::_('COM_SIMPLEHUB_ERROR_INVALID_GROUP'),
				'error'
			);

			return;
		}

		if ($targetGroupId < 1 || $targetGroupId === $id)
		{
			$this->setRedirect(
				'index.php?option=com_simplehub&view=dashboard',
				Text::_('COM_SIMPLEHUB_ERROR_INVALID_TARGET_GROUP'),
				'error'
			);

			return;
		}

		$repository = new HubRepository();

		try
		{
			$repository->moveItemsToGroup($id, $targetGroupId);
		}
		catch (\Throwable $e)
		{
			$this->setRedirect(
				'index.php?option=com_simplehub&view=dashboard',
				Text::_('COM_SIMPLEHUB_ERROR_MOVE_ITEMS_FAILED'),
				'error'
			);

			return;
		}

		/** @var \StamPlusJ\Component\Simplehub\Administrator\Model\GroupModel $model */
		$model = $this->getModel();

		$pks = [$id];

		// Door het resultaat eerst op te slaan vermijd je reference-passing problemen in PHP 8+
		$deleted = $model->delete($pks);

		if ($deleted)
		{
			$this->setRedirect(
				'index.php?option=com_simplehub&view=dashboard',
				Text::_('COM_SIMPLEHUB_GROUP_ITEMS_MOVED'),
				'message'
			);

			return;
		}

		/*
		 * De items zijn al verplaatst; alleen het verwijderen van de nu
		 * lege bronproep is mislukt. Dit melden we expliciet zodat de
		 * beheerder niet in de veronderstelling blijft dat er niets is
		 * gebeurd.
		 */
		$this->setRedirect(
			'index.php?option=com_simplehub&view=dashboard',
			Text::_('COM_SIMPLEHUB_GROUP_DELETE_FAILED'),
			'error'
		);
	}

	/**
	* Verwijdert een Hubgroep.
	*/
	public function delete(): void
	{
		$this->checkToken();

		$id = $this->input->post->getInt('id');

		if ($id < 1)
		{
			$this->setRedirect(
				'index.php?option=com_simplehub&view=dashboard',
				Text::_('COM_SIMPLEHUB_ERROR_INVALID_GROUP'),
				'error'
			);

			return;
		}

		/** @var \StamPlusJ\Component\Simplehub\Administrator\Model\GroupModel $model */
		$model = $this->getModel();

		$pks = [$id];

		// Door het resultaat eerst op te slaan vermijd je reference-passing problemen in PHP 8+
		$deleted = $model->delete($pks);

		if ($deleted)
		{
			$this->setRedirect(
				'index.php?option=com_simplehub&view=dashboard',
				Text::_('COM_SIMPLEHUB_GROUP_DELETED'),
				'message'
			);

			return;
		}

		$this->setRedirect(
			'index.php?option=com_simplehub&view=dashboard',
			Text::_('COM_SIMPLEHUB_GROUP_DELETE_FAILED'),
			'error'
		);
	}
}