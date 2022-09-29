(function() {
    class Token {
        static TYPE = {
            ASSETLINK: 'assetlink',
            PAGELINK: 'pagelink',
            DWC: 'dwc',
            CONTACTFIELD: 'contactfield',
            COMPANYFIELD: 'companyfield',
            CUSTOM: 'custom'
        }

        constructor(name, tokenCode) {
            this.name = name;
            this.tokenCode = tokenCode;
        }

        static makeLink(title, href, text) {
            return `<a title="${title}" href="${href}">${text}</a>`;
        }
    }

    class TokenStorage {
        constructor() {
            this.storage = {};
            for (const [index,value] of Object.entries(Token.TYPE)) {
                this.storage[value] = [];
            }
        }

        process(tokens, editor, callback) {
            for (const key in tokens) {
                const value = tokens[key];
                let tempValue, tempKey;
                let typeOfToken = key.replace(/^{+|}+$/g, '').split(/=/);

                if(typeOfToken.length) {
                    switch (typeOfToken[0]) {
                        case Token.TYPE.ASSETLINK:
                            delete tokens[key];
                            tempValue = value.replace('a:', '');
                            tempKey = Token.makeLink('Asset Link', key, tempValue);
                            this.storage[Token.TYPE.ASSETLINK].push(new Token(tempValue, tempKey));
                            break;
                        case Token.TYPE.PAGELINK:
                            delete tokens[key];
                            tempValue = value.replace('a:', '');
                            tempKey = Token.makeLink('Page Link', key, tempValue);
                            this.storage[Token.TYPE.PAGELINK].push(new Token(tempValue, tempKey));
                            break;
                        case Token.TYPE.DWC:
                            const token = key.substr(5, key.length - 6);
                            this.storage[Token.TYPE.DWC].push(new Token(value + ' (' + token + ')', key));
                            break;
                        case Token.TYPE.CONTACTFIELD:
                            if (typeOfToken[1] && typeOfToken[1].match(/company/i))
                                this.storage[Token.TYPE.COMPANYFIELD].push(new Token(value, key));
                            else
                                this.storage[Token.TYPE.CONTACTFIELD].push(new Token(value, key));
                            break;
                        default:
                            this.storage[Token.TYPE.CUSTOM].push(new Token(value, key));
                    }
                }
            }

            callback(editor, this.storage);
            return this.storage;
        }

        fetch(editor, callback) {
            const method = location.href.match(/(email|dwc)/i)? 'email:getBuilderTokens' : 'page:getBuilderTokens';

            return mQuery.ajax({
                url: mauticAjaxUrl,
                data: 'action=' + method,
                async: false,
                success: (response) => {
                    if (typeof response.tokens === 'object') {
                        return this.process(response.tokens, editor, callback);
                    }
                },
                error: (request, textStatus, errorThrown) => {
                    Mautic.processAjaxError(request, textStatus, errorThrown);
                },
            });
        }

        static sort(tokens) {
            tokens.sort((val1,val2) => val1.name < val2.name);
        }
    }

    CKEDITOR.plugins.add( 'mautictoken', {
        requires: ['richcombo'],
        init: (editor) => {
            const tokens = new TokenStorage();
            tokens.fetch(editor,(editor, options) => {
                editor.ui.addRichCombo('Token', {
                    label: "Token",
                    title: "Token",
                    panel: {
                        css: [CKEDITOR.skin.getPath('editor')].concat(editor.config.contentsCss),
                        multiSelect: false,
                        editorattributes: {'aria-label': 'Insert Token'}
                    },
                    init: function () {
                        for (const [type, records] of Object.entries(options)) {
                            if (Object.keys(records).length) {
                                let groupName;
                                TokenStorage.sort(options[Token.TYPE.ASSETLINK]);

                                switch (type) {
                                    case Token.TYPE.ASSETLINK:
                                        groupName = 'Assets';
                                        break;
                                    case Token.TYPE.CONTACTFIELD:
                                        groupName = 'Contact fields';
                                        break;
                                    case Token.TYPE.COMPANYFIELD:
                                        groupName = 'Company fields';
                                        break;
                                    case Token.TYPE.DWC:
                                        groupName = 'DWC';
                                        break;
                                    case Token.TYPE.PAGELINK:
                                        groupName = 'Page links';
                                        break;
                                    default:
                                        groupName = 'Custom tokens';
                                }

                                this.startGroup(groupName);
                                records.forEach(item => {
                                    this.add(item.tokenCode, item.name, item.name);
                                });
                            }
                        }
                    },
                    onClick: (value) => {
                        editor.focus();
                        editor.fire('saveSnapshot');
                        editor.insertHtml(value)
                        editor.fire('saveSnapshot');
                    }
                })
            });
        }
    });
})();