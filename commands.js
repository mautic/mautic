import DynamicContentCommands from './dynymicContent/dynamicContent.commands';

export default (editor) => {
  if (!editor || !editor.Commands) {
    throw new Error('no editor commands found');
  }

  // Launch Dynamic Content popup: new
  editor.Commands.add('preset-mautic:dynamic-content', {
    run: DynamicContentCommands.launchDynamicContent,
    stop: DynamicContentCommands.grapesConvertDynamicContentTokenToSlot,
  });

  // Launch Dynamic Content popup: edit
  editor.Commands.add('preset-mautic:code-edit', {
    run: DynamicContentCommands.launchCodeEdit,
    stop: DynamicContentCommands.grapesConvertDynamicContentTokenToSlot,
  });
};
