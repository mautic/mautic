import Logger from './logger';
import loadComponents from './components';
import loadCommands from './commands';
import loadButtons from './buttons';
import loadEvents from './events';
import loadBlocks from './blocks';

export default (editor, opts = {}) => {
  const am = editor.AssetManager;

  const config = {
    showLayersManager: 0,
    showImportButton: 0,
    logFilter: 'log:debug',
    ...opts,
  };

  const logger = new Logger(editor);
  logger.addListener(config.logFilter, editor);

  // Extend the original `image` and add a confirm dialog before removing it
  am.addType('image', {
    // As you adding on top of an already defined type you can avoid indicating
    // `am.getType('image').view.extend({...` the editor will do it by default
    // but you can eventually extend some other type
    view: {
      // If you want to see more methods to extend check out
      // https://github.com/artf/grapesjs/blob/dev/src/asset_manager/view/AssetImageView.js
      onRemove(e) {
        e.stopImmediatePropagation();
        const { model } = this;

        // eslint-disable-next-line no-alert, no-restricted-globals
        if (confirm(Mautic.translate('grapesjsbuilder.deleteAssetConfirmText'))) {
          model.collection.remove(model);
        }
      },
    },
  });

  // Load other parts
  loadCommands(editor, config);
  loadComponents(editor, config);
  loadEvents(editor, config);
  loadButtons(editor, config);
  loadBlocks(editor, config);
};
