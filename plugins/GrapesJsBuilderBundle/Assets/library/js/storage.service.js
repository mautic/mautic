import MjmlService from "grapesjs-preset-mautic/dist/mjml/mjml.service";
import ContentService from 'grapesjs-preset-mautic/dist/content.service';

export default class StorageService {
    constructor(editor, mode) {
        this.editor = editor;
        this.mode = mode;
        this.restoreMessage = null;
        this.init();
    }

    init() {
        this.initStorageKey();
        const storage = this.getStorage();
        const editorContent = this.getEditorContent();
        if (storage && editorContent !== storage.content) {
            this.displayRestoreMessage(storage);
        }
        this.editor.on("update", () => this.handleUpdate());
    }

    initStorageKey() {
        const entityId = this.mode === 'page' ? this.getPageId() : this.getEmailId();
        this.key = `gjs-${this.mode}-${Mautic.builderTheme}-${entityId}`
    }

    displayRestoreMessage(storedContent) {
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'alert-growl-buttons';

        const restoreButton = document.createElement('button');
        restoreButton.innerHTML = '<i class="fa fa-undo"></i> ' + Mautic.translate('mautic.core.builder.storage.restore.button')
        restoreButton.className = 'btn btn-primary';

        const dismissButton = document.createElement('button');
        dismissButton.innerHTML = Mautic.translate('mautic.core.builder.storage.dismiss.button')
        dismissButton.className = 'btn btn-default';
        buttonContainer.append(restoreButton, dismissButton);

        const formattedDateTime = this.formatDateTime(storedContent.date);
        const message = Mautic.translate('mautic.core.builder.storage.restore.message', {
            date: formattedDateTime
        });
        const flashMessage = Mautic.addInfoFlashMessage(message);
        flashMessage.append(buttonContainer);

        const closeButton = flashMessage.querySelector('button.close')

        this.addMessageEventListeners(restoreButton, dismissButton, closeButton);
        Mautic.setFlashes(flashMessage, false);
        this.restoreMessage = flashMessage;
    }

    dismissRestoreMessage() {
        if (this.restoreMessage instanceof Element) {
            this.restoreMessage.remove();
        }
        this.restoreMessage = null;
    }

    addMessageEventListeners(restoreButton, dismissButtom, closeButton) {
        restoreButton.addEventListener('click', (event) => {
            this.load();
            this.dismissRestoreMessage();
            event.preventDefault();
        });

        dismissButtom.addEventListener('click', (event) => {
            this.handleUpdate();
            this.dismissRestoreMessage();
            event.preventDefault();
        });

        closeButton.addEventListener('click', () => {
            this.handleUpdate();
        });

        this.editor.on('hide', () => this.dismissRestoreMessage());
    }

    handleUpdate() {
        // update the storage content only when the restore prompt is not available
        if (!this.restoreMessage) {
            const editorContent = this.getEditorContent();
            const dateTime = new Date().toISOString();
            const contentWithDateTime = { content: editorContent, date: dateTime };
            this.saveStorage(contentWithDateTime);
        }
    }

    load() {
        const storage = this.getStorage();
        if (storage) {
            this.editor.setComponents(storage.content);
        }
    }

    getEditorContent() {
        let content;
        if (ContentService.isMjmlMode(this.editor)) {
            content = MjmlService.getEditorMjmlContent(this.editor);
        } else {
            content = ContentService.getEditorHtmlContent(this.editor);
        }
        return content;
    }

    getStorage() {
        const contentData = localStorage.getItem(this.key);
        return contentData ? JSON.parse(contentData) : null;
    }

    saveStorage(storage) {
        localStorage.setItem(this.key, JSON.stringify(storage));
    }

    formatDateTime(dateTime) {
        return new Date(dateTime).toISOString().slice(0, 16).replace('T', ' ');
    }

    getEmailId() {
        return parseInt(document.getElementById('emailform_sessionId').value) || 'new';
    }

    getPageId() {
        return parseInt(document.getElementById('page_sessionId').value) || 'new';
    }
}