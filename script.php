<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

/**
 * Installer script voor com_simplehub.
 *
 * Registreert bij installatie/upgrade een titelbalk-snelkoppeling als kern-Custom-module
 * ("mod_custom") op positie "title", altijd als eerste module in die positie. Geen eigen
 * SimpleHub-modultype nodig. Oorspronkelijk uitgewerkt als PoC in Sprint 24
 * (NEXT_INCREMENT.md, onderzoekspunt 3) en productierijp gemaakt in Sprint 25 (aan/uit-
 * koppeling met de module, definitief icoon, vaste moduletitel — zie ARCHITECTURE_APPENDIX.md,
 * ADR-6). Foutafhandeling rond de databasequery's staat nog als open punt in
 * ROADMAP_INTERN.md.
 *
 * Huidige Joomla-conventie (bevestigd tegen de officiële Joomla 6.0-documentatie,
 * manual.joomla.org, stap 6 "Adding a Script File"): het scriptbestand moet een
 * instantie van InstallerScriptInterface RETURNEN — Joomla zoekt niet naar een
 * class op naam. De klasse blijft bewust ongenamespaced (legacy-stijl, zoals in
 * de officiële voorbeelden), los van de `StamPlusJ\Component\Simplehub\...`
 * namespace die de rest van de component gebruikt.
 */
class Com_SimplehubInstallerScript implements InstallerScriptInterface
{
    /** Kern-modulenaam die wordt hergebruikt (geen eigen SimpleHub-module — zie sprintbeslissing). */
    private const MODULE_TYPE = 'mod_custom';

    /**
     * Vaste "note"-waarde op de modulerij, gebruikt om de eigen module terug te vinden
     * (idempotentie bij herinstallatie/upgrade, en bij deïnstallatie).
     */
    private const MODULE_NOTE = 'com_simplehub';

    /** Vaste doelpositie in het Atum-adminsjabloon. */
    private const MODULE_POSITION = 'title';

