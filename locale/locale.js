export default class Locale {
  editor;

  constructor(editor) {
    this.editor = editor;
  }

  addMessages() {
    this.editor.I18n.addMessages({
      en: {
        'grapesjs-mjml': {
          components: {
            names: {
              twoColumnThirdSevens: '2 Columns 3/7',
              textSectionBlkLabel: 'Text Section',
              gridItemsBlkLabel: 'Grid Items',
              listItemsBlkLabel: 'List Items',
            },
          },
        },
      },
      de: {
        'grapesjs-mjml': {
          components: {
            names: {
              twoColumnThirdSevens: '2 Spalten 3/7',
              textSectionBlkLabel: 'Text Abschnitt',
              gridItemsBlkLabel: 'Rasterpunkte',
              listItemsBlkLabel: 'Artikel auflistens',
            },
          },
        },
      },
      fr: {
        'grapesjs-mjml': {
          components: {
            names: {
              twoColumnThirdSevens: '2 Colonnes 3/7',
              textSectionBlkLabel: 'Section de texte',
              gridItemsBlkLabel: 'Éléments de la grille',
              listItemsBlkLabel: 'Articles de la liste',
            },
          },
        },
      },
    });
  }
}
