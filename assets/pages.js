/*
 * Copyright (c) 2025-2026 Yunyun (Wang Yaoze)
 * Licensed under the Apache License, Version 2.0.
 * See the LICENSE file in this repository for the full text.
 * Source: https://github.com/NomenID/plugin-pages
 * Project: https://nomenid.org/w/plugin-pages
 */

// 有 JS 时目录标题只负责展开收起，链接留给爬虫和「新标签页打开」
document.querySelectorAll('.docs-nav summary > a').forEach(function (link) {
    link.addEventListener('click', function (event) {
        if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        var details = link.closest('details');
        if (!details) {
            return;
        }

        event.preventDefault();
        details.open = !details.open;
    });
});

// 左右侧栏折叠；状态记在 cookie 里，服务端下次直接渲染成折叠态，刷新不闪
document.documentElement.classList.add('docs-js');

(function () {
    var layout = document.querySelector('.docs-layout');
    if (!layout) {
        return;
    }

    var buttons = layout.querySelectorAll('[data-pane]');
    var base = layout.getAttribute('data-docs-base') || '/';
    var prefix = 'docs-layout--';
    var suffix = '-hidden';

    function save() {
        var hidden = [];
        if (layout.classList.contains(prefix + 'nav' + suffix)) {
            hidden.push('nav');
        }
        if (layout.classList.contains(prefix + 'toc' + suffix)) {
            hidden.push('toc');
        }

        document.cookie = 'nomen_docs_panes=' + hidden.join('-')
            + ';path=' + base + ';max-age=31536000;SameSite=Lax';
    }

    buttons.forEach(function (button) {
        button.addEventListener('click', function () {
            var pane = button.getAttribute('data-pane');
            var hidden = !layout.classList.contains(prefix + pane + suffix);

            layout.classList.toggle(prefix + pane + suffix, hidden);
            buttons.forEach(function (other) {
                if (other.getAttribute('data-pane') === pane) {
                    other.setAttribute('aria-expanded', hidden ? 'false' : 'true');
                }
            });

            save();
        });
    });
})();
