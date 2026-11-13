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
