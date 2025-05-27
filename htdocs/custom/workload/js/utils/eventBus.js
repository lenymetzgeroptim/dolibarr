window.EventBus = (function () {
    const subscribers = {};

    return {
        subscribe: function (event, callback) {
            if (!subscribers[event]) subscribers[event] = [];
            subscribers[event].push(callback);
        },

        dispatch: function (event, data) {
            if (!subscribers[event]) return;
            subscribers[event].forEach(cb => cb(data));
        }
    };
})();

// Vérifie que `window.EventBus` est bien défini
if (window.EventBus) {
    // Abonnements
    window.EventBus.subscribe('gantt:update', function () {
        setTimeout(() => {
            addFixedHeader();
        }, 100);
    });

    window.EventBus.subscribe('gantt5:update', function () {
        setTimeout(() => {
            addFixedHeader();
            const lineElement5 = document.getElementById('GanttChartDIV5line1');
            centerScrollOnAllLines5(lineElement5, document.getElementById("fixedHeader_GanttChartDIV5"));
        }, 100);
    });

    window.EventBus.subscribe('gantt2:update', function () {
        setTimeout(() => {
            addFixedHeader();
            const lineElement2 = document.getElementById('GanttChartDIV2line1');
            centerScrollOnAllLines2(lineElement2, document.getElementById("fixedHeader_GanttChartDIV5"));
        }, 100);
    });
} else {
    console.error('EventBus n\'est pas défini');
}