    public function preflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }

    public function install(InstallerAdapter $adapter): bool
    {
        $this->createOrRefreshTitlebarModule();

        return true;
    }

    public function update(InstallerAdapter $adapter): bool
    {
        $this->createOrRefreshTitlebarModule();

        return true;
    }

    public function uninstall(InstallerAdapter $adapter): bool
    {
        $this->removeTitlebarModule();

        return true;
    }

    public function postflight(string $type, InstallerAdapter $adapter): bool
    {
        return true;
    }

    /**
     * Maakt de titelbalk-snelkoppeling aan (nieuw), of ververst uitsluitend de
     * content van een reeds bestaande rij (install/update). De ordering/verschuif-
     * logica draait bewust alléén bij een echt nieuwe rij — anders zou de
     * titelmodule bij elke upgrade opnieuw een plekje opschuiven.
     */
    private function createOrRefreshTitlebarModule(): void
    {

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $existingId = $this->findExistingModuleId($db);

        if ($existingId !== null) {
            $this->refreshContent($db, $existingId);

            return;
        }

        $this->createTitlebarModule($db);
    }

    private function refreshContent(DatabaseInterface $db, int $id): void
    {
        $db->setQuery(
            $db->getQuery(true)
                ->update('#__modules')
                ->set($db->quoteName('content') . ' = ' . $db->quote($this->buildContent()))
                ->where($db->quoteName('id') . ' = ' . (int) $id)
        )->execute();
    }

    /**
     * Ordering 1 claimen voor een nieuwe rij: bestaande modules in de positie
     * worden eerst één plek opgeschoven i.p.v. dat wij op een negatieve waarde
     * onder het bestaande minimum gaan zitten — de kolom `ordering` is in
     * Joomla's kernschema UNSIGNED, dus een negatieve waarde kan stilzwijgend
     * naar 0 worden geclipt en zo alsnog achter de bestaande module belanden.
     */
    private function createTitlebarModule(DatabaseInterface $db): void
    {

        $db->setQuery(
            $db->getQuery(true)
                ->update('#__modules')
                ->set('ordering = ordering + 1')
                ->where($db->quoteName('position') . ' = ' . $db->quote(self::MODULE_POSITION))
                ->where($db->quoteName('client_id') . ' = 1')
        )->execute();

        /** @var \Joomla\CMS\Table\Module $table */
        $table = Table::getInstance('Module');

        /*
         * Sprint 35-audit (taaloptimalisatie, item #29): deze titel is BEWUST
         * geen taalsleutel. Zie ARCHITECTURE_APPENDIX.md, ADR-6 (Aanvulling —
         * Sprint 25): sleutels die vóór de taalinitialisatie nodig zijn
         * (installatie/upgrade) horen in een .sys.ini-bestand, en zelfs dan
         * blijft een eenmalig weggeschreven titel vastliggen op het
         * installatiemoment. Vaste Engelse titel, analoog aan Joomla's eigen
         * kernbeheermodules. Niet opnieuw omzetten naar Text::_() zonder deze
         * ADR te herzien.
         */
        $table->title     = 'SimpleHub - Quick Link';
        $table->note      = self::MODULE_NOTE;
        $table->content   = $this->buildContent();
        $table->position  = self::MODULE_POSITION;
        $table->module    = self::MODULE_TYPE;
        $table->access    = 1;
        $table->showtitle = 0;
        $table->params    = '{}';
        $table->client_id = 1;
        $table->language  = '*';
        $table->published = 1;
        $table->ordering  = 1;

        $table->store();

        // "Op alle pagina's tonen" voor Administrator-modules — zie openstaande verificatie hierboven.
        $insertQuery = $db->getQuery(true)
            ->insert('#__modules_menu')
            ->columns([$db->quoteName('moduleid'), $db->quoteName('menuid')])
            ->values($table->id . ', 0');
        $db->setQuery($insertQuery)->execute();
    }

    private function removeTitlebarModule(): void
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $id = $this->findExistingModuleId($db);

        if ($id === null) {
            return;
        }

        /** @var \Joomla\CMS\Table\Module $table */
        $table = Table::getInstance('Module');
        $table->delete($id);
    }

    private function findExistingModuleId(DatabaseInterface $db): ?int
    {
        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__modules')
            ->where($db->quoteName('note') . ' = ' . $db->quote(self::MODULE_NOTE))
            ->where($db->quoteName('position') . ' = ' . $db->quote(self::MODULE_POSITION));

        $id = $db->setQuery($query)->loadResult();

        return $id !== null ? (int) $id : null;
    }

    /**
     * Bouwt de modulecontent programmatisch (geen editor-tussenkomst), zodat de
     * relatieve-URL-omzetting van onderzoekspunt 1 (TinyMCE) hier niet optreedt.
     *
     * KEUZE (te toetsen tijdens de PoC): de CSS wordt inline meegegeven i.p.v. via
     * media/css/titlebar.css + joomla.asset.json. Een kern-Custom-module heeft geen
     * eigen hook om zelf een web asset te laden ($wa->useStyle() is niet beschikbaar
     * vanuit statische modulecontent); dat zou een apart mechanisme vereisen (bijv.
     * een systeemplugin op onBeforeRender) enkel voor twee CSS-regels — dat weegt
     * niet op tegen "Eenvoud vóór uitbreidbaarheid" (WAYOFWORK.md). Het los
     * meegeleverde titlebar.css/joomla.asset.json blijft als alternatief beschikbaar
     * mocht de PO toch voor gescheiden assets kiezen.
     *
     * Icoon (Sprint 25, taak 2): SimpleHub-logo in huisstijlkleuren, wit vlak
     * eronder zodat het icoon ook op een donkere titelbalk-achtergrond
     * (Atum donkere modus) goed leesbaar blijft.
     */
    private function buildContent(): string
    {
        $link = Route::_('index.php?option=com_simplehub');

        $svg = '<svg viewBox="0 0 800 800" width="20" height="20" aria-hidden="true">'
            . '<rect x="2" y="2" width="796" height="796" fill="#FFFFFF"/>'
            . '<rect x="50"  y="50"  width="197" height="197" fill="#DA251D"/>'
            . '<rect x="300" y="50"  width="197" height="197" fill="#000080"/>'
            . '<rect x="550" y="50"  width="197" height="197" fill="#DA251D"/>'
            . '<rect x="50"  y="300" width="197" height="197" fill="#000080"/>'
            . '<rect x="300" y="300" width="197" height="197" fill="#DA251D"/>'
            . '<rect x="550" y="300" width="197" height="197" fill="#000080"/>'
            . '<rect x="50"  y="550" width="197" height="197" fill="#DA251D"/>'
            . '<rect x="300" y="550" width="197" height="197" fill="#000080"/>'
            . '<rect x="550" y="550" width="197" height="197" fill="#DA251D"/>'
            . '</svg>';

        return \sprintf(
            '<style>.sh-titlebar-link{display:flex;align-items:center;align-self:center;'
                . 'height:100%%;padding:0 .75rem 0 .5rem;margin-right:.5rem;'
                . 'border-right:1px solid var(--border-color,rgba(0,0,0,.15));'
                . 'color:inherit;text-decoration:none}'
                . '.sh-titlebar-link:hover{text-decoration:none;opacity:.8}</style>'
            . '<a href="%s" class="sh-titlebar-link">%s'
                . '<span class="visually-hidden">%s</span>'
            . '</a>',
            \htmlspecialchars($link, \ENT_QUOTES),
            $svg,
            Text::_('COM_SIMPLEHUB')
        );
    }
}

// Cruciaal: Joomla gebruikt de returnwaarde van dit bestand als scriptobject,
// niet een class die op naam wordt opgezocht. Zonder deze regel gebeurt er
// stilzwijgend niets bij install/update/uninstall — zie toelichting hierboven.
return new Com_SimplehubInstallerScript();
