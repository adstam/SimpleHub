<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace StamPlusJ\Component\Simplehub\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\Database\ParameterType;
use StamPlusJ\Component\Simplehub\Administrator\Support\ExternalUrlChecker;
use StamPlusJ\Component\Simplehub\Administrator\Support\LinkResolver;

final class ItemModel extends AdminModel
{
    /**
     * @var string
     */
    protected $text_prefix = 'COM_SIMPLEHUB';

    /**
     * @var string
     */
    protected $table = '#__simplehub_items';

    /**
     * Haalt een Item op, aangevuld met het effectieve icoon (override
     * vóór automatisch).
     *
     * Hergebruikt dezelfde iconresolutie als het Dashboard
     * (HubRepository::getGroups()), zodat het Item-formulier nooit
     * kan afwijken van de Dashboardweergave. Sprint 26; zie
     * ARCHITECTURE.md, hoofdstuk 10c.
     *
     * @param   integer|null  $pk
     *
     * @return  object|false
     */
    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item === false) {
            return false;
        }

        /*
         * Sprint 28: de ruwe icon-kolom ($item->icon) blijft ongewijzigd
         * staan - dat is de brondata voor IconpickerField. De weer te
         * geven waarde (override vóór automatisch, ADR-7) komt in
         * aparte properties, zodat die de kolomwaarde niet langer
         * overschrijft zoals per abuis gebeurde in Sprint 26 (met als
         * gevolg dat elke Save de auto-waarde terugschreef naar de DB).
         */
        $override = trim((string) ($item->icon ?? ''));

        if ($override !== '') {
            $item->resolved_icon_type = 'override';
            $item->resolved_icon      = $override;

            return $item;
        }

        $resolved = (new LinkResolver())->resolve(
            (string) ($item->type ?? ''),
            (string) ($item->target ?? '')
        );

        $item->resolved_icon_type = $resolved['icon_type'];
        $item->resolved_icon      = $resolved['icon'];

        return $item;
    }

    /**
     * Haalt het formulier op.
     *
     * @param   array   $data
     * @param   bool    $loadData
     *
     * @return Form|false
     */
    public function getForm(
        $data = [],
        $loadData = true
    ) {
        $form = $this->loadForm(
            'com_simplehub.item',
            'item',
            [
                'control'   => 'jform',
                'load_data' => $loadData,
            ]
        );

        if (!$form) {
            return false;
        }

        if (!$loadData) {
            return $form;
        }

        /*
         * Gebruik dezelfde bron als de normale Joomla form-load-flow.
         *
         * Bij een validatiefout kunnen hier al target_* waarden
         * aanwezig zijn. Die mogen we NIET overschrijven met de
         * oude databasewaarde uit target.
         */
        $formData = $this->loadFormData();

        if (is_array($formData)) {
            $formData = (object) $formData;
        }

        if (!is_object($formData)) {
            return $form;
        }

        $type   = trim((string) ($formData->type ?? ''));
        $target = trim((string) ($formData->target ?? ''));

        /*
         * Vul het type-specifieke formulier-veld vanuit het
         * bestaande databaseveld 'target'.
         *
         * Alleen wanneer het betreffende formulier-veld nog
         * geen waarde heeft, vullen we het automatisch.
         *
         * Hierdoor:
         *
         * - bestaande items worden correct weergegeven;
         * - target_component / target_plugin / etc. worden
         *   automatisch gevuld;
         * - waarden die na een validatiefout door Joomla zijn
         *   aangeleverd, worden niet overschreven.
         */
        switch ($type) {
            case 'component':
                if (
                    trim((string) ($formData->target_component ?? '')) === ''
                    && $target !== ''
                ) {
                    $form->setValue(
                        'target_component',
                        null,
                        $target
                    );
                }
                break;

            case 'plugin':
                if (
                    trim((string) ($formData->target_plugin ?? '')) === ''
                    && $target !== ''
                ) {
                    $form->setValue(
                        'target_plugin',
                        null,
                        $target
                    );
                }
                break;

            case 'module':
                if (
                    trim((string) ($formData->target_module ?? '')) === ''
                    && $target !== ''
                ) {
                    $form->setValue(
                        'target_module',
                        null,
                        $target
                    );
                }
                break;

            case 'article':
                if (
                    trim((string) ($formData->target_article ?? '')) === ''
                    && $target !== ''
                ) {
                    $form->setValue(
                        'target_article',
                        null,
                        $target
                    );
                }
                break;

            case 'external':
                if (
                    trim((string) ($formData->target_external ?? '')) === ''
                    && $target !== ''
                ) {
                    $form->setValue(
                        'target_external',
                        null,
                        $target
                    );
                }
                break;
        }

        return $form;
    }

    /**
     * Slaat een Item op.
     *
     * De formuliervelden target_component, target_plugin,
     * target_module, target_article en target_external zijn
     * alleen UI-velden.
     *
     * Voor de database wordt de gekozen waarde teruggezet
     * naar het bestaande veld 'target'.
     *
     * Nieuwe items krijgen expliciet de eerstvolgende
     * ordering binnen hun groep.
     *
     * @param   array  $data
     *
     * @return bool
     */
    public function save($data): bool
    {
        $type = trim(
            (string) ($data['type'] ?? '')
        );

        /*
         * Zet de type-specifieke formulierwaarde terug naar
         * het bestaande databaseveld 'target'.
         */
        switch ($type) {
            case 'component':
                $data['target'] = trim(
                    (string) ($data['target_component'] ?? '')
                );
                break;

            case 'plugin':
                $data['target'] = trim(
                    (string) ($data['target_plugin'] ?? '')
                );
                break;

            case 'module':
                $data['target'] = trim(
                    (string) ($data['target_module'] ?? '')
                );
                break;

            case 'article':
                $data['target'] = trim(
                    (string) ($data['target_article'] ?? '')
                );
                break;

            case 'external':
                $data['target'] = trim(
                    (string) ($data['target_external'] ?? '')
                );
                break;

            default:
                $data['target'] = trim(
                    (string) ($data['target'] ?? '')
                );
                break;
        }

        $target = $data['target'];

        /*
         * Component
         */
        if ($type === 'component') {
            if ($target === '') {
                $this->setError(
                    Text::_('COM_SIMPLEHUB_ERROR_COMPONENT_REQUIRED')
                );

                return false;
            }

            $component = ComponentHelper::getComponent(
                $target,
                true
            );

            if (
                !$component
                || !(int) $component->enabled
            ) {
                $this->setError(
                    Text::_('COM_SIMPLEHUB_ERROR_COMPONENT_INVALID')
                );

                return false;
            }
        }

        /*
         * Plugin
         */
        if ($type === 'plugin') {
            if ($target === '') {
                $this->setError(
                    Text::_('COM_SIMPLEHUB_ERROR_PLUGIN_REQUIRED')
                );

                return false;
            }

            $targetId = (int) $target;

            $query = $this->getDatabase()->createQuery()
                ->select(
                    $this->getDatabase()->quoteName('extension_id')
                )
                ->from(
                    $this->getDatabase()->quoteName('#__extensions')
                )
                ->where(
                    $this->getDatabase()->quoteName('extension_id')
                    . ' = :id'
                )
                ->where(
                    $this->getDatabase()->quoteName('type')
                    . ' = '
                    . $this->getDatabase()->quote('plugin')
                )
                ->bind(
                    ':id',
                    $targetId,
                    ParameterType::INTEGER
                );

            $this->getDatabase()->setQuery($query);

            if (!$this->getDatabase()->loadResult()) {
                $this->setError(
                    Text::_('COM_SIMPLEHUB_ERROR_PLUGIN_INVALID')
                );

                return false;
            }
        }

        /*
         * Module
         */
        if ($type === 'module') {
            if ($target === '') {
                $this->setError(
                    Text::_('COM_SIMPLEHUB_ERROR_MODULE_REQUIRED')
                );

                return false;
            }

            $targetId = (int) $target;

            $query = $this->getDatabase()->createQuery()
                ->select(
                    $this->getDatabase()->quoteName('id')
                )
                ->from(
                    $this->getDatabase()->quoteName('#__modules')
                )
                ->where(
                    $this->getDatabase()->quoteName('id')
                    . ' = :id'
                )
                ->where(
                    $this->getDatabase()->quoteName('published')
                    . ' = 1'
                )
                ->bind(
                    ':id',
                    $targetId,
                    ParameterType::INTEGER
                );

            $this->getDatabase()->setQuery($query);

            if (!$this->getDatabase()->loadResult()) {
                $this->setError(
                    Text::_('COM_SIMPLEHUB_ERROR_MODULE_INVALID')
                );

                return false;
            }
        }

        /*
         * Artikel
         */
        if ($type === 'article') {
            if ($target === '') {
                $this->setError(
                    Text::_('COM_SIMPLEHUB_ERROR_ARTICLE_REQUIRED')
                );

                return false;
            }

            $targetId = (int) $target;

            $query = $this->getDatabase()->createQuery()
                ->select(
                    $this->getDatabase()->quoteName('id')
                )
                ->from(
                    $this->getDatabase()->quoteName('#__content')
                )
                ->where(
                    $this->getDatabase()->quoteName('id')
                    . ' = :id'
                )
                ->bind(
                    ':id',
                    $targetId,
                    ParameterType::INTEGER
                );

            $this->getDatabase()->setQuery($query);

            if (!$this->getDatabase()->loadResult()) {
                $this->setError(
                    Text::_('COM_SIMPLEHUB_ERROR_ARTICLE_INVALID')
                );

                return false;
            }
        }

        /*
         * Externe URL
         *
         * Bij het opslaan controleren we alleen de VORM van de URL
         * (schema, hostnaam, allow-list) via checkFormat(). Dit is
         * een instant, netwerkloze controle die o.a. beschermt tegen
         * een niet-toegestane host (SSRF), zonder dat daarvoor een
         * externe server bereikbaar hoeft te zijn.
         *
         * De daadwerkelijke bereikbaarheid (HTTP-verzoek, kan tot
         * enkele seconden duren en kan tijdelijk falen) wordt NIET
         * meer bij het opslaan gecontroleerd. Die controle gebeurt
         * nu client-side via AJAX op het moment dat de gebruiker het
         * URL-veld verlaat (zie ItemController::checkUrl() en
         * media/js/item.js). Hierdoor blijft het formulier bij een
         * opslaanpoging altijd behouden, ook wanneer de externe
         * server op dat moment niet bereikbaar is.
         */
        if ($type === 'external') {
            if ($target === '') {
                $this->setError(
                    Text::_('COM_SIMPLEHUB_ERROR_EXTERNAL_REQUIRED')
                );

                return false;
            }

            $checker = new ExternalUrlChecker();

            $check = $checker->checkFormat($target);

            if (!$check['reachable']) {
                $this->setError(
                    $checker->describeFailure($check)
                );

                return false;
            }
        }

        /*
         * Nieuwe items expliciet achteraan plaatsen.
         *
         * Bij een bestaand item laten we de bestaande ordering
         * volledig ongemoeid. Hierdoor blijft handmatig
         * herschikken intact.
         */
        $itemId = (int) ($data['id'] ?? 0);

        if ($itemId < 1) {
            $groupId = (int) ($data['group_id'] ?? 0);

            if ($groupId < 1) {
                $this->setError(
                    Text::_('COM_SIMPLEHUB_ERROR_GROUP_REQUIRED')
                );

                return false;
            }

            $query = $this->getDatabase()->createQuery()
                ->select(
                    'COALESCE(MAX('
                    . $this->getDatabase()->quoteName('ordering')
                    . '), 0)'
                )
                ->from(
                    $this->getDatabase()->quoteName(
                        '#__simplehub_items'
                    )
                )
                ->where(
                    $this->getDatabase()->quoteName('group_id')
                    . ' = :groupId'
                )
                ->bind(
                    ':groupId',
                    $groupId,
                    ParameterType::INTEGER
                );

            $this->getDatabase()->setQuery($query);

            $maxOrdering = (int) $this->getDatabase()->loadResult();

            $data['ordering'] = $maxOrdering + 1;
        }

        unset(
            $data['target_component'],
            $data['target_plugin'],
            $data['target_module'],
            $data['target_article'],
            $data['target_external']
        );

        return parent::save($data);
    }

    /**
     * Laadt de formulierdata.
     *
     * Eerst wordt gekeken of er formulierdata in de user state
     * staat, bijvoorbeeld na een validatiefout.
     *
     * Als die data niet aanwezig is, wordt het bestaande record
     * uit de database geladen.
     *
     * Dit behoudt tevens de bestaande werking waarbij de
     * controller vooraf group_id in de user state kan plaatsen.
     */
    protected function loadFormData(): object
    {
        $data = Factory::getApplication()->getUserState(
            'com_simplehub.edit.item.data',
            null
        );

        if ($data === null) {
            $data = $this->getItem();
        }

        if (is_array($data)) {
            return (object) $data;
        }

        return $data;
    }
}