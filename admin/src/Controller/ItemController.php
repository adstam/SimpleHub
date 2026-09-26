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
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Response\JsonResponse;
use StamPlusJ\Component\Simplehub\Administrator\Support\ExternalUrlChecker;
use StamPlusJ\Component\Simplehub\Administrator\Support\LinkResolver;

final class ItemController extends FormController
{
    /**
     * View waarnaar wordt teruggekeerd.
     *
     * @var string
     */
    protected $view_list = 'dashboard';

    /**
     * Opent het formulier voor een nieuw Hub-item.
     *
     * Als de Add-actie vanuit een groep wordt gestart, wordt de
     * group_id uit de URL tijdelijk in de user state gezet.
     *
     * @param   string|null  $key     Primary key.
     * @param   string|null  $urlVar  URL-variable.
     *
     * @return  void
     */
    public function add($key = null, $urlVar = null): void
    {
        $groupId = $this->input->getInt('group_id');

        parent::add($key, $urlVar);

        if ($groupId > 0)
        {
            $this->app->setUserState(
                'com_simplehub.edit.item.data',
                (object) [
                    'group_id' => $groupId,
                ]
            );
        }
    }

    /**
     * Slaat een Hub-item op.
     *
     * De standaard FormController::save() wordt binnen SimpleHub
     * niet gebruikt, omdat de primaire sleutel van bestaande records
     * binnen deze architectuur niet betrouwbaar behouden blijft.
     *
     * De verdere afhandeling wordt bepaald door de task:
     *
     * - item.apply    = Save
     * - item.save     = Save & Close
     * - item.save2new = Save & New
     */
    public function save($key = null, $urlVar = null): void
    {
        $this->checkToken();

        $data = $this->input->post->get('jform', [], 'array');

        /** @var \StamPlusJ\Component\Simplehub\Administrator\Model\ItemModel $model */
        $model = $this->getModel();

        if (!$model->save($data))
        {
            /*
             * Bewaar de al ingevulde/gekozen formulierwaarden in de
             * user state, zodat het formulier bij het opnieuw tonen
             * (na de redirect hieronder) NIET wordt leeggemaakt. Dit
             * volgt dezelfde context-sleutel als ItemModel::loadFormData()
             * gebruikt ('com_simplehub.edit.item.data').
             */
            $this->app->setUserState(
                'com_simplehub.edit.item.data',
                (object) $data
            );

            $this->setRedirect(
                'index.php?option=com_simplehub&task=item.edit&id=' . (int) ($data['id'] ?? 0),
                $model->getError(),
                'error'
            );

            return;
        }

        /*
         * Bij een geslaagde opslag mag er geen "oude" formulierstate
         * blijven hangen, anders zou een volgend Add New/Edit-scherm
         * per ongeluk deze data weer tonen.
         */
        $this->app->setUserState(
            'com_simplehub.edit.item.data',
            null
        );

        /*
         * AdminModel::save() zet het primaire sleutelnummer van het
         * opgeslagen record in de modelstate. Dit is noodzakelijk bij
         * een nieuw record, omdat $data['id'] vóór de INSERT nog leeg is.
         */
        $id = (int) $model->getState('item.id');

        if ($id < 1)
        {
            $id = (int) ($data['id'] ?? 0);
        }

        if ($id < 1)
        {
            $this->setRedirect(
                'index.php?option=com_simplehub&view=dashboard',
                Text::_('COM_SIMPLEHUB_ITEM_SAVE_FAILED'),
                'error'
            );

            return;
        }

        $task = $this->getTask();

        /*
         * Save:
         * opslaan en op het huidige Item-formulier blijven.
         */
        if ($task === 'apply')
        {
            $this->setRedirect(
                'index.php?option=com_simplehub&task=item.edit&id=' . $id,
                Text::_('COM_SIMPLEHUB_ITEM_SAVED'),
                'message'
            );

            return;
        }

        /*
         * Save & New:
         * opslaan en een nieuw Item-formulier openen.
         */
		
		if ($task === 'save2new')
		{
			$groupId = (int) ($data['group_id'] ?? 0);

			$this->setRedirect(
				'index.php?option=com_simplehub&task=item.add&group_id=' . $groupId,
				Text::_('COM_SIMPLEHUB_ITEM_SAVED'),
				'message'
			);

			return;
}		

        /*
         * Save & Close:
         * opslaan en terug naar het Dashboard.
         */
        $this->setRedirect(
            'index.php?option=com_simplehub&view=dashboard',
            Text::_('COM_SIMPLEHUB_ITEM_SAVED'),
            'message'
        );
    }

