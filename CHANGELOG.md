# Changelog

Alle noemenswaardige wijzigingen aan SimpleHub worden in dit bestand bijgehouden, op hoofdlijnen en
gericht op gebruikers. Dit project volgt [Semantic Versioning](https://semver.org/lang/nl/).

## [1.0.0]

Eerste publieke release van SimpleHub.

### Initiële versie

* Dashboard met Hubgroepen: groepeer snelkoppelingen in zelf te benoemen groepen en herschik ze via
  slepen (drag & drop).
* Hub-items: snelkoppelingen binnen een groep, met vijf mogelijke bestemmingen — een component, een
  plugin, een module, een artikel, of een externe URL. Items binnen een groep zijn eveneens te
  herschikken via slepen.
* Automatische herkenning van het bijpassende icoon per item, met een ingebouwde FontAwesome-iconkiezer
  om zelf een icoon te kiezen als dat gewenst is.
* Externe URL's: controle op bereikbaarheid bij het opslaan, beveiliging tegen misbruik van de server
  (SSRF-bescherming, inclusief bescherming tegen kwaadaardige omleidingen en DNS-manipulatie), en een
  keuze voor het doelvenster (hetzelfde venster, een nieuw tabblad, of een nieuw, los venster).
  Snelkoppelingen naar een uitgeschakelde plugin, module of vergelijkbare bestemming worden herkenbaar
  als niet-beschikbaar getoond.
* Robuust verwijderen van een Hubgroep: een lege groep wordt direct verwijderd; bij een groep met items
  kun je kiezen om de items mee te verwijderen, of ze eerst te verplaatsen naar een andere groep.
* Titelbalk-snelkoppeling: vanaf elke pagina in de Joomla Administrator in één klik naar het SimpleHub
  Dashboard, met een eigen aan/uit-schakelaar in de componentopties.
* Meertalige beheerinterface: Nederlands, Engels, Duits, Frans en Spaans.
