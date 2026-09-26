<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * @copyright   Copyright (C) 2025 Ad Stam. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace StamPlusJ\Component\Simplehub\Administrator\Support;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

final class ExternalUrlChecker
{
    /**
     * Maximum time in seconds allowed for the HTTP request.
     */
    private const TIMEOUT = 5;

    /**
     * Controleert alleen de vorm van een externe URL (schema,
     * hostnaam, allow-list). Er wordt GEEN netwerkverzoek gedaan.
     *
     * Dit is een goedkope, synchrone controle die veilig bij het
     * opslaan van het item kan worden uitgevoerd: ze beschermt
     * (o.a. tegen SSRF via een niet-toegestane host) zonder dat
     * daarvoor een externe server bereikbaar hoeft te zijn.
     *
     * De daadwerkelijke bereikbaarheid (HTTP-verzoek) wordt apart
     * gecontroleerd via check(), bijvoorbeeld op het moment dat de
     * gebruiker het URL-veld verlaat.
     *
     * @return array{
     *     reachable:bool,
     *     reason:string,
     *     status:int
     * }
     */
    public function checkFormat(string $url): array
    {
        $failure = $this->structuralCheck($url);

        if ($failure !== null) {
            return $failure;
        }

        return [
            'reachable' => true,
            'reason'    => 'ok',
            'status'    => 0,
        ];
    }

    /**
     * Controleert een externe URL volledig, inclusief een
     * daadwerkelijk netwerkverzoek om de bereikbaarheid vast te
     * stellen, en geeft de technische reden terug wanneer de
     * controle mislukt.
     *
     * Deze controle kost tijd (netwerk-timeout) en hoort dus niet
     * thuis in het opslaan van het formulier, maar bijvoorbeeld in
     * een losse AJAX-aanroep wanneer de gebruiker het URL-veld
     * verlaat.
     *
     * Een 3xx-respons wordt NIET gevolgd (zie pingUrl()) en wordt
     * hier expliciet als "niet bereikbaar volgens de toegestane
     * regels" behandeld: een toegestane host mag niet via een
     * redirect alsnog een niet-toegestane host laten benaderen
     * zonder dat de allow-list dat opnieuw toetst (Sprint 29).
     *
     * @return array{
     *     reachable:bool,
     *     reason:string,
     *     status:int
     * }
     */
    public function check(string $url): array
    {
        $pinnedIp = null;
        $failure  = $this->structuralCheck($url, $pinnedIp);

        if ($failure !== null) {
            return $failure;
        }

        $url = trim($url);

        $logger      = Factory::getApplication()->getLogger();
        $logCategory = 'com_simplehub.externalurl';

        // DNS-rebinding (Sprint 32, item #30): pingUrl() moet verzoeken
        // tegen het reeds gevalideerde adres, niet tegen een nieuwe,
        // ongecontroleerde DNS-resolutie op verzoekmoment. Voor een
        // hostname (geen IP-literal) levert isAllowedHost() daarom een
        // gevalideerd, publiek IPv4-adres om te pinnen (zie pingUrl()).
        // Bestaat dat niet — de host heeft uitsluitend IPv6-adressen —
        // dan kan niet veilig gepind worden. De transportlaag dwingt
        // sowieso CURLOPT_IPRESOLVE_V4 af (ongewijzigd, ouder dan deze
        // sprint), dus een dergelijke host was al nooit daadwerkelijk
        // bereikbaar; zonder pin zou hij alsnog een nieuwe, ongecontroleerde
        // DNS-resolutie riskeren. Er wordt daarom geen verzoek gedaan.
        $parts        = parse_url($url);
        $host         = (string) ($parts['host'] ?? '');
        $hostIsLiteral = filter_var($host, FILTER_VALIDATE_IP) !== false;

        if (!$hostIsLiteral && $pinnedIp === null) {
            $logger->debug(
                'CHECK FAIL: geen publiek IPv4-adres om te pinnen voor "' . $host
                . '" (host heeft vermoedelijk uitsluitend IPv6-adressen); geen'
                . ' verzoek zonder pin.',
                ['category' => $logCategory]
            );

            return [
                'reachable' => false,
                'reason'    => 'host_not_allowed',
                'status'    => 0,
            ];
        }

        $statusCode = $this->pingUrl(
            $url,
            $pinnedIp,
            $logger,
            $logCategory
        );

        if ($statusCode >= 200 && $statusCode < 300) {
            return [
                'reachable' => true,
                'reason'    => 'ok',
                'status'    => $statusCode,
            ];
        }

        if ($statusCode >= 300 && $statusCode < 400) {
            return [
                'reachable' => false,
                'reason'    => 'redirect_blocked',
                'status'    => $statusCode,
            ];
        }

        if ($statusCode === 0) {
            return [
                'reachable' => false,
                'reason'    => 'http_failed',
                'status'    => 0,
            ];
        }

        return [
            'reachable' => false,
            'reason'    => 'http_error',
            'status'    => $statusCode,
        ];
    }

