/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * SimpleHub Item form behaviour.
 */

document.addEventListener('DOMContentLoaded', () => {
    const titleField = document.getElementById('jform_title');
    const typeField = document.getElementById('jform_type');

    if (!titleField || !typeField) {
        return;
    }

    const targetFields = {
        component: document.getElementById('jform_target_component'),
        plugin: document.getElementById('jform_target_plugin'),
        module: document.getElementById('jform_target_module'),
        article: document.getElementById('jform_target_article'),
        external: document.getElementById('jform_target_external'),
    };

    const getSelectedText = (field) => {
        if (!field) {
            return '';
        }

        if (field.tagName === 'SELECT') {
            const option = field.options[field.selectedIndex];

            if (!option || !option.value) {
                return '';
            }

            return option.textContent.trim();
        }

        return field.value.trim();
    };

    const updateTitle = (type) => {
        if (titleField.value.trim() !== '') {
            return true;
        }

        const targetField = targetFields[type];

        if (!targetField) {
            return false;
        }

        const itemName = getSelectedText(targetField);

        if (itemName === '') {
            return false;
        }

        if (type === 'external') {
            titleField.value = itemName;
            return true;
        }

        titleField.value = `${type}: ${itemName}`;

        return true;
    };

    /*
     * Component, plugin en module:
     * vul de titel bij het wijzigen van de keuze.
     */
    ['component', 'plugin', 'module'].forEach((type) => {
        const field = targetFields[type];

        if (!field) {
            return;
        }

        field.addEventListener('change', () => {
            updateTitle(type);
        });
    });

    /*
     * Externe URL:
     * vul de titel wanneer het URL-veld wordt verlaten en
     * controleer de bereikbaarheid van de URL via AJAX.
     *
     * Deze netwerkcontrole gebeurt bewust NIET meer bij het
     * opslaan van het item (dat zou het formulier bij een fout
     * leegmaken), maar hier, op het moment dat het veld wordt
     * verlaten. De reeds gemaakte keuzes in de rest van het
     * formulier blijven daardoor altijd gewoon staan.
     */
    const externalField = targetFields.external;

    if (externalField) {
        let feedback = document.getElementById(
            'jform_target_external-urlcheck'
        );

        if (!feedback) {
            feedback = document.createElement('div');
            feedback.id = 'jform_target_external-urlcheck';
            feedback.className = 'small mt-1';
            externalField.insertAdjacentElement(
                'afterend',
                feedback
            );
        }

        const setFeedback = (text, type) => {
            feedback.textContent = text;
            feedback.classList.remove(
                'text-danger',
                'text-success',
                'text-muted'
            );

            if (type) {
                feedback.classList.add(type);
            }

            externalField.classList.remove(
                'is-invalid',
                'is-valid'
            );

            if (type === 'text-danger') {
                externalField.classList.add('is-invalid');
            } else if (type === 'text-success') {
                externalField.classList.add('is-valid');
            }
        };

        let requestToken = 0;

        const checkExternalUrl = () => {
            const url = externalField.value.trim();

            requestToken += 1;
            const currentToken = requestToken;

            if (url === '') {
                setFeedback('', null);
                return;
            }

            /*
             * Sprint 35-audit (taaloptimalisatie, item #29): de
             * 'Controleren...'-fallback hier is bewust NIET verwijderd.
             * Joomla.Text._() geeft bij een ontbrekende sleutel de kale
             * sleutelnaam terug (geen lege string), dus de `||`-fallback
             * vangt in de praktijk alleen het scenario af waarin de sleutel
             * COM_SIMPLEHUB_EXTERNAL_URL_CHECKING zelf niet via
             * Text::script() aan de pagina is toegevoegd. Dat is een
             * bestaand, ongewijzigd stukje defensieve code van vóór deze
             * sprint en valt buiten de "hardgecodeerde, gebruikersgerichte
             * tekst"-scope van AC #1 (de taalsleutel zelf wordt al gebruikt).
             */
            setFeedback(
                Joomla.Text._(
                    'COM_SIMPLEHUB_EXTERNAL_URL_CHECKING'
                ) || 'Controleren...',
                'text-muted'
            );

            const formData = new FormData();

            formData.append('task', 'item.checkUrl');
            formData.append('url', url);
            formData.append(
                Joomla.getOptions('csrf.token'),
                '1'
            );

            fetch(
                'index.php?option=com_simplehub',
                {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }
            )
                .then((response) => response.json())
                .then((result) => {
                    if (currentToken !== requestToken) {
                        // Er is inmiddels een nieuwere controle gestart.
                        return;
                    }

                    if (result.success) {
                        setFeedback('', null);
                        return;
                    }

                    const message =
                        result.message ||
                        Joomla.Text._('COM_SIMPLEHUB_ERROR_EXTERNAL_URL_UNREACHABLE');

                    setFeedback(message, 'text-danger');
                })
                .catch(() => {
                    if (currentToken !== requestToken) {
                        return;
                    }

                    // Netwerkfout bij de controle zelf blokkeert het
                    // invullen van het formulier niet.
                    setFeedback('', null);
                });
        };

        externalField.addEventListener('blur', () => {
            updateTitle('external');
            checkExternalUrl();
        });
    }

    /*
     * Artikel:
     *
     * Het Joomla modal_article veld bestaat uit:
     *
     * - een zichtbaar readonly veld met de artikelnaam;
     * - een verborgen veld met het artikel-ID.
     *
     * Joomla vult het zichtbare veld vanuit de modal,
     * zonder dat daarbij betrouwbaar een change-event
     * op het zichtbare veld wordt afgevuurd.
     *
     * Daarom controleren we kortstondig of Joomla het
     * zichtbare artikelveld heeft gevuld.
     */
    const articleField = targetFields.article;

    if (articleField) {
        let articleCheckCount = 0;
        const maxArticleChecks = 100;

        const checkArticleTitle = () => {
            if (
                titleField.value.trim() !== ''
                || typeField.value !== 'article'
                || articleCheckCount >= maxArticleChecks
            ) {
                return;
            }

            articleCheckCount++;

            if (updateTitle('article')) {
                return;
            }

            window.setTimeout(checkArticleTitle, 100);
        };

        articleField.addEventListener('focus', () => {
            articleCheckCount = 0;
            checkArticleTitle();
        });

        articleField.addEventListener('click', () => {
            articleCheckCount = 0;
            checkArticleTitle();
        });
    }

    /*
     * Icoon:
     *
     * Werkt het automatisch bepaalde icoon (LinkResolver) bij zodra de
     * gebruiker tijdens het invullen een ander type/doel kiest, zonder
     * pagina-herlaad. Volgt hetzelfde Ajax-patroon als de externe-URL-
     * controle hierboven (ItemController::icon()).
     */
    const iconImage = document.getElementById('sh-item-icon-image');
    const iconClass = document.getElementById('sh-item-icon-class');

    if (iconImage && iconClass) {
        let iconRequestToken = 0;

        const applyIcon = (iconType, icon) => {
            if (iconType === 'image') {
                iconImage.src = icon;
                iconImage.classList.remove('d-none');
                iconClass.classList.add('d-none');
                return;
            }

            /*
             * Sprint 28: een override is al een volledige, natieve
             * FontAwesome-classstring (bv. "fa-solid fa-address-book")
             * en wordt rechtstreeks gebruikt - GEEN icon--prefix, want
             * dat zou de classstring corrumperen. Het automatisch
             * bepaalde icoon blijft de bestaande, korte aliasnaam die
             * via Joomla's icon-<naam>-classmapping wordt gerenderd.
             */
            const cssClass = iconType === 'override' ? icon : `icon-${icon}`;

            iconClass.className = `${cssClass} sh-icon me-2`;
            iconClass.classList.remove('d-none');
            iconImage.classList.add('d-none');
        };

        const updateIcon = (type) => {
            const targetField = targetFields[type];
            const target = targetField ? targetField.value.trim() : '';

            iconRequestToken += 1;
            const currentToken = iconRequestToken;

            const formData = new FormData();

            formData.append('task', 'item.icon');
            formData.append('type', type);
            formData.append('target', target);
            formData.append(
                Joomla.getOptions('csrf.token'),
                '1'
            );

            fetch(
                'index.php?option=com_simplehub',
                {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }
            )
                .then((response) => response.json())
                .then((result) => {
                    if (currentToken !== iconRequestToken) {
                        // Er is inmiddels een nieuwere aanvraag gestart.
                        return;
                    }

                    if (result.data && result.data.icon_type && result.data.icon) {
                        applyIcon(result.data.icon_type, result.data.icon);
                    }
                })
                .catch(() => {
                    // Netwerkfout bij het bijwerken van het icoon
                    // blokkeert het invullen van het formulier niet.
                });
        };

        /*
         * Type gewijzigd: meteen de fallback voor het nieuwe type tonen,
         * ook al is er nog geen doel gekozen.
         */
        typeField.addEventListener('change', () => {
            updateIcon(typeField.value);
        });

        /*
         * Component, plugin en module: bijwerken bij het wijzigen van
         * de keuze (zelfde trigger als updateTitle hierboven).
         */
        ['component', 'plugin', 'module'].forEach((type) => {
            const field = targetFields[type];

            if (!field) {
                return;
            }

            field.addEventListener('change', () => {
                updateIcon(type);
            });
        });

        /*
         * Externe URL: bijwerken op hetzelfde blur-moment als de
         * bereikbaarheidscontrole. Het icoon is voor dit type altijd
         * vast (globe), maar loopt bewust ook via de server, zodat
         * LinkResolver de enige plek blijft die deze waarde kent.
         */
        if (externalField) {
            externalField.addEventListener('blur', () => {
                updateIcon('external');
            });
        }

        /*
         * Artikel: het verborgen doelveld wordt door Joomla vanuit de
         * modal gevuld zonder betrouwbaar change-event (zie de
         * bestaande toelichting bij checkArticleTitle hierboven).
         * Icoon-update is, anders dan de titel, niet afhankelijk van
         * een lege titel en wordt daarom los gepolld op waardewijziging.
         */
        if (articleField) {
            let lastArticleValue = articleField.value.trim();
            let articleIconCheckCount = 0;
            const maxArticleIconChecks = 100;

            const checkArticleIcon = () => {
                const currentValue = articleField.value.trim();

                if (currentValue !== lastArticleValue) {
                    lastArticleValue = currentValue;
                    updateIcon('article');
                    return;
                }

                if (articleIconCheckCount >= maxArticleIconChecks) {
                    return;
                }

                articleIconCheckCount++;
                window.setTimeout(checkArticleIcon, 100);
            };

            articleField.addEventListener('focus', () => {
                articleIconCheckCount = 0;
                checkArticleIcon();
            });

            articleField.addEventListener('click', () => {
                articleIconCheckCount = 0;
                checkArticleIcon();
            });
        }

        /*
         * Iconpicker (Sprint 28): kiezen, zoeken en wissen.
         *
         * De popup-inhoud staat statisch (server-side gerenderd door
         * IconpickerField) in een <template>, pas door JoomlaDialog bij
         * het openen in de DOM geplaatst. Zoeken/kiezen werken daarom
         * via event delegation op document, zodat het niet uitmaakt
         * wanneer de inhoud precies verschijnt.
         */
        const iconHiddenInput = document.querySelector('[data-sh-iconpicker-value]');
        const iconClearButton = document.querySelector('[data-sh-iconpicker-clear]');

        if (iconHiddenInput) {
            document.addEventListener('input', (event) => {
                if (!event.target.matches('[data-sh-iconpicker-search]')) {
                    return;
                }

                const term = event.target.value.trim().toLowerCase();
                const dialog = event.target.closest('[data-sh-iconpicker-dialog]');

                if (!dialog) {
                    return;
                }

                const grid = dialog.querySelector('[data-sh-iconpicker-grid]');
                const empty = dialog.querySelector('[data-sh-iconpicker-empty]');
                let visibleCount = 0;

                grid.querySelectorAll('.sh-iconpicker-icon').forEach((button) => {
                    const match = button.dataset.shIconName.includes(term);
                    button.classList.toggle('d-none', !match);

                    if (match) {
                        visibleCount += 1;
                    }
                });

                if (empty) {
                    empty.classList.toggle('d-none', visibleCount > 0);
                }
            });

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.sh-iconpicker-icon');

                if (!button) {
                    return;
                }

                const value = `fa-${button.dataset.shIconStyle} fa-${button.dataset.shIconName}`;

                iconHiddenInput.value = value;
                applyIcon('override', value);

                if (iconClearButton) {
                    iconClearButton.disabled = false;
                }
            });

            if (iconClearButton) {
                iconClearButton.addEventListener('click', () => {
                    iconHiddenInput.value = '';
                    iconClearButton.disabled = true;

                    // Criterium 6: val live terug op het automatisch
                    // bepaalde icoon voor het huidige type/doel, zonder
                    // paginaherlaad.
                    updateIcon(typeField.value);
                });
            }
        }
    }
});
