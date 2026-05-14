(function () {
    'use strict';

    var map = window.dynamoBindings || {};

    var CONTENT_KEYWORDS = ['none', 'normal', 'initial', 'inherit', 'unset', 'revert', 'revert-layer'];
    var URL_TYPES = ['url', 'image', 'media'];

    function resolve(value, choicesMap, unit) {
        var resolved = value;
        if (choicesMap && Object.prototype.hasOwnProperty.call(choicesMap, value)) {
            resolved = choicesMap[value];
        }
        if (unit) {
            resolved = String(resolved) + unit;
        }
        return String(resolved);
    }

    function wrapContentString(value) {
        var trimmed = String(value).replace(/^\s+/, '');
        if (trimmed.charAt(0) === '"' || trimmed.charAt(0) === "'") {
            return value;
        }
        if (/^[a-zA-Z-]+\(/.test(trimmed)) {
            return value;
        }
        if (CONTENT_KEYWORDS.indexOf(trimmed.toLowerCase()) !== -1) {
            return value;
        }
        return '"' + String(value).replace(/"/g, '\\"') + '"';
    }

    function wrapUrl(value) {
        var s = String(value);
        if (s.indexOf('url(') === 0) {
            return s;
        }
        return "url('" + s + "')";
    }

    function parseBlockDeclarations(css) {
        if (typeof css !== 'string') {
            return [];
        }
        var match = css.match(/\{([\s\S]*)\}/);
        if (!match) {
            return [];
        }
        return match[1].split(';').reduce(function (acc, raw) {
            var line = raw.trim();
            if (!line) {
                return acc;
            }
            var colon = line.indexOf(':');
            if (colon === -1) {
                return acc;
            }
            var prop = line.slice(0, colon).trim();
            var val  = line.slice(colon + 1).trim();
            if (prop && val) {
                acc.push([prop, val]);
            }
            return acc;
        }, []);
    }

    function ensureExtrasStyleTag(id) {
        var tagId = 'dynamo-binding-extras-' + id;
        var tag = document.getElementById(tagId);
        if (!tag) {
            tag = document.createElement('style');
            tag.id = tagId;
            document.head.appendChild(tag);
        }
        return tag;
    }

    function applyCodeBinding(settingId, id, meta, newValue) {
        var decls = parseBlockDeclarations(newValue);
        var bound = '';
        var extras = [];
        for (var i = 0; i < decls.length; i++) {
            if (decls[i][0] === meta.property) {
                bound = decls[i][1];
            } else {
                extras.push(decls[i]);
            }
        }
        document.documentElement.style.setProperty('--dynamo-' + id, bound);

        var tag = ensureExtrasStyleTag(id);
        if (!extras.length) {
            tag.textContent = '';
            return;
        }
        var parts = extras.map(function (d) { return d[0] + ': ' + d[1] + ';'; });
        tag.textContent = meta.selector + ' { ' + parts.join(' ') + ' }';
    }

    function bindSetting(settingId, meta) {
        var id = settingId.replace(/^dynamo_/, '');
        var unit = meta.unit || '';
        var choicesMap = meta.choicesMap || null;
        var isCode = meta.type === 'code';

        if (typeof wp === 'undefined' || !wp.customize) {
            return;
        }

        wp.customize(settingId, function (setting) {
            setting.bind(function (newValue) {
                if (isCode) {
                    applyCodeBinding(settingId, id, meta, newValue);
                    return;
                }
                var resolved = resolve(newValue, choicesMap, unit);
                if (resolved !== '') {
                    if (URL_TYPES.indexOf(meta.type) !== -1) {
                        resolved = wrapUrl(resolved);
                    }
                    if (meta.property === 'content') {
                        resolved = wrapContentString(resolved);
                    }
                }
                document.documentElement.style.setProperty('--dynamo-' + id, resolved);
            });
        });
    }

    Object.keys(map).forEach(function (settingId) {
        bindSetting(settingId, map[settingId]);
    });
})();
