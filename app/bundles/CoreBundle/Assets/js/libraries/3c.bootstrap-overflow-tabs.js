/* ===================================================
 * bootstrap-overflow-navs.js v0.4
 * ===================================================
 * Copyright 2012-15 Michael Langford, Evan Owens
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */

+function ($) { "use strict";
    /**
     * options:
     *		more - translated "more" text
     *		offset - dimension that needs to be subtracted from the parent div dimension
     */
    $.fn.overflowNavs = function(options) {
        // Create a handle to our ul menu
        // @todo Implement some kind of check to make sure there is only one?  If we accidentally get more than one
        // then strange things happen
        var ul = $(this);

        // This should work with all navs, not just the navbar, so you should be able to pass a parent in
        var parent     = $(this).closest(options.parent);
        var isVertical = $(this).hasClass('tabs-left') || $(this).hasClass('tabs-right');

        if (!options.offset) {
            options.offset = {};
        }

        if (!isVertical) {
            $(ul).css(
                {
                    'height': '42px',
                    'overflow-y': 'hidden'
                }
            );
        }

        // Check if it is a navbar and twitter bootstrap collapse is in use
        var collapse = $('div.nav-collapse').length; // Boostrap < 2
        if(!collapse) {
            var collapse = $('div.navbar-collapse').length; // Boostrap > 2
        }

        // Check if bootstrap navbar is collapsed (mobile)
        if(collapse) {
            var collapsed = $('.btn-navbar').is(":visible"); // Boostrap < 2
            if(!collapsed) {
                var collapsed = $('.navbar-toggle').is(":visible"); // Boostrap > 2
            }
        }
        else {
            var collapsed = false;
        }

        // Only process dropdowns if not collapsed
        if(collapsed === false) {

            // Get dimension of the navbar parent so we know how much room we have to work with
            var parent_dimension = (isVertical) ? $(parent).height() - (options.offset.height ? parseInt(options.offset.height) : 0) : $(parent).width() - (options.offset.width ? parseInt(options.offset.width) : 0);

            // Find an already existing .overflow-nav dropdown
            var dropdown = $('li.overflow-nav', ul);

            // Create one if none exists
            if (! dropdown.length) {
                dropdown = $('<li class="overflow-nav dropdown"></li>');
                if (!isVertical) {
                    dropdown.addClass('pull-right');
                }
                dropdown.append($('<a class="dropdown-toggle" data-toggle="dropdown" href="#"><span class="overflow-count"></span><b class="caret"></b></a>'));
                dropdown.append($('<ul class="dropdown-menu"></ul>'));
            }

            // Get the dimension of the navbar, need to add together <li>s as the ul wraps in bootstrap
            var dimension = (isVertical) ? 42 : 100;
            ul.children('li').not('li.dropdown').each(function() {
                dimension += (isVertical) ? $(this).outerHeight(true) : $(this).outerWidth(true);
            });

            // Window is shrinking
            if (dimension >= parent_dimension) {
                // Loop through each non-dropdown li in the ul menu from right to left (using .get().reverse())
                $($('li', ul).not('.dropdown').get().reverse()).each(function() {
                    // Get the dimension of the navbar
                    var dimension = (isVertical) ? 42 : 100; // Allow for padding
                    ul.children('li').each(function() {
                        var $this = $(this);
                        dimension += (isVertical) ? $this.outerHeight(true) : $this.outerWidth(true);
                    });
                    // Count tabs in the drop down as well
                    if (dimension >= parent_dimension) {
                        // Remember the original dimension so that we can restore as the window grows
                        $(this).attr('data-original-dimension', (isVertical) ? $(this).outerHeight(true) : $(this).outerWidth(true));
                        // Move the rightmost item to top of dropdown menu if we are running out of space
                        dropdown.children('ul.dropdown-menu').prepend(this);
                    }
                    // @todo on shrinking resize some menu items are still in drop down when bootstrap mobile navigation is displaying
                });
            }

            // Window is growing
            else {
                // We used to just look at the first one, but this doesn't work when the window is maximized
                //var dropdownFirstItem = dropdown.children('ul.dropdown-menu').children().first();
                dropdown.children('ul.dropdown-menu').children().each(function() {
                    dimension += parseInt($(this).attr('data-original-dimension'));
                    if (dimension < parent_dimension) {
                        // Restore the topmost dropdown item to the main menu
                        dropdown.before(this);
                    }
                    else {
                        // If the topmost item can't be restored, don't look any further
                        return false;
                    }
                });
            }

            // Remove or add dropdown depending on whether or not it contains menu items
            if (! dropdown.children('ul.dropdown-menu').children().length) {
                dropdown.remove();
            }
            else {
                // Append new dropdown menu to main menu iff it doesn't already exist
                if (! ul.children('li.overflow-nav').length) {
                    ul.append(dropdown);
                }
            }
        } else {
            // Find an already existing .overflow-nav dropdown
            var dropdown = $('li.overflow-nav', ul);
            if (dropdown.length) {
                // Restore dropdown items to the main menu
                dropdown.children('ul.dropdown-menu').children().each(function() {
                    dropdown.before(this);
                });
                // Remove the dropdown menu
                dropdown.remove();
            }
        }

        // Update overflow tab count
        dropdown.find('.dropdown-toggle .overflow-count').text(dropdown.find('ul.dropdown-menu li').length+" "+options.more);

        if (isVertical) {
            dropdown.find('ul.dropdown-menu').css('width', '100%');
        }

        if (!isVertical) {
            $(ul).css(
                {
                    'height': 'auto',
                    'overflow-y': 'inherit'
                }
            );
        }
    };

}(window.jQuery);