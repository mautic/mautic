import MjmlService from "grapesjs-preset-mautic/dist/mjml/mjml.service";
import ContentService from 'grapesjs-preset-mautic/dist/content.service';

export default class StorageService {
    constructor(editor, mode) {
        this.editor = editor;
        this.mode = mode;
        this.maxStorageItems = 10;
        this.init();
    }

    init() {
        this.storageKey = 'gjs-storage';
        this.restoreMessage = null;
        const entityId = this.mode === 'page' ? this.getPageId() : this.getEmailId();
        this.stackItemId = `gjs-${this.mode}-${Mautic.builderTheme}-${entityId}`;
        const storageItem = this.getStorageItemById(this.stackItemId);
        const editorContent = this.getEditorContent();
        if (storageItem && editorContent !== storageItem.content) {
            this.displayRestoreMessage(storageItem);
        }
        this.editor.on("update", () => this.handleUpdate());
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
            const contentWithDateTime = { id: this.stackItemId, content: editorContent, date: dateTime };
            this.saveStorageItem(contentWithDateTime);
        }
    }

    load() {
        const storageItem = this.getStorageItemById(this.stackItemId);
        if (storageItem) {
            this.editor.setComponents(storageItem.content);
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

    getStackItemId() {
        const entityId = this.mode === 'page' ? this.getPageId() : this.getEmailId();
        return `gjs-${this.mode}-${Mautic.builderTheme}-${entityId}`;
    }

    saveStorageItem(item) {
        const stack = JSON.parse(localStorage.getItem(this.storageKey)) || [];
        const index = stack.findIndex(existingItem => existingItem.id === item.id);
        if (index !== -1) {
            // If the item already exists, update it
            stack[index] = item;
        } else {
            // If the item doesn't exist, push it to the stack
            if (stack.length >= this.maxStorageItems) {
                // Ensure that the stack does not exceed the maximum allowed number of items
                // to prevent web storage from exceeding its 10MiB per domain limit
                stack.pop(); // Remove the oldest item
            }
            stack.push(item);
        }

        stack.sort((a, b) => new Date(b.date) - new Date(a.date));
        localStorage.setItem(this.storageKey, JSON.stringify(stack));
    }

    getStorageItemById(id) {
        const stack = JSON.parse(localStorage.getItem(this.storageKey)) || [];
        return stack.find(item => item.id === id);
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