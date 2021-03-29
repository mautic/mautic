/**
 * @license Copyright (c) 2003-2021, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

'use strict';

( function() {

	CKEDITOR.plugins.add( 'autocomplete', {
		requires: 'textwatcher',
		onLoad: function() {
			CKEDITOR.document.appendStyleSheet( this.path + 'skins/default.css' );
		},
		isSupportedEnvironment: function() {
			return !CKEDITOR.env.ie || CKEDITOR.env.version > 8;
		}
	} );

	/**
	 * The main class implementing a generic [Autocomplete](https://ckeditor.com/cke4/addon/autocomplete) feature in the editor.
	 * It acts as a controller that works with the {@link CKEDITOR.plugins.autocomplete.model model} and
	 * {@link CKEDITOR.plugins.autocomplete.view view} classes.
	 *
	 * It is possible to maintain multiple autocomplete instances for a single editor at a time.
	 * In order to create an autocomplete instance use its {@link #constructor constructor}.
	 *
	 * @class CKEDITOR.plugins.autocomplete
	 * @since 4.10.0
	 * @constructor Creates a new instance of autocomplete and attaches it to the editor.
	 *
	 * In order to initialize the autocomplete feature it is enough to instantiate this class with
	 * two required callbacks:
	 *
	 * * {@link CKEDITOR.plugins.autocomplete.configDefinition#textTestCallback config.textTestCallback} &ndash; A function being called by
	 *  the {@link CKEDITOR.plugins.textWatcher text watcher} plugin, as new text is being inserted.
	 *  Its purpose is to determine whether a given range should be matched or not.
	 *  See {@link CKEDITOR.plugins.textWatcher#constructor} for more details.
	 *  There is also {@link CKEDITOR.plugins.textMatch#match} which is a handy helper for that purpose.
	 * * {@link CKEDITOR.plugins.autocomplete.configDefinition#dataCallback config.dataCallback} &ndash; A function that should return
	 *  (through its callback) suggestion data for the current query string.
	 *
	 * # Creating an autocomplete instance
	 *
	 * Depending on your use case, put this code in the {@link CKEDITOR.pluginDefinition#init} callback of your
	 * plugin or, for example, in the {@link CKEDITOR.editor#instanceReady} event listener. Ensure that you loaded the
	 * {@link CKEDITOR.plugins.textMatch Text Match} plugin.
	 *
	 * ```javascript
	 *	var itemsArray = [ { id: 1, name: '@Andrew' }, { id: 2, name: '@Kate' } ];
	 *
	 *	// Called when the user types in the editor or moves the caret.
	 *	// The range represents the caret position.
	 *	function textTestCallback( range ) {
	 *		// You do not want to autocomplete a non-empty selection.
	 *		if ( !range.collapsed ) {
	 *			return null;
	 *		}
	 *
	 *		// Use the text match plugin which does the tricky job of doing
	 *		// a text search in the DOM. The matchCallback function should return
	 *		// a matching fragment of the text.
	 *		return CKEDITOR.plugins.textMatch.match( range, matchCallback );
	 *	}
	 *
	 *	// Returns the position of the matching text.
	 *	// It matches with a word starting from the '@' character
	 *  // up to the caret position.
	 *	function matchCallback( text, offset ) {
	 *			// Get the text before the caret.
	 *		var left = text.slice( 0, offset ),
	 *			// Will look for an '@' character followed by word characters.
	 *			match = left.match( /@\w*$/ );
	 *
	 *		if ( !match ) {
	 *			return null;
	 *		}
	 *
	 *		return { start: match.index, end: offset };
	 *	}
	 *
	 *	// Returns (through its callback) the suggestions for the current query.
	 *	function dataCallback( matchInfo, callback ) {
	 *		// Simple search.
	 *		// Filter the entire items array so only the items that start
	 *		// with the query remain.
	 *		var suggestions = itemsArray.filter( function( item ) {
	 *			return item.name.toLowerCase().indexOf( matchInfo.query.toLowerCase() ) == 0;
	 *		} );
	 *
	 *		// Note: The callback function can also be executed asynchronously
	 *		// so dataCallback can do XHR requests or use any other asynchronous API.
	 *		callback( suggestions );
	 *	}
	 *
	 *	// Finally, instantiate the autocomplete class.
	 *	new CKEDITOR.plugins.autocomplete( editor, {
	 *		textTestCallback: textTestCallback,
	 *		dataCallback: dataCallback
	 *	} );
	 * ```
	 *
	 * # Changing the behavior of the autocomplete class by subclassing it
	 *
	 * This plugin will expose a `CKEDITOR.plugins.customAutocomplete` class which uses
	 * a custom view that positions the panel relative to the {@link CKEDITOR.editor#container}.
	 *
	 * ```javascript
	 *	CKEDITOR.plugins.add( 'customautocomplete', {
	 *		requires: 'autocomplete',
	 *
	 *		onLoad: function() {
	 *			var View = CKEDITOR.plugins.autocomplete.view,
	 *				Autocomplete = CKEDITOR.plugins.autocomplete;
	 *
	 *			function CustomView( editor ) {
	 *				// Call the parent class constructor.
	 *				View.call( this, editor );
	 *			}
	 *			// Inherit the view methods.
	 *			CustomView.prototype = CKEDITOR.tools.prototypedCopy( View.prototype );
	 *
	 *			// Change the positioning of the panel, so it is stretched
	 *			// to 100% of the editor container width and is positioned
	 *			// relative to the editor container.
	 *			CustomView.prototype.updatePosition = function( range ) {
	 *				var caretRect = this.getViewPosition( range ),
	 *					container = this.editor.container;
	 *
	 *				this.setPosition( {
	 *					// Position the panel relative to the editor container.
	 *					left: container.$.offsetLeft,
	 *					top: caretRect.top,
	 *					bottom: caretRect.bottom
	 *				} );
	 *				// Stretch the panel to 100% of the editor container width.
	 *				this.element.setStyle( 'width', container.getSize( 'width' ) + 'px' );
	 *			};
	 *
	 *			function CustomAutocomplete( editor, configDefinition ) {
	 *				// Call the parent class constructor.
	 *				Autocomplete.call( this, editor, configDefinition );
	 *			}
	 *			// Inherit the autocomplete methods.
	 *			CustomAutocomplete.prototype = CKEDITOR.tools.prototypedCopy( Autocomplete.prototype );
	 *
	 *			CustomAutocomplete.prototype.getView = function() {
	 *				return new CustomView( this.editor );
	 *			}
	 *
	 *			// Expose the custom autocomplete so it can be used later.
	 *			CKEDITOR.plugins.customAutocomplete = CustomAutocomplete;
	 *		}
	 *	} );
	 * ```
	 * @param {CKEDITOR.editor} editor The editor to watch.
	 * @param {CKEDITOR.plugins.autocomplete.configDefinition} config Configuration object for this autocomplete instance.
	 */
	function Autocomplete( editor, config ) {
		var configKeystrokes = editor.config.autocomplete_commitKeystrokes || CKEDITOR.config.autocomplete_commitKeystrokes;

		/**
		 * The editor instance that autocomplete is attached to.
		 *
		 * @readonly
		 * @property {CKEDITOR.editor}
		 */
		this.editor = editor;

		/**
		 * Indicates throttle threshold expressed in milliseconds, reducing text checks frequency.
		 *
		 * @property {Number} [throttle=20]
		 */
		this.throttle = config.throttle !== undefined ? config.throttle : 20;

		/**
		 * The autocomplete view instance.
		 *
		 * @readonly
		 * @property {CKEDITOR.plugins.autocomplete.view}
		 */
		this.view = this.getView();

		/**
		 * The autocomplete model instance.
		 *
		 * @readonly
		 * @property {CKEDITOR.plugins.autocomplete.model}
		 */
		this.model = this.getModel( config.dataCallback );
		this.model.itemsLimit = config.itemsLimit;

		/**
		 * The autocomplete text watcher instance.
		 *
		 * @readonly
		 * @property {CKEDITOR.plugins.textWatcher}
		 */
		this.textWatcher = this.getTextWatcher( config.textTestCallback );

		/**
		 * The autocomplete keystrokes used to finish autocompletion with the selected view item.
		 * The property is using the {@link CKEDITOR.config#autocomplete_commitKeystrokes} configuration option as default keystrokes.
		 * You can change this property to set individual keystrokes for the plugin instance.
		 *
		 * @property {Number[]}
		 * @readonly
		 */
		this.commitKeystrokes = CKEDITOR.tools.array.isArray( configKeystrokes ) ? configKeystrokes.slice() : [ configKeystrokes ];

		/**
		 * Listeners registered by this autocomplete instance.
		 *
		 * @private
		 */
		this._listeners = [];

		/**
		 * Template of markup to be inserted as the autocomplete item gets committed.
		 *
		 * You can use {@link CKEDITOR.plugins.autocomplete.model.item item} properties to customize the template.
		 *
		 * ```javascript
		 * var outputTemplate = `<a href="/tracker/{ticket}">#{ticket} ({name})</a>`;
		 * ```
		 *
		 * @readonly
		 * @property {CKEDITOR.template} [outputTemplate=null]
		 */
		this.outputTemplate = config.outputTemplate !== undefined ? new CKEDITOR.template( config.outputTemplate ) : null;

		if ( config.itemTemplate ) {
			this.view.itemTemplate = new CKEDITOR.template( config.itemTemplate );
		}

		// Attach autocomplete when editor instance is ready (#2114).
		if ( this.editor.status === 'ready' ) {
			this.attach();
		} else {
			this.editor.on( 'instanceReady', function() {
				this.attach();
			}, this );
		}

		editor.on( 'destroy', function() {
			this.destroy();
		}, this );
	}

	Autocomplete.prototype = {
		/**
		 * Attaches the autocomplete to the {@link #editor}.
		 *
		 * * The view is appended to the DOM and the listeners are attached.
		 * * The {@link #textWatcher text watcher} is attached to the editor.
		 * * The listeners on the {@link #model} and {@link #view} events are added.
		 */
		attach: function() {
			var editor = this.editor,
				win = CKEDITOR.document.getWindow(),
				editable = editor.editable(),
				editorScrollableElement = editable.isInline() ? editable : editable.getDocument();

			// iOS classic editor listens on frame parent element for editor `scroll` event (#1910).
			if ( CKEDITOR.env.iOS && !editable.isInline() ) {
				editorScrollableElement = iOSViewportElement( editor );
			}

			this.view.append();
			this.view.attach();
			this.textWatcher.attach();

			this._listeners.push( this.textWatcher.on( 'matched', this.onTextMatched, this ) );
			this._listeners.push( this.textWatcher.on( 'unmatched', this.onTextUnmatched, this ) );
			this._listeners.push( this.model.on( 'change-data', this.modelChangeListener, this ) );
			this._listeners.push( this.model.on( 'change-selectedItemId', this.onSelectedItemId, this ) );
			this._listeners.push( this.view.on( 'change-selectedItemId', this.onSelectedItemId, this ) );
			this._listeners.push( this.view.on( 'click-item', this.onItemClick, this ) );

			// Update view position on viewport change.
			// Note: CKEditor's event system has a limitation that one function
			// cannot be used as listener for the same event more than once. Hence, wrapper functions.
			this._listeners.push( win.on( 'scroll', function() {
				this.viewRepositionListener();
			}, this ) );
			this._listeners.push( editorScrollableElement.on( 'scroll', function() {
				this.viewRepositionListener();
			}, this ) );

			// Don't let browser to focus dropdown element (#2107).
			this._listeners.push( this.view.element.on( 'mousedown', function( e ) {
				e.data.preventDefault();
			}, null, null, 9999 ) );

			// Register keybindings if editor is already initialized.
			if ( editable ) {
				this.registerPanelNavigation();
			}

			// Note: CKEditor's event system has a limitation that one function
			// cannot be used as listener for the same event more than once. Hence, wrapper function.
			// (#4107)
			editor.on( 'contentDom', function() {
				this.registerPanelNavigation();
			}, this );
		},

		registerPanelNavigation: function() {
			var editable = this.editor.editable();

			// Priority 5 to get before the enterkey.
			// Note: CKEditor's event system has a limitation that one function (in this case this.onKeyDown)
			// cannot be used as listener for the same event more than once. Hence, wrapper function.
			this._listeners.push( editable.attachListener( editable, 'keydown', function( evt ) {
				this.onKeyDown( evt );
			}, this, null, 5 ) );
		},

		/**
		 * Closes the view and sets its {@link CKEDITOR.plugins.autocomplete.model#isActive state} to inactive.
		 */
		close: function() {
			this.model.setActive( false );
			this.view.close();
		},

		/**
		 * Commits the currently chosen or given item. HTML is generated for this item using the
		 * {@link #getHtmlToInsert} method and then it is inserted into the editor. The item is inserted
		 * into the {@link CKEDITOR.plugins.autocomplete.model#range query's range}, so the query text is
		 * replaced by the inserted HTML.
		 *
		 * @param {Number/String} [itemId] If given, then the specified item will be inserted into the editor
		 * instead of the currently chosen one.
		 */
		commit: function( itemId ) {
			if ( !this.model.isActive ) {
				return;
			}

			this.close();

			if ( itemId == null ) {
				itemId = this.model.selectedItemId;

				// If non item is selected abort commit.
				if ( itemId == null ) {
					return;
				}
			}

			var item = this.model.getItemById( itemId ),
				editor = this.editor;

			editor.fire( 'saveSnapshot' );
			editor.getSelection().selectRanges( [ this.model.range ] );
			editor.insertHtml( this.getHtmlToInsert( item ), 'text' );
			editor.fire( 'saveSnapshot' );
		},

		/**
		 * Destroys the autocomplete instance.
		 * View element and event listeners will be removed from the DOM.
		 */
		destroy: function() {
			CKEDITOR.tools.array.forEach( this._listeners, function( obj ) {
				obj.removeListener();
			} );

			this._listeners = [];

			this.view.element && this.view.element.remove();
		},

		/**
		 * Returns HTML that should be inserted into the editor when the item is committed.
		 *
		 * See also the {@link #commit} method.
		 *
		 * @param {CKEDITOR.plugins.autocomplete.model.item} item
		 * @returns {String} The HTML to insert.
		 */
		getHtmlToInsert: function( item ) {
			var encodedItem = encodeItem( item );
			return this.outputTemplate ? this.outputTemplate.output( encodedItem ) : encodedItem.name;
		},

		/**
		 * Creates and returns the model instance. This method is used when
		 * initializing the autocomplete and can be overwritten in order to
		 * return an instance of a different class than the default model.
		 *
		 * @param {Function} dataCallback See {@link CKEDITOR.plugins.autocomplete.configDefinition#dataCallback configDefinition.dataCallback}.
		 * @returns {CKEDITOR.plugins.autocomplete.model} The model instance.
		 */
		getModel: function( dataCallback ) {
			var that = this;

			return new Model( function( matchInfo, callback ) {
				return dataCallback.call( this, CKEDITOR.tools.extend( {
					// Make sure autocomplete instance is available in the callback (#2108).
					autocomplete: that
				}, matchInfo ), callback );
			} );
		},

		/**
		 * Creates and returns the text watcher instance. This method is used while
		 * initializing the autocomplete and can be overwritten in order to
		 * return an instance of a different class than the default text watcher.
		 *
		 * @param {Function} textTestCallback See the {@link CKEDITOR.plugins.autocomplete} arguments.
		 * @returns {CKEDITOR.plugins.textWatcher} The text watcher instance.
		 */
		getTextWatcher: function( textTestCallback ) {
			return new CKEDITOR.plugins.textWatcher( this.editor, textTestCallback, this.throttle );
		},

		/**
		 * Creates and returns the view instance. This method is used while
		 * initializing the autocomplete and can be overwritten in order to
		 * return an instance of a different class than the default view.
		 *
		 * @returns {CKEDITOR.plugins.autocomplete.view} The view instance.
		 */
		getView: function() {
			return new View( this.editor );
		},

		/**
		 * Opens the panel if {@link CKEDITOR.plugins.autocomplete.model#hasData there is any data available}.
		 */
		open: function() {
			if ( this.model.hasData() ) {
				this.model.setActive( true );
				this.view.open();
				this.model.selectFirst();
				this.view.updatePosition( this.model.range );
			}
		},

		// LISTENERS ------------------

		/**
		 * The function that should be called when the view has to be repositioned, e.g on scroll.
		 *
		 * @private
		 */
		viewRepositionListener: function() {
			if ( this.model.isActive ) {
				this.view.updatePosition( this.model.range );
			}
		},

		/**
		 * The function that should be called once the model data has changed.
		 *
		 * @param {CKEDITOR.eventInfo} evt
		 * @private
		 */
		modelChangeListener: function( evt ) {
			if ( this.model.hasData() ) {
				this.view.updateItems( evt.data );
				this.open();
			} else {
				this.close();
			}
		},

		/**
		 * The function that should be called once a view item was clicked.
		 *
		 * @param {CKEDITOR.eventInfo} evt
		 * @private
		 */
		onItemClick: function( evt ) {
			this.commit( evt.data );
		},

		/**
		 * The function that should be called on every `keydown` event occurred within the {@link CKEDITOR.editable editable} element.
		 *
		 * @param {CKEDITOR.dom.event} evt
		 * @private
		 */
		onKeyDown: function( evt ) {
			if ( !this.model.isActive ) {
				return;
			}

			var keyCode = evt.data.getKey(),
				handled = false;

			// Esc key.
			if ( keyCode == 27 ) {
				this.close();
				this.textWatcher.unmatch();
				handled = true;
			// Down Arrow.
			} else if ( keyCode == 40 ) {
				this.model.selectNext();
				handled = true;
			// Up Arrow.
			} else if ( keyCode == 38 ) {
				this.model.selectPrevious();
				handled = true;
			// Completion keys.
			} else if ( CKEDITOR.tools.indexOf( this.commitKeystrokes, keyCode ) != -1 ) {
				this.commit();
				this.textWatcher.unmatch();
				handled = true;
			}

			if ( handled ) {
				evt.cancel();
				evt.data.preventDefault();
				this.textWatcher.consumeNext();
			}
		},

		/**
		 * The function that should be called once an item was selected.
		 *
		 * @param {CKEDITOR.eventInfo} evt
		 * @private
		 */
		onSelectedItemId: function( evt ) {
			this.model.setItem( evt.data );
			this.view.selectItem( evt.data );
		},

		/**
		 * The function that should be called once a text was matched by the {@link CKEDITOR.plugins.textWatcher text watcher}
		 * component.
		 *
		 * @param {CKEDITOR.eventInfo} evt
		 * @private
		 */
		onTextMatched: function( evt ) {
			this.model.setActive( false );
			this.model.setQuery( evt.data.text, evt.data.range );
		},

		/**
		 * The function that should be called once a text was unmatched by the {@link CKEDITOR.plugins.textWatcher text watcher}
		 * component.
		 *
		 * @param {CKEDITOR.eventInfo} evt
		 * @private
		 */
		onTextUnmatched: function() {
			// Remove query and request ID to avoid opening view for invalid callback (#1984).
			this.model.query = null;
			this.model.lastRequestId = null;

			this.close();
		}
	};

	/**
	 * Class representing the autocomplete view.
	 *
	 * In order to use a different view, implement a new view class and override
	 * the {@link CKEDITOR.plugins.autocomplete#getView} method.
	 *
	 * ```javascript
	 *	myAutocomplete.prototype.getView = function() {
	 *		return new myView( this.editor );
	 *	};
	 * ```
	 *
	 * You can also modify this autocomplete instance on the fly.
	 *
	 * ```javascript
	 *	myAutocomplete.prototype.getView = function() {
	 *		// Call the original getView method.
	 *		var view = CKEDITOR.plugins.autocomplete.prototype.getView.call( this );
	 *
	 *		// Override one property.
	 *		view.itemTemplate = new CKEDITOR.template( '<li data-id={id}><img src="{iconSrc}" alt="..."> {name}</li>' );
	 *
	 *		return view;
	 *	};
	 * ```
	 *
	 * **Note:** This class is marked as private, which means that its API might be subject to change in order to
	 * provide further enhancements.
	 *
	 * @class CKEDITOR.plugins.autocomplete.view
	 * @since 4.10.0
	 * @private
	 * @mixins CKEDITOR.event
	 * @constructor Creates the autocomplete view instance.
	 * @param {CKEDITOR.editor} editor The editor instance.
	 */
	function View( editor ) {
		/**
		 * The panel's item template used to render matches in the dropdown.
		 *
		 * You can use {@link CKEDITOR.plugins.autocomplete.model#data data item} properties to customize the template.
		 *
		 * A minimal template must be wrapped with a HTML `li` element containing the `data-id="{id}"` attribute.
		 *
		 * ```javascript
		 * var itemTemplate = '<li data-id="{id}"><img src="{iconSrc}" alt="{name}">{name}</li>';
		 * ```
		 *
		 * @readonly
		 * @property {CKEDITOR.template}
		 */
		this.itemTemplate = new CKEDITOR.template( '<li data-id="{id}">{name}</li>' );

		/**
		 * The editor instance.
		 *
		 * @readonly
		 * @property {CKEDITOR.editor}
		 */
		this.editor = editor;

		/**
		 * The ID of the selected item.
		 *
		 * @readonly
		 * @property {Number/String} selectedItemId
		 */

		/**
		 * The document to which the view is attached. It is set by the {@link #append} method.
		 *
		 * @readonly
		 * @property {CKEDITOR.dom.document} document
		 */

		/**
		 * The view's main element. It is set by the {@link #append} method.
		 *
		 * @readonly
		 * @property {CKEDITOR.dom.element} element
		 */

		/**
		 * Event fired when an item in the panel is clicked.
		 *
		 * @event click-item
		 * @param {String} The clicked item {@link CKEDITOR.plugins.autocomplete.model.item#id}. Note: the ID
		 * is stringified due to the way how it is stored in the DOM.
		 */

		/**
		 * Event fired when the {@link #selectedItemId} property changes.
		 *
		 * @event change-selectedItemId
		 * @param {Number/String} data The new value.
		 */
	}

	View.prototype = {
		/**
		 * Appends the {@link #element main element} to the DOM.
		 */
		append: function() {
			this.document = CKEDITOR.document;
			this.element = this.createElement();

			this.document.getBody().append( this.element );
		},

		/**
		 * Removes existing items and appends given items to the {@link #element}.
		 *
		 * @param {CKEDITOR.dom.documentFragment} itemsFragment The document fragment with item elements.
		 */
		appendItems: function( itemsFragment ) {
			this.element.setHtml( '' );
			this.element.append( itemsFragment );
		},

		/**
		 * Attaches the view's listeners to the DOM elements.
		 */
		attach: function() {
			this.element.on( 'click', function( evt ) {
				var target = evt.data.getTarget(),
					itemElement = target.getAscendant( this.isItemElement, true );

				if ( itemElement ) {
					this.fire( 'click-item', itemElement.data( 'id' ) );
				}
			}, this );

			this.element.on( 'mouseover', function( evt ) {
				var target = evt.data.getTarget();

				if ( this.element.contains( target ) ) {

					// Find node containing data-id attribute inside target node tree (#2187).
					target = target.getAscendant( function( element ) {
						return element.hasAttribute( 'data-id' );
					}, true );

					if ( !target ) {
						return;
					}

					var itemId = target.data( 'id' );

					this.fire( 'change-selectedItemId', itemId );
				}

			}, this );
		},

		/**
		 * Closes the panel.
		 */
		close: function() {
			this.element.removeClass( 'cke_autocomplete_opened' );
		},

		/**
		 * Creates and returns the view's main element.
		 *
		 * @private
		 * @returns {CKEDITOR.dom.element}
		 */
		createElement: function() {
			var el = new CKEDITOR.dom.element( 'ul', this.document );

			el.addClass( 'cke_autocomplete_panel' );
			// Below float panels and context menu, but above maximized editor (-5).
			el.setStyle( 'z-index', this.editor.config.baseFloatZIndex - 3 );

			return el;
		},

		/**
		 * Creates the item element based on the {@link #itemTemplate}.
		 *
		 * @param {CKEDITOR.plugins.autocomplete.model.item} item The item for which an element will be created.
		 * @returns {CKEDITOR.dom.element}
		 */
		createItem: function( item ) {
			var encodedItem = encodeItem( item );
			return CKEDITOR.dom.element.createFromHtml( this.itemTemplate.output( encodedItem ), this.document );
		},

		/**
		 * Returns the view position based on a given `range`.
		 *
		 * Indicates the start position of the autocomplete dropdown.
		 * The value returned by this function is passed to the {@link #setPosition} method
		 * by the {@link #updatePosition} method.
		 *
		 * @param {CKEDITOR.dom.range} range The range of the text match.
		 * @returns {Object} Represents the position of the caret. The value is relative to the panel's offset parent.
		 * @returns {Number} rect.left
		 * @returns {Number} rect.top
		 * @returns {Number} rect.bottom
		 */
		getViewPosition: function( range ) {
			// Use the last rect so the view will be
			// correctly positioned with a word split into few lines.
			var rects = range.getClientRects(),
				viewPositionRect = rects[ rects.length - 1 ],
				offset,
				editable = this.editor.editable();

			if ( editable.isInline() ) {
				offset = CKEDITOR.document.getWindow().getScrollPosition();
			} else {
				offset = editable.getParent().getDocumentPosition( CKEDITOR.document );
			}

			// Consider that offset host might be repositioned on its own.
			// Similar to #1048. See https://github.com/ckeditor/ckeditor4/pull/1732#discussion_r182790235.
			var hostElement = CKEDITOR.document.getBody();
			if ( hostElement.getComputedStyle( 'position' ) === 'static' ) {
				hostElement = hostElement.getParent();
			}

			var offsetCorrection = hostElement.getDocumentPosition();

			offset.x -= offsetCorrection.x;
			offset.y -= offsetCorrection.y;

			return {
				top: ( viewPositionRect.top + offset.y ),
				bottom: ( viewPositionRect.top + viewPositionRect.height + offset.y ),
				left: ( viewPositionRect.left + offset.x )
			};
		},

		/**
		 * Gets the item element by the item ID.
		 *
		 * @param {Number/String} itemId
		 * @returns {CKEDITOR.dom.element} The item element.
		 */
		getItemById: function( itemId ) {
			return this.element.findOne( 'li[data-id="' + itemId + '"]' );
		},

		/**
		 * Checks whether a given node is the item element.
		 *
		 * @param {CKEDITOR.dom.node} node
		 * @returns {Boolean}
		 */
		isItemElement: function( node ) {
			return node.type == CKEDITOR.NODE_ELEMENT &&
				Boolean( node.data( 'id' ) );
		},

		/**
		 * Opens the panel.
		 */
		open: function() {
			this.element.addClass( 'cke_autocomplete_opened' );
		},

		/**
		 * Selects the item in the panel and scrolls the list to show it if needed.
		 * The {@link #selectedItemId currently selected item} is deselected first.
		 *
		 * @param {Number/String} itemId The ID of the item that should be selected.
		 */
		selectItem: function( itemId ) {
			if ( this.selectedItemId != null ) {
				this.getItemById( this.selectedItemId ).removeClass( 'cke_autocomplete_selected' );
			}

			var itemElement = this.getItemById( itemId );
			itemElement.addClass( 'cke_autocomplete_selected' );
			this.selectedItemId = itemId;

			this.scrollElementTo( itemElement );
		},

		/**
		 * Sets the position of the panel. This method only performs the check
		 * for the available space below and above the specified `rect` and
		 * positions the panel in the best place.
		 *
		 * This method is used by the {@link #updatePosition} method which
		 * controls how the panel should be positioned on the screen, for example
		 * based on the caret position and/or the editor position.
		 *
		 * @param {Object} rect Represents the position of a vertical (e.g. a caret) line relative to which
		 * the panel should be positioned.
		 * @param {Number} rect.left The position relative to the panel's offset parent in pixels.
		 * For example, the position of the caret.
		 * @param {Number} rect.top The position relative to the panel's offset parent in pixels.
		 * For example, the position of the upper end of the caret.
		 * @param {Number} rect.bottom The position relative to the panel's offset parent in pixels.
		 * For example, the position of the bottom end of the caret.
		 */
		setPosition: function( rect ) {
			var documentWindow = this.element.getWindow(),
				windowRect = documentWindow.getViewPaneSize(),
				top = getVerticalPosition( {
					editorViewportRect: getEditorViewportRect( this.editor ),
					caretRect: rect,
					viewHeight: this.element.getSize( 'height' ),
					scrollPositionY: documentWindow.getScrollPosition().y,
					windowHeight: windowRect.height
				} ),
				left = getHorizontalPosition( {
					leftPosition: rect.left,
					viewWidth: this.element.getSize( 'width' ),
					windowWidth: windowRect.width
				} );

			this.element.setStyles( {
				left: left + 'px',
				top: top + 'px'
			} );

			function getVerticalPosition( options ) {
				var editorViewportRect = options.editorViewportRect,
					caretRect = options.caretRect,
					viewHeight = options.viewHeight,
					scrollPositionY = options.scrollPositionY,
					windowHeight = options.windowHeight;

				// If the caret position is below the view - keep it at the bottom edge.
				// +---------------------------------------------+
				// |       editor viewport                       |
				// |                                             |
				// |     +--------------+                        |
				// |     |              |                        |
				// |     |     view     |                        |
				// |     |              |                        |
				// +-----+==============+------------------------+
				// |                                             |
				// |     █ - caret position                      |
				// |                                             |
				// +---------------------------------------------+
				if ( editorViewportRect.bottom < caretRect.bottom ) {
					return Math.min( caretRect.top, editorViewportRect.bottom ) - viewHeight;
				}

				// If the view doesn't fit below the caret position and fits above, set it there.
				// This means that the position below the caret is preferred.
				// +---------------------------------------------+
				// |                                             |
				// |       editor viewport                       |
				// |     +--------------+                        |
				// |     |              |                        |
				// |     |     view     |                        |
				// |     |              |                        |
				// |     +--------------+                        |
				// |     █ - caret position                      |
				// |                                             |
				// |                                             |
				// +---------------------------------------------+
				// How much space is there for the view above and below the specified rect.
				var spaceAbove = caretRect.top - editorViewportRect.top,
					spaceBelow = editorViewportRect.bottom - caretRect.bottom,
					viewExceedsTopViewport = ( caretRect.top - viewHeight ) < scrollPositionY;

				if ( viewHeight > spaceBelow && viewHeight < spaceAbove && !viewExceedsTopViewport ) {
					return caretRect.top - viewHeight;
				}

				// If the caret position is above the view - keep it at the top edge.
				// +---------------------------------------------+
				// |                                             |
				// |     █ - caret position                      |
				// |                                             |
				// +-----+==============+------------------------+
				// |     |              |                        |
				// |     |     view     |                        |
				// |     |              |                        |
				// |     +--------------+                        |
				// |                                             |
				// |       editor viewport                       |
				// +---------------------------------------------+
				if ( editorViewportRect.top > caretRect.top ) {
					return Math.max( caretRect.bottom, editorViewportRect.top );
				}

				// (#3582)
				// If the view goes beyond bottom window border - reverse view position, even if it fits editor viewport.
				// +---------------------------------------------+
				// |               editor viewport               |
				// |                                             |
				// |                                             |
				// |                  +--------------+           |
				// |                  |     view     |           |
				// |                  +--------------+           |
				// | caret position - █                          |
				// |                                             |
				// =============================================== - bottom window border
				// |                                             |
				// |                                             |
				// +---------------------------------------------+
				var viewExceedsBottomViewport = ( caretRect.bottom + viewHeight ) > ( windowHeight + scrollPositionY );

				if ( !( viewHeight > spaceBelow && viewHeight < spaceAbove ) && viewExceedsBottomViewport ) {
					return caretRect.top - viewHeight;
				}

				// As a default, keep the view inside the editor viewport.
				// +---------------------------------------------+
				// |       editor viewport                       |
				// |                                             |
				// |                                             |
				// |                                             |
				// |     █ - caret position                      |
				// |     +--------------+                        |
				// |     |     view     |                        |
				// |     +--------------+                        |
				// |                                             |
				// |                                             |
				// +---------------------------------------------+
				return Math.min( editorViewportRect.bottom, caretRect.bottom );
			}

			function getHorizontalPosition( options ) {
				var caretLeftPosition = options.leftPosition,
					viewWidth = options.viewWidth,
					windowWidth = options.windowWidth;

				// (#3582)
				// If the view goes beyond right window border - stick it to the edge of the available viewport.
				// +---------------------------------------------+   ||
				// |               editor viewport               |   ||
				// |                                             |   ||
				// |                                             |   ||
				// |                         caret position - █  |   || - right window border
				// |                                 +--------------+||
				// |                                 |              |||
				// |                                 |     view     |||
				// |                                 |              |||
				// |                                 +--------------+||
				// |                                             |   ||
				// +---------------------------------------------+   ||
				if ( caretLeftPosition + viewWidth > windowWidth ) {
					return windowWidth - viewWidth;
				}

				// Otherwise inherit the horizontal position from caret.
				return caretLeftPosition;
			}

			// Bounding rect where the view should fit (visible editor viewport).
			function getEditorViewportRect( editor ) {
				var editable = editor.editable();

				// iOS classic editor has different viewport element (#1910).
				if ( CKEDITOR.env.iOS && !editable.isInline() ) {
					return iOSViewportElement( editor ).getClientRect( true );
				} else {
					return editable.isInline() ? editable.getClientRect( true ) : editor.window.getFrame().getClientRect( true );
				}
			}
		},

		/**
		 * Scrolls the list so the item element is visible in it.
		 *
		 * @param {CKEDITOR.dom.element} itemElement
		 */
		scrollElementTo: function( itemElement ) {
			itemElement.scrollIntoParent( this.element );
		},

		/**
		 * Updates the list of items in the panel.
		 *
		 * @param {CKEDITOR.plugins.autocomplete.model.item[]} items.
		 */
		updateItems: function( items ) {
			var i,
				frag = new CKEDITOR.dom.documentFragment( this.document );

			for ( i = 0; i < items.length; ++i ) {
				frag.append( this.createItem( items[ i ] ) );
			}

			this.appendItems( frag );
			this.selectedItemId = null;
		},

		/**
		 * Updates the position of the panel.
		 *
		 * By default this method finds the position of the caret and uses
		 * {@link #setPosition} to move the panel to the best position close
		 * to the caret.
		 *
		 * @param {CKEDITOR.dom.range} range The range of the text match.
		 */
		updatePosition: function( range ) {
			this.setPosition( this.getViewPosition( range ) );
		}
	};

	CKEDITOR.event.implementOn( View.prototype );

	/**
	 * Class representing the autocomplete model.
	 *
	 * In case you want to modify the model behavior, check out the
	 * {@link CKEDITOR.plugins.autocomplete.view} documentation. It contains
	 * examples of how to easily override the default behavior.
	 *
	 * A model instance is created by the {@link CKEDITOR.plugins.autocomplete#getModel} method.
	 *
	 * **Note:** This class is marked as private, which means that its API might be subject to change in order to
	 * provide further enhancements.
	 *
	 * @class CKEDITOR.plugins.autocomplete.model
	 * @since 4.10.0
	 * @private
	 * @mixins CKEDITOR.event
	 * @constructor Creates the autocomplete model instance.
	 * @param {Function} dataCallback See {@link CKEDITOR.plugins.autocomplete} arguments.
	 */
	function Model( dataCallback ) {
		/**
		 * The callback executed by the model when requesting data.
		 * See {@link CKEDITOR.plugins.autocomplete} arguments.
		 *
		 * @readonly
		 * @property {Function}
		 */
		this.dataCallback = dataCallback;

		/**
		 * Whether the autocomplete is active (i.e. can receive user input like click, key press).
		 * Should be modified by the {@link #setActive} method which fires the {@link #change-isActive} event.
		 *
		 * @readonly
		 */
		this.isActive = false;

		/**
		 * Indicates the limit of items rendered in the dropdown.
		 *
		 * For falsy values like `0` or `null` all items will be rendered.
		 *
		 * @property {Number} [itemsLimit=0]
		 */
		this.itemsLimit = 0;

		/**
		 * The ID of the last request for data. Used by the {@link #setQuery} method.
		 *
		 * @readonly
		 * @private
		 * @property {Number} lastRequestId
		 */

		/**
		 * The query string set by the {@link #setQuery} method.
		 *
		 * The query string always has a corresponding {@link #range}.
		 *
		 * @readonly
		 * @property {String} query
		 */

		/**
		 * The range in the DOM where the {@link #query} text is.
		 *
		 * The range always has a corresponding {@link #query}. Both can be set by the {@link #setQuery} method.
		 *
		 * @readonly
		 * @property {CKEDITOR.dom.range} range
		 */

		/**
		 * The query results &mdash; the items to be displayed in the autocomplete panel.
		 *
		 * @readonly
		 * @property {CKEDITOR.plugins.autocomplete.model.item[]} data
		 */

		/**
		 * The ID of the item currently selected in the panel.
		 *
		 * @readonly
		 * @property {Number/String} selectedItemId
		 */

		/**
		 * Event fired when the {@link #data} array changes.
		 *
		 * @event change-data
		 * @param {CKEDITOR.plugins.autocomplete.model.item[]} data The new value.
		 */

		/**
		 * Event fired when the {@link #selectedItemId} property changes.
		 *
		 * @event change-selectedItemId
		 * @param {Number/String} data The new value.
		 */

		/**
		 * Event fired when the {@link #isActive} property changes.
		 *
		 * @event change-isActive
		 * @param {Boolean} data The new value.
		 */
	}

	Model.prototype = {
		/**
		 * Gets an index from the {@link #data} array of the item by its ID.
		 *
		 * @param {Number/String} itemId
		 * @returns {Number}
		 */
		getIndexById: function( itemId ) {
			if ( !this.hasData() ) {
				return -1;
			}

			for ( var data = this.data, i = 0, l = data.length; i < l; i++ ) {
				if ( data[ i ].id == itemId ) {
					return i;
				}
			}

			return -1;
		},

		/**
		 * Gets the item from the {@link #data} array by its ID.
		 *
		 * @param {Number/String} itemId
		 * @returns {CKEDITOR.plugins.autocomplete.model.item}
		 */
		getItemById: function( itemId ) {
			var index = this.getIndexById( itemId );
			return ~index && this.data[ index ] || null;
		},

		/**
		 * Whether the model contains non-empty {@link #data}.
		 *
		 * @returns {Boolean}
		 */
		hasData: function() {
			return Boolean( this.data && this.data.length );
		},

		/**
		 * Sets the {@link #selectedItemId} property.
		 *
		 * @param {Number/String} itemId
		 */
		setItem: function( itemId ) {
			if ( this.getIndexById( itemId ) < 0 ) {
				throw new Error( 'Item with given id does not exist' );
			}

			this.selectedItemId = itemId;
		},

		/**
		 * Fires the {@link #change-selectedItemId} event.
		 *
		 * @param {Number/String} itemId
		 */
		select: function( itemId ) {
			this.fire( 'change-selectedItemId', itemId );
		},

		/**
		 * Selects the first item. See also the {@link #select} method.
		 */
		selectFirst: function() {
			if ( this.hasData() ) {
				this.select( this.data[ 0 ].id );
			}
		},

		/**
		 * Selects the last item. See also the {@link #select} method.
		 */
		selectLast: function() {
			if ( this.hasData() ) {
				this.select( this.data[ this.data.length - 1 ].id );
			}
		},

		/**
		 * Selects the next item in the {@link #data} array. If no item is selected,
		 * it selects the first one. If the last one is selected, it selects the first one.
		 *
		 * See also the {@link #select} method.
		 */
		selectNext: function() {
			if ( this.selectedItemId == null ) {
				this.selectFirst();
				return;
			}

			var index = this.getIndexById( this.selectedItemId );

			if ( index < 0 || index + 1 == this.data.length ) {
				this.selectFirst();
			} else {
				this.select( this.data[ index + 1 ].id );
			}
		},

		/**
		 * Selects the previous item in the {@link #data} array. If no item is selected,
		 * it selects the last one. If the first one is selected, it selects the last one.
		 *
		 * See also the {@link #select} method.
		 */
		selectPrevious: function() {
			if ( this.selectedItemId == null ) {
				this.selectLast();
				return;
			}

			var index = this.getIndexById( this.selectedItemId );

			if ( index <= 0 ) {
				this.selectLast();
			} else {
				this.select( this.data[ index - 1 ].id );
			}
		},

		/**
		 * Sets the {@link #isActive} property and fires the {@link #change-isActive} event.
		 *
		 * @param {Boolean} isActive
		 */
		setActive: function( isActive ) {
			this.isActive = isActive;
			this.fire( 'change-isActive', isActive );
		},

		/**
		 * Sets the {@link #query} and {@link #range} and makes a request for the query results
		 * by executing the {@link #dataCallback} function. When the data is returned (synchronously or
		 * asynchronously, because {@link #dataCallback} exposes a callback function), the {@link #data}
		 * property is set and the {@link #change-data} event is fired.
		 *
		 * This method controls that only the response for the current query is handled.
		 *
		 * @param {String} query
		 * @param {CKEDITOR.dom.range} range
		 */
		setQuery: function( query, range ) {
			var that = this,
				requestId = CKEDITOR.tools.getNextId();

			this.lastRequestId = requestId;
			this.query = query;
			this.range = range;
			this.data = null;
			this.selectedItemId = null;

			this.dataCallback( {
				query: query,
				range: range
			}, handleData );

			// Note: don't put any executable code here because the callback passed to
			// this.dataCallback may be executed synchronously or asynchronously
			// so execution order will differ.

			function handleData( data ) {
				// Handle only the response for the most recent setQuery call.
				if ( requestId == that.lastRequestId ) {
					// Limit number of items (#2030).
					if ( that.itemsLimit ) {
						that.data = data.slice( 0, that.itemsLimit );
					} else {
						that.data = data;
					}
					that.fire( 'change-data', that.data );
				}
			}
		}
	};

	CKEDITOR.event.implementOn( Model.prototype );

	/**
	 * An abstract class representing one {@link CKEDITOR.plugins.autocomplete.model#data data item}.
	 * A item can be understood as one entry in the autocomplete panel.
	 *
	 * An item must have a unique {@link #id} and may have more properties which can then be used, for example,
	 * in the {@link CKEDITOR.plugins.autocomplete.view#itemTemplate} template or the
	 * {@link CKEDITOR.plugins.autocomplete#getHtmlToInsert} method.
	 *
	 * Example items:
	 *
	 * ```javascript
	 *	{ id: 345, name: 'CKEditor' }
	 *	{ id: 'smile1', alt: 'smile', emojiSrc: 'emojis/smile.png' }
	 * ```
	 *
	 * @abstract
	 * @class CKEDITOR.plugins.autocomplete.model.item
	 * @since 4.10.0
	 */

	/**
	 * The unique ID of the item. The ID should not change with time, so two
	 * {@link CKEDITOR.plugins.autocomplete.model#dataCallback}
	 * calls should always result in the same ID for the same logical item.
	 * This can, for example, allow to keep the same item selected when
	 * the data changes.
	 *
	 * **Note:** When using a string as an item, make sure that the string does not
	 * contain any special characters (above all `"[]` characters). This limitation is
	 * due to the simplified way the {@link CKEDITOR.plugins.autocomplete.view}
	 * stores IDs in the DOM.
	 *
	 * @readonly
	 * @property {Number/String} id
	 */

	CKEDITOR.plugins.autocomplete = Autocomplete;
	Autocomplete.view = View;
	Autocomplete.model = Model;

	/**
	 * The autocomplete keystrokes used to finish autocompletion with the selected view item.
	 * This setting will set completing keystrokes for each autocomplete plugin respectively.
	 *
	 * To change completing keystrokes individually use the {@link CKEDITOR.plugins.autocomplete#commitKeystrokes} plugin property.
	 *
	 * ```javascript
	 * // Default configuration (9 = Tab, 13 = Enter).
	 * config.autocomplete_commitKeystrokes = [ 9, 13 ];
	 * ```
	 *
	 * Commit keystroke can also be disabled by setting it to an empty array.
	 *
	 * ```javascript
	 * // Disable autocomplete commit keystroke.
	 * config.autocomplete_commitKeystrokes = [];
	 * ```
	 *
	 * @since 4.10.0
	 * @cfg {Number/Number[]} [autocomplete_commitKeystrokes=[9, 13]]
	 * @member CKEDITOR.config
	 */
	CKEDITOR.config.autocomplete_commitKeystrokes = [ 9, 13 ];

	// Viewport on iOS is moved into iframe parent element because of https://bugs.webkit.org/show_bug.cgi?id=149264 issue.
	// Once upstream issue is resolved this function should be removed and its concurrences should be refactored to
	// follow the default code path.
	function iOSViewportElement( editor ) {
		return editor.window.getFrame().getParent();
	}

	function encodeItem( item ) {
		return CKEDITOR.tools.array.reduce( CKEDITOR.tools.object.keys( item ), function( cur, key ) {
			cur[ key ] = CKEDITOR.tools.htmlEncode( item[ key ] );
			return cur;
		}, {} );
	}

	/**
	 * Abstract class describing the definition of the [Autocomplete](https://ckeditor.com/cke4/addon/autocomplete) plugin configuration.
	 *
	 * It lists properties used to define and create autocomplete configuration definition.
	 *
	 * Simple usage:
	 *
	 * ```javascript
 	 * var definition = {
	 * 	dataCallback: dataCallback,
	 * 	textTestCallback: textTestCallback,
	 * 	throttle: 200
	 * };
	 * ```
	 *
	 * @class CKEDITOR.plugins.autocomplete.configDefinition
	 * @abstract
	 * @since 4.10.0
	 */

	/**
	 * Callback executed to get suggestion data based on the search query. The returned data will be
	 * displayed in the autocomplete view.
	 *
	 * ```javascript
	 *	// Returns (through its callback) the suggestions for the current query.
	 *	// Note: The itemsArray variable is the example "database".
	 *	function dataCallback( matchInfo, callback ) {
	 *		// Simple search.
	 *		// Filter the entire items array so only the items that start
	 *		// with the query remain.
	 *		var suggestions = itemsArray.filter( function( item ) {
	 *			return item.name.indexOf( matchInfo.query ) === 0;
	 *		} );
	 *
	 *		// Note: The callback function can also be executed asynchronously
	 *		// so dataCallback can do an XHR request or use any other asynchronous API.
	 *		callback( suggestions );
	 *	}
	 *
	 * ```
	 *
	 * @method dataCallback
	 * @param {CKEDITOR.plugins.autocomplete.matchInfo} matchInfo
	 * @param {Function} callback The callback which should be executed with the matched data.
	 * @param {CKEDITOR.plugins.autocomplete.model.item[]} callback.data The suggestion data that should be
	 * displayed in the autocomplete view for a given query. The data items should implement the
	 * {@link CKEDITOR.plugins.autocomplete.model.item} interface.
	 */

	/**
	 * Callback executed to check if a text next to the selection should open
	 * the autocomplete. See the {@link CKEDITOR.plugins.textWatcher}'s `callback` argument.
	 *
	 * ```javascript
	 *	// Called when the user types in the editor or moves the caret.
	 *	// The range represents the caret position.
	 *	function textTestCallback( range ) {
	 *		// You do not want to autocomplete a non-empty selection.
	 *		if ( !range.collapsed ) {
	 *			return null;
	 *		}
	 *
	 *		// Use the text match plugin which does the tricky job of doing
	 *		// a text search in the DOM. The matchCallback function should return
	 *		// a matching fragment of the text.
	 *		return CKEDITOR.plugins.textMatch.match( range, matchCallback );
	 *	}
	 *
	 *	// Returns a position of the matching text.
	 *	// It matches with a word starting from the '@' character
	 *  // up to the caret position.
	 *	function matchCallback( text, offset ) {
	 *			// Get the text before the caret.
	 *		var left = text.slice( 0, offset ),
	 *			// Will look for an '@' character followed by word characters.
	 *			match = left.match( /@\w*$/ );
	 *
	 *		if ( !match ) {
	 *			return null;
	 *		}
	 *		return { start: match.index, end: offset };
	 *	}
	 * ```
	 *
	 * @method textTestCallback
	 * @param {CKEDITOR.dom.range} range Range representing the caret position.
	 */

	/**
	 * @inheritdoc CKEDITOR.plugins.autocomplete#throttle
	 * @property {Number} [throttle]
	 */

	/**
	 * @inheritdoc CKEDITOR.plugins.autocomplete.model#itemsLimit
	 * @property {Number} [itemsLimit]
	 */

	/**
	 * @inheritdoc CKEDITOR.plugins.autocomplete.view#itemTemplate
	 * @property {String} [itemTemplate]
	 */

	/**
	 * @inheritdoc CKEDITOR.plugins.autocomplete#outputTemplate
	 * @property {String} [outputTemplate]
	 */

	/**
	 * Abstract class describing a set of properties that can be used to produce more adequate suggestion data based on the matched query.
	 *
	 * @class CKEDITOR.plugins.autocomplete.matchInfo
	 * @abstract
	 * @since 4.10.0
	 */

	/**
	 * The query string that was accepted by the
	 * {@link CKEDITOR.plugins.autocomplete.configDefinition#textTestCallback config.textTestCallback}.
	 *
	 * @property {String} query
	 */

	/**
	 * The range in the DOM indicating the position of the {@link #query}.
	 *
	 * @property {CKEDITOR.dom.range} range
	 */

	/**
	 * The {@link CKEDITOR.plugins.autocomplete Autocomplete} instance that matched the query.
	 *
	 * @property {CKEDITOR.plugins.autocomplete} autocomplete
	 */
} )();
