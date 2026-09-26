/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 *
 * SimpleHub-Opties — titelbalk-snelkoppeling aan/uit.
 */

document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('[data-simplehub-titlebar-toggle]');

    if (!toggle) {
        return;
    }

    const feedback = document.getElementById('titlebar_shortcut-feedback');

    const setFeedback = (text, type) => {
        if (!feedback) {
            return;
        }

        feedback.textContent = text;
        feedback.classList.remove('text-danger');

        if (type) {
            feedback.classList.add(type);
        }
    };

    toggle.addEventListener('change', () => {
        const published = toggle.checked;

        toggle.disabled = true;
        setFeedback('', null);

        const formData = new FormData();
        formData.append('task', 'titlebar.toggle');
        formData.append('published', published ? '1' : '0');
        formData.append(Joomla.getOptions('csrf.token'), '1');

        fetch('index.php?option=com_simplehub', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then((response) => response.json())
            .then((result) => {
                if (result.success) {
                    /*
                     * Zelfde gedrag als bij het aan/uit-icoon in
                     * Extensies -> Modules: het scherm wordt ververst
                     * zodat de wijziging ook zichtbaar bevestigd wordt
                     * (o.a. het icoontje in de titelbalk).
                     */
                    window.location.reload();
                    return;
                }

                toggle.disabled = false;
                toggle.checked = !published;
                setFeedback(
                    result.message || Joomla.Text._('COM_SIMPLEHUB_ERROR_TITLEBAR_UPDATE_FAILED'),
                    'text-danger'
                );
            })
            .catch(() => {
                toggle.disabled = false;
                toggle.checked = !published;
                setFeedback(Joomla.Text._('COM_SIMPLEHUB_ERROR_TITLEBAR_UPDATE_FAILED'), 'text-danger');
            });
    });
});
