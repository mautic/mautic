mQuery(document).ready(function () {
    mQuery('.dropdown-toggle').dropdown();
    mQuery('[data-toggle="tooltip"]').tooltip();
    mQuery('input[data-toggle="color"]').pickAColor({
        fadeMenuToggle: false,
        inlineDropdown: true
    });
    mQuery('*[data-toggle="sortablelist"]').sortable({
        placeholder: 'list-group-item ui-placeholder-highlight',
        handle: '.ui-sort-handle',
        start: function(event, ui) {
            mQuery('.ui-placeholder-highlight').append('<i class="fa fa-arrow-right"></i>');
        }
    });

    CKEDITOR.disableAutoInline = true;
    mQuery("div[contenteditable='true']").each(function (index) {
        var content_id = mQuery(this).attr('id');
        var that = this;
        CKEDITOR.inline(content_id, {
            toolbar: 'advanced',
            // Inline mode seems to ignore this but leaving anyway
            allowedContent: true,
            // Allow any attributes and prevent conversion of height/width attributes to styles
            extraAllowedContent: '*{*}; img[height,width]; table[height,width]',
            on: {
                // Remove inserted <p /> tag if empty to allow the CSS3 placeholder to display
                blur: function( event ) {
                    var data = event.editor.getData();
                    if (!data) {
                        mQuery(that).html('');
                    }
                },
                instanceReady: function( event ) {
                    var data = event.editor.getData();
                    if (!data) {
                        mQuery(that).html('');
                    }
                }
            }
        });
    });

    mQuery("[data-remove-slide]").change(function () {
        SlideshowManager.removeSlide(mQuery(this));
    });
});

var SlideshowManager = {};

SlideshowManager.toggleFileOpened = false;
SlideshowManager.slotConfigs = {};

// add newProp (dot separated string) to obj with new value
SlideshowManager.addValueToObj = function (obj, newProp, value) {
    var path = newProp.split(":");
    for (var i = 0, tmp = obj; i < path.length - 1; i++) {
        if (typeof tmp[path[i]] === 'undefined') {
            tmp = tmp[path[i]] = {};
        } else {
            tmp = tmp[path[i]]
        }
    }
    tmp[path[i]] = value;
}

SlideshowManager.updateOrder = function () {
    mQuery('.list-of-slides ul.list-group').find('a.steps').each(function (index, link) {
        mQuery(mQuery(link).attr('href')).find('input.slide-order').val(index);
    });
}

SlideshowManager.removeSlide = function (checkbox) {
    var slideId = checkbox.attr('[data-remove-slide]');
    var remove = checkbox.is(':checked');
    mQuery('.list-of-slides li.active a').toggleClass('stroked');
    mQuery('.tab-pane.active input[type="text"').prop('disabled', remove);
    if (remove) {
        checkbox.parent().addClass('text-danger');
    } else {
        checkbox.parent().removeClass('text-danger');
    }
}

SlideshowManager.buildConfigObject = function (slot) {
    var allSlotConfigs = mQuery('[data-slot-config=\"' + slot + '\"]');
    allSlotConfigs.each(function (index, value) {
        element = mQuery(this);
        var slotConfigPath = element.attr('name');
        var value = element.val();

        if (element.attr('type') === 'checkbox' || element.attr('type') === 'radio') {
            value = element.is(':checked');
        }

        if (typeof SlideshowManager.slotConfigs[slot] === 'undefined') {
            SlideshowManager.slotConfigs[slot] = {};
        }

        SlideshowManager.addValueToObj(SlideshowManager.slotConfigs[slot], slotConfigPath, value);
    });
}

SlideshowManager.saveConfigObject = function (slot) {
    SlideshowManager.updateOrder();
    SlideshowManager.buildConfigObject(slot);

    // remove slides which should be removed
    var slides = [];
    mQuery.each(SlideshowManager.slotConfigs[slot].slides, function (index, slide) {
        if (slide.remove) {
            delete SlideshowManager.slotConfigs[slot].slides[index];
        } else {
            slides.push(slide);
        }
    });
    SlideshowManager.slotConfigs[slot].slides = slides;

    var jsonObject = {};
    jsonObject[slot] = JSON.stringify(SlideshowManager.slotConfigs[slot]);

    Mautic.saveBuilderContent('page', mQuery('#builder_entity_id').val(), jsonObject, function() {
        document.location.href = document.location.href;
    });
}

