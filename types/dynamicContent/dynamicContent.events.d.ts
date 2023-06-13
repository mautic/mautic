export default class DynamicContentEvents {
    constructor(editor: any);
    editor: any;
    dcService: DynamicContentService;
    dccmd: DynamicContentCommands;
    onComponentRemove(): void;
}
import DynamicContentService from './dynamicContent.service';
import DynamicContentCommands from './dynamicContent.commands';
