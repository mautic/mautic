import MjmlService from "grapesjs-preset-mautic/dist/mjml/mjml.service";

export default class StorageService {

    editor;

    key;

    constructor(editor, key) {
        this.editor = editor;
        this.key = key;
        this.init();
    }

    init() {
        const storedContent = this.getStorage();

        if (storedContent) {
            const linkId = this.key + 'restore';
            const formattedDateTime = this.formatDateTime(storedContent.date);
            const message = Mautic.translate('mautic.core.builder.storage.restore', {
                date: formattedDateTime,
                linkId: linkId
            });
            const flashMessage = Mautic.addInfoFlashMessage(message);
            flashMessage.querySelector('button.close').addEventListener('click', function(event) {
                this.handleUpdate();
            }.bind(this));
            flashMessage.querySelector('#'+linkId).addEventListener('click', function(event) {
                this.load();
                flashMessage.remove();
                event.preventDefault();
            }.bind(this));
            Mautic.setFlashes(flashMessage, false);
        }

        this.editor.on("update", this.handleUpdate.bind(this));
    }

    handleUpdate() {
        const parsedContent = MjmlService.getEditorMjmlContent(this.editor);
        const dateTime = new Date().toISOString();
        const contentWithDateTime = {
            content: parsedContent,
            date: dateTime
        };
        this.saveStorage(contentWithDateTime);
        console.log("Content stored in local storage with datetime:", contentWithDateTime);
    }

    load() {
        const storedContent = this.getStorage()
        if (storedContent) {
            this.editor.setComponents(storedContent.content);
        }
    }

    getStorage() {
        const contentData = localStorage.getItem(this.key);
        return contentData === null ? null : JSON.parse(contentData);
    }

    saveStorage(contentData) {
        localStorage.setItem(this.key, JSON.stringify(contentData));
    }

    formatDateTime(dateTime) {
        const date = new Date(dateTime);
        return date.toISOString().slice(0, 19).replace('T', ' ');
    }

}