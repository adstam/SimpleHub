/**
 * @package     Joomla.Administrator
 * @subpackage  com_simplehub
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('adminForm');

    if (!form) {
        return;
    }

    form.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        if (event.target instanceof HTMLTextAreaElement) {
            return;
        }

        event.preventDefault();

        if (
            typeof Joomla !== 'undefined' &&
            typeof Joomla.submitbutton === 'function'
        ) {
            Joomla.submitbutton('group.save');
            return;
        }

        const task = form.querySelector('input[name="task"]');

        if (task) {
            task.value = 'group.save';
        }

        form.submit();
    });
});