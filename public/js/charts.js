(function () {
    function setLoaderMessage(message) {
        document.querySelectorAll("[data-chart-loader]").forEach((loader) => {
            loader.textContent = message;
        });
    }

    const currencyFormatter = new Intl.NumberFormat("it-IT", {
        style: "currency",
        currency: "EUR",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    const compactCurrencyFormatter = new Intl.NumberFormat("it-IT", {
        style: "currency",
        currency: "EUR",
        maximumFractionDigits: 0
    });

    const callbackMap = {
        currency(value) {
            return compactCurrencyFormatter.format(value);
        },
        expenseShareOfTotal(context) {
            const dataset = context.dataset || {};
            const percentages = Array.isArray(dataset.sharePercentages) ? dataset.sharePercentages : [];
            const referenceTotal = Number(dataset.referenceTotal || 0);
            const percentage = percentages[context.dataIndex] ?? 0;

            if (referenceTotal <= 0) {
                return "Nessuna spesa registrata nel mese.";
            }

            return `${percentage.toFixed(2)}% rispetto alle spese del mese`;
        }
    };

    const callbackKeys = new Set([
        "callback",
        "label",
        "afterLabel",
        "beforeLabel",
        "footer",
        "title"
    ]);

    function hydrateCallbacks(node) {
        if (Array.isArray(node)) {
            return node.map(hydrateCallbacks);
        }

        if (node && typeof node === "object") {
            Object.keys(node).forEach((key) => {
                if (callbackKeys.has(key) && typeof node[key] === "string" && callbackMap[node[key]]) {
                    node[key] = callbackMap[node[key]];
                    return;
                }

                node[key] = hydrateCallbacks(node[key]);
            });
        }

        return node;
    }

    function initializeCharts() {
        if (typeof Chart === "undefined") {
            setLoaderMessage("Chart.js non e stato caricato dal CDN.");
            return;
        }

        document.querySelectorAll("canvas[data-chart-config-id]").forEach((canvas) => {
            const wrapper = canvas.closest(".chart-card");
            const configId = canvas.dataset.chartConfigId;
            const configNode = configId ? document.getElementById(configId) : null;

            if (!configNode) {
                const loader = wrapper?.querySelector("[data-chart-loader]");
                if (loader) {
                    loader.textContent = "Configurazione grafico non trovata.";
                }
                return;
            }

            try {
                const config = hydrateCallbacks(JSON.parse(configNode.textContent || "{}"));
                new Chart(canvas, config);
                wrapper?.classList.add("is-ready");
            } catch (error) {
                const loader = wrapper?.querySelector("[data-chart-loader]");
                if (loader) {
                    loader.textContent = "Errore durante il caricamento del grafico.";
                }
                console.error(error);
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initializeCharts, { once: true });
    } else {
        initializeCharts();
    }
})();
