import DynamicContentCommands from './dynymicContent/dynamicContent.commands';

export default (editor) => {
  if (!editor || !editor.Commands) {
    throw new Error('no editor commands found');
  }

  // Launch Dynamic Content popup: new or edit
  editor.Commands.add('preset-mautic:dynamic-content', {
    run: DynamicContentCommands.launchDynamicContent,
    stop: DynamicContentCommands.grapesConvertDynamicContentTokenToSlot,
  });

  // Add function within builder to edit source code
  // editor.Commands.add('preset-mautic:code-edit', {
  //   run: DynamicContentCommands.launchCodeEdit,
  //   stop: DynamicContentCommands.grapesConvertDynamicContentTokenToSlot,
  // });
};
