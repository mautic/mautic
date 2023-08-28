(function(Mautic, mQuery) {
    class Heatmap {
        constructor(emailId) {
            this.emailId = emailId;
        }

        init() {
            const self = this;

            Mautic.ajaxActionRequest('email:heatmap', {id: this.emailId}, function(response){
                if (response.success) {
                    self.content = response.content;
                    self.renderModal();
                }
            }, false, true, "GET");
        }

        renderModal() {
            const $modalContainer = mQuery("<div />").attr({"class": "modal fade heatmap-modal"});
            const $modalDialogDiv = mQuery("<div />").attr({"class": "modal-dialog"});
            const $modalContentDiv = mQuery("<div />").attr({"class": "modal-content"});

            const $iframe = mQuery('<iframe class="heatmap-iframe">' + this.content + '</iframe>');
            $modalContentDiv.append($iframe);


            $modalContainer.append($modalDialogDiv.append($modalContentDiv));
            mQuery('body').append($modalContainer);

            const iframeDocument = $iframe[0].contentDocument || $iframe[0].contentWindow.document;
            iframeDocument.open();
            iframeDocument.write(this.content);
            iframeDocument.close();

            mQuery('.heatmap-modal').on('hidden.bs.modal', function () {
                mQuery(this).remove();
            });

            mQuery('.heatmap-modal').modal('show');
        }
    }

    mQuery(document).ready(function() {
        mQuery('[data-email-heatmap]').click(function(e) {
            const emailId = mQuery(this).data('email-heatmap');
            const heatmap = new Heatmap(emailId);
            heatmap.init();
        });
    });

})(Mautic, mQuery);
