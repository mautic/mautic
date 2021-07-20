import PreferenceCenterCommands from './preferenceCenter.commands';
import PreferenceCenterService from './preferenceCenter.service';

export default class PreferenceCenterEvents {
  editor;

  dcService;

  constructor(editor) {
    this.editor = editor;
    this.dcService = new PreferenceCenterService(this.editor);
    this.dccmd = new PreferenceCenterCommands(this.editor);
  }

  onComponentRemove() {
    this.editor.on('component:remove', (component) => {
      // Delete preference-center on Mautic side
      if (component.get('type') === 'preference-center') {
        this.editor.runCommand('preset-mautic:preference-center-delete-store-item', { component });
      }
    });
  }

  
}
