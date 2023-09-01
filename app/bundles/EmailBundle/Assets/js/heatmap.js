(function(window, document, Mautic, $, Math) {
    class Heatmap {
        constructor(emailId) {
            this.emailId = emailId;
            this.content = null;
            this.clickStats = null;
            this.$modal = null;
            this.$iframe = null;
            this.$iframeBody = null;
            this.iframeDocument = null;
            this.totalUniqueClicks = null;
            this.legendTemplate = null;
            this.links = [];
            this.gradient = [
                [44, 59, 182],   // #2c3bb6
                [10, 133, 255],  // #0a85ff
                [240, 223, 66],  // #f0df42
                [248, 195, 68],  // #f8c344
                [255, 132, 58],  // #ff843a
                [248, 56, 52]    // #f83834
            ];
        }

        init() {
            this.fetchHeatmap(function() {
                this.render();
            }.bind(this));
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
                    this.totalUniqueClicks = response.totalUniqueClicks;
                    this.legendTemplate = response.legendTemplate;
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
                self.waitForIframeContent(function() {
                    self.renderLabels();
                    self.bindMouseEvents();
                });
            });

            $(window).on('resize', function() {
                self.labelPositions();
            });

            self.$modal.on('hidden.bs.modal', function () {
                $(this).remove();
            });
        }

        bindMouseEvents() {
            const self = this;

            const moveUp = function() {
                const $label = $(this).hasClass('heatmap-link') ? $(this).data('heatmap-label') : $(this);
                $label.css('z-index', 2050);
            }
            const moveDown = function() {
                const $label = $(this).hasClass('heatmap-link') ? $(this).data('heatmap-label') : $(this);
                $label.css('z-index', 1050);
            }

            const $heatmapElements = $('.heatmap-label, a.heatmap-link', self.$iframeBody);
            $heatmapElements.on('mouseenter focus', moveUp);
            $heatmapElements.on('mouseleave blur', moveDown);
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
            $modalContentDiv.append(this.legendTemplate);
            this.iframeDocument.close();
        }

        renderLabels() {
            const self = this;

            self.clickStats.forEach(function(link) {
                const $a = $('a[href="' + link.url + '"]', self.$iframeBody);
                $a.addClass('heatmap-link');
                $a.each(function() {
                    const $el = $(this);
                    self.links.push($el);
                    const uniqueHitsPercent =  parseFloat((link.unique_hits_rate * 100).toFixed(2));
                    const text =  link.unique_hits_text + ' (' + uniqueHitsPercent.toString() + '%)';
                    const $label = $('<div class="heatmap-label"><p>' + text + '</p></div>');
                    const bgColor = self.interpolateColor(link.unique_hits_rate);
                    const bgColorLeft = self.interpolateColor(link.unique_hits_rate - 0.1);
                    const bgColorRight = self.interpolateColor(link.unique_hits_rate + 0.1);
                    $label.css({
                        'background-color': bgColor,
                        'background': 'linear-gradient(to right, ' +bgColorLeft+ ', '+ bgColorRight +')'
                    });
                    const $border = $('<div class="heatmap-label-border"></div>');
                    $border.css({
                        'border': '1px dashed ' + bgColor,
                        'border-bottom': 'none'
                    });
                    $label.append($border);
                    $label.attr('title', link.url);

                    self.$iframeBody.append($label);
                    $el.data('heatmap-label', $label);
                    $el.data('heatmap-label-border', $border);
                    $label.data('a', $a);
                });
            });
            self.labelPositions();
        }

        labelPositions() {
            const self = this;

            $(self.links).each(function() {
                const $el = $(this);
                const $label = $el.data('heatmap-label');
                const $border = $el.data('heatmap-label-border');
                const position = $el.position();
                $label.css({
                    position: 'absolute',
                    top: position.top + $el.outerHeight(),
                    left: position.left - 1,
                    'min-width': Math.max($el.outerWidth(), 60) + 2
                });
                $border.css({
                    position: 'absolute',
                    bottom: '100%',
                    left: 0,
                    width: $el.outerWidth(),
                    height: $el.outerHeight()
                });
            });
        }

        interpolateColor(rate) {
            if (rate <= 0) {
                return 'rgb(' + this.gradient[0].join(',') + ')';
            }
            if (rate >= 1) {
                const lastIndex = this.gradient.length - 1;
                return 'rgb(' + this.gradient[lastIndex].join(',') + ')';
            }

            const segmentCount = this.gradient.length - 1;
            const segmentWidth = 1 / segmentCount;
            const segmentIndex = Math.floor(rate / segmentWidth);
            const segmentPercent = (rate - segmentIndex * segmentWidth) / segmentWidth;

            const color1 = this.gradient[segmentIndex];
            const color2 = this.gradient[segmentIndex + 1];

            const r = Math.round(color1[0] + (color2[0] - color1[0]) * segmentPercent);
            const g = Math.round(color1[1] + (color2[1] - color1[1]) * segmentPercent);
            const b = Math.round(color1[2] + (color2[2] - color1[2]) * segmentPercent);

            return 'rgb(' + r + ',' + g + ',' + b + ')';
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

})(window, document, Mautic, mQuery, Math);
