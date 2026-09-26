
# SimpleHub – Architectuurinventaris

## Doel

Dit document vormt een inventaris van de actuele implementatie van de SimpleHub-component.

In tegenstelling tot `ARCHITECTURE.md`, waarin de softwarearchitectuur wordt beschreven, documenteert deze bijlage de concrete implementatie van de component.

De inventaris dient als referentie voor toekomstige doorontwikkeling en AI-ondersteunde
ontwikkelomgevingen, zodat niet steeds de volledige codebasis vooraf hoeft te worden geanalyseerd.

Dit document beschrijft uitsluitend de actuele implementatie.

---

# Statuswaarden

| Status    | Betekenis                                          |
| --------- | -------------------------------------------------- |
| Actief    | Onderdeel van de huidige implementatie             |
| Tijdelijk | Tijdelijke implementatie die later wordt vervangen |
| Gepland   | Voorzien maar nog niet geïmplementeerd             |
| Verouderd | Nog aanwezig maar gepland voor verwijdering        |

---

# Administrator

## DisplayController

**Bestand**

```text
administrator/src/Controller/DisplayController.php
```

**Status**

Actief

**Verantwoordelijkheid**

Startpunt van de administratorcomponent.

Stelt de standaardweergave (`dashboard`) vast.

---

## GroupController

**Bestand**

```text
administrator/src/Controller/GroupController.php
```

**Status**

Actief

**Verantwoordelijkheid**

Controller voor CRUD-bewerkingen op Hubgroepen.

Verantwoordelijk voor:

* Add
* Edit
* Save
* Apply
* Save & New
* Delete
* `moveItemsAndDelete()` — verplaatst eerst de items van de groep naar een gekozen doelgroep
  (`HubRepository::moveItemsToGroup()`) en verwijdert daarna de lege bronproep via het bestaande
  delete-pad. Serverside afhandeling van "Keuze B" uit de verwijdermodal; zie ADR-2.
* Redirects

De controller bevat een eigen implementatie van `save()` omdat de standaard Joomla `FormController::save()` binnen de gekozen Dashboard-architectuur de primaire sleutel van bestaande records niet correct behoudt. `save()` zet, net als `ItemController::save()`, de ingevoerde `jform`-waarden bij een mislukte opslag terug in de user state (`com_simplehub.edit.group.data`) en ruimt deze op bij een geslaagde opslag.

**Afhankelijkheden**

* GroupModel
* HubRepository (uitsluitend voor `moveItemsAndDelete()`)

---

## GroupsController

**Bestand**

```text
administrator/src/Controller/GroupsController.php
```

**Status**

Actief

**Verantwoordelijkheid**

Verwerkt de Ajax-aanvraag voor het opslaan van de nieuwe sorteervolgorde van Hubgroepen.

Verantwoordelijk voor:

* ontvangen van de groepsvolgorde;
* converteren van ontvangen groeps-ID's;
* aanroepen van de ordering-opslag;
* retourneren van een `JsonResponse`;
* foutafhandeling.

De controller bevat geen directe database-logica.

**Afhankelijkheden**

* HubRepository
* Joomla `JsonResponse`

---

## ItemsController

```
administrator/src/Controller/ItemsController.php
```

**Status**

Actief

**Verantwoordelijkheid**

Verwerkt de Ajax-aanvraag voor het opslaan van de nieuwe sorteervolgorde van Items binnen één Hubgroep.

Verantwoordelijk voor:

* CSRF-tokencontrole;
* ontvangen van de Hubgroep-ID;
* ontvangen en normaliseren van de Item-ID's;
* aanroepen van de Item-ordering opslag;
* retourneren van een JsonResponse;
* foutafhandeling.

De controller bevat geen directe database-logica.

**Afhankelijkheden**

HubRepository
Joomla JsonResponse

---

## ItemController

**Bestand**

```text
administrator/src/Controller/ItemController.php
```

**Status**

Actief

**Verantwoordelijkheid**

Controller voor CRUD-bewerkingen op Hub-items.

Verantwoordelijk voor:

* Add (inclusief overname van `group_id` uit de URL naar de user state, zodat een nieuw item wordt
  gekoppeld aan de groep vanwaaruit "Add" is gestart);
* Save (Apply / Save & Close / Save & New);
* `checkUrl()` — Ajax-actie die de bereikbaarheid van een externe URL controleert via
  `ExternalUrlChecker::check()`, aangeroepen vanuit `media/js/item.js` bij het verlaten van het
  URL-veld;
* `icon()` — Ajax-actie die het effectieve icoon (override vóór automatisch) van een (nog niet
  opgeslagen) Item teruggeeft, naar het patroon van `checkUrl()`. Aangeroepen vanuit `media/js/item.js`
  bij het wijzigen van type/doel, zodat de icoon-preview in het Item-formulier live meebeweegt. Roept
  dezelfde iconresolutie aan als `ItemModel::getItem()`;
* Delete;
* Redirects.

Bevat, net als `GroupController`, een eigen `save()`-implementatie omdat de standaard Joomla
`FormController::save()` de primaire sleutel van bestaande records niet correct behoudt binnen deze
architectuur. `ItemController::save()` zet, net als `GroupController::save()`, de ingevoerde
`jform`-waarden bij een mislukte opslag terug in de user state (`com_simplehub.edit.item.data`), zodat
het formulier niet leeg wordt getoond.

**Afhankelijkheden**

* ItemModel
* ExternalUrlChecker

---

## TitlebarController

**Bestand**

```text
administrator/src/Controller/TitlebarController.php
```

**Status**

Actief

**Verantwoordelijkheid**

Verwerkt de Ajax-actie `toggle()` vanuit het schuifje in de SimpleHub-Opties (`TitlebarStateField`):
publiceert/depubliceert de titelbalk-snelkoppelingsmodule rechtstreeks, zonder tussenliggende opslag van
een eigen componentinstelling. Zie ADR-6.

**Afhankelijkheden**

* TitlebarModuleRepository

---

## TitlebarModuleRepository

**Bestand**

```text
administrator/src/Repository/TitlebarModuleRepository.php
```

**Status**

