jQuery.fn.extend({
    jQuery2Offset: function (options) {
        if (arguments.length) {
            return options === undefined ?
                this :
                this.each(function (i) {
                    jQuery.offset.setOffset(this, options, i);
                });
        }

        var docElem, win,
            elem = this[ 0 ],
            box = { top: 0, left: 0 },
            doc = elem && elem.ownerDocument;

        if (!doc) {
            return;
        }

        docElem = doc.documentElement;

        // Make sure it's not a disconnected DOM node
        if (!jQuery.contains(docElem, elem)) {
            return box;
        }

        // If we don't have gBCR, just use 0,0 rather than error
        // BlackBerry 5, iOS 3 (original iPhone)
        if (typeof elem.getBoundingClientRect !== strundefined) {
            box = elem.getBoundingClientRect();
        }
        win = getWindow(doc);
        var pageYOffset = win ? win.pageYOffset : 0;
        var pageXOffset = win ? win.pageXOffset : 0;
        return {
            top: box.top + pageYOffset - docElem.clientTop,
            left: box.left + pageXOffset - docElem.clientLeft
        };
    },
});