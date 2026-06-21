(function () {
    'use strict';

    const HIDDEN_CLASS = 'compacttabs-hidden';
    const READY_CLASS = 'compacttabs-ready';
    const TOGGLE_CLASS = 'compacttabs-toggle';
    const TOGGLE_WRAPPER_CLASS = 'compacttabs-toggle-wrapper';
    const DEBUG_CLASS = 'compacttabs-debug-outline';

    const TAB_LINK_SELECTOR = 'a[href*="common.tabs.php"][href*="_glpi_tab="]';

    let isApplying = false;
    let observer = null;
    let observerTimer = null;
    let expanded = false;
    let currentItemKey = null;
    let lastDebugSignature = '';

    function getPluginConfig() {
        return window.GLPICompactTabsConfig || {};
    }

    function getCurrentScreenDefinition() {
        const path = window.location.pathname;
        const config = getPluginConfig();

        const screens = [
            {
                key: 'ticket',
                path: '/front/ticket.form.php'
            },
            {
                key: 'problem',
                path: '/front/problem.form.php'
            },
            {
                key: 'change',
                path: '/front/change.form.php'
            },
            {
                key: 'formcreator_issue',
                path: '/plugins/formcreator/front/issue.form.php'
            }
        ];

        for (const screen of screens) {
            if (!path.endsWith(screen.path)) {
                continue;
            }

            const definition = config[screen.key];

            if (!definition || definition.enabled !== true) {
                return null;
            }

            return {
                key: screen.key,
                path: screen.path,
                itemtype: definition.itemtype,
                target: definition.target,
                mainTabKey: definition.mainTabKey,
                alwaysVisibleTabs: Array.isArray(definition.alwaysVisibleTabs)
                    ? definition.alwaysVisibleTabs
                    : []
            };
        }

        return null;
    }

    function isDebugEnabled() {
        const params = new URLSearchParams(window.location.search);
        return params.get('compacttabs_debug') === '1';
    }

    function getCurrentItemKey(definition) {
        const params = new URLSearchParams(window.location.search);
        const id = params.get('id') || 'new';

        return definition.key + ':' + window.location.pathname + ':' + id;
    }

    function resetStateIfItemChanged(definition) {
        const nextItemKey = getCurrentItemKey(definition);

        if (currentItemKey !== nextItemKey) {
            currentItemKey = nextItemKey;
            expanded = false;
            lastDebugSignature = '';
        }
    }

    function normalizeText(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    }

    function getTabMeta(link) {
        const href = link.getAttribute('href') || '';

        try {
            const url = new URL(href, window.location.origin);
            const params = url.searchParams;

            return {
                href: href,
                pathname: url.pathname,
                tabKey: params.get('_glpi_tab') || '',
                target: params.get('_target') || '',
                itemtype: params.get('_itemtype') || '',
                id: params.get('id') || ''
            };
        } catch (error) {
            return {
                href: href,
                pathname: '',
                tabKey: '',
                target: '',
                itemtype: '',
                id: ''
            };
        }
    }

    function isCurrentScreenTabLink(link, definition) {
        const meta = getTabMeta(link);

        if (!meta.pathname.includes('/ajax/common.tabs.php')) {
            return false;
        }

        if (definition.itemtype && meta.itemtype !== definition.itemtype) {
            return false;
        }

        if (!meta.target.includes(definition.target)) {
            return false;
        }

        if (meta.tabKey === '') {
            return false;
        }

        return true;
    }

    function getTabText(link) {
        const clone = link.cloneNode(true);

        clone.querySelectorAll('.badge, .badges, .bagdes, [class*="badge"]').forEach(function (item) {
            item.remove();
        });

        return normalizeText(clone.textContent);
    }

    function getBadgeValue(link) {
        const counters = link.querySelectorAll(
            '.badge, .badges, .bagdes, [class*="badge"]'
        );

        for (const counter of counters) {
            const text = normalizeText(counter.textContent);
            const match = text.match(/\d+/);

            if (match) {
                return parseInt(match[0], 10);
            }
        }

        return null;
    }

    function hasPositiveBadge(link) {
        const value = getBadgeValue(link);

        return value !== null && value > 0;
    }

    function getTabItem(link) {
        return link.closest('li, .nav-item, .list-group-item, .tab-item') || link;
    }

    function isActiveTab(link, item) {
        return link.classList.contains('active')
            || item.classList.contains('active')
            || link.getAttribute('aria-selected') === 'true';
    }

    function isMainTab(link, definition) {
        const tabKey = getTabMeta(link).tabKey;

        if (definition.mainTabKey && tabKey === definition.mainTabKey) {
            return true;
        }

        return tabKey.endsWith('$main');
    }

    function isAllTab(link) {
        return getTabMeta(link).tabKey === '-1';
    }

    function isConfiguredAlwaysVisibleTab(link, definition) {
        const meta = getTabMeta(link);

        return definition.alwaysVisibleTabs.includes(meta.tabKey);
    }

    function getCandidateContainers() {
        return Array.from(document.querySelectorAll([
            'ul.nav-tabs',
            '.nav-tabs',
            '.list-group',
            '[role="tablist"]'
        ].join(',')));
    }

    function getTabLinks(container, definition) {
        return Array.from(container.querySelectorAll(TAB_LINK_SELECTOR))
            .filter(function (link) {
                if (link.classList.contains(TOGGLE_CLASS)) {
                    return false;
                }

                return isCurrentScreenTabLink(link, definition);
            });
    }

    function findTabsContainer(definition) {
        const containers = getCandidateContainers();

        let selectedContainer = null;
        let selectedScore = 0;

        for (const container of containers) {
            const links = getTabLinks(container, definition);

            if (links.length < 3) {
                continue;
            }

            const tabKeys = links.map(function (link) {
                return getTabMeta(link).tabKey;
            });

            let score = links.length;

            if (tabKeys.includes(definition.mainTabKey)) {
                score += 20;
            }

            if (tabKeys.includes('-1')) {
                score += 20;
            }

            if (score > selectedScore) {
                selectedContainer = container;
                selectedScore = score;
            }
        }

        return selectedContainer;
    }

    function createToggleWrapper(container) {
        const wrapperTag = container.tagName.toLowerCase() === 'ul' ? 'li' : 'div';

        const wrapper = document.createElement(wrapperTag);
        wrapper.className = TOGGLE_WRAPPER_CLASS;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = TOGGLE_CLASS;
        button.setAttribute('aria-expanded', 'false');

        wrapper.appendChild(button);
        container.appendChild(wrapper);

        return button;
    }

    function getToggleButton(container) {
        let button = container.querySelector('.' + TOGGLE_CLASS);

        if (!button) {
            button = createToggleWrapper(container);
        }

        return button;
    }

    function updateToggleButton(button, hiddenCount) {
        const nextLabel = expanded
            ? 'Recolher abas vazias'
            : 'Mostrar abas ocultas (' + hiddenCount + ')';

        const nextIcon = expanded ? 'ti ti-chevron-up' : 'ti ti-chevron-down';
        const currentLabel = button.dataset.compacttabsLabel || '';
        const currentIcon = button.dataset.compacttabsIcon || '';

        button.setAttribute('aria-expanded', expanded ? 'true' : 'false');

        if (currentLabel !== nextLabel || currentIcon !== nextIcon) {
            button.dataset.compacttabsLabel = nextLabel;
            button.dataset.compacttabsIcon = nextIcon;

            button.innerHTML = '<i class="' + nextIcon + '"></i><span>' + nextLabel + '</span>';
        }

        const shouldDisplay = hiddenCount > 0 || expanded;
        const nextDisplay = shouldDisplay ? '' : 'none';

        if (button.style.display !== nextDisplay) {
            button.style.display = nextDisplay;
        }
    }

    function bindToggleButton(container, button, definition) {
        if (button.dataset.compacttabsBound === '1') {
            return;
        }

        button.dataset.compacttabsBound = '1';

        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            expanded = !expanded;

            applyCompactTabs(container, definition);
        });
    }

    function shouldKeepVisible(link, item, index, total, definition) {
        const isFirst = index === 0;
        const isLast = index === total - 1;
        const active = isActiveTab(link, item);

        return expanded
            || isFirst
            || isLast
            || isMainTab(link, definition)
            || isAllTab(link)
            || isConfiguredAlwaysVisibleTab(link, definition)
            || hasPositiveBadge(link)
            || active;
    }

    function setHiddenState(item, shouldHide) {
        if (shouldHide) {
            if (!item.classList.contains(HIDDEN_CLASS)) {
                item.classList.add(HIDDEN_CLASS);
            }
        } else {
            if (item.classList.contains(HIDDEN_CLASS)) {
                item.classList.remove(HIDDEN_CLASS);
            }
        }
    }

    function getDebugSignature(tabs) {
        return JSON.stringify(tabs.map(function (tab) {
            return {
                index: tab.index,
                tabKey: tab.tabKey,
                badge: tab.badge,
                visible: tab.visible
            };
        }));
    }

    function debugTabs(container, tabs, definition) {
        if (!isDebugEnabled()) {
            return;
        }

        if (!container.classList.contains(DEBUG_CLASS)) {
            container.classList.add(DEBUG_CLASS);
        }

        const signature = getDebugSignature(tabs);

        if (signature === lastDebugSignature) {
            return;
        }

        lastDebugSignature = signature;

        console.group('[Compact Tabs] Abas detectadas em ' + definition.itemtype);

        tabs.forEach(function (tab) {
            console.log(tab);
        });

        console.groupEnd();
    }

    function getDiscoveryConfig() {
        const config = getPluginConfig();

        return config.discovery || {};
    }

    function isPluginTabKey(tabKey) {
        return typeof tabKey === 'string' && tabKey.indexOf('Plugin') === 0;
    }

    function encodeBase64Utf8(value) {
        if (window.TextEncoder) {
            const bytes = new TextEncoder().encode(value);
            let binary = '';

            bytes.forEach(function (byte) {
                binary += String.fromCharCode(byte);
            });

            return btoa(binary);
        }

        return btoa(unescape(encodeURIComponent(value)));
    }

    function getDiscoverySignature(definition, tabs) {
        return definition.key + ':' + JSON.stringify(
            tabs.map(function (tab) {
                return {
                    tabKey: tab.tabKey,
                    text: tab.text
                };
            }).sort(function (a, b) {
                return a.tabKey.localeCompare(b.tabKey);
            })
        );
    }

    const compacttabsDiscoveryInFlight = new Set();
    const compacttabsDiscoveryProcessed = new Set();
    const compacttabsDiscoveryDebugLogged = new Set();

    function sendDiscoveredTabs(definition, debugData) {
        const discovery = getDiscoveryConfig();

        if (!discovery.enabled || !discovery.url || !discovery.csrfToken) {
            if (isDebugEnabled()) {
                console.warn('[Compact Tabs] Descoberta desativada ou configuração incompleta.', discovery);
            }

            return;
        }

        const discoveredTabs = debugData
            .filter(function (tab) {
                return tab.tabKey
                    && tab.main !== true
                    && tab.all !== true;
            })
            .map(function (tab) {
                return {
                    tabKey: tab.tabKey,
                    text: tab.text,
                    origin: isPluginTabKey(tab.tabKey) ? 'plugin' : 'native'
                };
            });

        if (discoveredTabs.length === 0) {
            return;
        }

        const signature = getDiscoverySignature(definition, discoveredTabs);
        const storageKey = 'compacttabs.discovery.sent.' + signature;

        if (
            sessionStorage.getItem(storageKey) === '1'
            || compacttabsDiscoveryInFlight.has(signature)
            || compacttabsDiscoveryProcessed.has(signature)
        ) {
            return;
        }

        if (isDebugEnabled() && !compacttabsDiscoveryDebugLogged.has(signature)) {
            compacttabsDiscoveryDebugLogged.add(signature);

            console.log('[Compact Tabs] Abas candidatas para descoberta:', {
                screen: definition.key,
                tabs: discoveredTabs
            });
        }

        compacttabsDiscoveryInFlight.add(signature);

        const jsonPayload = JSON.stringify(discoveredTabs);

        const body = new URLSearchParams();

        body.append('screen', definition.key);
        body.append('tabs', jsonPayload);
        body.append('tabs_b64', encodeBase64Utf8(jsonPayload));
        body.append('_glpi_csrf_token', discovery.csrfToken);

        if (isDebugEnabled()) {
            body.append('compacttabs_debug', '1');
        }

        fetch(discovery.url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json'
            },
            body: body
        }).then(function (response) {
            if (isDebugEnabled()) {
                console.log('[Compact Tabs] Resposta HTTP da descoberta:', {
                    status: response.status,
                    ok: response.ok,
                    url: discovery.url
                });
            }

            return response.text().then(function (text) {
                try {
                    return JSON.parse(text);
                } catch (error) {
                    return {
                        success: false,
                        message: 'Resposta não está em JSON.',
                        httpStatus: response.status,
                        raw: text.substring(0, 500)
                    };
                }
            });
        }).then(function (payload) {
            if (payload.success === true) {
                sessionStorage.setItem(storageKey, '1');
                compacttabsDiscoveryProcessed.add(signature);
            }

            if (isDebugEnabled()) {
                console.log('[Compact Tabs] Payload da descoberta:', payload);
            }
        }).catch(function (error) {
            if (isDebugEnabled()) {
                console.error('[Compact Tabs] Erro na descoberta automática:', error);
            }
        }).finally(function () {
            compacttabsDiscoveryInFlight.delete(signature);
        });
    }

    function applyCompactTabs(container, definition) {
        if (isApplying) {
            return;
        }

        isApplying = true;

        try {
            resetStateIfItemChanged(definition);

            const links = getTabLinks(container, definition);

            if (links.length < 3) {
                return;
            }

            let hiddenCount = 0;
            const debugData = [];

            links.forEach(function (link, index) {
                const item = getTabItem(link);
                const total = links.length;
                const meta = getTabMeta(link);

                const visible = shouldKeepVisible(link, item, index, total, definition);

                setHiddenState(item, !visible);

                if (!visible) {
                    hiddenCount++;
                }

                debugData.push({
                    screen: definition.key,
                    index: index,
                    text: getTabText(link),
                    tabKey: meta.tabKey,
                    itemtype: meta.itemtype,
                    target: meta.target,
                    badge: getBadgeValue(link),
                    first: index === 0,
                    last: index === total - 1,
                    main: isMainTab(link, definition),
                    all: isAllTab(link),
                    configuredAlwaysVisible: isConfiguredAlwaysVisibleTab(link, definition),
                    active: isActiveTab(link, item),
                    visible: visible,
                    href: meta.href
                });
            });

            if (!container.classList.contains(READY_CLASS)) {
                container.classList.add(READY_CLASS);
            }

            const nextExpanded = expanded ? '1' : '0';

            if (container.dataset.compacttabsExpanded !== nextExpanded) {
                container.dataset.compacttabsExpanded = nextExpanded;
            }

            const button = getToggleButton(container);

            bindToggleButton(container, button, definition);
            updateToggleButton(button, hiddenCount);
            debugTabs(container, debugData, definition);
            sendDiscoveredTabs(definition, debugData);
        } finally {
            isApplying = false;
        }
    }

    function initCompactTabs() {
        const definition = getCurrentScreenDefinition();

        if (!definition) {
            return;
        }

        const container = findTabsContainer(definition);

        if (!container) {
            return;
        }

        window.requestAnimationFrame(function () {
            applyCompactTabs(container, definition);
        });
    }

    function isCompactTabsOwnMutation(mutation) {
        const target = mutation.target;

        if (target && target.nodeType === 1 && target.closest && target.closest('.' + TOGGLE_WRAPPER_CLASS)) {
            return true;
        }

        const addedNodes = Array.from(mutation.addedNodes || []);
        const removedNodes = Array.from(mutation.removedNodes || []);

        const nodes = addedNodes.concat(removedNodes);

        return nodes.some(function (node) {
            if (!node || node.nodeType !== 1) {
                return false;
            }

            if (node.classList && node.classList.contains(TOGGLE_WRAPPER_CLASS)) {
                return true;
            }

            if (node.querySelector && node.querySelector('.' + TOGGLE_WRAPPER_CLASS)) {
                return true;
            }

            return false;
        });
    }

    function startObserver() {
        if (observer) {
            return;
        }

        observer = new MutationObserver(function (mutations) {
            if (isApplying) {
                return;
            }

            if (mutations.length > 0 && mutations.every(isCompactTabsOwnMutation)) {
                return;
            }

            clearTimeout(observerTimer);

            observerTimer = setTimeout(function () {
                initCompactTabs();
            }, 150);
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    function start() {
        initCompactTabs();

        setTimeout(initCompactTabs, 100);
        setTimeout(initCompactTabs, 300);
        setTimeout(initCompactTabs, 700);
        setTimeout(initCompactTabs, 1200);

        startObserver();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();