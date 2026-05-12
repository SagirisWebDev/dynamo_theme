(function () {
    'use strict';

    var map = window.dynamoBindings || {};

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

    function bindSetting(settingId, meta) {
        var id = settingId.replace(/^dynamo_/, '');
        var unit = meta.unit || '';
        var choicesMap = meta.choicesMap || null;

        if (typeof wp === 'undefined' || !wp.customize) {
            return;
        }

        wp.customize(settingId, function (setting) {
            setting.bind(function (newValue) {
                var resolved = resolve(newValue, choicesMap, unit);
                document.documentElement.style.setProperty('--dynamo-' + id, resolved);
            });
        });
    }

    Object.keys(map).forEach(function (settingId) {
        bindSetting(settingId, map[settingId]);
    });
})();