    /**
     * Zet een mislukt check()/checkFormat()-resultaat om naar een
     * vertaalde foutmelding voor de gebruiker.
     */
    public function describeFailure(array $check): string
    {
        $reason = (string) ($check['reason'] ?? 'http_failed');
        $status = (int) ($check['status'] ?? 0);

        switch ($reason) {
            case 'empty_url':
                return Text::_('COM_SIMPLEHUB_ERROR_EXTERNAL_EMPTY_URL');

            case 'invalid_url':
                return Text::_('COM_SIMPLEHUB_ERROR_EXTERNAL_INVALID_URL');

            case 'invalid_scheme':
                return Text::_('COM_SIMPLEHUB_ERROR_EXTERNAL_INVALID_SCHEME');

            case 'missing_host':
                return Text::_('COM_SIMPLEHUB_ERROR_EXTERNAL_MISSING_HOST');

            case 'host_not_allowed':
                return Text::_('COM_SIMPLEHUB_ERROR_EXTERNAL_HOST_NOT_ALLOWED');

            case 'redirect_blocked':
                return Text::_('COM_SIMPLEHUB_ERROR_EXTERNAL_REDIRECT_BLOCKED');

            case 'http_error':
                return Text::sprintf(
                    'COM_SIMPLEHUB_ERROR_EXTERNAL_HTTP_ERROR',
                    $status
                );

            case 'http_failed':
            default:
                return Text::_('COM_SIMPLEHUB_ERROR_EXTERNAL_HTTP_FAILED');
        }
    }

