export default (editor, opts = {}) => {
    const bm = editor.BlockManager;

    let blocks = bm.getAll();

    // All block inside Blocks category
    blocks.forEach(block => {
        block.set({
            category: 'Blocks'
        });
    });

    /*
     Custom block inside Sections category
     */

    // MJML columns
    if (typeof bm.get('mj-1-column') !== 'undefined') {
        bm.get('mj-1-column').set({
            category: 'Sections'
        });
    }

    if (typeof bm.get('mj-2-columns') !== 'undefined') {
        bm.get('mj-2-columns').set({
            category: 'Sections'
        });
    }

    if (typeof bm.get('mj-3-columns') !== 'undefined') {
        bm.get('mj-3-columns').set({
            category: 'Sections'
        });
    }

    // Newsletter columns
    if (typeof bm.get('sect100') !== 'undefined') {
        bm.get('sect100').set({
            category: 'Sections'
        });
    }

    if (typeof bm.get('sect50') !== 'undefined') {
        bm.get('sect50').set({
            category: 'Sections'
        });
    }

    if (typeof bm.get('sect30') !== 'undefined') {
        bm.get('sect30').set({
            category: 'Sections'
        });
    }

    if (typeof bm.get('sect37') !== 'undefined') {
        bm.get('sect37').set({
            category: 'Sections'
        });
    }

    // Webpage columns
    if (typeof bm.get('column1') !== 'undefined') {
        bm.get('column1').set({
            category: 'Sections'
        });
    }

    if (typeof bm.get('column2') !== 'undefined') {
        bm.get('column2').set({
            category: 'Sections'
        });
    }

    if (typeof bm.get('column3') !== 'undefined') {
        bm.get('column3').set({
            category: 'Sections'
        });
    }

    if (typeof bm.get('column3-7') !== 'undefined') {
        bm.get('column3-7').set({
            category: 'Sections'
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
};
