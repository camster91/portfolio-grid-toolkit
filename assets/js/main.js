(function () {
    'use strict';

    var activeClose = null;
    var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    var prefersReducedMotion = Boolean(motionQuery && motionQuery.matches);

    function clearChildren(element) {
        if (!element) {
            return;
        }
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    function getSafeUrl(rawUrl) {
        if (!rawUrl) {
            return null;
        }

        try {
            var url = new URL(rawUrl, document.baseURI);
            if (url.protocol !== 'http:' && url.protocol !== 'https:') {
                return null;
            }
            return url;
        } catch (error) {
            return null;
        }
    }

    function getYouTubeId(url) {
        var host = url.hostname.toLowerCase();
        var id = '';

        if (host === 'youtu.be' || host === 'www.youtu.be') {
            id = url.pathname.replace(/^\//, '').split('/')[0];
        } else if (host === 'youtube.com' || host === 'www.youtube.com' || host === 'm.youtube.com') {
            id = url.searchParams.get('v') || url.pathname.split('/').filter(Boolean).pop() || '';
        }

        return /^[A-Za-z0-9_-]{6,}$/.test(id) ? id : '';
    }

    function getVimeoId(url) {
        var host = url.hostname.toLowerCase();
        if (host !== 'vimeo.com' && host !== 'www.vimeo.com' && host !== 'player.vimeo.com') {
            return '';
        }

        var match = url.pathname.match(/(?:video\/)?(\d+)/);
        return match ? match[1] : '';
    }

    function createMediaElement(rawUrl, title) {
        var url = getSafeUrl(rawUrl);
        if (!url) {
            return null;
        }

        var youtubeId = getYouTubeId(url);
        if (youtubeId) {
            var youtube = document.createElement('iframe');
            youtube.src = 'https://www.youtube.com/embed/' + encodeURIComponent(youtubeId) + '?autoplay=1&rel=0';
            youtube.title = title + ' project video';
            youtube.setAttribute('frameborder', '0');
            youtube.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
            youtube.setAttribute('allowfullscreen', '');
            return youtube;
        }

        var vimeoId = getVimeoId(url);
        if (vimeoId) {
            var vimeo = document.createElement('iframe');
            vimeo.src = 'https://player.vimeo.com/video/' + encodeURIComponent(vimeoId) + '?autoplay=1';
            vimeo.title = title + ' project video';
            vimeo.setAttribute('frameborder', '0');
            vimeo.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
            vimeo.setAttribute('allowfullscreen', '');
            return vimeo;
        }

        var video = document.createElement('video');
        video.src = url.href;
        video.controls = true;
        video.autoplay = true;
        video.playsInline = true;
        video.preload = 'metadata';
        video.setAttribute('aria-label', title + ' project video');
        return video;
    }

    function renderCaption(element, rawCaption) {
        clearChildren(element);
        if (!element || !rawCaption) {
            return;
        }

        rawCaption.split(/\r?\n/).forEach(function (line) {
            var text = line.trim();
            if (!text) {
                return;
            }

            var block = document.createElement('div');
            block.className = 'caption-block';
            var separator = text.indexOf(':');

            if (separator > 0) {
                var label = document.createElement('span');
                label.className = 'caption-label';
                label.textContent = text.slice(0, separator + 1);

                var value = document.createElement('span');
                value.className = 'caption-value';
                value.textContent = text.slice(separator + 1).trim();

                block.appendChild(label);
                block.appendChild(document.createTextNode(' '));
                block.appendChild(value);
            } else {
                block.textContent = text;
            }

            element.appendChild(block);
        });
    }

    function getFocusable(container) {
        return Array.prototype.slice.call(container.querySelectorAll(
            'a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), video, iframe, [tabindex]:not([tabindex="-1"])'
        )).filter(function (element) {
            return element.offsetWidth > 0 && element.offsetHeight > 0 && !element.hidden;
        });
    }

    function trapFallbackFocus(event, modal) {
        if (event.key !== 'Tab') {
            return;
        }

        var focusable = getFocusable(modal);
        if (!focusable.length) {
            event.preventDefault();
            return;
        }

        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function initContainer(container) {
        var grid = container.querySelector('.pgtk-grid');
        var modal = container.querySelector('.pgtk-modal');
        if (!grid || !modal) {
            return;
        }

        var items = Array.prototype.slice.call(grid.querySelectorAll('.pgtk-item'));
        var filterButtons = Array.prototype.slice.call(container.querySelectorAll('.pgtk-filter-btn'));
        var modalContent = modal.querySelector('.pgtk-video-container');
        var modalCaption = modal.querySelector('.pgtk-modal-caption');
        var modalTitle = modal.querySelector('.pgtk-modal-title');
        var modalClose = modal.querySelector('.pgtk-modal-close');
        var modalOverlay = modal.querySelector('.pgtk-modal-overlay');
        var nativeDialog = typeof modal.showModal === 'function' && typeof modal.close === 'function';
        var returnFocus = null;
        var previousOverflow = '';
        var isOpen = false;
        var fallbackOpen = false;

        modal.setAttribute('aria-hidden', 'true');

        function cleanupModal() {
            if (!isOpen) {
                return;
            }

            isOpen = false;
            clearChildren(modalContent);
            clearChildren(modalCaption);
            if (modalTitle) {
                modalTitle.textContent = 'Portfolio project';
            }
            modal.setAttribute('aria-hidden', 'true');

            if (fallbackOpen) {
                modal.removeAttribute('open');
                modal.classList.remove('is-open');
                modal.style.removeProperty('display');
                fallbackOpen = false;
            }

            document.body.style.overflow = previousOverflow;
            if (activeClose === closeModal) {
                activeClose = null;
            }

            var focusTarget = returnFocus;
            returnFocus = null;
            if (focusTarget && document.contains(focusTarget) && !focusTarget.disabled) {
                focusTarget.focus();
            }
        }

        function closeModal() {
            if (!isOpen) {
                return;
            }

            if (nativeDialog && !fallbackOpen && modal.open) {
                modal.close();
            } else {
                cleanupModal();
            }
        }

        function openModal(item) {
            var videoUrl = item.getAttribute('data-video');
            if (!videoUrl || item.disabled) {
                return;
            }

            if (activeClose) {
                activeClose();
            }

            returnFocus = item;
            previousOverflow = document.body.style.overflow;
            isOpen = true;
            activeClose = closeModal;
            modal.setAttribute('aria-hidden', 'false');

            var title = item.getAttribute('data-title') || 'Portfolio project';
            if (modalTitle) {
                modalTitle.textContent = title;
            }
            renderCaption(modalCaption, item.getAttribute('data-caption') || '');
            var media = createMediaElement(videoUrl, title);
            if (media) {
                modalContent.appendChild(media);
            }

            if (nativeDialog) {
                try {
                    modal.showModal();
                } catch (error) {
                    // A stale open state should not prevent the fallback from
                    // presenting the requested project video.
                    fallbackOpen = true;
                    modal.setAttribute('open', '');
                    modal.classList.add('is-open');
                }
            } else {
                fallbackOpen = true;
                modal.setAttribute('open', '');
                modal.classList.add('is-open');
            }

            document.body.style.overflow = 'hidden';
            if (modalClose) {
                modalClose.focus();
            }

            if (media && media.tagName === 'VIDEO') {
                var playPromise = media.play();
                if (playPromise && typeof playPromise.catch === 'function') {
                    playPromise.catch(function () {
                        // Controls remain available when autoplay is blocked.
                    });
                }
            }
        }

        modal.addEventListener('close', cleanupModal);
        modal.addEventListener('cancel', function (event) {
            event.preventDefault();
            closeModal();
        });
        modal.addEventListener('keydown', function (event) {
            if (fallbackOpen) {
                trapFallbackFocus(event, modal);
            }
        });
        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });

        if (modalClose) {
            modalClose.addEventListener('click', closeModal);
        }
        if (modalOverlay) {
            modalOverlay.addEventListener('click', closeModal);
        }

        items.forEach(function (item) {
            item.addEventListener('click', function () {
                openModal(item);
            });

        });

        filterButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var filter = button.getAttribute('data-filter') || 'all';
                filterButtons.forEach(function (filterButton) {
                    var selected = filterButton === button;
                    filterButton.classList.toggle('active', selected);
                    filterButton.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });

                items.forEach(function (item) {
                    var visible = filter === 'all' || item.classList.contains(filter);
                    item.hidden = !visible;
                    item.classList.toggle('is-filtered-out', !visible);
                });
            });
        });

        if (!prefersReducedMotion && typeof window.gsap !== 'undefined' && items.length) {
            window.gsap.from(items, {
                y: 36,
                opacity: 0,
                duration: 0.7,
                stagger: 0.08,
                ease: 'power3.out'
            });
        }
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && activeClose) {
            event.preventDefault();
            activeClose();
        }
    }, true);

    if (motionQuery && typeof motionQuery.addEventListener === 'function') {
        motionQuery.addEventListener('change', function (event) {
            prefersReducedMotion = event.matches;
        });
    }

    document.querySelectorAll('.pgtk-container').forEach(initContainer);
})();