    /**
     * Voert de vormcontrole uit (schema, host, allow-list) zonder
     * netwerkverzoek. Geeft `null` terug wanneer de URL geldig is,
     * of anders het foutresultaat.
     *
     * @param  string|null  $pinnedIp  Output: bij een geldige, niet-
     *     IP-literal host het gevalideerde publieke IPv4-adres dat
     *     `pingUrl()` moet pinnen (DNS-rebinding, Sprint 32, item #30).
     *     Blijft `null` bij een IP-literal host (geen DNS, geen pin
     *     nodig) of wanneer geen publiek IPv4-adres gevonden is.
     *     Alleen door `check()` gebruikt; `checkFormat()` doet geen
     *     netwerkverzoek en heeft geen pin nodig.
     *
     * @return array{reachable:bool,reason:string,status:int}|null
     */
    private function structuralCheck(string $url, ?string &$pinnedIp = null): ?array
    {
        $pinnedIp = null;

        $url = trim($url);

        if ($url === '') {
            return [
                'reachable' => false,
                'reason'    => 'empty_url',
                'status'    => 0,
            ];
        }

        $parts = parse_url($url);

        if (!is_array($parts)) {
            return [
                'reachable' => false,
                'reason'    => 'invalid_url',
                'status'    => 0,
            ];
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host   = (string) ($parts['host'] ?? '');

        if (!in_array($scheme, ['http', 'https'], true)) {
            return [
                'reachable' => false,
                'reason'    => 'invalid_scheme',
                'status'    => 0,
            ];
        }

        if ($host === '') {
            return [
                'reachable' => false,
                'reason'    => 'missing_host',
                'status'    => 0,
            ];
        }

        if (!$this->isAllowedHost($host, $pinnedIp)) {
            return [
                'reachable' => false,
                'reason'    => 'host_not_allowed',
                'status'    => 0,
            ];
        }

        return null;
    }

    /**
     * Voert een HTTP HEAD/GET verzoek uit om de statuscode te achterhalen.
     *
     * Volgt bewust GEEN HTTP-redirects (CURLOPT_FOLLOWLOCATION = false):
     * de allow-list-controle in isAllowedHost() toetst alleen de
     * oorspronkelijk opgegeven host. Zonder deze instelling zou een
     * toegestane host via een 3xx-redirect alsnog naar een
     * niet-toegestane (interne) host kunnen wijzen zonder dat de
     * allow-list dat opnieuw toetst (SSRF-bypass, Sprint 29).
     *
     * DNS-rebinding (Sprint 32, item #30): curl lost de hostname bij dit
     * verzoek normaliter zelf, opnieuw, via DNS op — los van de eerdere
     * controle in isAllowedHost(). Wanneer $pinnedIp is meegegeven (niet-
     * IP-literal host, publiek IPv4-adres al gevalideerd), wordt curl via
     * CURLOPT_RESOLVE gedwongen dat exacte adres te gebruiken in plaats
     * van zelf opnieuw te resolven — zowel voor het HEAD- als het
     * eventuele GET-fallbackverzoek hieronder, want beide lopen via
     * dezelfde $options/$http-instantie. Bij een IP-literal host is
     * $pinnedIp altijd null; daar vindt sowieso geen DNS-resolutie
     * plaats en blijft dit gedrag ongewijzigd.
     */
    private function pingUrl(
        string $url,
        ?string $pinnedIp,
        $logger,
        string $logCategory
    ): int {
        $logger->debug(
            '============================================================',
            ['category' => $logCategory]
        );

        $logger->debug(
            'CHECK START',
            ['category' => $logCategory]
        );

        $logger->debug(
            'URL ontvangen: ' . $url,
            ['category' => $logCategory]
        );

        $curlOptions = [
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => false,
        ];

        if ($pinnedIp !== null) {
            $urlParts = parse_url($url);
            $pinHost  = (string) ($urlParts['host'] ?? '');
            $pinScheme = strtolower((string) ($urlParts['scheme'] ?? ''));
            $pinPort  = (int) ($urlParts['port'] ?? ($pinScheme === 'https' ? 443 : 80));

            // Eén host:port:adres-entry is voldoende: dit specifieke
            // verzoek (HEAD, en de eventuele GET-fallback hieronder) raakt
            // altijd exact deze host+poort-combinatie, nooit meerdere.
            $curlOptions[CURLOPT_RESOLVE] = [$pinHost . ':' . $pinPort . ':' . $pinnedIp];

            $logger->debug(
                'HTTP CONFIG: CURLOPT_RESOLVE gepind: ' . $pinHost . ':' . $pinPort
                . ' => ' . $pinnedIp . '.',
                ['category' => $logCategory]
            );
        }

        $options = new Registry([
            'transport.curl' => $curlOptions,
        ]);

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) SimpleHub/1.0',
        ];

