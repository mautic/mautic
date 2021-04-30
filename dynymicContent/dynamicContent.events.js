import DynamicContentService from './dynamicContent.service';

export default class DynamicContentEvents {
  editor;

  dcService;

  constructor(editor) {
    this.editor = editor;
    this.dcService = new DynamicContentService();
  }

  onComponentAdd() {
    this.editor.on('component:add', (component) => {
      const type = component.get('type');

      // Create dynamic-content on Mautic side
      if (type === 'dynamic-content') {
        this.dcService.manageDynamicContentTokenToSlot(component);
      }
    });
  }

  onComponentRemove() {
    this.editor.on('component:remove', (component) => {
      const type = component.get('type');

      // Delete dynamic-content on Mautic side
      if (type === 'dynamic-content') {
        this.deleteDynamicContentItem(component);
      }
    });
  }

  // @todo remove? not used
  modalClose() {
    const commands = this.editor.Commands;

    const cmdDynamicContent = 'preset-mautic:dynamic-content';
    // Launch preset-mautic:dynamic-content command stop
    if (commands.isActive(cmdDynamicContent)) {
      commands.stop(cmdDynamicContent, { editor: this.editor });
    }

    const modalContent = mQuery('#dynamic-content-popup');

    // On modal close -> move editor within Mautic
    if (modalContent) {
      const dynamicContentContainer = mQuery('#dynamicContentContainer');
      const content = mQuery(modalContent).contents().first();

      dynamicContentContainer.append(content.detach());
    }
  }
}
