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

SlideshowManager.slotConfigs = {};
SlideshowManager.urlobj;

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

SlideshowManager.BrowseServer = function(obj)
{
    SlideshowManager.urlobj = obj;
    SlideshowManager.OpenServerBrowser(
    mauticBasePath + '/' + mauticAssetPrefix + 'app/bundles/CoreBundle/Assets/js/libraries/ckeditor/filemanager/index.html?type=images',
    screen.width * 0.7,
    screen.height * 0.7 ) ;
}

SlideshowManager.OpenServerBrowser = function( url, width, height )
{
    var iLeft = (screen.width - width) / 2 ;
    var iTop = (screen.height - height) / 2 ;
    var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes" ;
    sOptions += ",width=" + width ;
    sOptions += ",height=" + height ;
    sOptions += ",left=" + iLeft ;
    sOptions += ",top=" + iTop ;
    var oWindow = window.open( url, "BrowseWindow", sOptions ) ;
}

function SetUrl( url, width, height, alt )
{
    document.getElementById(SlideshowManager.urlobj).value = url ;
    oWindow = null;
}
