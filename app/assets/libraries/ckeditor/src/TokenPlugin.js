import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import Model from '@ckeditor/ckeditor5-ui/src/model';
import Collection from '@ckeditor/ckeditor5-utils/src/collection';
import { addListToDropdown, createDropdown } from '@ckeditor/ckeditor5-ui/src/dropdown/utils';

export default class TokenPlugin extends Plugin {
    init() {
        const editor = this.editor;
        const tokens = typeof editor.config._config.dynamicToken != undefined ? editor.config._config.dynamicToken : [] ;
        const tokens_label = typeof editor.config._config.dynamicTokenLabel != undefined ? editor.config._config.dynamicTokenLabel : "Insert Token" ;
        editor.ui.componentFactory.add('TokenPlugin', (locale) => {
            const dropdownView = createDropdown(locale);
            dropdownView.buttonView.set({
                withText: true,
                label: tokens_label,
                tooltip: true,
            });

            const items = new Collection();
            tokens.map((item) => {
                const itemId = item.id;
                let tokenName = item.name;
                if (tokenName.startsWith('a:')) {
                    tokenName = tokenName.substring(2);
                }

                if (itemId.match(/dwc=/i)){
                    const tn = itemId.substr(5, itemId.length - 6);
                    tokenName = tokenName + ' (' + tn + ')';
                } else if (itemId.match(/contactfield=company/i) && !tokenName.match(/company/i)){
                    tokenName = 'Company ' + tokenName;
                }

                items.add({
                    type: 'button',
                    model: new Model({
                        withText: true,
                        label: tokenName,
                        id: itemId,
                    }),
                });
            });

            addListToDropdown(dropdownView, items);

            dropdownView.on('execute', (eventInfo) => {
                const {id, label} = eventInfo.source;
                editor.model.change(writer => {
                    let content = "<span class='atwho-inserted' data-fr-verified='true'>"+id+"</span>";
                    if (id.match(/assetlink=/i)) {
                        content = '<a title="Asset Link" href="' + id + '">' + label + '</a>';
                    } else if (id.match(/pagelink=/i)) {
                        content = '<a title="Page Link" href="' + id + '">' + label + '</a>';
                    }

                    const viewFragment = editor.data.processor.toView( content );
                    const modelFragment = editor.data.toModel( viewFragment );
                    editor.model.insertContent( modelFragment );
                });
            });

            return dropdownView;
        });
    }
};