        try {
            $logger->debug(
                'HTTP CONFIG: CURLOPT_IPRESOLVE = CURL_IPRESOLVE_V4.',
                ['category' => $logCategory]
            );

            $logger->debug(
                'HTTP CONFIG: CURLOPT_SSL_VERIFYPEER = true.',
                ['category' => $logCategory]
            );

            $logger->debug(
                'HTTP CONFIG: CURLOPT_TIMEOUT = ' . self::TIMEOUT . ' seconden.',
                ['category' => $logCategory]
            );

            $logger->debug(
                'HTTP CONFIG: CURLOPT_FOLLOWLOCATION = false.',
                ['category' => $logCategory]
            );

            $logger->debug(
                'HTTP CONFIG: User-Agent ingesteld.',
                ['category' => $logCategory]
            );

            $logger->debug(
                'HTTP 1: HttpFactory::getHttp() wordt aangeroepen.',
                ['category' => $logCategory]
            );

            $http = HttpFactory::getHttp($options);

            $logger->debug(
                'HTTP 2: HTTP-client succesvol aangemaakt.',
                ['category' => $logCategory]
            );

            $logger->debug(
                'HTTP 3: HEAD-request wordt uitgevoerd.',
                ['category' => $logCategory]
            );

            $logger->debug(
                'HTTP 3 URL: ' . $url,
                ['category' => $logCategory]
            );

            $response = $http->head(
                $url,
                $headers,
                self::TIMEOUT
            );

            $code = (int) $response->code;

            $logger->debug(
                'HTTP 4: HEAD-response ontvangen.',
                ['category' => $logCategory]
            );

            $logger->debug(
                'HTTP 4 STATUS: ' . $code,
                ['category' => $logCategory]
            );

            if ($code >= 300 && $code < 400) {
                $logger->debug(
                    'HTTP 4 REDIRECT: HEAD gaf een 3xx-status. Redirect wordt'
                    . ' geweigerd, geen GET-fallback nodig.',
                    ['category' => $logCategory]
                );

                $location = $this->extractLocationHeader($response);

                if ($location !== null) {
                    $logger->debug(
                        'HTTP 4 REDIRECT LOCATION: ' . $location,
                        ['category' => $logCategory]
                    );
                }
            } elseif ($code === 0 || $code >= 400) {
                $logger->debug(
                    'HTTP 5: HEAD gaf HTTP-code ' . $code . '. Fallback naar GET.',
                    ['category' => $logCategory]
                );

                $logger->debug(
                    'HTTP 5 URL: ' . $url,
                    ['category' => $logCategory]
                );

                $response = $http->get(
                    $url,
                    $headers,
                    self::TIMEOUT
                );

                $code = (int) $response->code;

                $logger->debug(
                    'HTTP 6: GET-response ontvangen.',
                    ['category' => $logCategory]
                );

                $logger->debug(
                    'HTTP 6 STATUS: ' . $code,
                    ['category' => $logCategory]
                );

                if ($code >= 300 && $code < 400) {
                    $logger->debug(
                        'HTTP 6 REDIRECT: GET gaf een 3xx-status. Redirect'
                        . ' wordt geweigerd.',
                        ['category' => $logCategory]
                    );

                    $location = $this->extractLocationHeader($response);

                    if ($location !== null) {
                        $logger->debug(
                            'HTTP 6 REDIRECT LOCATION: ' . $location,
                            ['category' => $logCategory]
                        );
                    }
                }
            } else {
                $logger->debug(
                    'HTTP 5: HEAD gaf een geldige response; GET is niet nodig.',
                    ['category' => $logCategory]
                );
            }

            if ($code >= 200 && $code < 300) {
                $logger->debug(
                    'HTTP SUCCESS: Geldige HTTP-statuscode: ' . $code,
                    ['category' => $logCategory]
                );
            } elseif ($code >= 300 && $code < 400) {
                $logger->debug(
                    'HTTP BLOCKED: Redirect (HTTP-statuscode ' . $code
                    . ') wordt niet gevolgd en telt als niet bereikbaar.',
                    ['category' => $logCategory]
                );
            } elseif ($code === 0) {
                $logger->debug(
                    'HTTP FAIL: Er is geen geldige HTTP-statuscode ontvangen.',
                    ['category' => $logCategory]
                );
            } else {
                $logger->debug(
                    'HTTP FAIL: HTTP-statuscode buiten bereik 200-399: ' . $code,
                    ['category' => $logCategory]
                );
            }

            $logger->debug(
                'HTTP END: pingUrl() retourneert ' . $code,
                ['category' => $logCategory]
            );

            return $code;
        } catch (\Throwable $e) {
            $logger->debug(
                'HTTP EXCEPTION: ' . $e->getMessage(),
                ['category' => $logCategory]
            );

            return 0;
        }
    }

    /**
     * Zoekt de Location-header op in een Http\Response, ongeacht de
     * schrijfwijze (headers zijn geen PSR-7-object maar een array op
     * `$response->headers`). Alleen gebruikt voor debug-logging bij
     * een geweigerde redirect; heeft geen invloed op de allow-list-
     * of bereikbaarheidsbeslissing zelf.
     */
    private function extractLocationHeader($response): ?string
    {
        if (!isset($response->headers) || !is_array($response->headers)) {
            return null;
        }

        foreach ($response->headers as $name => $value) {
            if (strtolower((string) $name) !== 'location') {
                continue;
            }

            $value = is_array($value) ? reset($value) : $value;

            return $value === false ? null : (string) $value;
        }

        return null;
    }

    /**
     * Controleert of een hostname naar een toegestane externe bestemming
     * verwijst.
     *
     * @param  string|null  $pinnedIp  Output (Sprint 32, item #30): bij een
     *     geldige, niet-IP-literal host het eerste gevalideerde publieke
     *     IPv4-adres uit de DNS-resolutie, bedoeld om door `pingUrl()`
     *     gepind te worden (`CURLOPT_RESOLVE`) zodat die geen eigen,
     *     ongecontroleerde DNS-resolutie meer uitvoert. Eén adres is
     *     voldoende: elk gevonden publiek adres is al hierboven getoetst,
     *     en `pingUrl()` dwingt sowieso IPv4 af (`CURLOPT_IPRESOLVE_V4`,
     *     ouder dan deze sprint) — een gepind IPv6-adres zou daar toch
     *     genegeerd worden. Blijft `null` bij een IP-literal host (geen
     *     DNS, dus geen pin nodig — ongewijzigd t.o.v. vóór deze sprint)
     *     of wanneer de host uitsluitend IPv6-adressen heeft.
     */
    private function isAllowedHost(string $host, ?string &$pinnedIp = null): bool
    {
        $pinnedIp = null;

        $logger      = Factory::getApplication()->getLogger();
        $logCategory = 'com_simplehub.externalurl';

        $logger->debug(
            'HOST START: Controle voor "' . $host . '".',
            ['category' => $logCategory]
        );

        $host = trim($host, '[]');

        if ($host === '') {
            $logger->debug(
                'HOST FAIL: Host is leeg.',
                ['category' => $logCategory]
            );

            return false;
        }

        $normalizedHost = strtolower($host);

        $logger->debug(
            'HOST: Genormaliseerde host: "' . $normalizedHost . '".',
            ['category' => $logCategory]
        );

        if (
            $normalizedHost === 'localhost'
            || str_ends_with($normalizedHost, '.localhost')
            || $normalizedHost === 'localhost.localdomain'
        ) {
            $logger->debug(
                'HOST FAIL: Host is localhost.',
                ['category' => $logCategory]
            );

            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $logger->debug(
                'HOST: Host is rechtstreeks een IP-adres.',
                ['category' => $logCategory]
            );

            $result = $this->isPublicIp($host);

            $logger->debug(
                'HOST END: IP-controle retourneert '
                . ($result ? 'TRUE' : 'FALSE') . '.',
                ['category' => $logCategory]
            );

            return $result;
        }

        $logger->debug(
            'HOST: Hostname wordt via DNS opgelost.',
            ['category' => $logCategory]
        );

        $addresses = $this->resolveHostAddresses($host);

        if ($addresses === []) {
            $logger->debug(
                'HOST FAIL: Geen DNS-adressen gevonden.',
                ['category' => $logCategory]
            );

            return false;
        }

        $logger->debug(
            'HOST: DNS-adressen gevonden: ' . implode(', ', $addresses),
            ['category' => $logCategory]
        );

        foreach ($addresses as $address) {
            $logger->debug(
                'HOST: Controle IP-adres: ' . $address,
                ['category' => $logCategory]
            );

            if (!$this->isPublicIp($address)) {
                $logger->debug(
                    'HOST FAIL: IP-adres is niet publiek: ' . $address,
                    ['category' => $logCategory]
                );

                return false;
            }
        }

        $logger->debug(
            'HOST SUCCESS: Alle DNS-adressen zijn publiek.',
            ['category' => $logCategory]
        );

        foreach ($addresses as $address) {
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                $pinnedIp = $address;

                $logger->debug(
                    'HOST PIN: IPv4-adres gekozen om te pinnen: ' . $pinnedIp . '.',
                    ['category' => $logCategory]
                );

                break;
            }
        }

        if ($pinnedIp === null) {
            $logger->debug(
                'HOST PIN: geen publiek IPv4-adres gevonden (host heeft'
                . ' vermoedelijk uitsluitend IPv6-adressen); pingUrl() kan'
                . ' niet pinnen.',
                ['category' => $logCategory]
            );
        }

        return true;
    }

    /**
     * Resolveert een hostname naar IPv4- en IPv6-adressen.
     *
     * @return array<int,string>
     */
    private function resolveHostAddresses(string $host): array
    {
        $logger      = Factory::getApplication()->getLogger();
        $logCategory = 'com_simplehub.externalurl';

        $logger->debug(
            'DNS START: dns_get_record() voor "' . $host . '".',
            ['category' => $logCategory]
        );

        $addresses = [];

        $records = @dns_get_record(
            $host,
            DNS_A | DNS_AAAA
        );

        if (!is_array($records)) {
            $logger->debug(
                'DNS FAIL: dns_get_record() gaf geen array terug.',
                ['category' => $logCategory]
            );

            return [];
        }

        $logger->debug(
            'DNS: Aantal DNS-records: ' . count($records),
            ['category' => $logCategory]
        );

        foreach ($records as $record) {
            $logger->debug(
			'DNS RECORD: {record}',
				[
					'record'   => json_encode($record, JSON_PRETTY_PRINT),
					'category' => $logCategory
				]
            );

            if (
                isset($record['type'], $record['ip'])
                && $record['type'] === 'A'
            ) {
                $addresses[] = $record['ip'];

                $logger->debug(
                    'DNS A: IPv4 gevonden: ' . $record['ip'],
                    ['category' => $logCategory]
                );
            }

            if (
                isset($record['type'], $record['ipv6'])
                && $record['type'] === 'AAAA'
            ) {
                $addresses[] = $record['ipv6'];

                $logger->debug(
                    'DNS AAAA: IPv6 gevonden: ' . $record['ipv6'],
                    ['category' => $logCategory]
                );
            }
        }

        $addresses = array_values(
            array_unique($addresses)
        );

        $logger->debug(
            'DNS RESULT: '
            . ($addresses === [] ? '(geen adressen)' : implode(', ', $addresses)),
            ['category' => $logCategory]
        );

        $logger->debug(
            'DNS END: resolveHostAddresses() voltooid.',
            ['category' => $logCategory]
        );

        return $addresses;
    }

    /**
     * Controleert of een IP-adres publiek en dus toegestaan is.
     */
    private function isPublicIp(string $ip): bool
    {
        $logger      = Factory::getApplication()->getLogger();
        $logCategory = 'com_simplehub.externalurl';

        $logger->debug(
            'IP CHECK START: ' . $ip,
            ['category' => $logCategory]
        );

        $result = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;

        $logger->debug(
            'IP CHECK RESULT: '
            . $ip
            . ' => '
            . ($result ? 'PUBLIC' : 'NOT PUBLIC'),
            ['category' => $logCategory]
        );

        return $result;
    }
}