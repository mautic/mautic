/**
 * @license Copyright (c) 2003-2021, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or https://ckeditor.com/legal/ckeditor-oss-license
 */

'use strict';

( function() {

	CKEDITOR.plugins.add( 'textmatch', {} );

	/**
	 * A global namespace for methods exposed by the [Text Match](https://ckeditor.com/cke4/addon/textmatch) plugin.
	 *
	 * The most important function is {@link #match} which performs a text
	 * search in the DOM.
	 *
	 * @singleton
	 * @class
	 * @since 4.10.0
	 */
	CKEDITOR.plugins.textMatch = {};

	/**
	 * Allows to search in the DOM for matching text using a callback which operates on strings instead of text nodes.
	 * Returns {@link CKEDITOR.dom.range} and the matching text.
	 *
	 * ```javascript
	 *	var range = editor.getSelection().getRanges()[ 0 ];
	 *
	 *	CKEDITOR.plugins.textMatch.match( range, function( text, offset ) {
	 *		// Let's assume that text is 'Special thanks to #jo.' and offset is 21.
	 *		// The offset "21" means that the caret is between '#jo' and '.'.
	 *
	 *		// Get the text before the caret.
	 *		var left = text.slice( 0, offset ),
	 *			// Will look for a literal '#' character and at least two word characters.
	 *			match = left.match( /#\w{2,}$/ );
	 *
	 *		if ( !match ) {
	 *			return null;
	 *		}
	 *
	 *		// The matching fragment is the '#jo', which can
	 *		// be identified by the following offsets: { start: 18, end: 21 }.
	 *		return { start: match.index, end: offset };
	 *	} );
	 * ```
	 *
	 * @member CKEDITOR.plugins.textMatch
	 * @param {CKEDITOR.dom.range} range A collapsed range &mdash; the position from which the scanning starts.
	 * Usually the caret position.
	 * @param {Function} testCallback A callback executed to check if the text matches.
	 * @param {String} testCallback.text The full text to check.
	 * @param {Number} testCallback.rangeOffset An offset of the `range` in the `text` to be checked.
	 * @param {Object} [testCallback.return] The position of the matching fragment (`null` if nothing matches).
	 * @param {Number} testCallback.return.start The offset of the start of the matching fragment.
	 * @param {Number} testCallback.return.end The offset of the end of the matching fragment.
	 *
	 * @returns {Object/null} An object with information about the matching text or `null`.
	 * @returns {String} return.text The matching text.
	 * The text does not reflect the range offsets. The range could contain additional,
	 * browser-related characters like {@link CKEDITOR.dom.selection#FILLING_CHAR_SEQUENCE}.
	 * @returns {CKEDITOR.dom.range} return.range A range in the DOM for the text that matches.
	 */
	CKEDITOR.plugins.textMatch.match = function( range, callback ) {
		var textAndOffset = CKEDITOR.plugins.textMatch.getTextAndOffset( range ),
			fillingCharSequence = CKEDITOR.dom.selection.FILLING_CHAR_SEQUENCE,
			fillingSequenceOffset = 0;

		if ( !textAndOffset ) {
			return;
		}

		// Remove filling char sequence for clean query (#2038).
		if ( textAndOffset.text.indexOf( fillingCharSequence ) == 0 ) {
			fillingSequenceOffset = fillingCharSequence.length;

			textAndOffset.text = textAndOffset.text.replace( fillingCharSequence, '' );
			textAndOffset.offset -= fillingSequenceOffset;
		}

		var result = callback( textAndOffset.text, textAndOffset.offset );

		if ( !result ) {
			return null;
		}

		return {
			range: CKEDITOR.plugins.textMatch.getRangeInText( range, result.start, result.end + fillingSequenceOffset ),
			text: textAndOffset.text.slice( result.start, result.end )
		};
	};

	/**
	 * Returns a text (as a string) in which the DOM range is located (the function scans for adjacent text nodes)
	 * and the offset of the caret in that text.
	 *
	 * ## Examples
	 *
	 * * `{}` is the range position in the text node (it means that the text node is **not** split at that position).
	 * * `[]` is the range position in the element (it means that the text node is split at that position).
	 * * `.` is a separator for text nodes (it means that the text node is split at that position).
	 *
	 * Examples:
	 *
	 * ```
	 *	Input: <p>he[]llo</p>
	 *	Result: { text: 'hello', offset: 2 }
	 *
	 *	Input: <p>he.llo{}</p>
	 *	Result: { text: 'hello', offset: 5 }
	 *
	 *	Input: <p>{}he.ll<i>o</i></p>
	 *	Result: { text: 'hell', offset: 0 }
	 *
	 *	Input: <p>he{}<i>ll</i>o</p>
	 *	Result: { text: 'he', offset: 2 }
	 *
	 *	Input: <p>he<i>ll</i>o.m{}y.friend</p>
	 *	Result: { text: 'omyfriend', offset: 2 }
	 * ```
	 *
	 * @member CKEDITOR.plugins.textMatch
	 * @param {CKEDITOR.dom.range} range
	 * @returns {Object/null}
	 * @returns {String} return.text The text in which the DOM range is located.
	 * @returns {Number} return.offset An offset of the caret.
	 */
	CKEDITOR.plugins.textMatch.getTextAndOffset = function( range ) {
		if ( !range.collapsed ) {
			return null;
		}

		var text = '', offset = 0,
			textNodes = CKEDITOR.plugins.textMatch.getAdjacentTextNodes( range ),
			nodeReached = false,
			elementIndex,
			startContainerIsText = ( range.startContainer.type != CKEDITOR.NODE_ELEMENT );

		if ( startContainerIsText ) {
			// Determining element index in textNodes array.
			elementIndex = indexOf( textNodes, function( current ) {
				return range.startContainer.equals( current );
			} );
		} else {
			// Based on range startOffset decreased by first text node index.
			elementIndex = range.startOffset - ( textNodes[ 0 ] ? textNodes[ 0 ].getIndex() : 0 );
		}

		var max = textNodes.length;
		for ( var i = 0; i < max; i += 1 ) {
			var currentNode = textNodes[ i ];
			text += currentNode.getText();

			// We want to increase text offset only when startContainer is not reached.
			if ( !nodeReached ) {
				if ( startContainerIsText ) {
					if ( i == elementIndex ) {
						nodeReached = true;
						offset += range.startOffset;
					} else {
						offset += currentNode.getText().length;
					}
				} else {
					if ( i == elementIndex ) {
						nodeReached = true;
					}

					// In below example there are three text nodes in p element and four possible offsets ( 0, 1, 2, 3 )
					// We are going to increase offset while iteration:
					// index 0 ==> 0
					// index 1 ==> 3
					// index 2 ==> 3 + 3
					// index 3 ==> 3 + 3 + 2

					// <p> foo bar ba </p>
					//    0^^^1^^^2^^3
					if ( i > 0 ) {
						offset += textNodes[ i - 1 ].getText().length;
					}

					// If element index at last element we also want to increase offset.
					if ( max == elementIndex && i + 1 == max ) {
						offset += currentNode.getText().length;
					}
				}
			}
		}

		return {
			text: text,
			offset: offset
		};
	};

	/**
	 * Transforms the `start` and `end` offsets in the text generated by the {@link #getTextAndOffset}
	 * method into a DOM range.
	 *
	 * ## Examples
	 *
	 * * `{}` is the range position in the text node (it means that the text node is **not** split at that position).
	 * * `.` is a separator for text nodes (it means that the text node is split at that position).
	 *
	 * Examples:
	 *
	 * ```
	 *	Input: <p>f{}oo.bar</p>, 0, 3
	 *	Result: <p>{foo}.bar</p>
	 *
	 *	Input: <p>f{}oo.bar</p>, 1, 5
	 *	Result: <p>f{oo.ba}r</p>
	 * ```
	 *
	 * @member CKEDITOR.plugins.textMatch
	 * @param {CKEDITOR.dom.range} range
	 * @param {Number} start A start offset.
	 * @param {Number} end An end offset.
	 * @returns {CKEDITOR.dom.range} Transformed range.
	 */
	CKEDITOR.plugins.textMatch.getRangeInText = function( range, start, end ) {
		var resultRange = new CKEDITOR.dom.range( range.root ),
			elements = CKEDITOR.plugins.textMatch.getAdjacentTextNodes( range ),
			startData = findElementAtOffset( elements, start ),
			endData = findElementAtOffset( elements, end );

		resultRange.setStart( startData.element, startData.offset );
		resultRange.setEnd( endData.element, endData.offset );

		return resultRange;
	};

	/**
	 * Creates a collection of adjacent text nodes which are between DOM elements, starting from the given range.
	 * This function works only for collapsed ranges.
	 *
	 * ## Examples
	 *
	 * * `{}` is the range position in the text node (it means that the text node is **not** split at that position).
	 * * `.` is a separator for text nodes (it means that the text node is split at that position).
	 *
	 * Examples:
	 *
	 * ```
	 *	Input: <p>he.llo{}</p>
	 *	Result: [ 'he', 'llo' ]
	 *
	 *	Input: <p>{}he.ll<i>o</i></p>
	 *	Result:  [ 'he', 'll' ]
	 *
	 *	Input: <p>he{}<i>ll</i>o.</p>
	 *	Result:  [ 'he' ]
	 *
	 *	Input: <p>he<i>ll</i>{}o.my.friend</p>
	 *	Result: [ 'o', 'my', 'friend' ]
	 * ```
	 *
	 * @member CKEDITOR.plugins.textMatch
	 * @param {CKEDITOR.dom.range} range
	 * @return {CKEDITOR.dom.text[]} An array of text nodes.
	 */
	CKEDITOR.plugins.textMatch.getAdjacentTextNodes = function( range ) {
		if ( !range.collapsed ) {
			throw new Error( 'Range must be collapsed.' ); // %REMOVE_LINE%
			// Reachable in prod mode.
			return []; // jshint ignore:line
		}

		var collection = [],
			siblings,
			elementIndex,
			node, i;

		if ( range.startContainer.type != CKEDITOR.NODE_ELEMENT ) {
			siblings = range.startContainer.getParent().getChildren();
			elementIndex = range.startContainer.getIndex();
		} else {
			siblings = range.startContainer.getChildren();
			elementIndex = range.startOffset;
		}

		i = elementIndex;
		while ( node = siblings.getItem( --i ) ) {
			if ( node.type == CKEDITOR.NODE_TEXT ) {
				collection.unshift( node );
			} else {
				break;
			}
		}

		i = elementIndex;
		while ( node = siblings.getItem( i++ ) ) {
			if ( node.type == CKEDITOR.NODE_TEXT ) {
				collection.push( node );
			} else {
				break;
			}
		}

		return collection;
	};

	function findElementAtOffset( elements, offset ) {
		var max = elements.length,
			currentOffset = 0;
		for ( var i = 0; i < max; i += 1 ) {
			var current = elements[ i ];
			if ( offset >= currentOffset && currentOffset + current.getText().length >= offset ) {
				return {
					element: current,
					offset: offset - currentOffset
				};
			}

			currentOffset += current.getText().length;
		}

		return null;
	}

	function indexOf( arr, checker ) {
		for ( var i = 0; i < arr.length; i++ ) {
			if ( checker( arr[ i ] ) ) {
				return i;
			}
		}

		return -1;
	}
} )();
