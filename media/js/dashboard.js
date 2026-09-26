/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('sh-groups');
    const errorMessage = document.getElementById('sh-order-error');

    if (!container) {
        console.error('SimpleHub: container #sh-groups niet gevonden.');
        return;
    }

    if (typeof Sortable === 'undefined') {
        console.error('SimpleHub: SortableJS is niet geladen.');
        return;
    }

    /*
     * Externe items met de keuze "nieuw venster" (external_target = 'popup').
     *
     * target="_blank" staat, als progressive-enhancement-fallback, altijd al
     * op de link zelf (zie admin/tmpl/dashboard/default.php) - deze handler
     * onderschept het klikken uitsluitend om er in plaats daarvan een los
     * venster van te maken. window.open() wordt bewust synchroon, binnen
     * dezelfde click-handler aangeroepen: alleen dan behandelen browsers dit
     * nog als een door de gebruiker geïnitieerde actie en blokkeren
     * popupblockers het venster niet alsnog.
     *
     * Belangrijk: 'noopener' wordt bewust NIET in de windowFeatures-string
     * meegegeven. Dat lijkt de voor de hand liggende manier om
     * window.opener te ontzeggen (net als rel="noopener" op een <a>), maar
     * bij window.open() zorgt een 'noopener'/'noreferrer'-feature ervoor dat
     * de methode zelf altijd null teruggeeft - ook bij een geslaagde open -
     * waardoor niet meer te onderscheiden is of de popup daadwerkelijk is
     * geopend. In plaats daarvan wordt window.opener na een geslaagde open
     * handmatig op null gezet; functioneel gelijk, maar dan mét een bruikbaar
     * vensterobject terug.
     */
    container.addEventListener('click', (event) => {
        const link = event.target.closest('[data-sh-target="popup"]');

        if (!link) {
            return;
        }

        const popup = window.open(
            link.href,
            '_blank',
            'width=1024,height=768'
        );

        if (!popup) {
            /*
             * Venster geblokkeerd (bv. door een popupblocker): geen
             * preventDefault(), de klik valt terug op de bestaande
             * target="_blank" op de link (opent als tabblad).
             */
            return;
        }

        popup.opener = null;

        event.preventDefault();
        popup.focus();
    });

    const showError = (message) => {
        if (!errorMessage) {
            console.error(
                'SimpleHub: foutmelding-container niet gevonden.'
            );
            return;
        }

        errorMessage.textContent = message;
        errorMessage.classList.remove('d-none');
    };

    const clearError = () => {
        if (!errorMessage) {
            return;
        }

        errorMessage.textContent = '';
        errorMessage.classList.add('d-none');
    };

    /*
     * Sorteren van Hubgroepen.
     */
    new Sortable(container, {
        animation: 150,
        handle: '.sh-drag-handle',

        onEnd() {
            clearError();

            const order = Array.from(
                container.querySelectorAll('.sh-group')
            ).map((group) => parseInt(group.dataset.id, 10));

            const formData = new FormData();

            formData.append(
                'task',
                'groups.saveOrderAjax'
            );

            formData.append(
                Joomla.getOptions('csrf.token'),
                '1'
            );

            order.forEach((id, index) => {
                formData.append(
                    `order[${index}]`,
                    id
                );
            });

            fetch(
                'index.php?option=com_simplehub',
                {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                }
            )
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(
                            Joomla.Text.sprintf(
                                'COM_SIMPLEHUB_ERROR_AJAX_HTTP_STATUS',
                                response.status
                            )
                        );
                    }

                    return response.json();
                })
                .then((result) => {
                    if (result.success) {
                        return;
                    }

                    const message =
                        result.message ||
                        Joomla.Text._('COM_SIMPLEHUB_ERROR_GROUP_ORDER_SAVE_FAILED');

                    showError(message);

                    console.error(
                        'SimpleHub: fout bij opslaan volgorde:',
                        message
                    );
                })
                .catch((error) => {
                    showError(
                        error.message ||
                        Joomla.Text._('COM_SIMPLEHUB_ERROR_GROUP_ORDER_SAVE_FAILED')
                    );

                    console.error(
                        'SimpleHub: fout bij opslaan volgorde:',
                        error
                    );
                });
        }
    });

    /*
     * Sorteren van Items binnen iedere Hubgroep.
     */
    const itemContainers = container.querySelectorAll('.sh-items');

    itemContainers.forEach((itemContainer) => {
        new Sortable(itemContainer, {
            animation: 150,
            handle: '.sh-item-drag-handle',

            onEnd() {
                clearError();

                const groupId = parseInt(
                    itemContainer.dataset.groupId,
                    10
                );

                const order = Array.from(
                    itemContainer.querySelectorAll('.sh-item')
                ).map((item) => parseInt(item.dataset.id, 10));

                if (!groupId) {
                    console.error(
                        'SimpleHub: geen geldige group_id gevonden.'
                    );

                    return;
                }

                if (order.length === 0) {
                    return;
                }

                const formData = new FormData();

                formData.append(
                    'task',
                    'items.saveOrderAjax'
                );

                formData.append(
                    Joomla.getOptions('csrf.token'),
                    '1'
                );

                formData.append(
                    'group_id',
                    groupId
                );

                order.forEach((id, index) => {
                    formData.append(
                        `order[${index}]`,
                        id
                    );
                });

                fetch(
                    'index.php?option=com_simplehub',
                    {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    }
                )
                    .then((response) => {
                        if (!response.ok) {
                            throw new Error(
                                Joomla.Text.sprintf(
                                    'COM_SIMPLEHUB_ERROR_AJAX_HTTP_STATUS',
                                    response.status
                                )
                            );
                        }

                        return response.json();
                    })
                    .then((result) => {
                        if (result.success) {
                            return;
                        }

                        const message =
                            result.message ||
                            Joomla.Text._('COM_SIMPLEHUB_ERROR_ITEM_ORDER_SAVE_FAILED');

                        showError(message);

                        console.error(
                            'SimpleHub: fout bij opslaan Item-volgorde:',
                            message
                        );
                    })
                    .catch((error) => {
                        showError(
                            error.message ||
                            Joomla.Text._('COM_SIMPLEHUB_ERROR_ITEM_ORDER_SAVE_FAILED')
                        );

                        console.error(
                            'SimpleHub: fout bij opslaan Item-volgorde:',
                            error
                        );
                    });
            }
        });
    });
});
