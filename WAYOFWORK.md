# Joomla extensie ontwikkeling – Way Of Work (WOW)

## Doel

Dit document beschrijft de samenwerking tijdens de ontwikkeling van een Joomla extensie.

Het is geen functionele of technische documentatie, maar legt vast hoe ontwerpbeslissingen worden genomen, hoe sprints worden uitgevoerd en welke verantwoordelijkheden iedere rol heeft.

---

# Uitgangspunten

## De extensie wordt iteratief ontwikkeld.

Iedere wijziging moet:

* een duidelijk doel hebben;
* zelfstandig testbaar zijn;
* blijvende waarde toevoegen;
* passen binnen de vastgestelde architectuur.

Tijdelijke oplossingen worden vermeden.

## KISS als expliciet principe

Wanneer twee oplossingen functioneel gelijkwaardig zijn, heeft de eenvoudigste oplossing de voorkeur.

## Joomla Core First

Architectuurbeslissingen volgen waar mogelijk de actuele Joomla Core.

Bootstrapcode, dependency injection en componentinitialisatie worden gebaseerd op vergelijkbare Core-componenten en alleen aangepast wanneer de functionaliteit daarom vraagt.


## Modern Joomla

Nieuwe code volgt de actuele Joomla Core-conventies.

Legacy API's worden niet meer geïntroduceerd tenzij compatibiliteit dit expliciet vereist.

## Geen speculative architecture

Functionaliteit wordt pas geïntroduceerd wanneer er een concrete gebruikersbehoefte bestaat. 

Er wordt geen infrastructuur gebouwd voor mogelijke toekomstige uitbreidingen.

Bij twijfel over volgen van de Joomla werkwijze: eerst de Core onderzoeken, dan ontwerpen.

## Refactoring is een sprintdoel

Het verwijderen van overbodige code is een volwaardige sprint wanneer hierdoor de architectuur eenvoudiger, beter onderhoudbaar of beter testbaar wordt.

## Installatie is onderdeel van iedere sprint

Een sprint is pas afgerond wanneer:

- de extensie zonder fouten installeert;
- de extensie zonder fouten opent;
- de basisfunctionaliteit werkt.

Installatieproblemen worden beschouwd als sprintwerk en niet als nazorg.

## Proof of Concept

Een Proof of Concept is een hulpmiddel om een ontwerpbeslissing te nemen, niet automatisch de eerste stap van de implementatie.

---

# Rollen

## AI Tool

AI Tool vervult twee technische rollen.

### Architect

De architect werkt vanuit het uitgangspunt dat de architectuur geen doel is maar de ontwikkeling van een zelfstandig werkende extensie ondersteunt.

Verantwoordelijkheden:
 
* bewaakt de softwarearchitectuur;
* bewaakt Joomla Core-conventies;
* voorkomt onnodige complexiteit;
* bewaakt de scheiding tussen businesslogica en presentatie;
* motiveert iedere architectuurwijziging voordat deze wordt voorgesteld;
* doet wanneer nodig voorstellen voor zo klein mogelijke sprints die de kwaliteit of eenvoud ten goede komen;
* toetst iedere sprint eerst aan de architectuur.

### Senior Joomla Core Developer

Verantwoordelijkheden

* ontwikkelt volgens Joomla Core-conventies;
* levert complete bestanden of exacte wijzigingen;
* introduceert geen eigen framework of alternatieve architectuur;
* gebruikt Constructor Injection waar Joomla Core dit ondersteunt;
* voorkomt duplicatie van code en kennis;
* denkt maximaal één sprint vooruit.

---

## Opdrachtgever

De opdrachtgever vervult drie rollen.

### Product Owner

Verantwoordelijkheden

* bepaalt de functionele waarde;
* stelt prioriteiten;
* bepaalt de inhoud van een increment;
* accepteert of verwerpt sprintresultaten.

### Scrum Master

Verantwoordelijkheden

* bewaakt de ontwikkelwerkwijze;
* houdt sprints klein;
* voorkomt scope creep;
* zorgt dat iedere sprint testbaar blijft.

### Gebruikerspanel

Verantwoordelijkheden

* beoordeelt de bruikbaarheid;
* denkt vanuit de beheerder van de extensie;
* toetst of functionaliteit logisch aanvoelt.

---

# Architectuur boven implementatie

