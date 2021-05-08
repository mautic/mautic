import DynamicContentCommands from './dynamicContent.commands';
import DynamicContentService from './dynamicContent.service';

export default class DynamicContentEvents {
  editor;

  dcService;

  constructor(editor) {
    this.editor = editor;
    this.dcService = new DynamicContentService();
    this.dccmd = new DynamicContentCommands(this.editor);
  }

  onComponentRemove() {
    this.editor.on('component:remove', (component) => {
      // Delete dynamic-content on Mautic side
      if (component.get('type') === 'dynamic-content') {
        this.dcService.deleteDynamicContentItem(component);
      }
    });
  }

  // @todo remove? not used
  // modalClose() {
  //   const commands = this.editor.Commands;

  //   const cmdDynamicContent = 'preset-mautic:dynamic-content-open';
  //   // Launch preset-mautic:dynamic-content-open command stop
  //   if (commands.isActive(cmdDynamicContent)) {
  //     commands.stop(cmdDynamicContent, { editor: this.editor });
  //   }

  //   const modalContent = mQuery('#dynamic-content-popup');

  //   // On modal close -> move editor within Mautic
  //   if (modalContent) {
  //     const dynamicContentContainer = mQuery('#dynamicContentContainer');
  //     const content = mQuery(modalContent).contents().first();

  //     dynamicContentContainer.append(content.detach());
  //   }
  // }
}
