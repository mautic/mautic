(function( $ ) {
    $.ui.ddmanager.frameOffsets = {};

    //Override droppable offsets in order to account for scrollable divs
    $.ui.ddmanager.prepareOffsets = function (t, event) {
        var i, j,
            m = $.ui.ddmanager.droppables[ t.options.scope ] || [],
            type = event ? event.type : null, // workaround for #2317
            list = ( t.currentItem || t.element ).find(":data(ui-droppable)").addBack();

        droppablesLoop: for (i = 0; i < m.length; i++) {

            // No disabled and non-accepted
            if (m[ i ].options.disabled || ( t && !m[ i ].accept.call(m[ i ].element[ 0 ], ( t.currentItem || t.element )) )) {
                continue;
            }

            // Filter out elements in the current dragged item
            for (j = 0; j < list.length; j++) {
                if (list[ j ] === m[ i ].element[ 0 ]) {
                    m[ i ].proportions().height = 0;
                    continue droppablesLoop;
                }
            }

            m[ i ].visible = m[ i ].element.css("display") !== "none";
            if (!m[ i ].visible) {
                continue;
            }

            // Activate the droppable if used directly from draggables
            if (type === "mousedown") {
                m[ i ]._activate.call(m[ i ], event);
            }

            m[ i ].offset = m[ i ].element.offset();

            if (t.options.iframeId) {
                var scrollOffset = $('#' + t.options.iframeId).contents().scrollTop();
                var newTop = m[ i ].offset.top - scrollOffset;
                m[ i ].offset = {top: newTop, left: m[ i ].offset.left};
            }

            m[ i ].proportions({width: m[ i ].element[ 0 ].offsetWidth, height: m[ i ].element[ 0 ].offsetHeight});
        }

    };

    $.widget("ui.draggable", $.extend({}, $.ui.draggable.prototype, {
        _generatePosition: function( event, constrainPosition ) {

            var containment, co, top, left,
                o = this.options,
                scrollIsRootNode = this._isRootNode( this.scrollParent[ 0 ] ),
                pageX = event.pageX,
                pageY = event.pageY;

            // Cache the scroll
                if (!scrollIsRootNode || !this.offset.scroll) {
                    this.offset.scroll = {
                        top: this.scrollParent.scrollTop(),
                        left: this.scrollParent.scrollLeft()
                    };
                }


            /*
             * - Position constraining -
             * Constrain the position to a mix of grid, containment.
             */

            // If we are not dragging yet, we won't check for options
            if ( constrainPosition ) {
                if ( this.containment ) {
                    if ( this.relativeContainer ){
                        co = this.relativeContainer.offset();
                        containment = [
                            this.containment[ 0 ] + co.left,
                            this.containment[ 1 ] + co.top,
                            this.containment[ 2 ] + co.left,
                            this.containment[ 3 ] + co.top
                        ];
                    } else {
                        containment = this.containment;
                    }

                    if (event.pageX - this.offset.click.left < containment[0]) {
                        pageX = containment[0] + this.offset.click.left;
                    }
                    if (event.pageY - this.offset.click.top < containment[1]) {
                        pageY = containment[1] + this.offset.click.top;
                    }
                    if (event.pageX - this.offset.click.left > containment[2]) {
                        pageX = containment[2] + this.offset.click.left;
                    }
                    if (event.pageY - this.offset.click.top > containment[3]) {
                        pageY = containment[3] + this.offset.click.top;
                    }
                }

                if (o.grid) {
                    //Check for grid elements set to 0 to prevent divide by 0 error causing invalid argument errors in IE (see ticket #6950)
                    top = o.grid[1] ? this.originalPageY + Math.round((pageY - this.originalPageY) / o.grid[1]) * o.grid[1] : this.originalPageY;
                    pageY = containment ? ((top - this.offset.click.top >= containment[1] || top - this.offset.click.top > containment[3]) ? top : ((top - this.offset.click.top >= containment[1]) ? top - o.grid[1] : top + o.grid[1])) : top;

                    left = o.grid[0] ? this.originalPageX + Math.round((pageX - this.originalPageX) / o.grid[0]) * o.grid[0] : this.originalPageX;
                    pageX = containment ? ((left - this.offset.click.left >= containment[0] || left - this.offset.click.left > containment[2]) ? left : ((left - this.offset.click.left >= containment[0]) ? left - o.grid[0] : left + o.grid[0])) : left;
                }

                if ( o.axis === "y" ) {
                    pageX = this.originalPageX;
                }

                if ( o.axis === "x" ) {
                    pageY = this.originalPageY;
                }
            }

            return {
                top: (
                    pageY -                                                                 // The absolute mouse position
                    this.offset.click.top   -                                               // Click offset (relative to the element)
                    this.offset.relative.top -                                              // Only for relative positioned nodes: Relative offset from element to offset parent
                    this.offset.parent.top +                                                // The offsetParent's offset without borders (offset + border)
                    ( this.cssPosition === "fixed" ? -this.offset.scroll.top : ( scrollIsRootNode ? 0 : this.offset.scroll.top ) )
                ),
                left: (
                    pageX -                                                                 // The absolute mouse position
                    this.offset.click.left -                                                // Click offset (relative to the element)
                    this.offset.relative.left -                                             // Only for relative positioned nodes: Relative offset from element to offset parent
                    this.offset.parent.left +                                               // The offsetParent's offset without borders (offset + border)
                    ( this.cssPosition === "fixed" ? -this.offset.scroll.left : ( scrollIsRootNode ? 0 : this.offset.scroll.left ) )
                )
            };

        }
    }));
}) (jQuery);