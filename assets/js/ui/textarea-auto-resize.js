import domready from "mf-js/modules/dom/ready";

domready(() => {
    const htmlEditor = document.querySelector('.html-editor');

    if (htmlEditor) {
        const htmlEditorTextarea = htmlEditor.querySelector('textarea');

        htmlEditorTextarea.addEventListener('keyup', () => {
            const rows = htmlEditorTextarea.value.split("\n");

            htmlEditorTextarea.rows = rows.length;
        });

        htmlEditorTextarea.dispatchEvent(new Event('keyup'));
    }
});
