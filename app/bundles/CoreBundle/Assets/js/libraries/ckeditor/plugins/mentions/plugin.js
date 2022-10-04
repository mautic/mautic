/**
 * @license Copyright (c) 2003-2021, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

'use strict';

( function() {

    CKEDITOR._.mentions = {
        cache: {}
    };

    var MARKER = '@',
        MIN_CHARS = 2,
        cache = CKEDITOR._.mentions.cache;

    CKEDITOR.plugins.add( 'mentions', {
        requires: 'autocomplete,textmatch,ajax',
        instances: [],
        init: function( editor ) {
            var self = this;

            editor.on( 'instanceReady', function() {
                CKEDITOR.tools.array.forEach( editor.config.mentions || [], function( config ) {
                    self.instances.push( new Mentions( editor, config ) );
                } );
            } );
        },
        isSupportedEnvironment: function( editor ) {
            return editor.plugins.autocomplete.isSupportedEnvironment( editor );
        }
    } );

    /**
     * The [Mentions](https://ckeditor.com/cke4/addon/mentions) plugin allows you to type a marker character and get suggested values for the
     * text matches so that you do not have to write it on your own.
     *
     * The recommended way to add the mentions feature to an editor is by setting the {@link CKEDITOR.config#mentions config.mentions} option:
     *
     * ```javascript
     * // Passing mentions configuration when creating the editor.
     * CKEDITOR.replace( 'editor', {
     * 		mentions: [ { feed: ['Anna', 'Thomas', 'John'], minChars: 0 } ]
     * } );
     *
     * // Simple usage with the CKEDITOR.config.mentions property.
     * CKEDITOR.config.mentions = [ { feed: ['Anna', 'Thomas', 'John'], minChars: 0 } ];
     * ```
     *
     * @class CKEDITOR.plugins.mentions
     * @since 4.10.0
     * @constructor Creates a new instance of mentions and attaches it to the editor.
     * @param {CKEDITOR.editor} editor The editor to watch.
     * @param {CKEDITOR.plugins.mentions.configDefinition} config Configuration object keeping information about how to instantiate the mentions plugin.
     */
    function Mentions( editor, config ) {
        var feed = config.feed;

        /**
         * Indicates that a mentions instance is case-sensitive for simple items feed, i.e. an array feed.
         *
         * **Note:** This will take no effect on feeds using a callback or URLs, as in this case the results are expected to
         * be already filtered.
         *
         * @property {Boolean} [caseSensitive=false]
         * @readonly
         */
        this.caseSensitive = config.caseSensitive;

        /**
         * The character that should trigger autocompletion.
         *
         * @property {String} [marker='@']
         * @readonly
         */
        this.marker = config.hasOwnProperty( 'marker' ) ? config.marker : MARKER;

        /**
         * The number of characters that should follow the marker character in order to trigger the mentions feature.
         *
         * @property {Number} [minChars=2]
         * @readonly
         */
        this.minChars = config.minChars !== null && config.minChars !== undefined ? config.minChars : MIN_CHARS;

        /**
         * The pattern used to match queries.
         *
         * The default pattern matches words with the query including the {@link #marker config.marker} and {@link #minChars config.minChars} properties.
         *
         * ```javascript
         * // Match only words starting with "a".
         * var pattern = /^a+\w*$/;
         * ```
         *
         * @property {RegExp} pattern
         * @readonly
         */
        this.pattern = config.pattern || createPattern( this.marker, this.minChars );

        /**
         * Indicates if the URL feed responses will be cached.
         *
         * The cache is based on the URL request and is shared between all mentions instances (including different editors).
         *
         * @property {Boolean} [cache=true]
         * @readonly
         */
        this.cache = config.cache !== undefined ? config.cache : true;

        /**
         * @inheritdoc CKEDITOR.plugins.mentions.configDefinition#throttle
         * @property {Number} [throttle=200]
         * @readonly
         */
        this.throttle = config.throttle !== undefined ? config.throttle : 200;

        /**
         * {@link CKEDITOR.plugins.autocomplete Autocomplete} instance used by the mentions feature to implement autocompletion logic.
         *
         * @property {CKEDITOR.plugins.autocomplete}
         * @private
         */
        this._autocomplete = new CKEDITOR.plugins.autocomplete( editor, {
            textTestCallback: getTextTestCallback( this.marker, this.minChars, this.pattern ),
            dataCallback: getDataCallback( feed, this ),
            itemTemplate: config.itemTemplate,
            outputTemplate: config.outputTemplate,
            throttle: this.throttle,
            itemsLimit: config.itemsLimit
        } );
    }

    Mentions.prototype = {

        /**
         * Destroys the mentions instance.
         *
         * The view element and event listeners will be removed from the DOM.
         */
        destroy: function() {
            this._autocomplete.destroy();
        }
    };

    function createPattern( marker, minChars ) {
        // Match also diacritic characters (#2491).
        var pattern = '\\' + marker + '[_a-zA-Z0-9À-ž]';

        if ( minChars ) {
            pattern += '{' + minChars + ',}';
        } else {
            pattern += '*';
        }

        pattern += '$';

        return new RegExp( pattern );
    }

    function getTextTestCallback( marker, minChars, pattern ) {
        return function( range ) {
            if ( !range.collapsed ) {
                return null;
            }

            return CKEDITOR.plugins.textMatch.match( range, matchCallback );
        };

        function matchCallback( text, offset ) {
            var match = text.slice( 0, offset )
                .match( pattern );

            if ( !match ) {
                return null;
            }

            // Do not proceed if a query is a part of word.
            var prevChar = text[ match.index - 1];
            if ( prevChar !== undefined && !prevChar.match( /\s+/ ) ) {
                return null;
            }

            return {
                start: match.index,
                end: offset
            };
        }
    }

    function getDataCallback( feed, mentions ) {
        return function( matchInfo, callback ) {
            var query = matchInfo.query;

            // We are removing marker here to give clean query result for the endpoint callback.
            if ( mentions.marker ) {
                query = query.substring( mentions.marker.length );
            }

            if ( CKEDITOR.tools.array.isArray( feed ) ) {
                createArrayFeed();
            } else if ( typeof feed === 'string' ) {
                createUrlFeed();
            } else {
                feed( {
                    query: query,
                    marker: mentions.marker
                }, resolveCallbackData );
            }

            function createArrayFeed() {
                var data = indexArrayFeed( feed ).filter( function( item ) {
                    var itemName = item.name;

                    if ( !mentions.caseSensitive ) {
                        itemName = itemName.toLowerCase();
                        query = query.toLowerCase();
                    }

                    return itemName.indexOf( query ) === 0;
                } );

                resolveCallbackData( data );
            }

            function indexArrayFeed( feed ) {
                var index = 1;
                return CKEDITOR.tools.array.reduce( feed, function( current, name ) {
                    current.push( { name: name, id: index++ } );
                    return current;
                }, [] );
            }

            function createUrlFeed() {
                var encodedUrl = new CKEDITOR.template( feed )
                    .output( { encodedQuery: encodeURIComponent( query ) } );

                if ( mentions.cache && cache[ encodedUrl ] ) {
                    return resolveCallbackData( cache[ encodedUrl ] );
                }

                CKEDITOR.ajax.load( encodedUrl, function( data ) {
                    var items = JSON.parse( data );

                    // Cache URL responses for performance improvement (#1969).
                    if ( mentions.cache && items !== null ) {
                        cache[ encodedUrl ] = items;
                    }

                    resolveCallbackData( items );
                } );
            }

            function resolveCallbackData( data ) {
                if ( !data ) {
                    return;
                }

                // We don't want to change item data, so lets create new one.
                var newData = CKEDITOR.tools.array.map( data, function( item ) {
                    var name = mentions.marker + item.name;
                    return CKEDITOR.tools.object.merge( item, { name: name } );
                } );

                callback( newData );
            }
        };
    }

    CKEDITOR.plugins.mentions = Mentions;

    /**
     * A list of mentions configuration objects.
     *
     * For each configuration object a new {@link CKEDITOR.plugins.mentions mentions} plugin instance will be created and attached to the editor.
     *
     * ```javascript
     * config.mentions = [
     * 	{ feed: [ 'Anna', 'Thomas', 'Jack' ], minChars: 0 },
     * 	{ feed: backendApiFunction, marker: '#' },
     * 	{ feed: '/users?query={encodedQuery}', marker: '$' }
     * ];
     *
     * ```
     *
     * @cfg {CKEDITOR.plugins.mentions.configDefinition[]} [mentions]
     * @since 4.10.0
     * @member CKEDITOR.config
     */

    /**
     * Abstract class describing the definition of a {@link CKEDITOR.plugins.mentions mentions} plugin configuration.
     *
     * This virtual class illustrates the properties that the developers can use to define and create
     * a mentions configuration definition.
     * The mentions definition object represents an object as a set of properties defining a mentions
     * data feed and its optional parameters.
     *
     * Simple usage:
     *
     * ```javascript
     * var definition = { feed: ['Anna', 'Thomas', 'John'], minChars: 0 };
     * ```
     *
     * @class CKEDITOR.plugins.mentions.configDefinition
     * @abstract
     * @since 4.10.0
     */

    /**
     * The feed of items to be displayed in the mentions plugin.
     *
     * Essential option which should be configured to create a correct mentions configuration definition.
     * There are three different ways to create a data feed:
     *
     * * A simple array of text matches as a synchronous data feed.
     * * A backend URL string responding with a list of items in the JSON format. This method utilizes an asynchronous data feed.
     * * A function allowing to use an asynchronous callback to customize the data source.
     * Gives the freedom to use any data source depending on your implementation.
     *
     * # An array of text matches
     * The easiest way to configure the data feed is to provide an array of text matches.
     * The mentions plugin will use a synchronous data feed and create item IDs by itself.
     * The biggest advantage of this method is its simplicity, although it is limited to a synchronous data feed.
     * Please see two other methods if you require more complex techniques to fetch the text matches.
     *
     *```javascript
     * var definition = { feed: ['Anna', 'Thomas', 'John'], minChars: 0 };
     * ```
     *
     * By default query matching for an array feed is case insensitive.
     * You can change this behavior by setting the {@link #caseSensitive caseSensitive} property to `true`.
     *
     * # A backend URL string
     * You can provide a backend URL string which will be used to fetch text matches from a custom endpoint service.
     * Each time the user types matching text into an editor, your backend service will be queried for text matches.
     * An Ajax URL request should response with an array of matches in the JSON format. A URL response will appear in the mentions dropdown.
     *
     * A backend URL string features the special `encodedQuery` variable replaced with a mentions query.
     * The `encodedQuery` variable allows you to create a customized URL which can be both RESTful API compliant or any other
     * URL which suits your needs. E.g. for the query `@anna` and the given URL `/users?name={encodedQuery}` your endpoint
     * service will be queried with `/users?name=anna`.
     *
     * ```javascript
     * var definition = { feed: '/users?query={encodedQuery}' };
     * ```
     *
     * To avoid multiple HTTP requests to your endpoint service, each HTTP response is cached by default and shared globally.
     * See the {@link #cache cache} property for more details.
     *
     * # Function feed
     * This method is recommended for advanced users who would like to take full control of the data feed.
     * It allows you to provide the data feed as a function that accepts two parameters: `options` and `callback`.
     * The provided function will be called every time the user types matching text into an editor.
     *
     * The `options` object contains information about the current query and a {@link #marker marker}.
     *
     * ```javascript
     * { query: 'anna', marker: '@' }
     * ```
     *
     * The `callback` argument should be used to pass an array of text match items into the mentions instance.
     *
     * ```javascript
     * callback( [ { id: 1, name: 'anna' }, { id: 2, name: 'annabelle' } ] );
     * ```
     *
     * Depending on your use case, you can use this code as an example boilerplate to create your own function feed:
     *
     * ```javascript
     * var definition = {
     *		feed: function( opts, callback ) {
     *			var xhr = new XMLHttpRequest();
     *
     *			xhr.onreadystatechange = function() {
     *				if ( xhr.readyState == 4 ) {
     *					if ( xhr.status == 200 ) {
     *						callback( JSON.parse( this.responseText ) );
     *					} else {
     *						callback( [] );
     *					}
     *				}
     *			}
     *
     *			xhr.open( 'GET', '/users?name=' + opts.query );
     *			xhr.send();
     *		}
     * };
     * ```
     *
     * **Other details**
     *
     * When using the asynchronous method, i.e. a backend URL string or a function,
     * you should provide correct object structure containing a unique item ID and a name.
     *
     * ```javascript
     * // Example of expected results from the backend API.
     * // The `firstName` and `lastName` properties are optional.
     * [
     * 		{ id: 1, name: 'anna87', firstName: 'Anna', lastName: 'Doe' },
     * 		{ id: 2, name: 'tho-mass', firstName: 'Thomas', lastName: 'Doe' },
     * 		{ id: 3, name: 'ozzy', firstName: 'John', lastName: 'Doe' }
     * ]
     * ```
     *
     * @property {String/String[]/Function} feed
     */

    /**
     * @inheritdoc CKEDITOR.plugins.autocomplete.configDefinition#itemTemplate
     * @property {String} [itemTemplate]
     */

    /**
     * @inheritdoc CKEDITOR.plugins.autocomplete.configDefinition#outputTemplate
     * @property {String} [outputTemplate]
     */

    /**
     * @inheritdoc CKEDITOR.plugins.autocomplete.configDefinition#throttle
     * @property {Number} [throttle=200]
     */

    /**
     * @inheritdoc CKEDITOR.plugins.mentions#marker
     * @property {String} [marker='@']
     */

    /**
     * @inheritdoc CKEDITOR.plugins.mentions#minChars
     * @property {Number} [minChars=2]
     */

    /**
     * @inheritdoc CKEDITOR.plugins.mentions#caseSensitive
     * @property {Boolean} [caseSensitive=false]
     */

    /**
     * @inheritdoc CKEDITOR.plugins.mentions#cache
     * @property {Boolean} [cache=true]
     */

    /**
     * @inheritdoc CKEDITOR.plugins.mentions#pattern
     * @property {RegExp} pattern
     */

    /**
     * @inheritdoc CKEDITOR.plugins.autocomplete.configDefinition#itemsLimit
     * @property {Number} [itemsLimit]
     */
} )();