Actief (foutafhandeling rond de databasequery's nog niet uitgewerkt — zie ROADMAP_INTERN.md)

**Verantwoordelijkheid**

Zoekt en wijzigt de `published`-status van de door `script.php` geregistreerde titelbalk-Custom-module
(herkend via `note = 'com_simplehub'`). Aangeroepen door `TitlebarController::toggle()`.

---

## TitlebarStateField

**Bestand**

```text
administrator/src/Field/TitlebarStateField.php
```

**Status**

Actief

**Verantwoordelijkheid**

Custom form field type voor de SimpleHub-Opties: rendert het aan/uit-schuifje
(Bootstrap `form-check form-switch`) voor de titelbalk-snelkoppeling, gekoppeld aan de daadwerkelijke
`published`-status van de module (geen eigen opgeslagen instelling). Vereist `addfieldprefix` in
`admin/config.xml` om binnen de `com_config`-renderomgeving gevonden te worden.

---

## IconpickerField

**Bestand**

```text
administrator/src/Field/IconpickerField.php
```

**Status**

Actief

**Verantwoordelijkheid**

Custom form field type voor het `icon`-veld in `item.xml` (`addfieldprefix`), naar het patroon van
`TitlebarStateField`. Rendert een preview, een verborgen invoerveld en een JoomlaDialog-popup met een
statisch gerenderde, doorzoekbare iconengrid (geen Ajax-rondje voor de iconenlijst zelf). Hergebruikt het
door Joomla core al systeembreed geladen `fontawesome`-webasset. Zie ARCHITECTURE_APPENDIX.md, ADR-7.

**Afhankelijkheden**

* FontAwesomeIcons

---

## FontAwesomeIcons

**Bestand**

```text
administrator/src/Support/FontAwesomeIcons.php
```

**Status**

Actief (curated selectie, niet rechtstreeks geverifieerd tegen de daadwerkelijk geladen
`joomla-fontawesome`-webasset — zie ROADMAP_INTERN.md)

**Verantwoordelijkheid**

Statische, curated lijst van FontAwesome-iconnamen (Solid, Brands, de door Joomla meegeleverde
Regular-subset), gegroepeerd per stijl, gebruikt door `IconpickerField` om de iconengrid te renderen.

---

## Dashboard HtmlView

**Bestand**

```text
administrator/src/View/Dashboard/HtmlView.php
```

**Status**

Actief

**Verantwoordelijkheid**

* Toolbar opbouwen
* Hubgegevens laden
* Dashboardgegevens beschikbaar stellen
* Dashboard-assets activeren, inclusief `joomla.dialog-autocreate` t.b.v. de verwijdermodal bij
  Hubgroepen met items — zie ADR-1.

**Afhankelijkheden**

* HubRepository
* Joomla Toolbar
* WebAssetManager

---

## Group HtmlView

**Bestand**

```text
administrator/src/View/Group/HtmlView.php
```

**Status**

Actief

**Verantwoordelijkheid**

Toont het formulier voor het toevoegen en wijzigen van Hubgroepen.

**Afhankelijkheden**

* GroupModel
* Joomla Form API

---

## Item HtmlView

**Bestand**

```text
administrator/src/View/Item/HtmlView.php
```

**Status**

Actief

**Verantwoordelijkheid**

Toont het formulier voor het toevoegen en wijzigen van Hub-items.

**Afhankelijkheden**

* ItemModel
* Joomla Form API

---

## GroupModel

**Bestand**

```text
administrator/src/Model/GroupModel.php
```

**Status**

Actief

**Verantwoordelijkheid**

Joomla AdminModel voor Hubgroepen.

Verantwoordelijk voor:

* Formulier laden
* Record laden
* Validatie
* Opslaan
* Verwijderen

**Afhankelijkheden**

* GroupTable
* Joomla AdminModel

---

## GroupTable

**Bestand**

```text
administrator/src/Table/GroupTable.php
```

**Status**

Actief

**Verantwoordelijkheid**

Databasekoppeling tussen het Group-object en de tabel `#__simplehub_groups`.

---

## ItemModel

**Bestand**

```text
administrator/src/Model/ItemModel.php
```

**Status**

Actief

**Verantwoordelijkheid**

Joomla AdminModel voor Hub-items.

Verantwoordelijk voor:

* `getItem()`: haalt het item op en vult het aan met het effectieve icoon (`resolved_icon_type`/
  `resolved_icon`) — een handmatig gekozen icoon (niet-lege `icon`-kolom) krijgt voorrang boven het via
  `LinkResolver` automatisch bepaalde icoon. De ruwe `icon`-kolomwaarde zelf blijft ongemoeid (dit is de
  brondata voor `IconpickerField`); zie ADR-7 voor de achtergrond van deze scheiding;
* Formulier laden, waarbij bij een validatiefout de al gekozen `target_*`-waarden uit de user state
  worden gebruikt in plaats van de (oude) databasewaarde;
* normaliseren van de vijf type-afhankelijke doelvelden (`target_component`, `target_plugin`,
  `target_module`, `target_article`, `target_external`) naar één opgeslagen `target`-waarde;
* type-afhankelijke validatie bij opslaan, waaronder de vormcontrole en host-allowlist voor het type
  `external` via `ExternalUrlChecker::checkFormat()` (uitsluitend bij een nieuw `external`-item of een
  gewijzigde URL, zodat niet bij iedere opslag opnieuw gevalideerd hoeft te worden);
* opslaan en verwijderen (via `AdminModel`).

**Afhankelijkheden**

* ItemTable
* ExternalUrlChecker
* Joomla AdminModel

---

## ItemTable

**Bestand**

```text
administrator/src/Table/ItemTable.php
```

**Status**

Actief

**Verantwoordelijkheid**

Databasekoppeling tussen het Item-object en de tabel `#__simplehub_items`.

---

## HubRepository

**Bestand**

```text
administrator/src/Repository/HubRepository.php
```

**Status**

Actief

**Verantwoordelijkheid**

Centrale repository voor Hub-specifieke databasefunctionaliteit.

Verantwoordelijk voor:

* uitlezen van Hubgroepen (`getGroups()`), inclusief het via `LinkResolver` bepalen van link,
  beschikbaarheidsstatus en icoon per Item;
* uitlezen van Hub-items;
* voorbereiden van Dashboardgegevens;
* valideren van Hubgegevens (`validateItem()`);
* opslaan van de sorteervolgorde van Hubgroepen (`saveGroupOrder()`);
* valideren en opslaan van de sorteervolgorde van Items binnen een Hubgroep (`saveItemOrder()`);
* tellen van het werkelijke aantal items per groep, ongeacht published-status (`loadItemCounts()`,
  gebruikt door `getGroups()` t.b.v. de verwijdermodal — de `items`-lijst zelf bevat immers uitsluitend
  gepubliceerde items);
* verplaatsen van alle items van één groep naar een andere groep, transactioneel en met een geldige
  ordening (`moveItemsToGroup()`). Zie ADR-2.

De repository vormt daarmee de centrale gegevenslaag voor het Dashboard en voor de specifieke ordering-flow.

De repository bevat geen gebruikersinterface en geen standaard CRUD-flow voor individuele records.

De repository wordt op alle aanroepplekken rechtstreeks geïnstantieerd (`new HubRepository()`) en is
niet geregistreerd in `services/provider.php` — zie `ROADMAP_INTERN.md`, item #4.

**Afhankelijkheden**

* Database
* LinkResolver

---

## LinkResolver

**Bestand**

```text
administrator/src/Support/LinkResolver.php
```

**Status**

Actief

**Verantwoordelijkheid**

Zet het opgeslagen `type` + `target` van een Hub-item om naar een bruikbare Joomla-administratorlink,
een beschikbaarheidsstatus en een icoon (type + waarde). Bevat een aparte resolver-methode per itemtype
(component, plugin, module, artikel, extern) — zie `ARCHITECTURE.md`, hoofdstuk 10c, voor de volledige
beschrijving inclusief de dynamische iconresolutie via de administrator-menuhiërarchie.

Wordt momenteel uitsluitend aangeroepen vanuit `HubRepository::getGroups()`. Instantiatie gebeurt
rechtstreeks (`new LinkResolver()`), niet via de DI-container — zie `ROADMAP_INTERN.md`, item #4.

**Afhankelijkheden**

* Joomla administrator-menu (`#__menu`)
* Joomla extensie- en modulegegevens (`#__extensions`, `#__modules`)

---

## ExternalUrlChecker

**Bestand**

```text
administrator/src/Support/ExternalUrlChecker.php
```

**Status**

Actief

**Verantwoordelijkheid**

Beoordeelt een externe URL (itemtype `external`) in twee gescheiden lagen: een synchrone vormcontrole
met host-allowlist (`checkFormat()`, geen HTTP-verzoek) en een volledige controle inclusief
daadwerkelijk HTTP-verzoek (`check()`). Zie `ARCHITECTURE.md`, hoofdstuk 10d, voor de volledige
beschrijving, de architectuurmotivatie voor deze scheiding, en de bekende, nog openstaande
SSRF-redirect-bevinding (`ROADMAP_INTERN.md`, item #2).

**Afhankelijkheden**

* Joomla `HttpFactory`
* Joomla Logger (categorie `com_simplehub.externalurl`)

---

# JavaScript

## dashboard.js

**Bestand**

```textdash
media/js/dashboard.js
```

**Status**

Actief

**Verantwoordelijkheid**

Verzorgt de client-side drag & drop-functionaliteit voor Hubgroepen.

Verantwoordelijk voor:

* initialiseren van SortableJS;
* initialiseren van Group ordering;
* initialiseren van Item ordering per Hubgroep;
* detecteren van wijzigingen in de Group-volgorde;
* detecteren van wijzigingen in de Item-volgorde;
* verzamelen van Group-ID's;
* verzamelen van Item-ID's;
* meesturen van de betreffende Hubgroep-ID bij Item ordering;
* verzenden van nieuwe volgordes via Ajax;
* verwerken van JSON-resultaten;
* tonen van ordering-fouten op het Dashboard;
* onderscheppen van klikken op externe items met `data-sh-target="popup"` en deze, via
  `window.open()`, in een apart, los venster openen in plaats van in het tabblad/venster van de
  standaard-`target`-fallback. Zie ADR-4.

Bevat geen achtergebleven debugregels (`console.log`/`console.warn`).

---

## group.js

**Bestand**

```text
media/js/group.js
```

**Status**

Actief

**Verantwoordelijkheid**

Formulierinteractie op het Group-formulier.

Bevat geen achtergebleven debugregels.

---

## item.js

**Bestand**

```text
media/js/item.js
```

**Status**

Actief

**Verantwoordelijkheid**

Formulierinteractie op het Item-formulier.

Verantwoordelijk voor:

* tonen/verbergen van de type-afhankelijke doelvelden;
* automatisch voorstellen van een titel op basis van het gekozen target, zonder een bestaande titel te
  overschrijven;
* bij het verlaten (`blur`) van het `target_external`-veld: aanroepen van de Ajax-actie
  `item.checkUrl` en tonen van een statusmelding onder het veld, zonder het formulier op te slaan of
  opnieuw te laden.

Bevat geen achtergebleven debugregels.

---

## titlebar-config.js

**Bestand**

```text
media/js/titlebar-config.js
```

**Status**

Actief (bevat nog hardgecodeerde Nederlandse teksten — zie ROADMAP_INTERN.md)

**Verantwoordelijkheid**

Formulierinteractie voor het aan/uit-schuifje in de SimpleHub-Opties (`TitlebarStateField`): stuurt de
Ajax-aanroep naar `TitlebarController::toggle()` en werkt de zichtbare schuifjesstatus direct bij.
Geregistreerd als eigen Web Asset `com_simplehub.titlebar-config`.

---

## SortableJS

**Bestand**

```text
media/js/vendor/sortable.min.js
```

**Status**

Actief

**Verantwoordelijkheid**

Externe JavaScript-library voor de drag & drop-functionaliteit.

De library wordt als Joomla Web Asset geregistreerd onder de assetnaam:

```text
com_simplehub.sortable
```

---

## joomla.asset.json

**Bestand**

```text
media/joomla.asset.json
```

**Status**

Actief

**Verantwoordelijkheid**

Definieert de Joomla Web Assets van SimpleHub.

Bevat onder andere:

* `com_simplehub.sortable`
* `com_simplehub.dashboard`

`com_simplehub.dashboard` heeft een dependency op `com_simplehub.sortable`.

Hierdoor wordt de afhankelijkheid tussen de component-JavaScript en SortableJS door Joomla's Web Asset Manager afgehandeld.

---

# Forms

## group.xml

**Bestand**

```text
administrator/forms/group.xml
```

**Status**

Actief

**Verantwoordelijkheid**

Definitie van het Joomla formulier voor Hubgroepen.

---

## item.xml

**Bestand**

```text
administrator/forms/item.xml
```

**Status**

Actief

**Verantwoordelijkheid**

Definitie van het Joomla-formulier voor Hub-items, inclusief de vijf type-afhankelijke doelvelden
(zie `ARCHITECTURE.md`, hoofdstuk 10b).

Bevat nog een `icon`-veld dat geen functie heeft in de runtime-resolutie — zie `ROADMAP_INTERN.md`,
item #5.

Het `external_target`-veld (list) biedt drie waarden: `_blank` (nieuw tabblad), `_self` (huidig venster)
en `popup` (nieuw, los venster — client-side afgehandeld door `dashboard.js`, zie ADR-4). `popup` is
bewust geen geldige HTML `target`-waarde (die zijn beperkt tot `_blank`/`_self`/`_parent`/`_top`).

---

# Templates

## Dashboard

**Bestand**

```text
administrator/tmpl/dashboard/default.php
```

**Status**

Actief

**Verantwoordelijkheid**

Dashboardweergave.

Bevat uitsluitend presentatielogica.

De groepen worden als sorteerbare elementen weergegeven voor de drag & drop-functionaliteit.

De groepen worden als sorteerbare elementen weergegeven.

Binnen iedere Hubgroep worden de Items eveneens als afzonderlijke sorteerbare
elementen weergegeven.

De template levert uitsluitend de HTML-structuur, data-attributen en
drag-handles die door `dashboard.js` en SortableJS worden gebruikt.

De template bevat geen database- of orderinglogica.

De itemlink krijgt, wanneer `external_target === 'popup'`, naast de bestaande `target="_blank"`-fallback
(progressive enhancement) ook een `data-sh-target="popup"`-attribuut, waarop `dashboard.js` aanhaakt om
het venster als apart popup-venster te openen. Zie ADR-4.

De template rendert daarnaast, per Hubgroep met items, een verborgen `<template>`-element met de inhoud
van de verwijdermodal (waarschuwing, keuze A/B), gekoppeld aan de trashknop via het
`data-joomla-dialog`-attribuut. Groepen zonder items behouden de bestaande, kale
`onsubmit="confirm(...)"`-afhandeling. Zie ADR-1.

---

## Group Edit

**Bestand**

```text
administrator/tmpl/group/edit.php
```

**Status**

Actief

**Verantwoordelijkheid**

Formulier voor toevoegen en wijzigen van Hubgroepen.

---

## Item Edit

**Bestand**

```text
administrator/tmpl/item/edit.php
```

**Status**

Actief

**Verantwoordelijkheid**

Formulier voor toevoegen en wijzigen van Hub-items, inclusief de type-afhankelijke doelvelden.

Toont (nog) niet het door `LinkResolver` bepaalde icoon — dat wordt vooralsnog uitsluitend op het
Dashboard getoond. Zie `ROADMAP_INTERN.md`, item #14.

---

# Styling

## dashboard.css

**Bestand**

```text
media/css/dashboard.css
```

**Status**

Actief

**Verantwoordelijkheid**

Dashboard-opmaak.

---

## titlebar.css

**Bestand**

```text
media/css/titlebar.css
```

**Status**

Actief

**Verantwoordelijkheid**

Opmaak van de titelbalk-snelkoppeling (icoon, verticale scheidingslijn, verticale centrering t.o.v. de
paginatitel), meegegeven als onderdeel van de door `script.php` geregistreerde Custom-module-inhoud.

---

# Web Assets

SimpleHub gebruikt Joomla's Web Asset Manager voor het laden van component-CSS en JavaScript.

De assets worden beschreven in:

```text
media/joomla.asset.json
```

De Dashboard-view activeert de benodigde assets via:

```text
WebAssetManager
```

De JavaScript-afhankelijkheid is:

```text
com_simplehub.dashboard
        │
        ▼
com_simplehub.sortable
```

Hierdoor wordt SortableJS vóór `dashboard.js` geladen.

---

# Configuratie

## config.xml

**Bestand**

```text
administrator/config.xml
```

**Status**

Actief

---

## services/provider.php

**Bestand**

```text
administrator/services/provider.php
```

**Status**

Actief

**Verantwoordelijkheid**

Registratie van alle Joomla services.

---

## Extension

### SimplehubComponent

**Bestand**

```text
administrator/src/Extension/SimplehubComponent.php
```

**Status**

Actief

**Verantwoordelijkheid**

Registratie van de MVC-component en het beschikbaar stellen van de component-capabilities.

---

# Database

## install.mysql.sql

**Status**

Actief

---

## uninstall.mysql.sql

**Status**

Actief

---

## updates

**Status**

Actief

Database-updates voor toekomstige componentversies.

De aanwezige SQL-bestanden kunnen als dummy/updatebestand aanwezig zijn om de `<updates>`-structuur in het installatie- en update-manifest te ondersteunen.

---

# Media-installatie

De component gebruikt in het installatie-manifest een afzonderlijk `<media>`-blok.

De structuur is:

```text
media/
├── css/
└── js/
    └── vendor/
```

De `media`-bestanden worden hiermee geïnstalleerd naar:

```text
media/com_simplehub/
```

De Joomla Web Asset-definitie bevindt zich in de bronstructuur van de component:

```text
media/joomla.asset.json
```

Deze definitie wordt door Joomla gebruikt voor registratie van de component-assets.

---

# Overige bestanden

## com_simplehub.xml

**Status**

Actief

Installatie- en update-manifest. Bevat `<scriptfile>script.php</scriptfile>` (zie `script.php`
hieronder en ADR-6).

---

## script.php

**Bestand**

```text
script.php (projectroot, naast com_simplehub.xml)
```

**Status**

Actief

**Verantwoordelijkheid**

Registreert bij installatie/upgrade automatisch de titelbalk-snelkoppeling als kern-Custom-module
(`mod_custom`) op modulepositie "title", altijd als eerste module in die positie (ordering 1, bestaande
modules in de positie worden één plek opgeschoven). Bij `update()` wordt uitsluitend de content van een
reeds bestaande rij ververst — de ordening wordt dan niet opnieuw aangepast. Bij `uninstall()` wordt de
modulerij verwijderd (herkend via de vaste waarde `note = 'com_simplehub'`).

De klasse (`Com_SimplehubInstallerScript`) implementeert `InstallerScriptInterface` en wordt, conform de
huidige Joomla-conventie, als instantie ge-`return`-d aan het einde van het bestand — zie Kanttekening bij
ADR-6.

**Afhankelijkheden**

* `Joomla\CMS\Table\Table` (`Module`)
* `Joomla\CMS\Router\Route`
* `Joomla\Database\DatabaseInterface` (via `Factory::getContainer()`)

**Nog openstaand**

* Foutafhandeling rond de databasequery's (een falende query breekt momenteel de volledige
  installatie/upgrade van de component af, zonder nette foutmelding).

De aan/uit-koppeling met de SimpleHub-Opties (via `TitlebarController`/`TitlebarModuleRepository`) en de
moduletitel zijn opgelost — zie de ADR-6-aanvulling hieronder; de moduletitel is bewust een vaste tekst
gebleven, geen taalsleutel (zelfde ADR).

---

## README.md

**Status**

Actief

Projectbeschrijving voor eindgebruikers: beschrijft functionaliteit, installatie en gebruik.

---

## ARCHITECTURE.md

**Status**

Actief

Beschrijving van de softwarearchitectuur.

---

## ARCHITECTURE_APPENDIX.md

**Status**

Actief

Inventaris van de actuele implementatie (dit document).

---

## CHANGELOG.md

**Status**

Actief

Release notes op hoofdlijnen voor eindgebruikers.

---

## LICENSE.txt

**Status**

Actief

Standaard, ongewijzigde GNU General Public License versie 2 (or later)-licentietekst. 

---

# Architectuuroverzicht

De component is een volledig werkende Hub met vijf itemtypen:

* Dashboard en HubRepository verzorgen het uitlezen van Hubgegevens (groepen én items).
* CRUD op groepen én items wordt afgehandeld via Joomla AdminModel (`GroupModel`/`ItemModel`).
* `GroupController`/`ItemController` verzorgen de procesbesturing voor individuele records, elk met een
  eigen `save()`-implementatie (zie `ARCHITECTURE.md`, hoofdstuk 9/10a).
* `GroupsController`/`ItemsController` verzorgen de Ajax-flow voor respectievelijk de groeps- en de
  item-volgorde binnen een groep.
* `HubRepository` verzorgt de specifieke ordering-opslag voor beide.
* `GroupTable`/`ItemTable` verzorgen de databasekoppeling voor CRUD.
* `LinkResolver` bepaalt per item, op basis van `type` + `target`, de link, status en het icoon.
* `ExternalUrlChecker` beschermt het itemtype `external` tegen SSRF, met een gescheiden vorm- en
  bereikbaarheidscontrole.
* Views en templates bevatten uitsluitend presentatielogica.
* `dashboard.js` verzorgt de client-side drag & drop-functionaliteit voor zowel groepen als items;
  `item.js` verzorgt de Item-formulierinteractie.
* Joomla Web Asset Manager verzorgt de registratie en afhankelijkheden van de JavaScript-assets.
* De database is de enige bron van waarheid.

Aanvullend op deze basis:

* Het verwijderen van een Hubgroep is robuust: een groep zonder items wordt direct verwijderd, een groep
  mét items toont een JoomlaDialog-modal met een expliciete keuze tussen verwijderen inclusief items of
  eerst verplaatsen naar een andere groep. Zie ADR-1 en ADR-2.
* Bij het itemtype `external` kan het doelvenster gekozen worden: huidig venster, nieuw tabblad, of een
  los popupvenster (`window.open()`, met `target="_blank"` als progressive-enhancement-fallback). Zie
  ADR-4.
* Vanaf elke pagina in de Joomla Administrator is SimpleHub met één klik bereikbaar via een
  titelbalk-snelkoppeling: een kern-Custom-module (`mod_custom`) op de vaste positie "title",
  geregistreerd door een installer-script (`script.php`), met een eigen aan/uit-schakelaar in de
  SimpleHub-Opties. Zie ADR-6.
* Bij een Item is desgewenst een eigen icoon te kiezen via een ingebouwde FontAwesome-iconkiezer, als
  override op het automatisch herkende icoon. Zie ADR-7.
* Externe URL's zijn beschermd tegen SSRF via zowel schema/host-validatie als het pinnen van het
  gecontroleerde IP-adres tijdens de bereikbaarheidscontrole (bescherming tegen DNS-rebinding). Zie
  ADR-8.

Twee onderzochte richtingen zijn bewust **niet** doorgevoerd: een apart paneel náást het beheermenu
(technisch mogelijk, maar geen architecturaal voordeel t.o.v. de titelbalk-snelkoppeling — zie ADR-3) en
een eigen top-level hoofdmenu-item in de Joomla Administrator (geen ondersteunde extensie-weg binnen
Joomla 6 core, zonder Joomla Core zelf aan te passen — zie ADR-5). Automatische detectie van
veelgebruikte Dashboard-links is eveneens onderzocht en bewust niet gebouwd — zie ADR-9.

---

# Architecture Decision Records (ADR's)

Genummerde inhoudsopgave van architectuurbeslissingen. Nieuwe ADR's worden onderaan toegevoegd; bestaande
ADR's worden niet hernummerd.

1. [ADR-1 — Popup-methode voor de groep-verwijderflow: JoomlaDialog i.p.v. Bootstrap-modal](#adr-1--popup-methode-voor-de-groep-verwijderflow-joomladialog-ipv-bootstrap-modal)
2. [ADR-2 — Plaats van de verplaats-logica bij groepverwijdering: HubRepository](#adr-2--plaats-van-de-verplaats-logica-bij-groepverwijdering-hubrepository)
3. [ADR-3 — Extern paneel náást het adminmenu: geen dedicated Core-mechanisme, wel haalbaar via het modulesysteem](#adr-3--extern-paneel-náást-het-adminmenu-geen-dedicated-core-mechanisme-wel-haalbaar-via-het-modulesysteem)
4. [ADR-4 — Derde external_target-optie "nieuw venster": window.open() i.p.v. het target-attribuut](#adr-4--derde-external_target-optie-nieuw-venster-windowopen-ipv-het-target-attribuut)
5. [ADR-5 — Eigen top-level hoofdmenu-item: geen ondersteunde extensie-weg binnen Joomla 6 core](#adr-5--eigen-top-level-hoofdmenu-item-geen-ondersteunde-extensie-weg-binnen-joomla-6-core)
6. [ADR-6 — Titelbalk-snelkoppeling naar SimpleHub: kern-Custom-module via installer-script, positie "title"](#adr-6--titelbalk-snelkoppeling-naar-simplehub-kern-custom-module-via-installer-script-positie-title)
7. [ADR-7 — Eigen iconkeuze via FontAwesome: haalbaar, geen tweede library, `icon`-kolom als override](#adr-7--eigen-iconkeuze-via-fontawesome-haalbaar-geen-tweede-library-icon-kolom-als-override)
8. [ADR-8 — DNS-rebindingvenster in ExternalUrlChecker: IP-adres pinnen via CURLOPT_RESOLVE](#adr-8--dns-rebindingvenster-in-externalurlchecker-ip-adres-pinnen-via-curlopt_resolve)
9. [ADR-9 — Meest gebruikte Dashboard-links: haalbaarheid en aanpak van gebruiksdetectie](#adr-9--meest-gebruikte-dashboard-links-haalbaarheid-en-aanpak-van-gebruiksdetectie)
10. [ADR-10 — Packaging vanaf v1.0.0: publicatie-zip vs. backup-zip](#adr-10--packaging-vanaf-v100-publicatie-zip-vs-backup-zip)

---

## ADR-1 — Popup-methode voor de groep-verwijderflow: JoomlaDialog i.p.v. Bootstrap-modal

**Status:** Aanvaard

**Context**

Voor de groep-verwijderflow is een popup-mechanisme nodig. De voor de hand liggende, klassieke aanpak —
Joomla's Bootstrap-modalconventie (`data-bs-toggle="modal"`, `HTMLHelper::_('bootstrap.renderModal', ...)`),
naar analogie van de bestaande Joomla-batchverwerkingsmodals — blijkt bij nader onderzoek achterhaald:

* Sinds Joomla 5.1 is de modal-editing voor plugins en modules in Joomla Core zelf al vervangen door
  **JoomlaDialog** (native `<dialog>`-element, wrapper-script), niet langer door een vooraf gerenderde
  Bootstrap-modal met `data-bs-toggle`.
* Joomla 6/7 merkt `bootstrap.modal`-assets en `data-bs-toggle="modal"`-markup expliciet aan als
  deprecated/upgrade-blocker (zie de `cwm-lint-deprecations`-tooling in de bredere Joomla-toolchain).
* JoomlaDialog ondersteunt naast `iframe` ook een `inline`-popuptype, met een declaratieve
  **autocreate**-variant (`$wa->useScript('joomla.dialog-autocreate')` + een `data-joomla-dialog`-JSON-
  attribuut op de trigger-knop, wijzend naar een `<template>`-element met de popup-inhoud). Hiervoor is
  **geen eigen JavaScript** nodig.

**Beslissing**

De verwijdermodal voor Hubgroepen met items gebruikt JoomlaDialog, autocreate-variant, `popupType:
"inline"`. Per groep-met-items wordt een `<template id="sh-delete-group-modal-<id>">` gerenderd
(`admin/tmpl/dashboard/default.php`) met de waarschuwingstekst, keuze A (knop met `form="delete-group-
<id>"`, hergebruikt het bestaande delete-formulier) en, indien van toepassing, keuze B (nieuw
`move-then-delete-group-<id>`-formulier met een `<select>` van de overige gepubliceerde groepen). De
trashknop krijgt hiervoor:

```php
data-joomla-dialog='{"popupType":"inline","src":"#sh-delete-group-modal-<id>", ...}'
```

`Dashboard\HtmlView` activeert `joomla.dialog-autocreate` naast de bestaande `com_simplehub.dashboard`-
en `com_simplehub.dashboard.css`-assets. Groepen zonder items blijven het bestaande, kale
`onsubmit="confirm(...)"` gebruiken (ongewijzigd, geen modal).

**Consequenties**

* Geen nieuw eigen JavaScript-bestand nodig voor de modal zelf; `dashboard.js` blijft beperkt tot zijn
  bestaande verantwoordelijkheid (drag & drop-ordering).
* Doordat `<template>`-inhoud pas bij het openen wordt gekloond, blijven de knoppen binnen de modal via
  hun `form="..."`-attribuut gewoon gekoppeld aan de elders in het document gedefinieerde `<form>`-
  elementen; token-controle (`checkToken()`) blijft daarmee ongewijzigd serverside afgedwongen.
* Toekomstige Joomla-upgrades naar 7 lopen geen risico op deprecated Bootstrap-modal-code binnen
  SimpleHub.

---

## ADR-2 — Plaats van de verplaats-logica bij groepverwijdering: HubRepository

**Status:** Aanvaard

**Context**

Bij het verwijderen van een Hubgroep met items moeten die items eerst worden verplaatst naar een andere
groep, voordat de (dan lege) bronproep wordt verwijderd. Deze logica kan op twee plekken thuishoren:
`HubRepository` (analoog aan de bestaande ordening-verantwoordelijkheden, zie `ARCHITECTURE.md`
hoofdstuk 3/8), of een nieuwe methode op `GroupModel`/`GroupController`.

**Beslissing**

Nieuwe methode `HubRepository::moveItemsToGroup(int $sourceGroupId, int $targetGroupId): void`. Deze:

* controleert dat de doelgroep bestaat;
* verplaatst alle items van de bronproep naar de doelgroep (`group_id`), met een nieuwe, oplopende
  `ordering` die achteraan de bestaande items van de doelgroep wordt geplaatst (analoog aan hoe
  `GroupModel::save()` nieuwe groepen en `ItemModel::save()` nieuwe items achteraan plaatsen);
  * geen bestaande items botsen daarbij op eenzelfde `ordering`-waarde binnen de doelgroep, omdat de
    nieuwe waarden altijd hoger liggen dan de op dat moment hoogste bestaande waarde in de doelgroep;
* voert dit transactioneel uit (rollback bij een fout, analoog aan `saveGroupOrder()`/`saveItemOrder()`,
  zie `ARCHITECTURE.md` hoofdstuk 8) en controleert na het committen dat de bronproep daadwerkelijk leeg
  is.

`GroupController::moveItemsAndDelete()` (task `group.moveItemsAndDelete`) roept deze methode aan en
delegeert het daaropvolgende verwijderen van de nu lege bronproep aan het bestaande
`GroupModel::delete()`-pad — functioneel ongewijzigd ten opzichte van de directe verwijdering (Keuze A).

**Motivatie**

Het verplaatsen van items tussen groepen is, net als de bestaande groeps- en item-ordering, een
collectieve wijziging over meerdere records heen, geen CRUD-bewerking op één individueel record. Dit
sluit aan bij het bestaande architectuurprincipe "CRUD en HubRepository blijven gescheiden
verantwoordelijkheden" (`ARCHITECTURE.md`, hoofdstuk 11) en voorkomt dat `GroupModel`/`ItemModel` een
verantwoordelijkheid krijgen die buiten hun scope (één record) valt.

**Consequenties**

* `HubRepository` krijgt naast leesfunctionaliteit en ordening-opslag nu ook deze specifieke
  bulk-schrijfoperatie; dit blijft binnen de bestaande, brede verantwoordelijkheid van de repository voor
  "Hub-specifieke gegevenslogica die niet natuurlijk binnen een afzonderlijke CRUD-bewerking past" (zie
  `ARCHITECTURE.md`, hoofdstuk 3).
* `GroupController` krijgt een afhankelijkheid op `HubRepository`, naast de bestaande afhankelijkheid op
  `GroupModel`.

---

## ADR-3 — Extern paneel náást het adminmenu: geen dedicated Core-mechanisme, wel haalbaar via het modulesysteem

**Status:** Aanvaard (onderzoeksuitkomst; geen implementatiebeslissing — zie Consequenties)

**Context**

Onderzocht is of en hoe Joomla 6 core een paneel náást het adminmenu ondersteunt, "op de manier waarop
Joomla core-componenten dat doen", met als twee hypothesen: een iframe binnen de standaardlayout, of een
submenu-structuur. SimpleHub is uitsluitend bereikbaar via het reguliere componentmenu-item en het eigen
Dashboard; er bestaat geen enkele vorm van paneel naast het adminmenu.

**Onderzoek**

* **Adminmenu-sidebar van het standaardtemplate (Atum).** In `administrator/templates/atum/index.php`
  (Joomla Core) bevat de sidebar-wrapper (`<div id="sidebar-wrapper">`) uitsluitend één
  `<jdoc:include type="modules" name="menu" style="none" />` — dezelfde modulepositie ("menu") waarop het
  kern-navigatiemenu (mod_menu) draait. Er is geen aparte, permanent naast dat menu gerenderde positie in
  dit kernbestand. (Geverifieerd tegen de Atum-templatecode in de `joomla/joomla-cms`-repository op
  GitHub; niet tegen elke exacte 6.x-patchversie afzonderlijk — zie Kanttekening.)
* **Joomla's modulesysteem staat het delen van één positie door meerdere modules toe.** Dit is
  gedocumenteerd kerngedrag van het Joomla-modulesysteem, niet component-specifiek. Een module die
  eveneens aan positie "menu" wordt toegewezen, wordt daardoor vanzelf mee gerenderd binnen dezelfde
  sidebar als het navigatiemenu — zonder dat het adminsjabloon zelf hoeft te worden aangepast of
  overschreven.
* **Geen backend-equivalent van de frontend Iframe Wrapper.** Joomla Core kent wél een kernmechanisme om
  externe content via een iframe binnen de eigen paginalayout te tonen: het menu-itemtype "Wrapper ›
  Iframe Wrapper" (`com_wrapper`) en de bijbehorende Wrapper-module. Dit is echter uitsluitend een
  site-/frontendconcept; er bestaat geen vergelijkbare Administrator-tegenhanger in Joomla 6 core.
* **Submenu-structuur met externe link.** Een componentmanifest ondersteunt in de
  `<administration><submenu>`-sectie een `<menu link="...">`-item met een `target`-attribuut
  (`target="_blank"`), waarmee een submenu-item naar een externe URL kan wijzen en in een nieuw tabblad
  wordt geopend (officiële manifest-documentatie). Dit is het bestaande, kern-ondersteunde mechanisme
  voor een externe link "in" het adminmenu — maar het opent een nieuw tabblad/nieuwe pagina, geen paneel
  náást het menu, en verlaat daarmee het Adminpanel.

**Beslissing**

Ja, een paneel náást het adminmenu is haalbaar binnen Joomla 6 core, zonder Core te patchen — maar niet
via een dedicated "extern paneel"-mechanisme dat Joomla Core daarvoor biedt (dat bestaat niet), wel via
Joomla's generieke modulesysteem. Twee concrete routes, beide "Joomla Core First" (geen reflectie, geen
ongedocumenteerde Core-aanroepen):

* **Route A — geen SimpleHub-code.** De sitebeheerder maakt zelf, via **Extensions → Modules → New →
  Custom**, een kern-eigen Custom-module aan met de gewenste HTML-inhoud (inclusief eventueel een
  `<iframe>` naar een externe URL), toegewezen aan positie "menu", zichtbaar op alle admin-pagina's.
* **Route B — SimpleHub-code, indien dynamische, SimpleHub-specifieke inhoud gewenst is.** Een kleine,
  losstaande administrator-modulextensie (bijv. `mod_simplehub_panel`), eveneens toegewezen aan positie
  "menu", die dynamisch content genereert (bijvoorbeeld op basis van `HubRepository`).

**Consequenties**

* De positie "menu" is een eigenschap van het actieve admin-template (Atum), niet van Joomla Core in het
  algemeen. Dit werkt gegarandeerd binnen Joomla 6's standaardtemplate; een sitebeheerder met een
  afwijkend of eigen admin-template loopt het risico dat deze positie niet, of niet op dezelfde plek,
  wordt gerenderd. Dit is een inherente beperking van de core-conforme aanpak, geen tekortkoming ervan.
* Zonder een concrete, dynamische SimpleHub-gebruiksbehoefte voor de inhoud van zo'n paneel zou een
  dedicated `mod_simplehub_panel`-implementatie (Route B) in strijd zijn met "Geen speculative
  architecture" (`WAYOFWORK.md`). SimpleHub bevat daarom geen eigen modulextensie voor deze route; een
  sitebeheerder die dit wenst, kan zelf een kern-Custom-module aanmaken (Route A).
* Een eigen hoofdmenu-item náást Controlepaneel/Inhoud/Menu's is een afzonderlijke vraag — dat betreft
  Joomla's hoofdmenu-registratie (`#__menu`), niet modulepositie "menu". Zie ADR-5.

**Kanttekening**

Deze bevinding is gebaseerd op inspectie van de Joomla Core-broncode en de officiële moduledocumentatie,
niet op een live geteste Proof of Concept.

---

## ADR-4 — Derde external_target-optie "nieuw venster": window.open() i.p.v. het target-attribuut

**Status:** Aanvaard

**Context**

Bij het itemtype `external` is een derde keuze toegevoegd aan `external_target`, naast "nieuw tabblad"
(`_blank`) en "huidig venster" (`_self`): "nieuw venster" — een apart, los popupvenster, geen tabblad.

**Beslissing**

* **Interne waarde `popup`**, geen HTML `target`-waarde. Het HTML `target`-attribuut kan geen apart
  venster afdwingen: moderne browsers openen `target="_blank"` altijd als tabblad, ongeacht eventuele
  venstergrootte-achtige waarden. Bovendien zijn browsing-context-namen die met een underscore beginnen
  (`_blank`/`_self`/`_parent`/`_top`) gereserveerde kernwaarden; een eigen waarde hoort daar dus buiten
  te blijven.
* **Progressive enhancement.** De itemlink behoudt altijd `target="_blank"` (met
  `rel="noopener noreferrer"`) als basisgedrag. `admin/tmpl/dashboard/default.php` voegt voor
  `popup`-items daarnaast een `data-sh-target="popup"`-attribuut toe. `media/js/dashboard.js` onderschept
  het klikken op zo'n link en roept in plaats daarvan `window.open()` aan, met `event.preventDefault()`
  uitsluitend ná een bevestigd geopend venster. Zonder JavaScript, of wanneer de popup alsnog door de
  browser wordt geblokkeerd, valt de link terug op het gewone `target="_blank"`-gedrag (tabblad) — nooit
  een dode link.
* **`window.open()` synchroon, binnen het click-event.** Vereiste om niet als "niet door de gebruiker
  geïnitieerd" te worden aangemerkt door popupblockers.
* **Geen `noopener`/`noreferrer` in de `windowFeatures`-string van `window.open()`.** Dit lijkt de voor
  de hand liggende manier om `window.opener` te ontzeggen (analoog aan `rel="noopener"` op een `<a>`),
  maar `window.open()` geeft dan altijd `null` terug — ook bij een geslaagde open — waardoor niet meer te
  onderscheiden is of de popup daadwerkelijk is geopend of geblokkeerd. In plaats daarvan wordt
  `popup.opener = null` handmatig gezet ná een geslaagde `window.open()`-aanroep; functioneel
  gelijkwaardig (voorkomt reverse-tabnabbing), met wél een bruikbaar vensterobject terug (nodig om
  `preventDefault()` en `.focus()` correct te kunnen toepassen).
* **Vaste venstergrootte (1024×768), niet instelbaar.** Conform de besluitvorming-volgorde uit
  `WAYOFWORK.md` ("Eenvoud" vóór "Uitbreidbaarheid" bij twijfel); een instelbare afmeting kan, indien
  gewenst, een latere, losse wens worden.
* **Geen whitelist-validatie van `external_target` in `HubRepository`.** PO-besluit: de externe URL wordt
  al inhoudelijk gecontroleerd (`ExternalUrlChecker`), en de gebruikers van SimpleHub zijn sitebeheerders
  van wie verantwoord gebruik verwacht mag worden. `HubRepository::getGroups()` blijft daarom
  functioneel ongewijzigd (elke niet-lege waarde wordt, na escaping, doorgegeven).

**Consequenties**

* `HubRepository` blijft ongewijzigd — geen architectuurwijziging op Model/Repository-niveau.
* Een toekomstige extra `external_target`-waarde (bijvoorbeeld voor een instelbare venstergrootte) is,
  zonder whitelist, direct bruikbaar zodra formulier, template en JavaScript hem ondersteunen — geen
  Repository-wijziging nodig. Kende de whitelist wél bestaan, dan zou die bij iedere nieuwe waarde moeten
  worden bijgewerkt.
* De keuze om `noopener` niet in de `windowFeatures`-string te zetten is een niet-vanzelfsprekend
  gedrag van `window.open()` (in tegenstelling tot het overeenkomstige `rel="noopener"` op een `<a>`) —
  vastgelegd hier én als code-commentaar in `dashboard.js`, zodat dit niet per ongeluk later wordt
  "gecorrigeerd" naar de ogenschijnlijk voor de hand liggende variant.

---

## ADR-5 — Eigen top-level hoofdmenu-item: geen ondersteunde extensie-weg binnen Joomla 6 core

**Status:** Aanvaard (onderzoeksuitkomst; geen implementatiebeslissing — zie Consequenties)

**Context**

Onderzocht is of SimpleHub een eigen, gelijkwaardig item in het hoofdmenu van de Joomla 6 Administrator
kan krijgen — náást Controlepaneel, Inhoud en Menu's — in plaats van uitsluitend bereikbaar via het
reguliere componentmenu-item onder "Componenten". Dit onderzoek bouwt voort op ADR-3, dat vaststelde dat
de sidebar van het Atum-adminsjabloon wordt gevuld door de modulepositie "menu", gerenderd door
`mod_menu` op basis van `#__menu` (`client_id = 1`). Dít onderzoek gaat een laag dieper: niet "een paneel
náást de boomstructuur", maar "een eigen plek ván SimpleHub bovenin diezelfde boomstructuur".

**Onderzoek**

* **`#__menu` is niet de bron van de zichtbare top-level structuur bij een standaardinstallatie.** De
  standaard `mod_menu`-instantie (Joomla 6 core, `administrator/modules/mod_menu/mod_menu.xml`) heeft als
  default voor de parameter `menutype` de waarde `*` ("Voorgedefinieerd"). In
  `administrator/modules/mod_menu/src/Menu/CssMenu.php` bepaalt exact deze parameter de databron:
  * bij `menutype === '*'` (de standaardconfiguratie): `MenusHelper::loadPreset($name)` — de menuboom wordt
    rechtstreeks ingelezen uit een XML-presetbestand, **zonder** `#__menu` te raadplegen voor de
    hoofdstructuur;
  * bij een specifiek gekozen `menutype` (een door de sitebeheerder zelf aangemaakt Administrator-menutype
    via Menus → Beheren): `MenusHelper::getMenuItems($menutype, true)` — dán wél databasegedreven via
    `#__menu` (`client_id = 1`).
  * (Geverifieerd tegen `CssMenu.php` in de `joomla/joomla-cms`-repository, 4.0-dev/staging-broncode; het
    onderliggende onderscheid preset-vs-database bestaat sinds de introductie van het
    presetmechanisme in Joomla 3.7 en is in de geraadpleegde 4.x/5.x/6.x-broncode ongewijzigd aanwezig —
    niet tegen elke exacte 6.x-patchversie afzonderlijk geverifieerd, zie Kanttekening.)
* **De preset zelf is een Joomla Core-bestand.** De standaardpreset (`administrator/components/com_menus/
  presets/default.xml`, in oudere versies `joomla.xml`) bevat de top-level items Controlepaneel, Inhoud,
  Gebruikers, Menu's, Systeem, Componenten en Help als **hardgecodeerde** `<menuitem>`-knopen. Alleen de
  takken die met individuele componenten samenhangen (bijvoorbeeld de submenu-lijst onder "Componenten",
  en delen van "Inhoud"/"Systeem") worden ín die preset dynamisch gevuld via een ingebouwd
  `sql_select`/`sql_from`-mechanisme dat op dat moment alsnog `#__menu` bevraagt. Dit is exact het
  bestaande mechanisme waarmee de huidige SimpleHub-registratie (`<administration><menu>` in
  `com_simplehub.xml`) als submenu-item onder "Componenten" verschijnt: dat item bestaat wél als
  `#__menu`-rij (`menutype = main`, `client_id = 1`), maar uitsluitend als kind binnen de door de preset
  vastgelegde "Componenten"-tak — niet als sibling van die tak.
* **Geen manifest- of installer-API om de preset zelf te wijzigen.** Een extensiemanifest (`<administration>
  <menu>`) kan uitsluitend een rij toevoegen binnen de bestaande, preset-bepaalde structuur (in de praktijk:
  onder "Componenten"). Er bestaat geen gedocumenteerde Joomla-API waarmee een extensie, via manifest of
  installer-script, een knoop toevoegt aan de preset-XML zelf — dat bestand staat in de core-map
  `administrator/components/com_menus/presets/` en aanpassen ervan is een Core-bestand overschrijven, wat
  onder "Joomla Core First" als Core patchen geldt, niet als extensie-integratie.
* **Sinds Joomla 6.1: uitgebreid presetmechanisme, maar nog steeds ín de bestaande Componenten-tak.** Vanaf
  Joomla 6.1 kan een component een eigen `preset.xml` aanleveren waarmee groepen links, snelkoppelingen en
  dynamische onderdelen aan zijn eigen plek in het beschermde `menutype = main` worden toegevoegd (in
  aanvulling op de simpele `<administration><menu>`-registratie). Dit verrijkt wát een component onder
  "Componenten" kan tonen, maar verplaatst het component niet buiten die tak: de top-level knoop
  "Componenten" zelf blijft, net als de overige top-level items, een vast onderdeel van de core-preset.
  De vraag was expliciet naar een sibling van Controlepaneel/Inhoud/Menu's, niet naar een rijkere
  submenu-structuur binnen Componenten — dit mechanisme lost die vraag daarom niet op.
* **Theoretisch alternatief: het admin-menutype van de hele site omzetten naar databasegedreven.** Zou een
  sitebeheerder de `mod_menu`-instantie handmatig configureren met een eigen, aangemaakt
  Administrator-menutype (in plaats van de standaard `*`/preset-configuratie), dan wordt de volledige
  hoofdmenu-structuur wél uit `#__menu` opgebouwd, en zou een top-level `#__menu`-rij (`parent_id` op het
  boomniveau, `client_id = 1`) — bijvoorbeeld aangemaakt via `Table::getInstance('Menu')` in een
  installer-script — daadwerkelijk als sibling van Controlepaneel/Inhoud verschijnen. Dit is echter geen
  extensie-specifieke integratie: het vereist dat de sitebeheerder eerst, buiten SimpleHub om, de
  standaardconfiguratie van `mod_menu` voor de **hele Administrator** verlaat — een ingrijpende wijziging
  die iedere andere extensie en iedere andere beheerder op de site evenzeer raakt, niet iets wat een
  installer-script van één component redelijkerwijs zelf zou moeten afdwingen. Dit valt buiten wat één
  extensie via een ondersteunde, niet-ingrijpende weg voor zichzelf kan realiseren.

**Beslissing**

**Nee.** Er bestaat geen ondersteunde weg binnen Joomla 6 core waarmee een extensie, via manifest of
installer-script, zelfstandig een eigen top-level item in het hoofdmenu van de Administrator laat
verschijnen — gelijkwaardig aan Controlepaneel/Inhoud/Menu's — zonder Joomla Core te patchen (de
preset-XML aanpassen) én zonder de sitebrede `mod_menu`-configuratie te wijzigen (een stap die niet bij één
extensie hoort te liggen). De bestaande plek onder "Componenten" (via `<administration><menu>`) blijft
daarmee de enige core-conforme, niet-ingrijpende integratieweg voor SimpleHub.

**Consequenties**

* Een eigen top-level hoofdmenu-item wordt niet gebouwd (onderzoek afgerond, negatieve uitkomst).
* Geen wijziging aan `com_simplehub.xml`, geen wijziging aan installatie-/upgrade-/deïnstallatieproces: de
  bestaande, enkelvoudige `<administration><menu>`-registratie onder "Componenten" blijft ongewijzigd.
* Alternatief voor een duidelijkere vindbaarheid, zonder de hier afgewezen aanpak: het bestaande
  `img="class:simplehub"`-icoon en label in `com_simplehub.xml` zijn al de instrumenten die Joomla Core
  hiervoor biedt binnen de bestaande Componenten-plek; een concrete wens op dat vlak (bijvoorbeeld een
  duidelijker icoon) kan, indien gewenst, als eigen, kleine wens worden ingebracht.
* Mocht een toekomstige Joomla Core-versie alsnog een gedocumenteerde API bieden om de preset-structuur
  zelf per extensie uit te breiden met een top-level knoop (verder dan het Joomla 6.1-mechanisme, dat
  beperkt blijft tot ín de Componenten-tak), dan is deze ADR aan herziening toe — conform `WAYOFWORK.md`
  wordt een bestaand besluit dan niet met terugwerkende kracht gewijzigd, maar wordt een nieuwe ADR
  toegevoegd die deze expliciet "supersedet".

**Kanttekening**

Deze bevinding is gebaseerd op inspectie van de Joomla Core-broncode (`CssMenu.php`, de presetbestanden in
`administrator/components/com_menus/presets/`, `mod_menu.xml`) en op onafhankelijke, publieke
documentatie over het Joomla 6.1-presetmechanisme, niet op een live geteste Proof of Concept binnen dit
project. De preset-vs-database-scheiding in `CssMenu.php` is bevestigd tegen meerdere geraadpleegde
core-versies (4.0-dev tot en met de actuele staging-broncode), maar niet tegen elke exacte Joomla
6.x-patchversie afzonderlijk getest binnen een draaiende installatie — bij twijfel over een specifieke
patchversie wordt aanbevolen dit met een korte PoC (module-instellingen inspecteren, `#__menu` bevragen) te
bevestigen vóórdat hierop een implementatiebeslissing wordt gebaseerd.

---

## ADR-6 — Titelbalk-snelkoppeling naar SimpleHub: kern-Custom-module via installer-script, positie "title"

**Status:** Aanvaard en afgerond

**Context**

Onderzocht is hoe SimpleHub vanaf elke Administrator-pagina snel bereikbaar gemaakt kan worden, met drie
deelvragen: (1) een tijdens handmatig testen geconstateerde 404 bij een module-link, (2) de haalbaarheid
van modulepositie "status", (3) of en hoe installatie-automatisering mogelijk is. Dit bouwt voort op
ADR-3 (Joomla's modulesysteem is de core-conforme integratieweg voor een paneel/snelkoppeling náást het
adminmenu) en ADR-5 (een eigen top-level hoofdmenu-item is niet haalbaar zonder Core te patchen).

**Onderzoek**

* **De 404.** Bij handmatig testen bleek een volledige URL, ingevoerd in een TinyMCE-editorveld, bij
  opslaan omgezet te worden naar een pad relatief aan de site-root (TinyMCE's
  `relative_urls`/`document_base_url`-mechanisme). Bij weergave binnen `/administrator/` resulteerde dat
  in een verdubbeld pad en dus een 404. Dit speelt uitsluitend bij editor-ingevoerde content; bij
  programmatisch gegenereerde content (via `Route::_()`, buiten de editor om) treedt dit mechanisme niet
  op — relevant voor de uiteindelijke beslissing hieronder.
* **Positie "status".** Geverifieerd tegen de Atum-broncode (`administrator/templates/atum/index.php`,
  Joomla Core): deze positie wordt, anders dan de meeste posities, niet via een gewone
  `<jdoc:include type="modules" name="status" />` gerenderd, maar via een eigen layout
  (`html/layouts/status.php`) die elke module-uitvoer wrapt in een vaste `.header-item` flexcontainer,
  ontworpen voor compacte icoon-modules (vergelijkbaar met `mod_login`, `mod_quickicon`). Functioneel
  bruikbaar, maar architecturaal minder voorspelbaar dan een gewone jdoc-positie. Niet gebruikt, nadat
  positie "title" (eveneens een gewone jdoc-positie, gedeeld met de kernmodule `mod_title`) een werkende
  aanpak opleverde.
* **Installatie-automatisering.** Haalbaar zonder eigen SimpleHub-modultype: bij installatie/upgrade een
  kern-Custom-modulerij (`mod_custom`) aanmaken via `Table::getInstance('Module')`, programmatisch
  gevuld. Live getest op een testomgeving; zie Kanttekening voor de onderweg geconstateerde en opgeloste
  implementatievalkuilen.
* **Aan/uit-koppeling met de SimpleHub-Opties.**
  * *Verworpen aanpak 1 (aparte instelling + synchronisatie).* Een eigen SimpleHub-instelling die bij
    opslaan de module aan/uitzet zou twee afzonderlijke plekken opleveren waar dezelfde toestand wordt
    vastgelegd, met een synchronisatievraagstuk zodra één van de twee buiten SimpleHub om wijzigt
    (bijv. handmatig via Extensies → Modules).
  * *Verworpen aanpak 2 (plugin op `onExtensionAfterSave`).* Technisch onderzocht en bevestigd tegen de
    Joomla-kernbron (`com_config`'s `ComponentModel::save()`): het opslaan van de SimpleHub-Opties
    triggert wel degelijk een core-event, met de module-rij als vers-opgeslagen data beschikbaar op het
    moment van triggeren. Dit event wordt echter alleen ontvangen door een losse Joomla-plugin
    (extension-groep) — niet door `SimplehubComponent::boot()` via de bestaande `provider.php`-opzet,
    omdat bij het opslaan van de Opties de request `option=com_config` is, niet `option=com_simplehub`;
    SimpleHub zelf wordt dan niet gebootstrapt. Verworpen omdat dit een tweede sub-extensie aan het
    pakket zou toevoegen — niet in verhouding tot de omvang van deze wijziging.
  * *Gekozen aanpak: rechtstreekse besturing.* Er is geen synchronisatie nodig als er ook geen tweede,
    aparte waarde bestaat: het schuifje in de SimpleHub-Opties leest en beïnvloedt rechtstreeks het
    bestaande `published`-veld van de module zelf, buiten het reguliere "Opslaan" van het
    Opties-formulier om. Dit vereist geen plugin en geen nieuwe opslaglocatie.
  * *Twee registratieproblemen*, beide veroorzaakt doordat het Opties-scherm technisch binnen
    `com_config` wordt gerenderd, niet binnen SimpleHub zelf: een eigen formulierveldtype werd niet
    gevonden, opgelost met het `addfieldprefix`-attribuut op het veld (bevestigd tegen de officiële
    Joomla 6.0-documentatie); `joomla.asset.json` van SimpleHub werd niet automatisch ingelezen, opgelost
    met een expliciete `WebAssetRegistry::addExtensionRegistryFile('com_simplehub')`-aanroep vóór gebruik
    van het script.
  * *Visuele weergave.* Een eerste implementatie met zelfgebouwde "schuifje"-opmaak bleek niet overeen te
    komen met Joomla's eigen, niet-gedocumenteerde schuifjesstructuur en toonde daardoor als een kale
    checkbox. Opgelost met Bootstrap's standaard `form-check form-switch`-opmaak, die in Joomla 6 al
    overal beschikbaar is — geen extra CSS nodig.
* **Moduletitel.** Onderzocht of een taalsleutel mogelijk is in plaats van een vaste tekst: taalsleutels
  die vóór de eigenlijke taalinitialisatie nodig zijn (zoals bij installatie/upgrade) moeten in het
  `.sys.ini`-bestand staan, niet in het gewone taalbestand — en zelfs dan blijft de eenmalig in de
  database "gebakken" titel vastliggen op het installatiemoment, zonder dat hij meebeweegt bij een latere
  taalwissel (een kern-Custom-module heeft geen eigen manifest/taalbestand om dat wél te laten
  meebewegen). Joomla's eigen beheermodules laten hun titel om dezelfde reden vrijwel allemaal
  hardgecodeerd Engels.

**Beslissing**

SimpleHub registreert bij installatie/upgrade automatisch een kern-Custom-module op de vaste positie
"title" van het Atum-adminsjabloon — geen door de beheerder te kiezen positie, en geen eigen
SimpleHub-modultype. Concreet:

* Content wordt programmatisch opgebouwd (`Route::_('index.php?option=com_simplehub')`, geen
  editor-tussenkomst) — de 404-bevinding hierboven speelt hierdoor niet.
* De module krijgt ordering 1; bestaande modules in positie "title" (met name de kernmodule `mod_title`,
  die de zichtbare paginatitel toont) worden één plek opgeschoven, zodat de snelkoppeling altijd als
  eerste rendert.
* Icoon en opmaak (verticale scheidingslijn tussen snelkoppeling en paginatitel, verticale centrering)
  worden als zelfstandige inline SVG + inline `<style>` in de moduleinhoud meegegeven — geen los
  CSS-bestand/webasset (een kern-Custom-module heeft geen eigen hook om zelf een web asset te laden) en
  geen afhankelijkheid van de `icon-simplehub`-klasse uit het menu-icoon (die klasse is specifiek voor
  `mod_menu`, niet generiek herbruikbaar). Het icoon is het definitieve SimpleHub-logo (inline SVG,
  huisstijlkleuren, met een wit achtergrondvlak zodat het ook in Atum's donkere modus goed zichtbaar
  blijft).
* Positie "status" wordt niet gebruikt.
* De SimpleHub-Opties tonen een schuifje (`TitlebarStateField`, `administrator/src/Field/`) dat bij het
  openen van het scherm de actuele `published`-status van de titelbalk-module opvraagt en toont. Een klik
  werkt direct door, via een eigen controller-actie (`TitlebarController::toggle()`) die — na een CSRF-
  en rechtencontrole — rechtstreeks de module bijwerkt via een gedeelde `TitlebarModuleRepository`
  (`administrator/src/Repository/`). Er wordt bewust niet gewacht op het "Opslaan" van het
  Opties-formulier; na een geslaagde wijziging ververst het scherm zich automatisch
  (`window.location.reload()`), zodat het effect — net als bij het bekende aan/uit-icoon in
  Extensies → Modules — ook direct zichtbaar is.
* De moduletitel is bewust een vaste, Engelse tekst (`'SimpleHub - Quick Link'`), geen taalsleutel — zie
  Onderzoek hierboven. Joomla's eigen beheermodules volgen dezelfde aanpak.

**Consequenties**

* Geen eigen SimpleHub-modultype nodig; minimale extra code.
* De module is, net als de rest van de component, alleen via de installatie/upgrade van SimpleHub
  beheerd — een sitebeheerder kan de module nog steeds handmatig via Extensions → Modules depubliceren of
  verplaatsen, maar dat wordt (nog) niet automatisch teruggezet vanuit SimpleHub's eigen Opties.
* De titelwijziging geldt alleen voor nieuw geïnstalleerde modules: `refreshContent()` werkt bewust
  alleen de moduleinhoud bij, niet de titel, om een eventuele handmatige titelwijziging door de beheerder
  niet ongevraagd te overschrijven. Op een bestaande installatie wordt de titel dus pas zichtbaar bij een
  schone herinstallatie, niet bij een reguliere upgrade.
* **Bewust nog niet opgepakt, blijft op de backlog** (zie `ROADMAP_INTERN.md`):
  * foutafhandeling rond de databasequery's (zowel in `script.php` als in `TitlebarModuleRepository`) —
    een falende query breekt momenteel de volledige installatie/upgrade van de component af, zonder
    nette foutmelding;
  * de Nederlandse teksten die hardgecodeerd in JavaScript staan (`titlebar-config.js` en een
    vergelijkbare tekst in `item.js`) — bewust niet opgelost, omdat JavaScript een andere
    taalstring-techniek vereist dan PHP; wordt in één keer voor alle JS-bestanden van het component
    opgepakt, niet per bestand afzonderlijk.

**Kanttekening**

Deze bevindingen zijn, anders dan bij ADR-3/ADR-5, mede gebaseerd op live testen op een testomgeving,
niet uitsluitend op Core-broncode-inspectie vooraf. Tijdens het bouwen zijn drie implementatievalkuilen
geconstateerd en opgelost, relevant voor toekomstige installer-scripts binnen dit project:

* **Scriptbestand-conventie.** Joomla gebruikt de *returnwaarde* van `script.php` als scriptobject, niet
  een class die op naam wordt opgezocht — bevestigd tegen de officiële Joomla 6.0-documentatie
  (manual.joomla.org, "Adding a Script File"). Een eerste implementatie zonder `return`-statement aan het
  einde van het bestand resulteerde in een stille no-op: geen foutmelding, maar `install()`/`update()`
  werden nooit aangeroepen.
* **Unsigned `ordering`-kolom.** De kolom `ordering` in `#__modules` is in Joomla's kernschema `UNSIGNED`.
  Een negatieve waarde (de aanvankelijke aanpak: één onder het bestaande minimum gaan zitten) werd
  stilzwijgend anders opgeslagen dan bedoeld, met een verkeerde volgorde tot gevolg. Opgelost door
  bestaande rijen in de positie expliciet op te hogen en de eigen rij op een vaste waarde (1) te zetten.
* **`img="class:xxx"`-conventie niet generiek herbruikbaar.** De icoonklasse uit het componentmanifest
  (`img="class:simplehub"`, gebruikt door `mod_menu` voor het hoofdmenu-icoon) bleek buiten die
  specifieke menucontext geen gedefinieerde CSS-klasse — de link rendeerde wel, maar zonder zichtbare
  inhoud. Opgelost met een zelfstandige inline SVG.

---

## ADR-7 — Eigen iconkeuze via FontAwesome: geen tweede library, `icon`-kolom als override

**Status:** Aanvaard en geïmplementeerd

**Context**

Onderzocht is of Hub-items een eigen, klikbare iconkeuze via een FontAwesome-popup kunnen krijgen, als
override op het automatisch bepaalde icoon (`LinkResolver`). Drie onderzoeksvragen: (1) welke
FontAwesome-versie Joomla 6 al meelevert, om een dubbele library te vermijden; (2) de relatie tot het
bestaande, tot dan toe functieloze `icon`-veld/kolom; (3) de relatie tot de bestaande, automatische
iconresolutie.

**Onderzoek**

*Onderzoeksvraag 1 — FontAwesome-versie*

* Joomla core levert sinds Joomla 4 uitsluitend de **gratis** FontAwesome-set mee, nooit Pro — expliciet
  zo gedocumenteerd in de officiële Joomla-handleiding (manual.joomla.org, hoofdstuk "Icons", actuele
  versie 6.0). De set bevat de Solid-stijl (volledig), de Brands-stijl (logo's) en een **beperkte**
  Regular-subset (sinds Joomla 4.0 teruggebracht tot circa 150 iconen uit de veel grotere Pro-Regular-set
  — de volledige Regular-stijl is Pro-only; zie Joomla-issuetracker #31096).
* Versielijn: **FontAwesome 6**, niet 5 en niet 7. Joomla stapte in de 5.x-lijn over van FA5 naar FA6 en
  werd sindsdien binnen de 6-lijn bijgewerkt (o.a. naar 6.5.1 in Joomla 5.1, zichtbaar in het
  `package.json`-bestand van de `5.1-dev`-branch op GitHub, samenhangend met PR #42721, "[5.1] Update
  FontAwesome to 6.5.1"). Een expliciet verzoek om Joomla 6 al naar FontAwesome 7 te tillen
  (Joomla-issuetracker #45766, geopend juli 2025, tijdens de Joomla 6-alphafase) is door een Joomla
  core-maintainer voor de Joomla 6-lijn afgewezen. Joomla 6 blijft dus op FontAwesome 6.
  **Kanttekening:** het exacte FontAwesome 6-*patch*versienummer in de actuele Joomla 6.0/6.1/6.2-broncode
  (bijv. 6.5.x, 6.6.x of 6.7.x) is niet rechtstreeks tegen een lokale installatie geverifieerd — wel staat
  vast dát het major/minor-niveau "FontAwesome 6, Free" is, en dat versie 7 voor de Joomla 6-lijn is
  afgewezen. Voor de kernvraag (dubbele library vermijden) is de exacte patchversie niet relevant: welke
  patch ook actief is, een eigen popup gebruikt de al geladen klassen, niet een eigen meegeleverd bestand.
* **Hoe geladen, en waarom geen dubbele library dreigt:** Joomla registreert FontAwesome als het generieke,
  systeembrede web-asset `fontawesome` in `media/vendor/joomla.asset.json` (via het gecompileerde bestand
  `media/system/css/joomla-fontawesome.min.css`, dat naast de FontAwesome-CSS ook de `@font-face`-
  declaraties en Joomla's eigen `icon-`-klassemapping bevat — de sinds Joomla 3 gebruikte, backward-
  compatibele Icomoon-vertaalslag). Dit asset wordt, anders dan component-eigen assets, altijd als eerste
  geregistreerd zodra de Web Asset Registry voor het eerst wordt geraadpleegd, en het Atum-adminsjabloon
  gebruikt het als afhankelijkheid van zijn eigen template-stylesheet. Het is daarmee al op **elke**
  Administrator-pagina actief geladen — dus ook op het Item-formulier.
* **Consequentie:** een eigen iconpicker-popup hoeft geen eigen FontAwesome-bestanden mee te leveren en
  geen eigen entry in `media/joomla.asset.json` te registreren. Rechtstreeks de bestaande, kernbrede
  klassen gebruiken (`fa-solid fa-<naam>`, `fa-brands fa-<naam>`, en het beperkte `fa-regular fa-<naam>`-
  subsetje) is voldoende.

*Onderzoeksvraag 2 — relatie tot het bestaande `icon`-veld*

* De kolom is `VARCHAR(100) NOT NULL`, zonder default (zie `ARCHITECTURE.md`, hoofdstuk 10c).
  `VARCHAR(100)` is ruim voldoende voor een volledige FontAwesome-class-string (bijv.
  `fa-solid fa-address-book`, circa 28 tekens) — ook de langst bekende officiële klassenamen blijven ver
  onder de 100 tekens.
* `NOT NULL` zonder default is geen belemmering: zoals vastgelegd in `JOOMLA_NOTES.md` ("`NOT NULL` op
  een kolom betekent niet 'verplicht voor de gebruiker'"), voldoet een lege string `''` gewoon aan de
  constraint. De daadwerkelijke verplichting wordt door de form-XML bepaald, niet door de kolom — een leeg
  veld betekent dus probleemloos "geen handmatige keuze, gebruik het automatische icoon".
* **Gekozen opslagformat:** de volledige, direct bruikbare class-string in één keer opslaan (bijv.
  `fa-solid fa-address-book`), niet stijl en iconnaam in aparte kolommen. Eén kolom, één waarde, direct in
  een `class`-attribuut te plaatsen — sluit aan bij KISS en vergt geen schemawijziging.

*Onderzoeksvraag 3 — relatie tot de automatische iconresolutie (`LinkResolver`)*

* **Gekozen model:** leeg `icon`-veld = automatisch (het bestaande `LinkResolver`-gedrag, ongewijzigd);
  gevuld `icon`-veld = expliciete override die voorrang krijgt boven de automatische resolutie.
* **Architecturaal hoort dit niet ín `LinkResolver` zelf te worden opgelost.** `LinkResolver` heeft één
  verantwoordelijkheid: `type` + `target` omzetten naar link, status en icoon (`ARCHITECTURE.md`,
  hoofdstuk 11: "LinkResolver is de enige plek die `type` + `target` omzet naar link, status en icoon"). Een
  handmatig gekozen override is geen afgeleide van `type`/`target` maar een apart opgeslagen
  gebruikerskeuze; die twee vermengen zou `LinkResolver` een tweede verantwoordelijkheid geven en het
  bestaande principe juist ondermijnen.
* In plaats daarvan: een dunne merge-stap bij de **aanroepers** van `LinkResolver`
  (`HubRepository::getGroups()` voor het Dashboard, `ItemModel::getItem()`/`ItemController::icon()` voor
  het Item-formulier, zie hoofdstuk 10c): staat de opgeslagen `icon`-kolom niet leeg, dan die waarde tonen
  (type: class); anders het resultaat van `LinkResolver` gebruiken zoals nu.
* Client-side (`media/js/item.js`): bij het kiezen van een icoon in de popup wordt de preview direct
  bijgewerkt (de gekozen class is al bekend in de browser, geen Ajax-rondje nodig); bij het wissen van de
  keuze wordt de bestaande automatische-icoon-Ajax-aanroep (`ItemController::icon()`) opnieuw getriggerd
  om terug te vallen op het `LinkResolver`-resultaat.

**Beslissing**

* Een eigen custom form field type, `IconpickerField` (naar het patroon van de bestaande
  `TitlebarStateField`, `administrator/src/Field/`), vervangt het eerdere kale tekstveld voor `icon` in
  `admin/forms/item.xml`. Joomla core biedt zelf geen kant-en-klaar icoon-keuze-veldtype — een eigen
  `FormField`-subklasse met `addfieldprefix` is hiervoor de conventionele, Core-conforme weg.
* Geen wijziging aan `media/joomla.asset.json`, geen eigen FontAwesome-bestanden: hergebruik van het al
  overal in de Administrator geladen `fontawesome`-asset.
* Geen schemawijziging: de bestaande `icon`-kolom is hergebruikt, met de volledige class-string als
  waarde; leeg = automatisch.
* `LinkResolver` blijft ongewijzigd; de override-logica ligt bij de bestaande aanroepers ervan.
* De UI is een doorzoekbaar, plat iconengrid zonder categorieën (KISS, geen Speculative Architecture).

**Consequenties**

* Het eerder functieloze `icon`-veld heeft hiermee een vastgestelde functie gekregen.
* De iconlijst in `admin/src/Support/FontAwesomeIcons.php` is een curated selectie, niet geverifieerd
  tegen de daadwerkelijk geladen `joomla-fontawesome`-webasset op patchversie-niveau. Blijft een
  aanbevolen controle, zie `ROADMAP_INTERN.md`.
* **Bijvangst — bugfix.** Tijdens implementatie bleek `ItemModel::getItem()` de ruwe `icon`-kolomwaarde te
  overschrijven met het automatisch bepaalde icoon vóórdat het item het formulier in ging;
  `ItemModel::save()` schreef die auto-waarde vervolgens bij elke opslag terug naar de database — dit zou
  de override-logica uit dit ADR hebben ondermijnd. Opgelost door de weer te geven waarde in aparte
  properties (`resolved_icon_type`/`resolved_icon`) te zetten, los van de ruwe kolomwaarde.

**Kanttekening**

Het onderzoek is gebaseerd op de officiële Joomla-handleiding (manual.joomla.org, hoofdstuk "Icons",
versie 6.0), de Joomla Core-broncodegeschiedenis en -discussies op GitHub (o.a. PR #42721 "[5.1] Update
FontAwesome to 6.5.1", issue #31096 over de beperkte Regular-subset, issues #32541/#38898/#44451 over de
asset-registratie van `fontawesome`) en het Joomla-issuetrackerverzoek #45766 (FontAwesome 7 voor Joomla 6,
afgewezen voor de 6-lijn), niet op een live geteste Proof of Concept vooraf. Het exacte FontAwesome
6-patchversienummer in de actuele Joomla 6-broncode is niet rechtstreeks tegen een lokale installatie
geverifieerd (zie onderzoeksvraag 1); dit raakt de haalbaarheidsconclusie niet, maar verdient bij
gelegenheid alsnog een korte controle tegen de daadwerkelijke Joomla 6-testomgeving, voor het geval een
pas in een latere FontAwesome 6.x-patch toegevoegde iconnaam nodig zou zijn.

---

## ADR-8 — DNS-rebindingvenster in ExternalUrlChecker: IP-adres pinnen via CURLOPT_RESOLVE

**Status:** Aanvaard

**Context**

`checkFormat()`/`check()` valideren
de host via `isAllowedHost()`, die de hostname zelf via `resolveHostAddresses()` (DNS) opzoekt en ieder
gevonden adres met `isPublicIp()` toetst. `check()` voert vervolgens `pingUrl()` uit, dat het daadwerkelijke
HTTP-verzoek doet via `HttpFactory::getHttp()->head()` (met een GET-fallback). Deze curl-transportlaag lost
de hostname op het moment van het verzoek **opnieuw**, zelf, via DNS op — los van, en na, de eerdere
controle. Tussen deze twee momenten kan de DNS-registratie van de host wijzigen (DNS-rebinding): publiek
gevalideerd bij de eerste resolutie, niet-toegestaan (intern) bij de tweede, ongecontroleerde resolutie.

**Onderzoek (criterium 1 — bewijs)**

* De twee resoluties zijn bevestigd op broncodeniveau: `resolveHostAddresses()` (aangeroepen vanuit
  `isAllowedHost()`, op zijn beurt aangeroepen vanuit `structuralCheck()`) gebruikt `dns_get_record()`.
  `pingUrl()` doet zelf geen DNS-aanroep; de resolutie gebeurt binnen curl, ná `HttpFactory::getHttp($options)`
  via `$http->head()`/`$http->get()` — buiten SimpleHub's eigen code om, dus niet direct zichtbaar in
  `ExternalUrlChecker.php` zelf, maar bevestigd via de Joomla Core-transportlaag (zie hieronder).
* Joomla Core First: de daadwerkelijke transportklasse die `HttpFactory::getHttp()` in een actuele Joomla-
  installatie oplevert, is (sinds de curl-driver verhuisde naar het losse `joomla-framework/http`-package)
  `Joomla\Http\Transport\Curl`, niet de deprecated `Joomla\CMS\Http\Transport\CurlTransport`
  (die vanaf 6.0 is gemarkeerd als "will be removed in 7.0; use `Joomla\Http\Transport\Curl` instead").
  In `Curl::request()` wordt de aangeleverde `Registry`/array voor de reserved key `transport.curl`
  regel voor regel over een lokale `$options`-array gekopieerd, **als allerlaatste stap vóór**
  `curl_setopt_array($ch, $options)` — ná `CURLOPT_FOLLOWLOCATION`, authenticatie en protocolversie. Elke
  sleutel die SimpleHub via `transport.curl` meegeeft (zoals de bestaande `CURLOPT_IPRESOLVE`,
  `CURLOPT_SSL_VERIFYPEER`, `CURLOPT_TIMEOUT`, `CURLOPT_FOLLOWLOCATION`) krijgt hierdoor gegarandeerd het
  laatste woord, en wordt zonder verdere bewerking rechtstreeks aan `curl_setopt_array()` doorgegeven —
  dit is exact het reeds bestaande, werkende mechanisme in `pingUrl()`.

**Beslissing**

* **`CURLOPT_RESOLVE` via dezelfde `transport.curl`-array.** Omdat `transport.curl`-sleutels ongewijzigd
  en zonder Core-aanpassing bij `curl_setopt_array()` terechtkomen, is er geen ander mechanisme nodig:
  `pingUrl()` voegt, wanneer een gepind adres beschikbaar is, een entry `CURLOPT_RESOLVE => ["host:poort:ip"]`
  toe aan dezelfde array die de bestaande curl-opties bevat. Eén entry per verzoek is voldoende: HEAD en de
  eventuele GET-fallback in `pingUrl()` richten zich altijd op exact dezelfde host+poort-combinatie en lopen
  via dezelfde `$options`/`$http`-instantie, dus geen aparte pinning per sub-verzoek nodig.
* **Wie levert het adres, en welk adres bij meerdere A/AAAA-records?** `isAllowedHost()` (aangeroepen vanuit
  `structuralCheck()`) kent, via de al bestaande lus over `resolveHostAddresses()`, alle gevalideerde publieke
  adressen van de host. Er wordt **één publiek IPv4-adres** gekozen (het eerste gevonden) om te pinnen — niet
  alle gevonden adressen. Dit is afdoende: elk geretourneerd adres is al met `isPublicIp()` getoetst, dus élk
  van hen is een veilig doel; het doel van pinning is niet "dek alle mogelijke adressen af", maar "voorkom dat
  curl zelf, ongecontroleerd, opnieuw resolvet". Eén vooraf gevalideerd adres bereikt dat volledig (KISS).
* **Waarom specifiek IPv4, en de relatie tot `CURLOPT_IPRESOLVE_V4`.** `pingUrl()` dwingt daarnaast al
  `CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4` af. Curl-documentatie beschrijft `CURLOPT_IPRESOLVE` als een filter
  op welke IP-familie curl mag *gebruiken* bij het verbinden — dat filter geldt ongeacht of het adres via
  reguliere DNS dan wel via een `CURLOPT_RESOLVE`-cache-entry bekend is. Een gepind IPv6-adres zou dus, gezien
  de bestaande V4-only-instelling, hoe dan ook genegeerd worden (geen expliciete Joomla- of curl-bronvermelding
  gevonden die dit exacte samenspel letterlijk beschrijft; dit volgt uit de gedocumenteerde, algemene werking
  van `CURLOPT_IPRESOLVE` als connectiefilter — vermeld hier als aanname op basis van curl's gedocumenteerde
  gedrag, niet als letterlijk geciteerde bron). Er wordt daarom uitsluitend een IPv4-adres gepind.
  **Consequentie voor een AAAA-only host:** heeft de host uitsluitend IPv6-adressen (dus geen IPv4-adres om te
  pinnen), dan doet `check()` helemaal geen verzoek en geeft direct `host_not_allowed`
  terug, in plaats van `pingUrl()` zonder pin te laten proberen. Zo'n host faalt bij het
  daadwerkelijke verzoek toch al vrijwel altijd (curl vindt via de bestaande V4-only-instelling doorgaans geen
  bruikbaar adres); het waarneembare verschil is dat de foutreden verandert van `http_failed` naar
  `host_not_allowed` voor dit specifieke randgeval. Bewust zo gekozen: zonder pin zou curl zelf, ongecontroleerd,
  een DNS-resolutie mogen uitvoeren — exact het venster dat dit ADR moet sluiten — dus "geen verzoek" is
  hier veiliger dan "verzoek zonder pin toestaan".
* **IP-literal hosts (criterium 3c).** Wanneer de opgegeven host zelf al een IP-adres is, blijft `pinnedIp`
  `null` en verandert er niets: er vindt sowieso geen DNS-resolutie plaats (curl verbindt rechtstreeks met het
  opgegeven adres), dus geen pin-logica nodig of toegepast.
* **KISS-toets:** geen kleiner alternatief overwogen dat evenveel bereikt. Het
  venster ontstaat specifiek doordat curl zélf, op verzoekmoment, herresolvet; dat is alleen te voorkomen door
  curl expliciet een vast adres op te leggen. `CURLOPT_RESOLVE` is hiervoor het curl-eigen, standaardmechanisme
  en sluit naadloos aan op de al bestaande `transport.curl`-optie-array — geen eigen resolutielaag, geen nieuwe
  dependency, geen wijziging aan de allow-list-logica zelf.

**Consequenties**

* `structuralCheck()` en `isAllowedHost()` krijgen een optionele by-reference outputparameter (`&$pinnedIp`)
  in plaats van een gewijzigde returnwaarde — minimale wijziging aan het interne contract, geen wijziging aan
  het publieke contract van `checkFormat()`/`check()`/`describeFailure()`. `checkFormat()` geeft
  het argument niet door en is dus volledig ongewijzigd in gedrag.
  `pingUrl()` krijgt een extra parameter (`?string $pinnedIp`), altijd expliciet meegegeven door `check()`.
* Enige waarneembare gedragswijziging buiten het dichten van het venster zelf: voor een host die uitsluitend
  IPv6-adressen heeft (en dus nooit een geldig `check()`-resultaat kon opleveren via de bestaande
  `CURLOPT_IPRESOLVE_V4`-instelling), verandert de foutreden van `http_failed` naar `host_not_allowed`.
  `checkFormat()` (opslaan) is hierdoor niet geraakt.
* Geen wijziging aan `resolveHostAddresses()`, `isPublicIp()`, de allow-list-logica, `CURLOPT_FOLLOWLOCATION`,
  of enige andere klasse dan `ExternalUrlChecker`.

---

## ADR-9 — Meest gebruikte Dashboard-links: haalbaarheid en aanpak van gebruiksdetectie

**Status:** Aanvaard (onderzoek; geen implementatie)

**Context**

Onderzoek of "meest gebruikte links" op het Dashboard daadwerkelijk en betrouwbaar bepaald kunnen worden
via echte gebruiksdetectie, i.p.v. de bestaande handmatig samengestelde groepen.

**Onderzoek**

* Er bestaat vandaag geen enkel meetpunt voor een klik op een Dashboard-link: `HubRepository::getGroups()`
  levert via `LinkResolver` alleen link/status/icoon aan de View; een klik is daarna een gewone
  browsernavigatie die de server niet ziet. Het enige bestaande precedent voor het onderscheppen van een
  klik is de `popup`-clickhandler in `dashboard.js` (ADR-4), die nu niets naar de server stuurt.
  Joomla Core First: `com_actionlogs` is een audit-trail van schrijfacties (save/delete/publish/login),
  geen navigatie-/frequentiemeting, en dus niet bruikbaar of als voorbeeld toepasbaar; `mod_menu` en
  Quick Icons kennen geen "meest gebruikt"-mechanisme. Er is geen Core-mechanisme te hergebruiken.

**Beslissing**

**Technisch haalbaar**, maar **niet geïmplementeerd** — zie Consequenties. Aanpak, samengevat (mocht dit
ooit alsnog worden opgepakt):

* Uitbreiding van de bestaande click-onderschepping in `dashboard.js` naar alle Dashboard-itemlinks (niet
  uitsluitend `popup`), die bij een klik een lichte AJAX-aanroep (`navigator.sendBeacon()` voor
  `internal`/`_self`-navigatie, waar de huidige pagina wordt verlaten) doet met het item-ID.
* Een nieuw, klein endpoint naar het patroon van `GroupsController::saveOrderAjax()` dat het item-ID
  ontvangt, naar `HubRepository` doorgeeft en een `JsonResponse` teruggeeft.
* Opslag als eenvoudige tellerkolom (bijv. `use_count`) op de bestaande `#__simplehub_items`, niet als
  aparte, gedateerde logtabel — aggregaat per link, geen per-gebruiker- of tijdvenster-data, passend bij
  KISS.
* Dubbele tellingen: page-reloads zijn geen risico (telling gebeurt op klikmoment, niet bij laden);
  herhaalde kliks/aanroepen worden client-side met een korte debounce afgevangen — geen zwaardere
  server-side deduplicatie, in lijn met het bestaande afwegingsprincipe uit ADR-4.

**Consequenties**

* **Een automatische "meest gebruikt"-groep wordt niet gebouwd.** Het Dashboard is al een handmatig
  samengestelde, handmatig geordende lijst (drag & drop); een beheerder kan veelgebruikte links daar nu
  al zelf bovenaan zetten. Een automatisch geteld "meest gebruikt"-mechanisme zou grotendeels dezelfde
  behoefte dekken die de bestaande ordening al vervult, en levert onvoldoende toegevoegde waarde op om
  de extra complexiteit (nieuw endpoint, tellerkolom, click-onderschepping) te rechtvaardigen.
* Geen schemawijziging, geen tellerkolom.
* De hierboven uitgewerkte technische aanpak (click-onderschepping, endpoint-patroon, tellerkolom vs.
  logtabel) blijft als referentie in dit ADR staan, mocht de behoefte later — met een andere aanleiding —
  terugkomen.

**Kanttekening**

Geen Proof of Concept uitgevoerd: de bevindingen steunen op reeds bestaande, werkende precedenten in de
eigen codebase (`saveOrderAjax()`-patroon, de popup-clickhandler uit ADR-4), niet op aannames, waardoor
een PoC voor de haalbaarheidsvraag zelf geen extra zekerheid toevoegt. Het Core-onderzoek
(`com_actionlogs`, `mod_menu`, Quick Icons) is gebaseerd op de officiële Joomla-documentatie en
broncode-informatie, niet op een lokale installatie. De uiteindelijke uitkomst wordt dus niet bepaald door
deze Kanttekening, maar door de afweging onder Consequenties: een positieve haalbaarheid leidt hier
bewust niet tot implementatie.

---

## ADR-10 — Packaging vanaf v1.0.0: publicatie-zip vs. backup-zip

**Status:** Aanvaard

**Context**

Vóór deze packagingwijziging bevatte de installatie-zip alle projectdocumentatie door elkaar, inclusief
`CHANGELOG_INTERN.md` — een document dat `WAYOFWORK.md` expliciet als niet-openbaar aanmerkt. Voor een
publieke release moet de zip die gebruikers downloaden zich beperken tot wat ook daadwerkelijk op GitHub
gepubliceerd wordt.

**Beslissing**

Twee afzonderlijke zips, samengesteld door hetzelfde packagingscript:

* **Publicatie-zip** (`com_simplehub-<versie>.zip`) — de installeerbare extensie (alles onder
  `<files>`/`<administration>`/`<media>` in `com_simplehub.xml`) plus uitsluitend `README.md`,
  `CHANGELOG.md` en `LICENSE.txt`: de documentatiebestanden die ook op de GitHub-repository te zien zijn.
  Dit is de zip die gebruikers installeren.
* **Backup-zip** (`com_simplehub-<versie>-backup.zip`) — dezelfde extensiebestanden, aangevuld met de
  volledige interne projectdocumentatie (`WAYOFWORK.md`, `ARCHITECTURE.md`, `ARCHITECTURE_APPENDIX.md`,
  `JOOMLA_NOTES.md`, `NEXT_INCREMENT.md`, `ROADMAP_INTERN.md`, en — zodra het bestaat — `ROADMAP.md`) plus
  `README.md`/`CHANGELOG.md`/`LICENSE.txt`. Dit is de interne archiefkopie (`WAYOFWORK.md`,
  Ontwikkelvolgorde, stap 5).
* `CHANGELOG_INTERN.md` zit in **geen van beide** zips; dit document wordt voortaan altijd los aangeleverd
  naast de zips.

**Consequenties**

* Geen wijziging aan `com_simplehub.xml` anders dan het versienummer (de manifest-`<files>`-secties
  bepalen alleen de installeerbare inhoud, niet de doc-bestanden naast de zip — die worden door het
  packagingscript toegevoegd, niet door Joomla's installer gelezen).
* `ROADMAP.md` bestaat op dit moment nog niet; de regel in het packagingscript die het aan de backup-zip
  zou toevoegen is voorbereid maar heeft nog geen bestand om te verwerken. Geen speculatieve aanmaak van
  dit bestand (No Speculative Architecture) — dat is een aparte, toekomstige beslissing.
* Zie `ROADMAP_INTERN.md`.