Voordat code wordt geschreven, wordt eerst vastgesteld dat de voorgestelde oplossing past binnen de architectuur.

Wanneer tijdens een sprint blijkt dat een wijziging niet past binnen de architectuur, wordt eerst de architectuur besproken.

Pas daarna wordt code ontwikkeld.

---

# Ontwikkelvolgorde

Iedere sprint doorloopt dezelfde stappen.

1. Architectuurtoets.
2. Sprintdoel vaststellen.
3. Gewijzigde bestanden bepalen.
4. Implementatie.
5. Samenstellen nieuwe .zip (hiervoor wordt een python script gebruikt waarin nieuw versienummer wordt opgevraagd dat daarna wordt vastelegd in het manifest bestand én er wordt een backup gemaakt). 
6. Het samenstellen van de nieuwe .zip wordt normaal gesprokoen door de PO/SM gedaan. Alleen als er veel code bestanden in één increment worden geleverd kan aan de AI tool gevraagd worden om dit te faciliteren (als de tool die mogelijkheid heeft)
7. Installeren (in principe altijd als update, wel wordt met regelmaat getest of ook een nieuwe installatie de gewenste resultaten levert).
8. Testen.
9. Aanvullen CHANGELOG.md met het resultaat van de sprint
10. Pas daarna de volgende sprint.

---

# Omvang van een sprint

Een sprint bevat één kleine, logisch afgeronde wijziging.

Na iedere sprint moet de software:

* compileerbaar zijn;
* installeerbaar zijn;
* functioneel testbaar zijn.

---

# Bestandslevering

Per sprint worden uitsluitend de bestanden geleverd die daadwerkelijk wijzigen.

Complete, losse bestanden, hebben de voorkeur. 

De opdrachtgever kan aangeven (maar alleen dan) dat een installeerbare zip file óf een push naar de betreffende Github directory de voorkeur heeft.
Dit natuurlijk alleen naar technische mogelijkheden én als dit specifiek wordt aangegeven. 

Alleen wanneer dit aantoonbaar eenvoudiger is, worden exacte regelwijzigingen beschreven.

## Projectdocumentatie

## Projectdocumentatie

### Generieke documentatie

| Bestand           |   Status  | Doel                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| ----------------- | :-------: | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `WAYOFWORK.md`    | Verplicht | Beschrijft de ontwikkelwerkwijze, samenwerking, sprintaanpak en afspraken voor het project.                                                                                                                                                                                                                                                                                                                                                      |
| `JOOMLA_NOTES.md` | Optioneel | Bevat projectonafhankelijke Joomla-kennis die tijdens de ontwikkeling is opgedaan. Alleen geverifieerde en in de praktijk bewezen bevindingen worden opgenomen. Het document bevat geen projectspecifieke architectuur of sprintinformatie, maar dient als naslagwerk voor toekomstige ontwikkeling. Nieuwe bevindingen worden uitsluitend toegevoegd wanneer zij aantoonbaar waarde hebben voor toekomstige sprints of andere Joomla-projecten. |

### Projectspecifieke documentatie

