import DynamicContentBlocks from './dynamicContent/dynamicContent.blocks';
import ContentService from './content.service';
import ButtonBlock from './buttonBlock';
import BlocksMjml from './blocks/blocks.mjml';
import Locale from './locale/locale';

export default (editor, opts = {}) => {
  const bm = editor.BlockManager;
  const blocks = bm.getAll();

  const mode = ContentService.getMode(editor);

  if (mode === ContentService.modeEmailMjml) {
    const locale = new Locale(editor);
    locale.addMessages();

    const blockMjml = new BlocksMjml(editor);
    blockMjml.addBlocks();
  }

  // a add button block for landing page
  if (mode === ContentService.modePageHtml) {
    const buttonBlock = new ButtonBlock(editor);
    buttonBlock.addButtonBlock();
  } else {
    // Add Dynamic Content block only for email modes
    const dcb = new DynamicContentBlocks(editor, opts);
    dcb.addDynamciContentBlock();
  }

  // Add icon to mj-hero
  if (typeof bm.get('mj-hero') !== 'undefined') {
    bm.get('mj-hero').set({
      attributes: { class: 'gjs-fonts gjs-f-hero' },
    });
  }

  // Delete mj-wrapper
  if (typeof bm.get('mj-wrapper') !== 'undefined') {
    bm.remove('mj-wrapper');
  }

  // All block inside Blocks category
  blocks.forEach((block) => {
    block.set({
      category: Mautic.translate('grapesjsbuilder.categoryBlockLabel'),
    });
  });

  /*
   * Custom block inside Sections category
   */

  // MJML columns
  if (typeof bm.get('mj-1-column') !== 'undefined') {
    bm.get('mj-1-column').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  if (typeof bm.get('mj-2-columns') !== 'undefined') {
    bm.get('mj-2-columns').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  if (typeof bm.get('mj-3-columns') !== 'undefined') {
    bm.get('mj-3-columns').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  if (typeof bm.get('mj-37-columns') !== 'undefined') {
    bm.get('mj-37-columns').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  // Newsletter columns
  if (typeof bm.get('sect100') !== 'undefined') {
    bm.get('sect100').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  if (typeof bm.get('sect50') !== 'undefined') {
    bm.get('sect50').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  if (typeof bm.get('sect30') !== 'undefined') {
    bm.get('sect30').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  if (typeof bm.get('sect37') !== 'undefined') {
    bm.get('sect37').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  // Webpage columns
  if (typeof bm.get('column1') !== 'undefined') {
    bm.get('column1').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  if (typeof bm.get('column2') !== 'undefined') {
    bm.get('column2').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  if (typeof bm.get('column3') !== 'undefined') {
    bm.get('column3').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }

  if (typeof bm.get('column3-7') !== 'undefined') {
    bm.get('column3-7').set({
      category: Mautic.translate('grapesjsbuilder.categorySectionLabel'),
    });
  }
};