    /**
     * Controleert de bereikbaarheid van een externe URL via AJAX.
     *
     * Wordt aangeroepen wanneer de gebruiker het URL-veld in het
     * formulier verlaat (blur), zodat de netwerkcontrole niet meer
     * bij het opslaan van het item hoeft plaats te vinden. Zie ook
     * media/js/item.js.
     *
     * Geeft altijd een JSON-response terug en beëindigt de request.
     */
    public function checkUrl(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();

        $url = $app->input->post->getString('url', '');

        $checker = new ExternalUrlChecker();

        $check = $checker->check($url);

        if ($check['reachable'])
        {
            echo new JsonResponse([
                'reachable' => true,
            ]);

            $app->close();
        }

        echo new JsonResponse(
            [
                'reachable' => false,
            ],
            $checker->describeFailure($check),
            true
        );

        $app->close();
    }

    /**
     * Bepaalt het automatisch bepaalde icoon voor een type/doel-combinatie
     * via AJAX.
     *
     * Wordt aangeroepen wanneer de gebruiker tijdens het invullen een
     * ander type of doel kiest, zodat het getoonde icoon meewerkt zonder
     * pagina-herlaad (Sprint 26). Hergebruikt dezelfde resolutie als het
     * Dashboard (HubRepository::getGroups()); zie ook
     * ARCHITECTURE_APPENDIX.md, hoofdstuk 10c.
     *
     * Geen aparte "geen icoon"-status: bij een leeg of ongeldig doel
     * levert LinkResolver altijd een bruikbare fallbackwaarde.
     *
     * Geeft altijd een JSON-response terug en beëindigt de request.
     */
    public function icon(): void
    {
        $this->checkToken();

        $app = Factory::getApplication();

        $type   = $app->input->post->getString('type', '');
        $target = $app->input->post->getString('target', '');

        $resolved = (new LinkResolver())->resolve($type, $target);

        echo new JsonResponse([
            'icon_type' => $resolved['icon_type'],
            'icon'      => $resolved['icon'],
        ]);

        $app->close();
    }

	/**
	 * Verwijdert een Hub-item.
	 */
	public function delete(): void
	{
		$this->checkToken();

		$id = $this->input->post->getInt('id');

		if ($id < 1)
		{
			$this->setRedirect(
				'index.php?option=com_simplehub&view=dashboard',
				Text::_('COM_SIMPLEHUB_ERROR_INVALID_ITEM'),
				'error'
			);

			return;
		}

		/** @var \StamPlusJ\Component\Simplehub\Administrator\Model\ItemModel $model */
		$model = $this->getModel();

		$pks = [$id];

		// Door het resultaat eerst op te slaan vermijd je reference-passing problemen in PHP 8+
		$deleted = $model->delete($pks);

		if ($deleted)
		{
			$this->setRedirect(
				'index.php?option=com_simplehub&view=dashboard',
				Text::_('COM_SIMPLEHUB_ITEM_DELETED'),
				'message'
			);

			return;
		}

		$this->setRedirect(
			'index.php?option=com_simplehub&view=dashboard',
			Text::_('COM_SIMPLEHUB_ITEM_DELETE_FAILED'),
			'error'
		);
	}
}