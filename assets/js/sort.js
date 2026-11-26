/* global AshbiPortfolioGridSort */
(function ($) {
    'use strict';

    $(function () {
        var $list = $('#ashbpg-sort-list');
        if (!$list.length || typeof AshbiPortfolioGridSort === 'undefined') {
            return;
        }

        var $status = $('#ashbpg-sort-status');
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
                $(this).find('.ashbpg-sort-meta').text('#' + (total - index));
            });
        }

        function saveOrder() {
            if (inFlight) {
                pendingSave = true;
                return;
            }

            inFlight = true;
            setStatus(AshbiPortfolioGridSort.i18n.saving, 'saving');

            $.post(AshbiPortfolioGridSort.ajaxUrl, {
                action: 'ashbpg_save_order',
                nonce: AshbiPortfolioGridSort.nonce,
                order: collectOrder().join(','),
                post_status: AshbiPortfolioGridSort.context.postStatus,
                collection: AshbiPortfolioGridSort.context.collection,
                artist: AshbiPortfolioGridSort.context.artist
            })
                .done(function (response) {
                    if (response && response.success) {
                        setStatus(AshbiPortfolioGridSort.i18n.saved, 'saved');
                        if (saveTimer) {
                            clearTimeout(saveTimer);
                        }
                        saveTimer = setTimeout(function () {
                            $status.text('').removeClass('is-saved');
                        }, 1800);
                    } else {
                        setStatus(AshbiPortfolioGridSort.i18n.error, 'error');
                    }
                })
                .fail(function () {
                    setStatus(AshbiPortfolioGridSort.i18n.error, 'error');
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
            handle: '.ashbpg-sort-handle',
            placeholder: 'ui-sortable-placeholder ashbpg-sort-item',
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
