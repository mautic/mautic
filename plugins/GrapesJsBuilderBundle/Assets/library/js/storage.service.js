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
        const stackItemId = this.getStackItemId();
        const storageItem = this.getStorageItemById(stackItemId);
        const editorContent = this.getEditorContent();
        if (storageItem && editorContent !== storageItem.content) {
            this.displayRestoreMessage(storageItem);
        }
        this.editor.on("update", () => this.handleUpdate());
        this.addFormSubmitListeners();
    }

    displayRestoreMessage(storedContent) {
        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'alert-growl-buttons';

        const restoreButton = document.createElement('button');
        restoreButton.innerHTML = '<i class="ri-arrow-go-back-line"></i> ' + Mautic.translate('mautic.core.builder.storage.restore.button')
        restoreButton.className = 'btn btn-primary btn-sm ml-md';

        const dismissButton = document.createElement('button');
        dismissButton.innerHTML = Mautic.translate('mautic.core.builder.storage.dismiss.button')
        dismissButton.className = 'btn btn-ghost btn-sm';
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
            this.removeStorageItemById(this.getStackItemId());
            event.preventDefault();
        });

        closeButton.addEventListener('click', () => {
            this.handleUpdate();
        });

        this.editor.on('hide', () => this.dismissRestoreMessage());
    }

    addFormSubmitListeners() {
        mQuery(this.getForm()).on('submit:success', (e, requestUrl, response) => {
            const lastRequestUrlPart = requestUrl.split('/').pop();
            const lastResponseUrlPart = response.route.split('/').pop();

            // Check if the form was submitted for a new entity and the response contains the entity id
            // The success response code alone does not guarantee that the form was saved,
            // so we need to validate the URL changes and the presence of the entity id
            if (lastRequestUrlPart === 'new' && !isNaN(lastResponseUrlPart)){
                // Remove the local storage item for the newly created entity after successful form submission
                this.removeStorageItemById(`gjs-${this.mode}-${Mautic.builderTheme}-new`);
            }
        });
    }

    handleUpdate() {
        // update the storage content only when the restore prompt is not available
        if (!this.restoreMessage) {
            const editorContent = this.getEditorContent();
            const dateTime = new Date().toISOString();
            const stackItemId = this.getStackItemId();
            const contentWithDateTime = { id: stackItemId, content: editorContent, date: dateTime };
            this.saveStorageItem(contentWithDateTime);
        }
    }

    load() {
        const stackItemId = this.getStackItemId();
        const storageItem = this.getStorageItemById(stackItemId);
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
        const entityId = this.getFormEntityId(this.mode === 'page' ? 'page' : 'emailform')
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

    removeStorageItemById(id) {
        const stack = JSON.parse(localStorage.getItem(this.storageKey)) || [];
        const index = stack.findIndex(item => item.id === id);
        if (index !== -1) {
            stack.splice(index, 1);
            localStorage.setItem(this.storageKey, JSON.stringify(stack));
        }
    }

    formatDateTime(dateTime) {
        return new Date(dateTime).toISOString().slice(0, 16).replace('T', ' ');
    }

    getFormEntityId(name) {
        const form = document.querySelector(`form[name="${name}"]`);
        const actionUrl = form.getAttribute('action');
        const urlParts = actionUrl.split('/');
        const lastPart = urlParts.pop();
        if (isNaN(lastPart)) {
            return 'new';
        } else {
            return lastPart;
        }
    }

    getForm() {
        return document.querySelector(this.mode === 'page' ? 'form[name="page"]' : 'form[name="emailform"]')
    }
}