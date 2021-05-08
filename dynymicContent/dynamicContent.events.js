import DynamicContentCommands from './dynamicContent.commands';
import DynamicContentService from './dynamicContent.service';

export default class DynamicContentEvents {
  editor;

  dcService;

  constructor(editor) {
    this.editor = editor;
    this.dcService = new DynamicContentService(this.editor);
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
  //   const modalContent = mQuery('#dynamic-content-popup');
  //   // On modal close -> move editor within Mautic
  //   if (modalContent) {
  //     const dynamicContentContainer = mQuery('#dynamicContentContainer');
  //     const content = mQuery(modalContent).contents().first();
  //     dynamicContentContainer.append(content.detach());
  //   }
  // }
}
