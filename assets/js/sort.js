/* global PortfolioGridToolkitSort */
(function ($) {
    'use strict';

    $(function () {
        var $list = $('#pgtk-sort-list');
        if (!$list.length || typeof PortfolioGridToolkitSort === 'undefined') {
            return;
        }

        var $status = $('#pgtk-sort-status');
        var saveTimer = null;
        var inFlight = false;
        var pendingSave = false;

        function setStatus(text, kind) {
            $status
                .text(text)
                .removeClass('is-saving is-saved is-error')
                .addClass(kind ? 'is-' + kind : '');
        }

        function collectOrder() {
            var ids = [];
            $list.children('li').each(function () {
                ids.push($(this).data('id'));
            });
            return ids;
        }

        function renumberMeta() {
            var total = $list.children('li').length;
            $list.children('li').each(function (index) {
                $(this).find('.pgtk-sort-meta').text('#' + (total - index));
            });
        }

        function saveOrder() {
            if (inFlight) {
                pendingSave = true;
                return;
            }

            inFlight = true;
            setStatus(PortfolioGridToolkitSort.i18n.saving, 'saving');

            $.post(PortfolioGridToolkitSort.ajaxUrl, {
                action: 'pgtk_save_order',
                nonce: PortfolioGridToolkitSort.nonce,
                order: collectOrder().join(','),
                post_status: PortfolioGridToolkitSort.context.postStatus,
                collection: PortfolioGridToolkitSort.context.collection,
                artist: PortfolioGridToolkitSort.context.artist
            })
                .done(function (response) {
                    if (response && response.success) {
                        setStatus(PortfolioGridToolkitSort.i18n.saved, 'saved');
                        if (saveTimer) {
                            clearTimeout(saveTimer);
                        }
                        saveTimer = setTimeout(function () {
                            $status.text('').removeClass('is-saved');
                        }, 1800);
                    } else {
                        setStatus(PortfolioGridToolkitSort.i18n.error, 'error');
                    }
                })
                .fail(function () {
                    setStatus(PortfolioGridToolkitSort.i18n.error, 'error');
                })
                .always(function () {
                    inFlight = false;
                    if (pendingSave) {
                        pendingSave = false;
                        saveOrder();
                    }
                });
        }

        $list.sortable({
            handle: '.pgtk-sort-handle',
            placeholder: 'ui-sortable-placeholder pgtk-sort-item',
            tolerance: 'pointer',
            axis: 'y',
            opacity: 0.9,
            update: function () {
                renumberMeta();
                saveOrder();
            }
        });
    });
})(jQuery);