SlideshowManager.toggleFileManager = function () {
    var listOfSlides = mQuery('.modal.slides-config .list-of-slides li:not(.active)');
    var activeSlide = mQuery('.modal.slides-config .list-of-slides li.active');
    var configFields = mQuery('.modal.slides-config .config-fields .row:not(:last-child)');
    var fileManager = mQuery('#fileManager');
    var newSlideBtn = mQuery('.btn.new-slide');
    var handle = mQuery('.list-of-slides .ui-sortable-handle');

    listOfSlides.animate({
        opacity: "toggle",
        padding: "toggle",
        height: "toggle"
    }, 300);
    configFields.animate({
        opacity: "toggle",
        padding: "toggle",
        height: "toggle"
    }, 300);
    fileManager.animate({
        height: "toggle",
        opacity: "toggle"
    }, 300);
    newSlideBtn.animate({
        height: "toggle",
        opacity: "toggle"
    }, 300);
    handle.animate({
        opacity: "toggle"
    }, 300);

    if (SlideshowManager.toggleFileOpened) {
        activeSlide.animate({
            borderRadius: "0px"
        }, 500, function () {
            activeSlide.removeAttr('style');
        });
    } else {
        activeSlide.animate({
            borderRadius: "21px"
        }, 500);
    }

    SlideshowManager.toggleFileOpened = !SlideshowManager.toggleFileOpened;
}

SlideshowManager.newSlide = function () {
    var tabPaneExisting = mQuery('.config-fields .tab-pane').first();

    // get slot name
    var slotName = tabPaneExisting.find('[data-slot-config]').attr('data-slot-config');

    // get valid slide ID
    SlideshowManager.buildConfigObject(slotName);
    var slideCount = 0;
    for (i in SlideshowManager.slotConfigs[slotName].slides) {
        if (SlideshowManager.slotConfigs[slotName].slides.hasOwnProperty(i)) {
            slideCount++;
        }
    }
    var newSlideId = slideCount;

    // copy tab-pane
    var tabPaneNew = tabPaneExisting.clone().attr('id', 'slide-tab-' + newSlideId).removeClass('in active');
    tabPaneNew.find('input').each(function (index, value) {
        var input = $(this);
        input.val('');
        var name = input.attr('name');
        var newName = name.replace(/:\d+:/, ':' + newSlideId + ':');
        input.attr('id', newName).attr('name', newName);

    });
    tabPaneExisting.parent().append(tabPaneNew);

    // copy list-group-item
    var listGroupItemExisting = mQuery('.list-of-slides .list-group .list-group-item').first();
    var listGroupItemNew = listGroupItemExisting.clone().removeClass('active');
    listGroupItemNew.find('a').attr('href', '#slide-tab-' + newSlideId);
    listGroupItemNew.find('.slide-id').text(newSlideId);
    listGroupItemExisting.parent().append(listGroupItemNew);
}

SlideshowManager.preloadFileManager = function () {
    filebrowserImageBrowseUrl = mauticBasePath + '/' + mauticAssetPrefix + 'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/filemanager/index.html?type=images';
    var iframe = mQuery("<iframe id='filemanager_iframe' />").attr({src: filebrowserImageBrowseUrl});
    mQuery("#fileManager").hide().append(iframe);
    iframe.load(function () {
        var fileManager = mQuery('#filemanager_iframe').contents().find('body');
        fileManager.click(function () {
            var copyBtn = fileManager.find('#copy-button');
            if (copyBtn.length) {
                mQuery('.tab-pane.active.in input.background-image').val(copyBtn.attr('data-clipboard-text'));
            }
        });
    });
}