| Bestand                    |    Status    | Doel                                                                                                                                                                                                                                                               |
| -------------------------- | :----------: | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `ARCHITECTURE.md`          |   Verplicht  | Beschrijft de softwarearchitectuur, ontwerpprincipes en verantwoordelijkheden van de component.                                                                                                                                                                    |
| `ARCHITECTURE_APPENDIX.md` |   Verplicht  | Documenteert de actuele implementatie van de component. Bevat een inventaris van bestanden, verantwoordelijkheden, afhankelijkheden, status en bekende toekomstige wijzigingen, alsmede het hoofdstuk Architecture Decision Records (ADR's) met een genummerde inhoudsopgave. Samen met `ARCHITECTURE.md` vormt dit de architectuurdocumentatie van het project. |
| `CHANGELOG_INTERN.md`      |   Verplicht  | Beschrijft alle functionele en technische wijzigingen tijdens de ontwikkeling. Dit document is uitsluitend bedoeld voor het ontwikkelteam en wordt niet openbaar gepubliceerd.                                                                                     |
| `CHANGELOG.md`             | Vanaf v1.0.0 | Beschrijft op hoofdlijnen de wijzigingen die relevant zijn voor eindgebruikers (release notes). Technische implementatiedetails worden hierin niet opgenomen.                                                                                                      |
| `README.md`                | Vanaf v1.0.0 | Beschrijft de functionaliteit, installatie en het gebruik van de extensie. Dit document is bedoeld voor eindgebruikers. Tijdens de ontwikkelfase is `ARCHITECTURE.md` de primaire projectdocumentatie.                                                             |
| `ROADMAP.md`               |   Optioneel  | Beschrijft de verwachte toekomst van het project. Er wordt onderscheid gemaakt tussen geplande releases, waarschijnlijke toekomstige ontwikkelingen en gebruikerswensen (User Stories). Het document bestaat alleen wanneer hiervoor inhoud aanwezig is.           |
| `ROADMAP_INTERN.md`      	 |   Optioneel  | Beschrijft bekende interne verbeterpunten die bewust zijn uitgesteld, bijvoorbeeld op het gebied van security, onderhoudbaarheid, performance of architectuur. Dit document wordt niet extern gepubliceerd.                                                        |
| `NEXT_INCREMENT.md`        |   Verplicht  | Beschrijft het doel, de scope en de achtergrond van de eerstvolgende sprint. Bevat daarnaast de verwachte werkzaamheden en eventuele aanvullende bestanden die, naast de standaard projectdocumentatie, nodig zijn om de sprint uit te voeren.                     |
| `README_NOTES.md`          |   Optioneel  | Verzamelbestand voor eindgebruikers-tekstfragmenten die uiteindelijk in README.md terechtkomen. Wordt alleen gebruikt als de op te nemen teksten niet als vanzelfsprekend uit de verplichte documentatie volgt.                                                    |

---

## Taalbestanden

Tijdens de ontwikkeling wordt uitsluitend gewerkt in het Nederlandse taalbestand
(`nl-NL`), omdat de opdrachtgever daarin het beste communiceert.

De overige taalbestanden (`en-GB`, `fr-FR`, `de-DE`, `es-ES`) worden pas vlak voor
iedere publicatie aangevuld. De Engelstalige bestanden zijn in de basis aanwezig, met
het absolute minimum aan regels.

Deze afspraak voorkomt onnodige vragen over de status van met name de Engelstalige
bestanden tijdens de ontwikkelfase.
---

## licentiedocumentatieblok

In Joomla is het verpicht om in ieder PHP blok een licentiedocumentatieblok op te nemen.

Voor StamPlusJ projecten is dit het standaardblok waar ieder .php bestand mee opent:
```
<?php

/**
 * @package     <naam extensie>
 * @subpackage  <onderdeel van de extensie>
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
```

# Versiebeheer

Tijdens de ontwikkeling wordt een projectgerichte versienummering gebruikt.

## Ontwikkelversies

Ontwikkelversies volgen het formaat:

```
0.<Sprint>.<Increment>
```

waarbij:

- **0** = ontwikkelstatus (nog geen publieke release);
- **Sprint** = het nummer van de hoofdsprint;
- **Increment** = de geaccepteerde tussensprint binnen de hoofdsprint.

Voorbeelden:

| Versie | Betekenis |
|--------|-----------|
| 0.5.1 | Eerste geaccepteerde increment van Sprint 5 |
| 0.5.2 | Tweede geaccepteerde increment van Sprint 5 |
| 0.6.1 | Eerste geaccepteerde increment van Sprint 6 |

Iedere increment is een volledig werkende en testbare versie.

Na iedere succesvolle test:

- wordt de `CHANGELOG.md` bijgewerkt;
- wordt een nieuwe installatie-zip samengesteld;
- wordt de vorige versie gearchiveerd.

## Eerste publieke release

Na afronding van alle geplande ontwikkelsprints wordt de laatste ontwikkelversie gepubliceerd als:

```
1.0.0
```

Vanaf dat moment volgt de component de gebruikelijke Semantic Versioning (SemVer).


# Ontwerpprincipes

Nieuwe functionaliteit wordt uitsluitend toegevoegd wanneer deze blijvende waarde heeft.

Voorbereidende code zonder direct nut wordt vermeden.

Iedere wijziging moet een logisch onderdeel vormen van de uiteindelijke architectuur.

Configuratie is geschreven voor websitebeheerders, niet voor ontwikkelaars.

---

# Besluitvorming

Wanneer meerdere oplossingen mogelijk zijn:

1. Joomla Core-conventies.
2. Bestaande architectuur.
3. Eenvoud.
4. Uitbreidbaarheid.
5. Pas daarna persoonlijke voorkeur.

---

# Communicatie

Architectuurkeuzes worden kort gemotiveerd.

De nadruk ligt op het nemen van ontwerpbeslissingen, niet op lange theoretische beschouwingen.

Wanneer een voorstel afwijkt van de bestaande architectuur, wordt dit vooraf expliciet benoemd.

---

## Architectuurtoets

Voor iedere sprint wordt vóór de implementatie vastgesteld welke architectuurlagen door de sprint worden geraakt.

### 1. Sprintscope

- Wat is het doel van de sprint?
- Past de sprint binnen `NEXT_INCREMENT.md`?
- Wordt uitsluitend gerealiseerd wat binnen de scope van de sprint valt?

### 2. Architectuurlagen

Per architectuurlaag wordt vastgesteld of een wijziging noodzakelijk is.

| Architectuurlaag | Wijziging |
|------------------|-----------|
| Manifest (`<extensienaam>.xml`) | Ja / Nee |
| Installatiebestanden | Ja / Nee |
| Services / Dependency Injection | Ja / Nee |
| Controller | Ja / Nee |
| Model / Repository | Ja / Nee |
| View | Ja / Nee |
| Template | Ja / Nee |
| Media (CSS / JavaScript / Images) | Ja / Nee |
| Taalbestanden | Ja / Nee |
| Configuratie | Ja / Nee |
| Database | Ja / Nee |

Alleen de noodzakelijke architectuurlagen worden gewijzigd.

### 3. Gevolgen voor installatie

Controleren of de sprint gevolgen heeft voor:

- nieuwe bestanden;
- nieuwe mappen;
- verwijderde bestanden;
- hernoemde bestanden;
- wijzigingen in `<extensienaam>.xml`;
- database-installatie of update;
- update vanaf de vorige versie.

### 4. Achterwaartse compatibiliteit

Controleren of een update vanaf de vorige versie zonder handmatige acties mogelijk blijft.

### 5. Architectuurprincipes

Bevestigen dat de sprint voldoet aan de uitgangspunten uit `ARCHITECTURE.md`:

- KISS;
- No Speculative Architecture;
- Single Responsibility;
- Joomla Core-conventies;
- de bestaande architectuur blijft leidend.

### Resultaat

De uitkomst van de architectuurtoets bepaalt welke bestanden tijdens de sprint gewijzigd mogen worden.

# Projectdocumentatie

## Uitgangspunten

Na iedere afgeronde sprint wordt de projectdocumentatie bijgewerkt. 
Minimaal betreft dit de CHANGELOG.md en, indien van toepassing, één of meer nieuwe ADR-secties 
(Architecture Decision Records) in het hoofdstuk ADR van `ARCHITECTURE_APPENDIX.md`, 
zodat architectuurbesluiten en de context van wijzigingen blijvend worden vastgelegd.

Een geaccepteerde ADR wordt niet met terugwerkende kracht gewijzigd. Wanneer een eerder besluit wordt 
herzien, wordt een nieuwe ADR toegevoegd die de vorige expliciet "supersedet"; beide ADR's verwijzen 
naar elkaar.

---

# Nieuwe chat

Bij het starten van een nieuwe chat worden deze documenten gebruikt:

* ARCHITECTURE.md
* ARCHTIECTURE_APPENDIX.md
* WAYOFWORK.md
* NEXT_INCREMENT.md
* README.md (alleen bij reeds gepubliceerde projecten)
* ROADMAP_INTERN.md (alleen indien van toepassing)

Deze documenten vormen gezamenlijk de context voor de verdere ontwikkeling.

De architect/ontwikkelaar bepaalt na het doornemen van de documentatie welke bestanden nodig zijn om de sprint inhoudelijk te starten.

---

# Definitie van gereed (Definition of Done)

Een sprint is gereed wanneer:

* de wijziging past binnen de architectuur;
* de code voldoet aan Joomla Core-conventies;
* uitsluitend de noodzakelijke bestanden zijn gewijzigd;
* de software succesvol kan worden geïnstalleerd;
* de functionaliteit getest kan worden;
* geen bekende regressies zijn geïntroduceerd;
* in alle .php bestanden is het licentiedocumentatieblok opgenomen.
