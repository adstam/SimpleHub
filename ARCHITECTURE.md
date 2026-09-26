=============================================

# SimpleHub Architectuur

**Versie:** Sprint 17
**Status:** Actueel
**Laatste wijziging:** Augustus 2026 (bijgewerkt tijdens Sprint 18 — technische inventarisatie)

---

# 1. Architectuurvisie

SimpleHub is een Joomla Administrator Component voor het beheren van Hub-groepen en de daarbinnen geplaatste Hub-items (snelkoppelingen naar componenten, plugins, modules, artikelen en externe URL's), en (in volgende sprints) de volledige Hub-configuratie.

De component volgt zoveel mogelijk de standaard Joomla MVC-architectuur. Eigen oplossingen worden uitsluitend toegepast wanneer de standaard Joomla-implementatie aantoonbaar niet bruikbaar is binnen de gekozen componentarchitectuur.

Belangrijke uitgangspunten zijn:

* Joomla Core First
* MVC-scheiding
* Repository Pattern voor uitlezen en specifieke Hub-gerelateerde opslag
* Joomla AdminModel voor CRUD-bewerkingen
* Geen businesslogica in Views
* Database als enige bron van waarheid

---

# 2. Architectuuroverzicht

De component bestaat uit drie duidelijk onderscheiden gegevensstromen.

## Leesfunctionaliteit

Voor de Dashboard-uitleesfunctionaliteit wordt gebruikgemaakt van één centrale repository.

```text
Dashboard
       │
       ▼
HubRepository
       │
       ▼
Database
```

De repository verzorgt het ophalen en voorbereiden van Hub-data voor het Dashboard.

---

## CRUD-functionaliteit

Voor het toevoegen, wijzigen en verwijderen van records wordt gebruikgemaakt van Joomla's AdminModel.

```text
Toolbar

      │

      ▼

GroupController

      │

      ▼

GroupModel (AdminModel)

      │

      ▼

GroupTable

      │

      ▼

Database
```

Hiermee wordt optimaal aangesloten bij de Joomla Core.

---

## Drag & Drop / ordering

Het wijzigen van de sorteervolgorde van Hubgroepen vormt een afzonderlijke write-flow.

```text
SortableJS
     │
     ▼
dashboard.js
     │
     ▼
POST / Ajax
     │
     ▼
GroupsController
     │
     ▼
HubRepository
     │
     ▼
Database
```

Deze flow maakt geen gebruik van `GroupModel` of `GroupTable`.

De reden hiervoor is dat het herschikken van groepen geen CRUD-bewerking op één afzonderlijk record is, maar een collectieve wijziging van de `ordering`-waarden van meerdere groepen.

---

# 3. Repository Pattern

Voor de Hub-specifieke gegevensstromen wordt gebruikgemaakt van:

```text
HubRepository
```

De repository:

* leest groepen en items uit de database;
* bereidt de gegevens voor die het Dashboard nodig heeft;
* bevat de opslag van de groepsvolgorde;
* valideert de ontvangen groepsvolgorde voordat deze wordt opgeslagen;
* voert de ordering-update transactioneel uit.

De repository bevat geen gebruikersinterface.

De repository wordt daarmee gebruikt voor Hub-specifieke gegevenslogica die niet natuurlijk binnen een afzonderlijke CRUD-bewerking van `GroupModel` past.

---

# 4. CRUD-architectuur

CRUD wordt afgehandeld door Joomla's standaard MVC-structuur.

```text
GroupController
        │
        ▼
GroupModel
        │
        ▼
GroupTable
        │
        ▼
Database
```

De verantwoordelijkheden zijn:

### GroupController

Verantwoordelijk voor:

* toolbar-acties;
* routering;
* redirects;
* berichten aan de gebruiker;
* tokencontrole;
* specifieke controller-flow voor Save.

### GroupModel

Verantwoordelijk voor:

* formulierdefinitie;
* validatie;
* opslag;
* verwijderen;
* laden van records.

### GroupTable

Verantwoordelijk voor:

* mapping tussen database en object.

---

# 5. Dashboard

Het Dashboard is de centrale Administrator-pagina.

Vanuit het Dashboard worden alle onderhoudsacties gestart.

Onder andere:

* Groepen
* (toekomstig) Hubs
* (toekomstig) Gebruikers
* (toekomstig) Rollen

Het Dashboard ondersteunt daarnaast het rechtstreeks herschikken van Hubgroepen via drag & drop.

De View is uitsluitend verantwoordelijk voor het presenteren van de beschikbare groepen en het beschikbaar maken van de benodigde HTML-structuur.

De daadwerkelijke ordering-logica bevindt zich niet in de View.

---

# 6. Drag & Drop architectuur

Voor de drag & drop-functionaliteit wordt SortableJS gebruikt.

De JavaScript-functionaliteit wordt als Joomla Web Asset geregistreerd.

```text
joomla.asset.json
        │
        ├── com_simplehub.sortable
        │
        └── com_simplehub.dashboard
                    │
                    ▼
              dashboard.js
```

`com_simplehub.dashboard` heeft een dependency op `com_simplehub.sortable`.

De Dashboard-view activeert de geregistreerde component-assets via Joomla's Web Asset Manager.

De bestanden worden daarmee niet rechtstreeks vanuit de View als losse `<script>`-bestanden geladen.

---

# 7. Opslaan van de groepsvolgorde

Na een drag & drop bepaalt `dashboard.js` de actuele volgorde van de groepen aan de hand van hun database-ID.

Bijvoorbeeld:

```text
[4, 1, 3]
```

Deze volgorde wordt via een POST/Ajax-request naar:

```text
GroupsController::saveOrderAjax()
```

gestuurd.

De controller:

* ontvangt de groeps-ID's;
* converteert de ontvangen waarden naar integers;
* roept `HubRepository::saveGroupOrder()` aan;
* retourneert een `JsonResponse`.

De repository controleert vervolgens of:

* er daadwerkelijk een volgorde is ontvangen;
* alle ontvangen IDs geldige groepen zijn;
* uitsluitend gepubliceerde groepen voorkomen;
* iedere gepubliceerde groep exact één keer voorkomt.

Pas daarna worden de ordering-waarden aangepast.

---

# 8. Transactionele ordering-opslag

De nieuwe volgorde wordt transactioneel opgeslagen.

Omdat de `ordering`-kolom mogelijk uniek is, worden bestaande ordering-waarden eerst tijdelijk naar een hoog bereik verplaatst.

Daarna worden de definitieve waarden toegekend:

```text
positie 1 → ordering 1
positie 2 → ordering 2
positie 3 → ordering 3
...
```

Hierdoor ontstaan tijdens het herschikken geen tijdelijke conflicten met bestaande unieke `ordering`-waarden.

Wanneer tijdens de opslag een fout optreedt, wordt de transactie teruggedraaid.

Na het committen wordt de opgeslagen volgorde opnieuw gecontroleerd.

De database blijft hiermee de enige bron van waarheid.

---

# 9. Afwijking ten opzichte van standaard Joomla

Tijdens Sprint 9.2 is vastgesteld dat de standaard implementatie van:

```text
FormController::save()
```

binnen de gekozen Dashboard-architectuur de primaire sleutel (`id`) niet correct behoudt tijdens het opslaan van bestaande records.

Hierdoor werd bij een wijziging steeds een INSERT uitgevoerd in plaats van een UPDATE.

Daarom bevat `GroupController` een eigen implementatie van:

```text
save()
```

Deze methode:

* leest rechtstreeks `jform`;
* gebruikt `GroupModel::save()`;
* verzorgt redirects;
* ondersteunt Apply;
* ondersteunt Save;
* ondersteunt Save & New.

De daadwerkelijke CRUD-opslag blijft volledig plaatsvinden via `AdminModel`.

De afwijking is daarmee beperkt tot de controllerlaag.

---

# 10. Verwijderen van records

Het verwijderen van groepen verloopt eveneens via de controller.

```text
Delete

    │

    ▼

GroupController::delete()

    │

    ▼

GroupModel::delete()

    │

    ▼

GroupTable

    │

    ▼

Database
```

De controller verzorgt uitsluitend:

* tokencontrole;
* ophalen van de geselecteerde sleutel;
* foutafhandeling;
* redirects.

De verwijderlogica blijft in `AdminModel`. De database-foreign key `#__simplehub_items_group` is
`ON DELETE CASCADE`, waardoor items van de groep automatisch meeverwijderd worden.

## 10.1 Robuustere groep-verwijderflow (Sprint 19)

Een groep zonder items gedraagt zich ongewijzigd ten opzichte van het bovenstaande: een kale
`confirm()` op het Dashboard, gevolgd door het standaardpad hierboven.

Een groep mét items toont in plaats daarvan een modal (JoomlaDialog, zie `ARCHITECTURE_APPENDIX.md`,
ADR-1) met een expliciete keuze:

```text
Keuze A — Groep en items verwijderen
Delete (trash-knop)
    │
    ▼
Modal (JoomlaDialog, inline)
    │
    ▼
GroupController::delete()          ← ongewijzigd standaardpad
    │
    ▼
GroupModel::delete()
    │
    ▼
Database (FK-cascade verwijdert de items)


Keuze B — Items verplaatsen, dan groep verwijderen
Delete (trash-knop)
    │
    ▼
Modal (JoomlaDialog, inline)
    │
    ▼
GroupController::moveItemsAndDelete()
    │
    ▼
HubRepository::moveItemsToGroup()   ← verplaatst items, transactioneel
    │
    ▼
GroupModel::delete()                ← op de nu lege bronproep
    │
    ▼
Database
```

Beide keuzes vereisen, net als de bestaande verwijderflow, een geldig CSRF-token (`checkToken()`); de
modal-keuze is uitsluitend UX en vervangt geen tokencontrole.

Het daadwerkelijke aantal items in een groep (inclusief niet-gepubliceerde items, die immers ook door
de FK-cascade worden meeverwijderd) wordt door `HubRepository::getGroups()` afzonderlijk meegegeven
(`item_count`), los van de `items`-lijst die alleen gepubliceerde items voor de Dashboardweergave bevat.

Zie `ARCHITECTURE_APPENDIX.md`, ADR-1 en ADR-2, voor de volledige architectuurmotivatie.

---

# 10a. Items binnen een Hubgroep

Sinds Sprint 14 beheert SimpleHub naast Hubgroepen ook Hub-items: de individuele snelkoppelingen die
binnen een groep worden getoond.

```text
ItemController
        │
        ▼
ItemModel (AdminModel)
        │
        ▼
ItemTable
        │
        ▼
Database
```

De verantwoordelijkheidsverdeling is identiek aan die van Hubgroepen (zie hoofdstuk 4):

* `ItemController` verzorgt toolbar-acties, routering, redirects en tokencontrole;
* `ItemModel` verzorgt formulierdefinitie, validatie, opslag en verwijderen;
* `ItemTable` verzorgt de databasekoppeling.

Net als bij `GroupController` (hoofdstuk 9) verliest de standaard `FormController::save()` binnen de
gekozen architectuur de primaire sleutel van bestaande records. `ItemController` bevat daarom, net als
`GroupController`, een eigen `save()`-implementatie met dezelfde ondersteuning voor Apply, Save en
Save & New.

**Afwijking ten opzichte van `GroupController`:** bij een mislukte opslag zet `ItemController::save()`
de ingevoerde `jform`-waarden terug in de Joomla user state (`com_simplehub.edit.item.data`), zodat het
formulier bij het opnieuw tonen niet leeg is. Dit is in Sprint 17.5 opgelost naar aanleiding van
Bevinding 17.4. `GroupController::save()` doet dit niet — zie `ROADMAP_INTERN.md`, item #1.

## Volgorde van Items binnen een groep

Analoog aan de groepsvolgorde (hoofdstuk 7/8) kunnen Items binnen één Hubgroep opnieuw gesorteerd worden
via drag & drop:

```text
SortableJS
     │
     ▼
dashboard.js
     │
     ▼
POST / Ajax (met group_id)
     │
     ▼
ItemsController::saveOrderAjax()
     │
     ▼
HubRepository::saveItemOrder()
     │
     ▼
Database
```

Deze flow maakt, net als de groepsvolgorde-flow, geen gebruik van `ItemModel` of `ItemTable`: het
betreft een collectieve wijziging van `ordering`-waarden binnen één groep, niet een CRUD-bewerking op
een individueel record.

---

# 10b. Itemtypen en targetresolutie

Een Hub-item verwijst altijd naar precies één van vijf typen bestemmingen:

| Type | Geïntroduceerd | Bestemming |
|---|---|---|
| `component` | Sprint 14 | Een Joomla-administratorcomponent (optioneel met specifiek menu-item) |
| `plugin` | Sprint 16 | Een geïnstalleerde Joomla-plugin, geselecteerd op `extension_id` |
| `module` | Sprint 16 | Een module-instantie uit `#__modules` |
| `article` | Sprint 16 | Een artikel, geselecteerd via Joomla's `modal_article`-veld |
| `external` | Sprint 17 | Een willekeurige externe URL |

Het Item-formulier (`item.xml`) toont per type een eigen, type-specifiek doelveld
(`target_component`, `target_plugin`, `target_module`, `target_article`, `target_external`), zichtbaar
via `showon="type:<type>"`. Hierdoor krijgt ieder type zijn eigen Joomla-veldtype en validatie, zonder
dat de overige typen daar invloed op hebben.

`ItemModel::save()` normaliseert deze type-afhankelijke velden naar één opgeslagen `target`-waarde en
voert per type de bijbehorende validatie uit (bijvoorbeeld: bestaat de gekozen plugin/module/artikel
nog, of is de externe URL toegestaan — zie hoofdstuk 10d).

Wanneer na het kiezen van een target het titelveld nog leeg is, wordt automatisch een titel voorgesteld
(`component: <naam>`, `plugin: <naam>`, enzovoort). Een bestaande of handmatig ingevulde titel wordt
nooit overschreven. Deze logica draait client-side (`media/js/item.js`).

---

# 10c. LinkResolver

`LinkResolver` (`administrator/src/Support/LinkResolver.php`) zet een opgeslagen `type` + `target` om
naar drie dingen die het Dashboard nodig heeft om een Item bruikbaar te tonen:

* een bruikbare Joomla-administratorlink;
* een beschikbaarheidsstatus (bijvoorbeeld `disabled` wanneer de onderliggende plugin/module is
  uitgeschakeld, of wanneer de bestemming niet meer bestaat);
* een icoon (type + waarde).

Per itemtype bestaat een eigen resolver-methode. Voor `component` wordt, waar mogelijk, het
administrator-menu-item van de betreffende component gebruikt (inclusief onderliggende
component-items, zoals categorieweergaven); is dat niet te vinden, dan wordt teruggevallen op de
geïnstalleerde componentgegevens zelf. Voor `plugin`, `module` en `article` wordt de actuele Joomla-
status opgehaald en gebruikt om te bepalen of de link (nog) beschikbaar is. Voor `external` wordt geen
Joomla-status gecontroleerd; dit type krijgt altijd het vaste globe-icoon.

### Dynamische iconresolutie

Sinds Sprint 15 is het icoon van een Item **geen** opgeslagen eigenschap meer, maar wordt het bij het
tonen dynamisch bepaald. De resolver doorloopt hiervoor het gevonden administrator-menu-item en diens
ouders (`parent_id`); is daar geen bruikbaar icoon, dan valt de resolver terug op het hoofdmenu-item van
de extensie, en als laatste stap op het icoon van de Joomla-componentengroep (met `component` als vaste
eindfallback). Zowel Joomla `img`-waarden als `menu_icon_class` worden ondersteund.

De iconresolutie wordt aangeroepen vanuit twee plekken: `HubRepository::getGroups()` (Dashboard) en
`ItemModel::getItem()` / `ItemController::icon()` (Item-formulier, initieel en live tijdens het invullen —
Sprint 26).

### Override: handmatig gekozen icoon (Sprint 28)

Het `icon`-veld in `item.xml` en de bijbehorende databasekolom, sinds Sprint 15 zonder functie in deze
resolutieketen (`ROADMAP_INTERN.md`, item #5), zijn sinds Sprint 28 de opslagplaats voor een handmatig
door de beheerder gekozen FontAwesome-icoon (`IconpickerField`, `admin/src/Field/`). De kolom bevat dan de
volledige, direct bruikbare class-string (bijv. `fa-solid fa-address-book`); een lege kolom betekent nog
steeds "automatisch", exact het hierboven beschreven gedrag.

Beide aanroepers van `LinkResolver` passen dezelfde voorrangsregel toe: is de `icon`-kolom niet leeg, dan
die waarde tonen (type `override`, rechtstreeks als class gebruikt — géén `icon-`-prefix); anders het
resultaat van `LinkResolver` gebruiken zoals hierboven beschreven. Deze merge-stap zit bewust **niet** in
`LinkResolver` zelf (zie hoofdstuk 11: "`LinkResolver` is de enige plek die `type` + `target` omzet naar
link, status en icoon" — een handmatige override is geen afgeleide van `type`/`target`), maar bij de
aanroepers. Zie `ARCHITECTURE_APPENDIX.md`, ADR-7, voor de volledige onderbouwing.

---

# 10d. Externe URL's en SSRF-bescherming

Het itemtype `external` (Sprint 17) introduceert de mogelijkheid om vanuit SimpleHub naar een
willekeurige, door de beheerder opgegeven URL te linken. Omdat dit verzoek server-side (vanaf de Joomla-
server) wordt uitgevoerd, is expliciete bescherming tegen Server-Side Request Forgery (SSRF) nodig:
zonder die bescherming zou een kwaadwillende beheerder (of een aanvaller met toegang tot het
Item-formulier) de Joomla-server kunnen misbruiken om verzoeken naar interne, normaal niet bereikbare
adressen te laten uitvoeren.

Deze verantwoordelijkheid ligt volledig bij `ExternalUrlChecker`
(`administrator/src/Support/ExternalUrlChecker.php`), en bestaat uit twee, bewust gescheiden, lagen:

1. **`checkFormat()`** — een synchrone, netwerkloze* vormcontrole: geldig schema (`http`/`https`),
   aanwezige hostnaam, en een host-allowlist die `localhost`, gereserveerde adressen en private
   IP-ranges weigert. Wanneer de hostnaam geen IP-adres is, wordt deze via DNS opgelost
   (`dns_get_record`, A- en AAAA-records) en wordt elk gevonden adres afzonderlijk tegen dezelfde
   allowlist getoetst. Deze controle wordt bij **iedere opslag** van een `external`-item uitgevoerd
   (`ItemModel::save()`), ongeacht of de URL is gewijzigd.

   *(De DNS-resolutie die hiervoor plaatsvindt, is strikt genomen wél een netwerkoperatie; de term
   "netwerkloos" in de bestaande code-documentatie doelt op de afwezigheid van een HTTP-verzoek naar de
   externe host zelf.)*

2. **`check()`** — de volledige controle inclusief een daadwerkelijk HTTP-verzoek (`HEAD`, met
   `GET`-fallback) om de actuele bereikbaarheid vast te stellen. Deze controle kost tijd (tot de
   timeout van 5 seconden) en hoort daarom **niet** thuis in de opslaan-flow. Ze wordt uitsluitend
   aangeroepen via de aparte Ajax-actie `ItemController::checkUrl()`, getriggerd wanneer de gebruiker
   het URL-veld verlaat (`blur`, `media/js/item.js`).

```text
Opslaan (altijd, snel):
ItemController::save() → ItemModel::save() → ExternalUrlChecker::checkFormat()

Blur op URL-veld (optioneel, kan traag zijn):
item.js → ItemController::checkUrl() → ExternalUrlChecker::check() → JsonResponse
```

Deze scheiding is een bewuste architectuurkeuze (Sprint 17.4/17.5): de bereikbaarheidscontrole
(netwerk, kan traag of instabiel zijn) is losgekoppeld van de opslaan-flow (moet snel en voorspelbaar
blijven), terwijl de host-allowlist — de daadwerkelijke SSRF-bescherming — op **beide** plekken
afgedwongen blijft, omdat dit geen controle is die aan de client overgelaten kan worden.

**Bekende, nog niet gedichte kwetsbaarheid:** de host-allowlist wordt alleen tegen de opgegeven URL
gecontroleerd. Het daadwerkelijke HTTP-verzoek in `check()`/`pingUrl()` volgt HTTP-redirects zonder dit
opnieuw tegen de allowlist te toetsen, waardoor een toegestane host via een redirect alsnog naar een
niet-toegestane host zou kunnen wijzen. Zie `ROADMAP_INTERN.md`, item #2.

---

# 11. Architectuurprincipes

SimpleHub hanteert de volgende uitgangspunten.

* Joomla Core First
* MVC-scheiding
* Repository voor Hub-specifieke lees- en orderinglogica
* AdminModel voor CRUD
* Views bevatten geen businesslogica
* Controllers verzorgen flow-control
* Ajax-ordering is een afzonderlijke write-flow
* Database is de enige bron van waarheid
* CRUD en HubRepository blijven gescheiden verantwoordelijkheden
* Joomla Web Asset Management wordt gebruikt voor component-JavaScript
* Items volgen dezelfde architectuurscheiding als Hubgroepen (Controller/AdminModel/Table)
* Type-afhankelijke Item-targets krijgen elk hun eigen formulierveld en validatie
* LinkResolver is de enige plek die `type` + `target` omzet naar link, status en icoon
* Netwerkgevoelige controles (externe-URL-bereikbaarheid) worden losgekoppeld van de opslaan-flow
* Beveiligingscontroles die niet aan de client overgelaten kunnen worden (SSRF-allowlist) worden altijd
  server-side afgedwongen, ook wanneer de bijbehorende bereikbaarheidscontrole elders staat

---

# 12. Status Sprint 17 / analyse Sprint 18

Na Sprint 9.3 was de basis voor het beheren en ordenen van Hubgroepen voltooid. Sprint 14 tot en met 17
hebben daar de volledige Item-functionaliteit aan toegevoegd:

* toevoegen, wijzigen en verwijderen van Hub-items binnen een groep;
* volgorde van Items binnen een groep (drag & drop, analoog aan groepsvolgorde);
* vijf itemtypen: component, plugin, module, artikel en externe URL;
* automatische titelinvulling op basis van het gekozen target;
* dynamische iconresolutie (LinkResolver), losgekoppeld van een opgeslagen icoonwaarde;
* SSRF-beschermde afhandeling van externe URL's, met een gescheiden vorm- en bereikbaarheidscontrole.

Sprint 18 was een bewuste analysesprint, zonder functionele wijziging: een volledige technische
inventarisatie van de codebase, met als resultaat `ROADMAP_INTERN.md` (nieuw) en de bijwerking van dit
document en `ARCHITECTURE_APPENDIX.md` naar de actuele implementatie. De concrete bevindingen —
inclusief het niet-opgeloste formulier-leegmaak-patroon bij `GroupController` en de SSRF-redirect-
bevinding bij `ExternalUrlChecker` — staan in `ROADMAP_INTERN.md`, niet in dit document.

De architectuur is voorbereid op de sprintvolgorde 19 t/m 29 richting v1.0.0 (robuustere
groepsverwijdering, onderzoek naar een extern paneel en hoofdmenu-integratie, een zichtbaar/kiesbaar
icoon per item, en de resterende taalbestanden).
