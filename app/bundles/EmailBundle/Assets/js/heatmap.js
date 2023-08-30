(function(window, document, Mautic, $) {
    class Heatmap {
        constructor(emailId) {
            this.emailId = emailId;
            this.content = null;
            this.clickStats = null;
            this.$modal = null;
            this.$iframe = null;
            this.$iframeBody = null;
            this.iframeDocument = null;
        }

        init() {
            this.fetchHeatmap(this.render.bind(this));
        }

        render() {
            this.renderModal();
            this.bindEvents();
            this.$modal.modal('show');
        }

        fetchHeatmap(callback) {
            Mautic.ajaxActionRequest('email:heatmap', {id: this.emailId}, function(response){
                if (response.success) {
                    this.content = response.content;
                    this.clickStats = response.clickStats;
                    callback();
                }
            }.bind(this), false, true, "GET");
        }

        waitForIframeContent(callback) {
            const self = this;
            const interval = setInterval(function () {
                const height = self.$iframeBody.height();
                if (height > 0 && self.lastHeight === height) {
                    callback();
                    clearInterval(interval);
                } else {
                    self.lastHeight = height;
                }
            }, 100);
        }

        bindEvents() {
            const self = this;

            self.$iframe[0].addEventListener('load', function() {
                self.waitForIframeContent(self.renderLabels.bind(self));
            });

            $(window).on('resize', function() {
                self.labelPositions();
            });

            self.$modal.on('hidden.bs.modal', function () {
                $(this).remove();
            });
        }

        renderModal() {
            this.$modal = $("<div />").attr({"class": "modal fade heatmap-modal"});
            const $modalDialogDiv = $("<div />").attr({"class": "modal-dialog modal-dialog-heatmap"});
            const $modalContentDiv = $("<div />").attr({"class": "modal-content"});
            this.$iframe = $('<iframe class="heatmap-iframe">' + this.content + '</iframe>');
            $modalContentDiv.append(this.$iframe);

            this.$modal.append($modalDialogDiv.append($modalContentDiv));
            $('body').append(this.$modal);

            this.iframeDocument = this.$iframe[0].contentDocument || this.$iframe[0].contentWindow.document;
            this.iframeDocument.open();
            this.iframeDocument.write(this.content);

            const cssLink = document.createElement("link");
            cssLink.href = "/app/bundles/EmailBundle/Assets/css/heatmap.css?v=" + (Math.random() + 1).toString(36).substring(7);
            cssLink.rel = "stylesheet";
            cssLink.type = "text/css";
            this.iframeDocument.head.appendChild(cssLink);

            this.$iframeBody = $('body', this.iframeDocument);
            this.$iframeBody.addClass('heatmap-iframe-body');
            this.$iframeBody.append('<div class="heatmap-backdrop"></div>');
            this.iframeDocument.close();
        }

        renderLabels() {
            const self = this;
            self.clickStats.forEach(function(link) {
                const $a = $('a[href="' + link.url + '"]', self.$iframeBody);
                $a.addClass('heatmap-link');
                $a.each(function() {
                    const $el = $(this);
                    const $label = $('<div class="heatmap-label">' + link.unique_hits + ' clicks</div>');
                    self.$iframeBody.append($label);
                    $el.data('heatmap-label', $label);
                });
            });
            self.labelPositions();
        }

        labelPositions() {
            const self = this;
            const $links = $('a', self.$iframeBody);

            $links.each(function() {
                const $el = $(this);
                const $label = $el.data('heatmap-label');
                const position = $el.position();
                $label.css({
                    top: position.top + $el.outerHeight(),
                    left: position.left,
                    width: $el.outerWidth()
                });
            });
        }
    }

    $(document).ready(function() {
        $('[data-toggle="email-heatmap"]').click(function(e) {
            const emailId = $(this).data('email');
            const heatmap = new Heatmap(emailId);
            heatmap.init();
            console.log(heatmap);
            e.preventDefault();
        });
    });

})(window, document, Mautic, mQuery);
