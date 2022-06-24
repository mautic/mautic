/*
 * jQuery UI Autocomplete HTML Extension
 *
 * Copyright 2010, Scott González (http://scottgonzalez.com)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 *
 * http://github.com/scottgonzalez/jquery-ui-extensions
 */
(function( $ ) {

    var proto = $.ui.autocomplete.prototype,
        initSource = proto._initSource;

    function filter( array, term ) {
        var matcher = new RegExp( $.ui.autocomplete.escapeRegex(term), "i" );
        return $.grep( array, function(value) {
            return matcher.test( $( "<div>" ).html( value.companycity || value.companystate || value.value || value ).text() );
        });
    }

    $.extend( proto, {
        _initSource: function() {
            if ( this.options.html && $.isArray(this.options.source) ) {
                this.source = function( request, response ) {
                    response( filter( this.options.source, request.term ) );
                };
            } else {
                initSource.call( this );
            }
        },

        _renderItem: function( ul, item) {
            let listHtml = item.value;
            if (item.companystate || item.companycity)
            {
                listHtml+= '&nbsp;&nbsp; - ';
                if (item.companycity)
                {
                    listHtml+= item.companycity;
                }
                if (item.companystate && item.companycity)
                {
                    listHtml+= ', ';
                }
                if (item.companystate)
                {
                    listHtml+= item.companystate;
                }
            }

            return $( "<li></li>" )
                .data( "item.autocomplete", item )
                .append( $( "<a></a>" )[ this.options.html ? "html" : "text" ]( listHtml) )
                .appendTo( ul );
        }
    });

})( jQuery );