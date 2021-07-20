import DynamicContentBlocks from './dynamicContent/dynamicContent.blocks';
import PreferenceCenterBlocks from './preferenceCenter/preferenceCenter.blocks';

import ContentService from './content.service';

export default (editor, opts = {}) => {
  const bm = editor.BlockManager;
  const cfg = editor.getConfig();

  const blocks = bm.getAll();
  // Add Dynamic Content block only for newsletter
  if ('grapesjsmjml' in cfg.pluginsOpts) {
    // Dynamic Content MJML block
  } else if ('grapesjsnewsletter' in cfg.pluginsOpts) {
    const dcb = new DynamicContentBlocks(editor, opts);
    dcb.addDynamciContentBlock();
  }else if ('grapesjswebpage' in cfg.pluginsOpts) {
    const pcb = new PreferenceCenterBlocks(editor, opts);
    pcb.addPreferenceCenterBlock();
  }

  //add button block for landing page 
  const mode = ContentService.getMode(editor);    
  if (mode === ContentService.modePageHtml) {
    const pcb = new PreferenceCenterBlocks(editor, opts);
    pcb.addPreferenceCenterBlock();

    bm.add('button', {
      label: 'Button',
      category : Mautic.translate('grapesjsbuilder.categoryBlockLabel'),
      attributes: {
        class: 'gjs-fonts gjs-f-button'
      },
      content: 
        `<style>
            .button {
              display:inline-block;
              text-decoration:none;
              border-color:#4e5d9d;
              border-width:10px 20px;
              border-style:solid;
              -webkit-border-radius:3px;
              -moz-border-radius:3px;
              border-radius:3px;
              background-color:#4e5d9d;
              font-size:16px;
              color:#ffffff;
            }           
         </style>
         <a href="#" target="_blank" class="button">Button</a>`,
    });

   

    bm.add('success-msg', {
      label: 'Success Message',
      media: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 11.9c0-.6-.5-.9-1.3-.9H3.4c-.8 0-1.3.3-1.3.9V17c0 .5.5.9 1.3.9h17.4c.8 0 1.3-.4 1.3-.9V12zM21 17H3v-5h18v5z"/><rect width="14" height="5" x="2" y="5" rx=".5"/><path d="M4 13h1v3H4z"/></svg>',
      content: { type: 'success-msg',  editable: true, },
    });

    bm.add('save-pref', {
      label: 'Save Prefrence',
      category : 'Prefrence Center',
      attributes: {
        class: 'gjs-fonts gjs-f-button'
      },
      content: 
        `<a href="#" target="_blank" class="button">Button</a>`,
    });
    bm.add('frequency', {
      label: 'Frequency',
      category : 'Prefrence Center',
      attributes: {
        class: 'gjs-fonts gjs-f-button'
      },
      content: 
        `<a href="#" target="_blank" class="button">Button</a>`,
    });
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
     // category: 'Prefrence Center'
      category: Mautic.translate('grapesjsbuilder.categoryBlockLabel'),
    });
  });

  /*
   * Custom block inside Sections category
   */

  // MJML columns
  if (typeof bm.get('segment') !== 'undefined') {
    bm.get('segment').set({
      category: 'Preference Center'
    });
  }if (typeof bm.get('save-pref') !== 'undefined') {
    bm.get('save-pref').set({
      category: 'Preference Center'
    });
  }if (typeof bm.get('frequency') !== 'undefined') {
    bm.get('frequency').set({
      category: 'Preference Center'
    });
  }if (typeof bm.get('category-list') !== 'undefined') {
    bm.get('category-list').set({
      category: 'Preference Center'
    });
  }if (typeof bm.get('success-msg') !== 'undefined') {
    bm.get('success-msg').set({
      category: 'Preference Center'
    });
  }

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
