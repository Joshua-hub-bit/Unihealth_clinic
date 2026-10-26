/**
 * SUNN Clinic — Shared Analytics helpers
 * Requires Chart.js 4 (chart.umd.min.js) loaded before this file.
 *
 * Exports a global `ClinicAnalytics` object used by each role dashboard.
 */
'use strict';

(function (global) {

    /* ── Palette ──────────────────────────────────────────────── */
    var COLORS = {
        blue:   '#2563eb',
        green:  '#16a34a',
        amber:  '#ca8a04',
        red:    '#dc2626',
        purple: '#9333ea',
        teal:   '#0d9488',
        sky:    '#0284c7',
        indigo: '#4f46e5',
        muted:  '#94a3b8',
        blueAlpha:   'rgba(37,99,235,.15)',
        greenAlpha:  'rgba(22,163,74,.15)',
        amberAlpha:  'rgba(202,138,4,.15)',
        redAlpha:    'rgba(220,38,38,.15)',
        purpleAlpha: 'rgba(147,51,234,.15)',
        tealAlpha:   'rgba(13,148,136,.15)',
    };

    /* ── Registered Chart instances (for destroy-before-recreate) ── */
    var _charts = {};

    function _destroy(id) {
        if (_charts[id]) {
            try { _charts[id].destroy(); } catch (e) { /* ignore */ }
            delete _charts[id];
        }
    }

    /* ── Default Chart.js global options ──────────────────────── */
    function _applyDefaults() {
        if (!global.Chart) { return; }
        Chart.defaults.font.family = "'Inter','Segoe UI',system-ui,sans-serif";
        Chart.defaults.font.size   = 12;
        Chart.defaults.color       = '#64748b';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.padding       = 14;
        Chart.defaults.plugins.tooltip.backgroundColor     = '#1e293b';
        Chart.defaults.plugins.tooltip.padding             = 10;
        Chart.defaults.plugins.tooltip.cornerRadius        = 8;
        Chart.defaults.plugins.tooltip.titleFont           = { weight: '700' };
    }

    /* ── Helpers ──────────────────────────────────────────────── */

    /**
     * Create or replace a Chart.js chart on a canvas.
     * @param {string} canvasId
     * @param {object} config   — full Chart.js config object
     * @returns {Chart}
     */
    function makeChart(canvasId, config) {
        _destroy(canvasId);
        var canvas = document.getElementById(canvasId);
        if (!canvas) { return null; }
        var chart = new Chart(canvas, config);
        _charts[canvasId] = chart;
        return chart;
    }

    /**
     * Line chart — patient volume over time.
     * @param {string}   canvasId
     * @param {string[]} labels  — e.g. ['Mon','Tue',…]
     * @param {number[]} data
     * @param {object}   opts    — optional overrides { color, label, fill }
     */
    function lineChart(canvasId, labels, data, opts) {
        opts = opts || {};
        var color = opts.color || COLORS.blue;
        return makeChart(canvasId, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: opts.label || 'Visits',
                    data: data,
                    borderColor: color,
                    backgroundColor: opts.fill !== false
                        ? color.replace(')', ', 0.12)').replace('rgb', 'rgba')
                        : 'transparent',
                    fill: opts.fill !== false,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    borderWidth: 2.5,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { precision: 0 },
                    },
                },
            },
        });
    }

    /**
     * Bar chart — flexible.
     * @param {string}   canvasId
     * @param {string[]} labels
     * @param {number[]} data
     * @param {object}   opts   — { color, label, horizontal }
     */
    function barChart(canvasId, labels, data, opts) {
        opts = opts || {};
        var color = opts.color || COLORS.blue;
        var percentagePlugin = opts.showPercentages && opts.horizontal ? {
            id: canvasId + '-percentages',
            afterDatasetsDraw: function (chart) {
                var values = chart.data.datasets[0].data;
                var total = values.reduce(function (sum, value) { return sum + Number(value || 0); }, 0);
                if (!total) { return; }

                var context = chart.ctx;
                var bars = chart.getDatasetMeta(0).data;
                context.save();
                context.font = '600 10px Inter, Segoe UI, sans-serif';
                context.textBaseline = 'middle';

                bars.forEach(function (bar, index) {
                    var percentage = Math.round((Number(values[index] || 0) / total) * 100);
                    var label = percentage + '%';
                    var barWidth = Math.abs(bar.x - bar.base);
                    var fitsInside = barWidth >= context.measureText(label).width + 12;
                    context.textAlign = fitsInside ? 'center' : 'left';
                    context.fillStyle = fitsInside ? '#ffffff' : '#0f766e';
                    context.fillText(label, fitsInside ? (bar.x + bar.base) / 2 : bar.x + 5, bar.y);
                });
                context.restore();
            },
        } : null;
        return makeChart(canvasId, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: opts.label || 'Count',
                    data: data,
                    backgroundColor: color,
                    borderRadius: 6,
                    borderSkipped: false,
                    barThickness: opts.horizontal ? 14 : undefined,
                    maxBarThickness: 22,
                }],
            },
            options: {
                indexAxis: opts.horizontal ? 'y' : 'x',
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: opts.horizontal
                        ? { left: 6, right: opts.showPercentages ? 40 : 12, top: 4, bottom: 4 }
                        : { top: 4, bottom: 4 },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        cornerRadius: 8,
                        padding: 10,
                        callbacks: {
                            title: function (items) {
                                if (!items.length) { return ''; }
                                var idx = items[0].dataIndex;
                                return (opts.fullLabels && opts.fullLabels[idx]) ? opts.fullLabels[idx] : items[0].label;
                            },
                            label: function (ctx) {
                                var val = opts.horizontal ? ctx.parsed.x : ctx.parsed.y;
                                var countLabel = ' ' + (opts.label || 'Count') + ': ' + val + (val === 1 ? ' patient' : ' patients');
                                if (!opts.showPercentages) { return countLabel; }

                                var total = ctx.dataset.data.reduce(function (sum, value) { return sum + Number(value || 0); }, 0);
                                var percentage = total ? Math.round((val / total) * 100) : 0;
                                return countLabel + ' (' + percentage + '%)';
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        max: percentagePlugin ? Math.max(1, Math.max.apply(null, data)) * 1.25 : undefined,
                        grid: { display: opts.horizontal, color: '#f1f5f9' },
                        ticks: { precision: 0, font: { size: 10 } },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: opts.horizontal ? 'transparent' : '#f1f5f9' },
                        ticks: {
                            precision: 0,
                            font: { size: opts.horizontal ? 11 : 10, weight: opts.horizontal ? '600' : 'normal' },
                            color: opts.horizontal ? '#475569' : '#94a3b8',
                            padding: 6,
                        },
                    },
                },
            },
            plugins: percentagePlugin ? [percentagePlugin] : [],
        });
    }

    /**
     * Multi-dataset bar chart (grouped).
     */
    function groupedBarChart(canvasId, labels, datasets) {
        return makeChart(canvasId, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets.map(function (ds) {
                    return Object.assign({ borderRadius: 4, borderSkipped: false }, ds);
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { precision: 0 },
                    },
                },
            },
        });
    }

    /**
     * Doughnut / pie chart.
     * @param {string}   canvasId
     * @param {string[]} labels
     * @param {number[]} data
     * @param {object}   opts   — { colors[], type:'pie'|'doughnut', showPercentages }
     */
    function doughnutChart(canvasId, labels, data, opts) {
        opts = opts || {};
        var colors = opts.colors || [
            '#2563eb', '#10b981', '#f59e0b',
            '#ef4444', '#8b5cf6', '#06b6d4',
        ];
        var percentagePlugin = opts.showPercentages ? {
            id: canvasId + '-percentages',
            afterDatasetsDraw: function (chart) {
                var values = chart.data.datasets[0].data;
                var total = values.reduce(function (sum, value) { return sum + Number(value || 0); }, 0);
                if (!total) { return; }

                var context = chart.ctx;
                var arcs = chart.getDatasetMeta(0).data;
                context.save();
                context.font = '600 11px Inter, Segoe UI, sans-serif';
                context.textAlign = 'center';
                context.textBaseline = 'middle';
                context.fillStyle = '#ffffff';

                arcs.forEach(function (arc, index) {
                    if (!arc || arc.outerRadius < 28 || arc.circumference < Math.PI / 18) { return; }

                    var percentage = Math.round((Number(values[index] || 0) / total) * 100);
                    if (!percentage) { return; }

                    var center = arc.getCenterPoint();
                    context.fillText(percentage + '%', center.x, center.y);
                });
                context.restore();
            },
        } : null;

        return makeChart(canvasId, {
            type: opts.type || 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors.slice(0, data.length),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6,
                    spacing: 3,
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: opts.type === 'pie' ? 0 : '68%',
                layout: { padding: 4 },
                plugins: {
                    legend: {
                        position: opts.legendPosition || 'right',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 10,
                            boxWidth: 7,
                            font: { size: 11, weight: '500' },
                            color: '#475569',
                            generateLabels: function (chart) {
                                var generate = Chart.overrides[chart.config.type].plugins.legend.labels.generateLabels;
                                var items = generate(chart);
                                var values = chart.data.datasets[0].data;
                                var total = values.reduce(function (sum, value) { return sum + Number(value || 0); }, 0);
                                if (!opts.showPercentages || !total) { return items; }

                                return items.map(function (item) {
                                    var percentage = Math.round((Number(values[item.index] || 0) / total) * 100);
                                    item.text = percentage + '% · ' + item.text;
                                    return item;
                                });
                            },
                        },
                    },
                    tooltip: {
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct   = total ? Math.round((ctx.parsed / total) * 100) : 0;
                                return ' ' + ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                            },
                        },
                    },
                },
            },
            plugins: percentagePlugin ? [percentagePlugin] : [],
        });
    }

    /**
     * Sparkline — minimal single-series line for KPI cards.
     */
    function sparkline(canvasId, data, color) {
        color = color || COLORS.blue;
        return makeChart(canvasId, {
            type: 'line',
            data: {
                labels: data.map(function (_, i) { return i; }),
                datasets: [{
                    data: data,
                    borderColor: color,
                    backgroundColor: color.replace(')', ', 0.15)').replace('rgb', 'rgba'),
                    fill: true,
                    tension: 0.5,
                    pointRadius: 0,
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: {
                    x: { display: false },
                    y: { display: false, beginAtZero: true },
                },
            },
        });
    }

    /* ── Utility: animate KPI number counter ─────────────────── */
    function animateCount(el, target, duration) {
        if (!el) { return; }
        duration = duration || 600;
        var start     = 0;
        var startTime = null;
        var formatted = target.toString().includes('.')
            ? function (v) { return v.toFixed(1); }
            : function (v) { return Math.round(v).toLocaleString(); };

        function step(ts) {
            if (!startTime) { startTime = ts; }
            var progress = Math.min((ts - startTime) / duration, 1);
            el.textContent = formatted(start + (target - start) * progress);
            if (progress < 1) { requestAnimationFrame(step); }
        }
        requestAnimationFrame(step);
    }

    /* ── Fallback data generators (used when API returns nothing) ── */
    function last13Days() {
        var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        var labels = [], data = [];
        for (var i = 12; i >= 0; i--) {
            var d = new Date();
            d.setDate(d.getDate() - i);
            labels.push(days[d.getDay()]);
            data.push(0);
        }
        return { labels: labels, data: data };
    }

    function last12Months() {
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var now    = new Date();
        var labels = [], data = [];
        for (var i = 11; i >= 0; i--) {
            var idx = (now.getMonth() - i + 12) % 12;
            labels.push(months[idx]);
            data.push(0);
        }
        return { labels: labels, data: data };
    }

    function threeDayMovingAverage(values) {
        return values.map(function (_, i) {
            var slice = values.slice(Math.max(0, i - 2), i + 1);
            return Math.round(slice.reduce(function (sum, value) { return sum + value; }, 0) / slice.length);
        });
    }

    function _renderDecisionSupport(prefix, report, unavailable, messages) {
        var container = document.getElementById(prefix + '-decision-support-insights');
        if (!container) { return; }

        container.replaceChildren();
        container.dataset.aiRequestId = String((Number(container.dataset.aiRequestId) || 0) + 1);
        var analyzeButton = container.closest('.nh-pm-decision-card')
            ? container.closest('.nh-pm-decision-card').querySelector('[data-ai-analysis-button]')
            : null;
        if (analyzeButton) {
            analyzeButton.disabled = true;
            analyzeButton.removeAttribute('aria-busy');
            var analyzeLabel = analyzeButton.querySelector('.nh-ds-analyze-label');
            if (analyzeLabel) { analyzeLabel.textContent = 'Analyze with AI'; }
        }

        function percentage(part, total) {
            return total > 0 ? Math.round((part / total) * 100) : 0;
        }

        function openInsightDetails(insight, trigger) {
            var dialog = document.getElementById(prefix + '-decision-detail-dialog');
            if (!dialog) {
                dialog = document.createElement('dialog');
                dialog.id = prefix + '-decision-detail-dialog';
                dialog.className = 'nh-ds-dialog';
                dialog.setAttribute('aria-labelledby', prefix + '-decision-detail-title');

                var header = document.createElement('div');
                header.className = 'nh-ds-dialog-header';
                var title = document.createElement('h2');
                title.id = prefix + '-decision-detail-title';
                var close = document.createElement('button');
                close.type = 'button';
                close.className = 'nh-ds-dialog-close';
                close.textContent = 'Close';
                close.setAttribute('aria-label', 'Close insight details');
                close.addEventListener('click', function () { dialog.close(); });
                header.append(title, close);

                var summary = document.createElement('p');
                summary.className = 'nh-ds-dialog-summary';
                var evidence = document.createElement('dl');
                evidence.className = 'nh-ds-evidence-grid';
                var interpretation = document.createElement('section');
                interpretation.className = 'nh-ds-dialog-section';
                var interpretationTitle = document.createElement('h3');
                interpretationTitle.textContent = 'How to read this';
                var interpretationText = document.createElement('p');
                var nextStep = document.createElement('section');
                nextStep.className = 'nh-ds-dialog-section nh-ds-next-step';
                var nextStepTitle = document.createElement('h3');
                nextStepTitle.textContent = 'Operational follow-up';
                var nextStepText = document.createElement('p');
                interpretation.append(interpretationTitle, interpretationText);
                nextStep.append(nextStepTitle, nextStepText);
                dialog.append(header, summary, evidence, interpretation, nextStep);

                dialog.addEventListener('click', function (event) {
                    if (event.target === dialog) { dialog.close(); }
                });
                dialog.addEventListener('close', function () {
                    if (dialog.returnFocus && dialog.returnFocus.isConnected) {
                        dialog.returnFocus.focus();
                    }
                });
                document.body.appendChild(dialog);
            }

            dialog.querySelector('h2').textContent = insight.title;
            dialog.querySelector('.nh-ds-dialog-summary').textContent = insight.detail;
            var evidenceList = dialog.querySelector('.nh-ds-evidence-grid');
            evidenceList.replaceChildren();
            (insight.evidence || []).forEach(function (item) {
                var term = document.createElement('dt');
                term.textContent = item.label;
                var value = document.createElement('dd');
                value.textContent = item.value;
                evidenceList.append(term, value);
            });
            dialog.querySelector('.nh-ds-dialog-section p').textContent = insight.interpretation;
            dialog.querySelector('.nh-ds-next-step p').textContent = insight.action;
            dialog.returnFocus = trigger;
            if (!dialog.open) { dialog.showModal(); }
        }

        function addState(message, warning) {
            var state = document.createElement('div');
            state.className = warning ? 'nh-ds-state nh-ds-warning-text' : 'nh-ds-state';
            state.textContent = message;
            container.appendChild(state);
        }

        function addInsight(insight) {
            var card = document.createElement('article');
            card.className = insight.warning ? 'nh-ds-insight warning' : 'nh-ds-insight';
            card.dataset.insightKey = insight.key || '';

            var icon = document.createElement('span');
            icon.className = 'nh-ds-insight-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = insight.warning ? '<i class="fa fa-triangle-exclamation"></i>' : '<i class="fa fa-chart-line"></i>';

            var content = document.createElement('div');
            var heading = document.createElement('div');
            heading.className = 'nh-ds-insight-title';
            heading.textContent = insight.title;

            var summary = document.createElement('div');
            summary.className = 'nh-ds-insight-detail';
            summary.textContent = insight.detail;
            content.appendChild(heading);
            content.appendChild(summary);

            var aiSummary = document.createElement('p');
            aiSummary.className = 'nh-ds-insight-ai';
            aiSummary.hidden = true;
            content.appendChild(aiSummary);

            var action = document.createElement('div');
            action.className = 'nh-ds-insight-action';
            action.textContent = insight.action;
            content.appendChild(action);

            var detailsButton = document.createElement('button');
            detailsButton.type = 'button';
            detailsButton.className = 'nh-ds-open-details';
            detailsButton.textContent = 'View evidence and details';
            detailsButton.setAttribute('aria-label', 'View details for ' + insight.title);
            detailsButton.addEventListener('click', function () {
                openInsightDetails(insight, detailsButton);
            });

            card.append(icon, content, detailsButton);
            container.appendChild(card);
        }

        if (unavailable || !report) {
            addState(messages && messages.unavailable
                ? messages.unavailable
                : 'Report data is unavailable. Refresh the dashboard to try again.');
            return;
        }

        if (Array.isArray(report.insights)) {
            if (!report.insights.length) {
                addState(report.emptyMessage || 'There is not enough recorded data to generate an insight yet.');
                return;
            }

            report.insights.forEach(function (insight, index) {
                if (!insight.key) {
                    insight.key = 'insight_' + (index + 1);
                }
            });
            report.insights.forEach(addInsight);
            enableAiAnalysis(report.insights);
            return;
        }

        function enableAiAnalysis(reportInsights) {
            if (!analyzeButton) { return; }
            var focusAreas = reportInsights.filter(function (insight) {
                return insight.key;
            }).map(function (insight) {
                return {
                    key: insight.key,
                    title: String(insight.title || '').slice(0, 100),
                    finding: String(insight.detail || '').slice(0, 500),
                    limitation: String(insight.interpretation || '').slice(0, 500),
                };
            });

            if (!focusAreas.length) { return; }
            analyzeButton.disabled = false;
            analyzeButton.onclick = function () {
                requestAiInterpretations(focusAreas);
            };
        }

        function requestAiInterpretations(focusAreas) {
            if (!analyzeButton || analyzeButton.disabled) { return; }
            var requestId = container.dataset.aiRequestId;
            var label = analyzeButton.querySelector('.nh-ds-analyze-label');
            analyzeButton.disabled = true;
            analyzeButton.setAttribute('aria-busy', 'true');
            if (label) { label.textContent = 'Analyzing…'; }

            function finishAnalysis(success) {
                if (container.dataset.aiRequestId !== requestId) { return; }
                analyzeButton.disabled = false;
                analyzeButton.removeAttribute('aria-busy');
                if (label) { label.textContent = success ? 'Refresh AI analysis' : 'Try AI analysis again'; }
            }

            uhPost('/admin/decision-support/analyze', { focus_areas: focusAreas }).then(function (response) {
                if (container.dataset.aiRequestId !== requestId) { return; }

                if (!response || response.success !== true || !response.summaries) {
                    console.warn(
                        'Decision Support AI interpretations could not be generated.',
                        response && response.message ? response.message : 'The server returned no summaries.'
                    );
                    addState('AI interpretations are unavailable; the evidence-based summaries remain available.', true);
                    finishAnalysis(false);
                    return;
                }

                var summaries = response && response.summaries ? response.summaries : {};
                var cards = container.querySelectorAll('.nh-ds-insight[data-insight-key]');
                var appliedCount = 0;

                cards.forEach(function (card) {
                    var text = summaries[card.dataset.insightKey];
                    var summary = card.querySelector('.nh-ds-insight-ai');
                    if (typeof text !== 'string' || !summary) { return; }

                    summary.textContent = 'AI interpretation: ' + text;
                    summary.hidden = false;
                    appliedCount++;
                });

                if (!appliedCount) {
                    addState('AI interpretations are unavailable; the evidence-based summaries remain available.', true);
                }
                finishAnalysis(appliedCount > 0);
            }).catch(function (error) {
                if (container.dataset.aiRequestId !== requestId) { return; }

                console.warn('Decision Support AI interpretation request failed.', error);
                addState('AI interpretations are unavailable; the evidence-based summaries remain available.', true);
                finishAnalysis(false);
            });
        }

        var reasons = Array.isArray(report.top_reasons) ? report.top_reasons : [];
        var eligibleComplaintCount = Number(report.eligible_complaint_count || 0);
        var documentedComplaintCount = Number(report.documented_complaint_count || 0);
        var periodStart = report.period_start || 'the past 12 months';
        var periodEnd = report.period_end || 'today';
        var periodLabel = periodStart + ' to ' + periodEnd;
        var complaintCountsInconsistent = documentedComplaintCount > eligibleComplaintCount;
        var complaintSampleQuality = complaintCountsInconsistent
            ? 'Counts need review'
            : documentedComplaintCount < 5 ? 'Limited sample' : 'Usable sample';
        var complaintCoverage = eligibleComplaintCount > 0 && !complaintCountsInconsistent
            ? percentage(documentedComplaintCount, eligibleComplaintCount)
            : null;
        var complaintCoverageLabel = complaintCountsInconsistent
            ? 'Not calculable: documented complaints exceed eligible requests.'
            : eligibleComplaintCount === 0 ? 'Not available: no eligible requests.'
                : complaintCoverage + '%';
        var insights = [];
        var rankedReasons = reasons.filter(function (item) {
            return Number(item.count) > 0 && String(item.reason || '').trim() !== '';
        }).sort(function (a, b) {
            return Number(b.count) - Number(a.count) || String(a.reason).localeCompare(String(b.reason));
        });
        var leadingReason = rankedReasons[0];

        insights.push({
            key: 'complaint_coverage',
            title: 'Student complaint coverage and confidence',
            detail: complaintCountsInconsistent
                ? 'The documented complaint count exceeds eligible consultation requests; coverage cannot be calculated. Data check required.'
                : eligibleComplaintCount === 0
                    ? 'No eligible campus consultation requests were recorded, so complaint coverage is not available.'
                    : documentedComplaintCount.toLocaleString() + ' of ' + eligibleComplaintCount.toLocaleString() +
                        ' campus consultation requests include a written complaint (' + complaintCoverage + '%). ' + complaintSampleQuality + '.',
            action: complaintCountsInconsistent
                ? 'Review report filters and source records before using this coverage measure.'
                : documentedComplaintCount < 5
                ? 'Continue capturing student complaints; avoid using this small sample to forecast demand.'
                : 'Review documentation coverage before comparing this period with other campuses.',
            warning: complaintCountsInconsistent || documentedComplaintCount < 5,
            evidence: [
                { label: 'Reporting period', value: periodLabel },
                { label: 'Campus consultation requests', value: eligibleComplaintCount.toLocaleString() },
                { label: 'Requests with a student complaint', value: documentedComplaintCount.toLocaleString() },
                { label: 'Complaint coverage', value: complaintCoverageLabel },
                { label: 'Audio-only placeholders', value: 'Excluded from documented complaint trends.' },
                { label: 'Synthetic sample clinic visits', value: Number(report.excluded_demo_visit_count || 0).toLocaleString() + ' excluded from visit totals and history.' },
            ],
            interpretation: complaintCountsInconsistent
                ? 'The documented count cannot exceed its eligible-request denominator. The source data or report filters must be checked before drawing a coverage conclusion.'
                : eligibleComplaintCount === 0
                    ? 'No denominator is available, so a coverage percentage cannot be calculated. This is not evidence that documentation coverage is zero.'
                    : 'Confidence reflects how many campus consultation requests contain a written student complaint. It is a reporting-quality signal, not a clinical confidence score.',
        });

        if (leadingReason) {
            var reasonCount = Number(leadingReason.count);
            var reasonShare = percentage(reasonCount, documentedComplaintCount);
            insights.push({
                key: 'common_complaint',
                title: 'Most common student complaint',
                detail: String(leadingReason.reason) + ' — ' + reasonCount.toLocaleString() +
                    ' of ' + documentedComplaintCount.toLocaleString() + ' documented campus complaints (' + reasonShare + '%).',
                action: documentedComplaintCount < 5
                    ? 'Treat this as an observed record, not a reliable demand trend.'
                    : 'Planning prompt: review whether staffing and routine supplies match this recorded demand.',
                evidence: [
                    { label: 'Reporting period', value: periodLabel },
                    { label: 'Most frequent student complaint', value: String(leadingReason.reason) },
                    { label: 'Matching campus requests', value: reasonCount.toLocaleString() },
                    { label: 'Share of documented complaints', value: reasonShare + '%' },
                    { label: 'Sample size', value: documentedComplaintCount.toLocaleString() + ' documented campus complaints' },
                ],
                interpretation: reasonShare + '% is the share among campus consultation requests with a written complaint. It does not indicate disease prevalence or patient risk.',
            });
        }

        var topThreeCount = rankedReasons.slice(0, 3).reduce(function (sum, item) {
            return sum + Math.max(0, Number(item.count) || 0);
        }, 0);
        if (topThreeCount > 0 && documentedComplaintCount > 0) {
            var topThreeShare = percentage(topThreeCount, documentedComplaintCount);
            insights.push({
                key: 'complaint_concentration',
                title: 'Complaint concentration',
                detail: 'The top three recorded complaint categories account for ' + topThreeShare +
                    '% of documented campus complaints.',
                action: 'Use this mix to guide service planning; review the full complaint list before making staffing changes.',
                evidence: [
                    { label: 'Reporting period', value: periodLabel },
                    { label: 'Top three categories', value: rankedReasons.slice(0, 3).map(function (item) { return item.reason; }).join(', ') },
                    { label: 'Requests in these categories', value: topThreeCount.toLocaleString() },
                    { label: 'Share of documented complaints', value: topThreeShare + '%' },
                    { label: 'Documented complaint denominator', value: documentedComplaintCount.toLocaleString() },
                ],
                interpretation: 'Concentration describes the recorded complaint mix within this campus and period. It is not a measure of prevalence or severity.',
            });
        }

        var complaintDepartments = Array.isArray(report.complaints_by_department) ? report.complaints_by_department : [];
        var leadingComplaintDepartment = complaintDepartments.find(function (item) {
            return Number(item.count) > 0 && String(item.department || '').trim() !== '';
        });
        if (leadingComplaintDepartment) {
            var departmentComplaintCount = Number(leadingComplaintDepartment.count);
            var departmentComplaintShare = percentage(departmentComplaintCount, documentedComplaintCount);
            insights.push({
                key: 'complaints_by_department',
                title: 'Complaints by student department',
                detail: String(leadingComplaintDepartment.department) + ' has the most documented complaints (' +
                    departmentComplaintCount.toLocaleString() + ', ' + departmentComplaintShare + '% of the campus total).',
                action: 'Check department enrollment and complaint documentation coverage before comparing departments.',
                evidence: [
                    { label: 'Reporting period', value: periodLabel },
                    { label: 'Department with the most complaints', value: String(leadingComplaintDepartment.department) },
                    { label: 'Documented complaints', value: departmentComplaintCount.toLocaleString() },
                    { label: 'Share of selected campus complaints', value: departmentComplaintShare + '%' },
                    { label: 'Documented complaint denominator', value: documentedComplaintCount.toLocaleString() },
                ],
                interpretation: 'Counts are grouped by the student’s recorded department. They are not adjusted for department enrollment size and do not imply a higher health risk.',
            });
        }

        var complaintCampuses = Array.isArray(report.complaints_by_campus) ? report.complaints_by_campus : [];
        var leadingComplaintCampus = complaintCampuses.find(function (item) {
            return Number(item.count) > 0 && String(item.campus || '').trim() !== '';
        });
        if (leadingComplaintCampus && complaintCampuses.length > 1) {
            var campusComplaintCount = Number(leadingComplaintCampus.count);
            var campusComplaintShare = percentage(campusComplaintCount, documentedComplaintCount);
            insights.push({
                key: 'complaints_by_campus',
                title: 'Complaint distribution by campus',
                detail: String(leadingComplaintCampus.campus) + ' has the largest recorded share: ' +
                    campusComplaintCount.toLocaleString() + ' complaints (' + campusComplaintShare + '%).',
                action: 'Compare campus complaint counts with each campus roster size and reporting coverage before reallocating services.',
                evidence: [
                    { label: 'Reporting period', value: periodLabel },
                    { label: 'Campus with the most documented complaints', value: String(leadingComplaintCampus.campus) },
                    { label: 'Documented complaints', value: campusComplaintCount.toLocaleString() },
                    { label: 'Share of all documented complaints', value: campusComplaintShare + '%' },
                    { label: 'Documented complaint denominator', value: documentedComplaintCount.toLocaleString() },
                ],
                interpretation: 'This compares counts, not rates. Different campus enrollment sizes and complaint documentation completeness can affect the totals.',
            });
        }

        var complaintMonths = (Array.isArray(report.monthly_complaints) ? report.monthly_complaints : [])
            .map(function (item) {
                return {
                    label: String(item.label || item.month || ''),
                    month: String(item.month || ''),
                    count: Number(item.count),
                };
            })
            .filter(function (item) { return item.label !== '' && item.count >= 0 && Number.isFinite(item.count); });
        if (complaintMonths.length >= 2) {
            var latestComplaintMonth = complaintMonths[complaintMonths.length - 1];
            var previousComplaintMonth = complaintMonths[complaintMonths.length - 2];
            var monthlyComplaintDifference = latestComplaintMonth.count - previousComplaintMonth.count;
            var currentMonthKey = String(report.period_end || '').slice(0, 7);
            var latestComplaintMonthKey = latestComplaintMonth.month;
            var isCurrentComplaintMonth = /^\d{4}-\d{2}$/.test(latestComplaintMonthKey)
                && latestComplaintMonthKey === currentMonthKey;
            var monthlyComplaintChange = isCurrentComplaintMonth
                ? 'Not directly comparable; the current month is incomplete.'
                : monthlyComplaintDifference > 0
                    ? 'Up by ' + monthlyComplaintDifference.toLocaleString()
                    : monthlyComplaintDifference < 0
                        ? 'Down by ' + Math.abs(monthlyComplaintDifference).toLocaleString()
                        : 'Unchanged';
            insights.push({
                key: 'monthly_complaints',
                title: 'Monthly complaint activity',
                detail: latestComplaintMonth.label + (isCurrentComplaintMonth ? ' to date: ' : ': ') +
                    latestComplaintMonth.count.toLocaleString() + ' documented complaints; previous month: ' +
                    previousComplaintMonth.count.toLocaleString() + (isCurrentComplaintMonth
                        ? ' (incomplete-month comparison).'
                        : ' (' + monthlyComplaintChange.toLowerCase() + ').'),
                action: isCurrentComplaintMonth
                    ? 'Wait until the month closes, or compare matching days using daily-level records.'
                    : 'Review the complaint records and reporting coverage before interpreting month-to-month change.',
                evidence: [
                    { label: isCurrentComplaintMonth ? 'Current month to date' : 'Latest month', value: latestComplaintMonth.label },
                    { label: 'Latest month complaints', value: latestComplaintMonth.count.toLocaleString() },
                    { label: 'Previous month', value: previousComplaintMonth.label },
                    { label: 'Previous month complaints', value: previousComplaintMonth.count.toLocaleString() },
                    { label: 'Change', value: monthlyComplaintChange },
                ],
                interpretation: isCurrentComplaintMonth
                    ? 'The current month is incomplete, while the previous month is a full-month total. Their counts are not a like-for-like trend comparison.'
                    : 'This compares the last two calendar-month buckets, including months with zero recorded complaints. Changes can reflect reporting completeness as well as demand.',
            });
        }

        var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        var months = (Array.isArray(report.monthly_visits) ? report.monthly_visits : [])
            .map(function (item) {
                return {
                    month: Number(item.month),
                    count: Number(item.count),
                };
            })
            .filter(function (item) {
                return item.month >= 1 && item.month <= 12 && item.count >= 0 && Number.isFinite(item.count);
            })
            .sort(function (a, b) { return a.month - b.month; });

        if (months.length >= 1) {
            var latestMonth = months[months.length - 1];
            var previousMonth = months.length >= 2 ? months[months.length - 2] : null;
            var difference = previousMonth ? latestMonth.count - previousMonth.count : null;
            var change = difference === null ? 'No earlier month with visits is available for comparison.'
                : difference > 0 ? 'Up by ' + difference.toLocaleString()
                    : difference < 0 ? 'Down by ' + Math.abs(difference).toLocaleString()
                        : 'Unchanged';
            insights.push({
                key: 'monthly_visits',
                title: 'Visit activity by month',
                detail: monthNames[latestMonth.month - 1] + ': ' + latestMonth.count.toLocaleString() +
                    ' real visits. ' + (previousMonth
                        ? monthNames[previousMonth.month - 1] + ': ' + previousMonth.count.toLocaleString() + ' (' + change.toLowerCase() + ').'
                        : change),
                action: previousMonth
                    ? 'Compare the underlying records and clinic schedule before interpreting this change.'
                    : 'More monthly records are needed before identifying a trend.',
                evidence: [
                    { label: 'Reporting year', value: String(report.year || new Date().getFullYear()) },
                    { label: 'Latest month with records', value: monthNames[latestMonth.month - 1] },
                    { label: 'Latest month visits', value: latestMonth.count.toLocaleString() },
                    { label: 'Previous month with records', value: previousMonth ? monthNames[previousMonth.month - 1] : 'Not available' },
                    { label: 'Change between reported months', value: change },
                ],
                interpretation: previousMonth
                    ? 'This compares the latest two months containing recorded visits. Months with zero activity are not shown by the report, so it is not necessarily a consecutive-month comparison.'
                    : 'Only one month currently has recorded visits in the selected reporting year; no direction or forecast can be inferred.',
            });
        }

        var dispositions = Array.isArray(report.dispositions) ? report.dispositions : [];
        var leadingDisposition = dispositions.find(function (item) {
            return Number(item.count) > 0 && String(item.disposition || '').trim() !== '';
        });
        var dispositionCount = dispositions.reduce(function (sum, item) {
            return sum + Math.max(0, Number(item.count) || 0);
        }, 0);

        if (leadingDisposition) {
            var outcomeCount = Number(leadingDisposition.count);
            var outcomeShare = percentage(outcomeCount, dispositionCount);
            insights.push({
                key: 'common_outcome',
                title: 'Most common recorded outcome',
                detail: String(leadingDisposition.disposition) + ' — ' + outcomeCount.toLocaleString() +
                    ' of ' + dispositionCount.toLocaleString() + ' visits with a recorded outcome (' + outcomeShare + '%).',
                action: 'Review these outcome records to identify operational follow-up needs.',
                evidence: [
                    { label: 'Reporting period', value: periodLabel },
                    { label: 'Most frequent outcome', value: String(leadingDisposition.disposition) },
                    { label: 'Matching visits', value: outcomeCount.toLocaleString() },
                    { label: 'Share of recorded outcomes', value: outcomeShare + '%' },
                    { label: 'Visits with a recorded outcome', value: dispositionCount.toLocaleString() },
                ],
                interpretation: 'This summarizes recorded dispositions only. Blank dispositions and excluded synthetic sample visits are not included.',
            });
        }

        if (!insights.length) {
            addState('There is not enough recorded visit data to generate an operational insight yet.');
            return;
        }

        insights.forEach(function (insight) {
            addInsight(insight);
        });
        enableAiAnalysis(insights);
    }

    function _renderDashboardInsights(prefix, insights, unavailableMessage, emptyMessage) {
        _renderDecisionSupport(
            prefix,
            { insights: insights, emptyMessage: emptyMessage },
            false,
            { unavailable: unavailableMessage }
        );
    }

    function _unavailableDashboardInsights(prefix, message) {
        _renderDecisionSupport(prefix, null, true, { unavailable: message });
    }

    /* ── Admin / NurseHead analytics loader ───────────────────── */
    function loadAdminAnalytics(campusQs, prefix) {
        prefix = prefix || 'an';
        var qs  = campusQs || '';

        /* --- KPI stats from dashboard endpoint --- */
        uhGet('/admin/dashboard' + qs).then(function (res) {
            var s = res.stats || {};
            _animateKpi(prefix + '-kpi-patients',  s.total_patients    || s.total_students || 0);
            _animateKpi(prefix + '-kpi-visits',    s.total_records     || 0);
            _animateKpi(prefix + '-kpi-today',     s.todays_visits     || 0);
            _animateKpi(prefix + '-kpi-appts',     s.todays_appointments || 0);
            _animateKpi(prefix + '-kpi-pending-appts', s.pending_appointments || 0);
            _animateKpi(prefix + '-kpi-referrals', s.pending_referrals || 0);
        }).catch(function () {});

        /* --- Daily visits line chart --- */
        uhGet('/admin/reports?type=daily&days=13' + (qs ? '&' + qs.slice(1) : '')).then(function (res) {
            var daily  = (res.report && res.report.daily_visits) ? res.report.daily_visits : [];
            var lbl    = daily.map(function (d) { return d.day_label || '—'; });
            var counts = daily.map(function (d) { return d.count || 0; });
            if (!lbl.length) { var fb = last13Days(); lbl = fb.labels; counts = fb.data; }
            _pmTrendChart(prefix + '-chart-daily', lbl, counts, threeDayMovingAverage(counts));
        }).catch(function () {
            var fb = last13Days();
            _pmTrendChart(prefix + '-chart-daily', fb.labels, fb.data, threeDayMovingAverage(fb.data));
        });

        /* --- Monthly visits bar chart --- */
        uhGet('/admin/reports?type=overview' + (qs ? '&' + qs.slice(1) : '')).then(function (res) {
            var rpt     = res.report || {};
            var monthly = rpt.monthly_visits || [];
            _renderDecisionSupport(prefix, rpt, !res.success || !res.report);
            var months  = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            var mLabels, mData;

            if (monthly.length) {
                mLabels = monthly.map(function (m) { return months[(m.month || 1) - 1]; });
                mData   = monthly.map(function (m) { return m.count || 0; });
            } else {
                var fb  = last12Months();
                mLabels = fb.labels;
                mData   = fb.data;
            }
            barChart(prefix + '-chart-monthly', mLabels, mData, { label: 'Visits', color: COLORS.indigo });

            /* Show the most common campus student complaints as a pie chart. */
            var top = rpt.top_reasons || [];
            if (top.length) {
                var complaintLabels = top.slice(0, 6).map(function (r) { return r.reason || '—'; });
                doughnutChart(
                    prefix + '-chart-complaints',
                    complaintLabels,
                    top.slice(0, 6).map(function (r) { return r.count || 0; }),
                    { type: 'pie', showPercentages: true }
                );
            } else {
                doughnutChart(
                    prefix + '-chart-complaints',
                    ['No data'],
                    [0],
                    { type: 'pie', colors: [COLORS.muted] }
                );
            }

            var complaintTrend = Array.isArray(rpt.monthly_complaints) ? rpt.monthly_complaints : [];
            if (complaintTrend.length) {
                lineChart(
                    prefix + '-chart-complaint-trend',
                    complaintTrend.map(function (item) { return item.label; }),
                    complaintTrend.map(function (item) { return Number(item.count) || 0; }),
                    { label: 'Documented complaints', color: COLORS.indigo }
                );
            } else {
                lineChart(prefix + '-chart-complaint-trend', ['No data'], [0], {
                    label: 'Documented complaints',
                    color: COLORS.muted,
                });
            }

            var complaintsByDepartment = Array.isArray(rpt.complaints_by_department)
                ? rpt.complaints_by_department.slice(0, 8)
                : [];
            if (complaintsByDepartment.length) {
                var departmentLabels = complaintsByDepartment.map(function (item) {
                    var share = Number(item.share_percent) || 0;
                    return String(item.department || 'Unspecified') + ' (' + share + '%)';
                });
                barChart(
                    prefix + '-chart-complaint-dept',
                    departmentLabels,
                    complaintsByDepartment.map(function (item) { return Number(item.count) || 0; }),
                    {
                        label: 'Documented complaints',
                        color: '#0d9488',
                        horizontal: true,
                    }
                );
            } else {
                barChart(prefix + '-chart-complaint-dept', ['No data'], [0], {
                    label: 'Documented complaints',
                    color: COLORS.muted,
                    horizontal: true,
                });
            }

            /* Disposition progress bars */
            var disp = rpt.dispositions || [];
            _renderDispositions(prefix + '-dispositions', disp);

            /* Department distribution horizontal bar */
            var dept = rpt.dept_distribution || [];
            if (dept.length) {
                var fullLabels = dept.slice(0, 8).map(function (d) { return d.department || '—'; });
                var shortLabels = fullLabels.map(function (name) {
                    var match = name.match(/\(([^)]+)\)/);
                    if (match && match[1]) { return match[1]; }
                    return name.length > 15 ? name.substring(0, 14) + '…' : name;
                });
                barChart(
                    prefix + '-chart-dept',
                    shortLabels,
                    dept.slice(0, 8).map(function (d) { return d.patient_count || 0; }),
                    {
                        label: 'Patients',
                        color: '#0d9488',
                        horizontal: true,
                        showPercentages: true,
                        fullLabels: fullLabels,
                    }
                );
            } else {
                barChart(prefix + '-chart-dept', ['No data'], [0], { label: 'Patients', color: '#0d9488', horizontal: true });
            }

            /* Campus comparison grouped bar — real per-campus monthly data */
            uhGet('/admin/reports?type=campus_monthly&months=6' + (qs ? '&' + qs.slice(1) : '')).then(function (campusRes) {
                var rep   = (campusRes && campusRes.report) || {};
                var lbls  = rep.labels || [];
                var series = rep.series || [];
                var palette = [COLORS.blue, COLORS.green, COLORS.amber, COLORS.purple, COLORS.red, COLORS.indigo, COLORS.teal];
                var sets = series.map(function (s, i) {
                    return {
                        label: s.name || ('Campus ' + (i + 1)),
                        data: s.data || [],
                        backgroundColor: palette[i % palette.length],
                    };
                });
                if (!lbls.length || !sets.length) {
                    groupedBarChart(prefix + '-chart-campus', ['—'], [{ label: 'No data', data: [0], backgroundColor: COLORS.muted }]);
                } else {
                    groupedBarChart(prefix + '-chart-campus', lbls, sets);
                }
            }).catch(function () {
                groupedBarChart(prefix + '-chart-campus', ['—'], [{ label: 'No data', data: [0], backgroundColor: COLORS.muted }]);
            });
        }).catch(function () {
            _renderDecisionSupport(prefix, null, true);
            // Don't show fake data when the API call fails — render an empty
            // chart so users see something is missing instead of a lie.
            var fb = last12Months();
            barChart(prefix + '-chart-monthly', fb.labels, fb.data.map(function () { return 0; }), { label: 'Visits', color: COLORS.indigo });
            barChart(prefix + '-chart-complaints', ['No data'], [0], {
                label: 'Visits',
                color: COLORS.muted,
                horizontal: true,
            });
            lineChart(prefix + '-chart-complaint-trend', ['No data'], [0], {
                label: 'Documented complaints',
                color: COLORS.muted,
            });
            barChart(prefix + '-chart-complaint-dept', ['No data'], [0], {
                label: 'Documented complaints',
                color: COLORS.muted,
                horizontal: true,
            });
        });
    }

    /* ── Nurse analytics loader ───────────────────────────────── */
    function loadNurseAnalytics(prefix) {
        prefix = prefix || 'nan';
        uhGet('/nurse/stats').then(function (res) {
            var s = (res && res.stats) || res || {};
            var todaysVisits = Number(s.todays_visits) || 0;
            var pendingAppointments = Number(s.pending_appointments) || 0;
            var formsIssued = Number(s.forms_issued) || 0;
            _animateKpi(prefix + '-kpi-visits',   s.todays_visits   || 0);
            _animateKpi(prefix + '-kpi-forms',    s.forms_issued    || 0);
            _animateKpi(prefix + '-kpi-patients', s.total_patients  || 0);
            _animateKpi(prefix + '-kpi-pending',  s.pending_appointments || 0);

            _renderDashboardInsights(prefix, [
                {
                    title: 'Appointments awaiting confirmation',
                    detail: pendingAppointments
                        ? pendingAppointments.toLocaleString() + ' appointment' + (pendingAppointments === 1 ? '' : 's') + ' currently need review.'
                        : 'There are no appointments awaiting confirmation in the current count.',
                    action: pendingAppointments
                        ? 'Review the appointment queue and update each request after checking the clinic workflow.'
                        : 'Continue monitoring the queue as new appointment requests arrive.',
                    warning: pendingAppointments > 0,
                    evidence: [
                        { label: 'Pending appointments', value: pendingAppointments.toLocaleString() },
                        { label: 'Source', value: 'Current nurse dashboard statistics' },
                    ],
                    interpretation: 'This is a workflow count, not a clinical urgency assessment.',
                },
                {
                    title: 'Today’s clinic activity',
                    detail: todaysVisits.toLocaleString() + ' visit' + (todaysVisits === 1 ? '' : 's') +
                        ' recorded today; ' + formsIssued.toLocaleString() + ' forms issued this month.',
                    action: 'Keep visit and form records complete so the next care-team member has an accurate handoff.',
                    evidence: [
                        { label: 'Visits recorded today', value: todaysVisits.toLocaleString() },
                        { label: 'Forms issued this month', value: formsIssued.toLocaleString() },
                        { label: 'Patient roster', value: (Number(s.total_patients) || 0).toLocaleString() },
                    ],
                    interpretation: 'Counts summarize the current nurse dashboard data and do not evaluate patient conditions.',
                },
            ], 'Nurse dashboard information is unavailable. Refresh the page to try again.');
        }).catch(function () {
            _unavailableDashboardInsights(prefix, 'Nurse dashboard information is unavailable. Refresh the page to try again.');
        });

        /* Daily visits sparkline — reuse admin daily endpoint with nurse prefix omitted */
        uhGet('/admin/reports?type=daily&days=7').then(function (res) {
            var daily  = (res.report && res.report.daily_visits) ? res.report.daily_visits : [];
            var lbl    = daily.map(function (d) { return d.day_label || '—'; });
            var counts = daily.map(function (d) { return d.count || 0; });
            if (!lbl.length) { var fb = last13Days(); lbl = fb.labels.slice(-7); counts = fb.data.slice(-7); }
            barChart(prefix + '-chart-daily', lbl, counts, { label: 'Visits', color: COLORS.blue });
        }).catch(function () {
            barChart(prefix + '-chart-daily', ['—'], [0], { label: 'Visits', color: COLORS.blue });
        });

        /* Disposition doughnut — real data from /admin/reports?type=overview */
        uhGet('/admin/reports?type=overview').then(function (res) {
            var disp = (res.report && res.report.dispositions) || [];
            if (disp.length) {
                doughnutChart(
                    prefix + '-chart-disposition',
                    disp.map(function (d) { return d.disposition || '—'; }),
                    disp.map(function (d) { return d.count || 0; })
                );
            } else {
                doughnutChart(prefix + '-chart-disposition', ['No data'], [1]);
            }
        }).catch(function () {
            doughnutChart(prefix + '-chart-disposition', ['No data'], [1]);
        });
    }

    /* ── Physician & Dentist analytics loader ──────────────────── */
    function loadPhysicianAnalytics(prefix, role) {
        prefix = prefix || 'pha';
        var user = (typeof uhGetUser === 'function') ? uhGetUser() : (window.__clinicUser || {});
        role = role || (user && user.role) || 'physician';
        var qs = role ? '?role=' + encodeURIComponent(role) : '';

        uhGet('/physician/stats' + qs).then(function (res) {
            var s = (res && res.stats) || res || {};
            _animateKpi(prefix + '-kpi-patients',  s.total_patients || 0);
            _animateKpi(prefix + '-kpi-diagnoses', s.total_diagnoses || 0);
            _animateKpi(prefix + '-kpi-rx',        s.active_prescriptions || 0);
            _animateKpi(prefix + '-kpi-referrals', s.total_referrals || 0);

            var todayConsultations = Number(s.today_consultations) || 0;
            var pendingTranscriptions = Number(s.pending_transcriptions) || 0;
            var pendingReferrals = Number(s.pending_referrals) || 0;
            var totalVisits = Number(s.total_visits) || 0;
            var documentedCareRecords = Number(s.total_diagnoses) || 0;
            var conditions = Array.isArray(s.top_conditions) ? s.top_conditions : [];
            var leadingCondition = conditions.find(function (item) {
                return Number(item.count) > 0 && String(item.cond_name || item.condition || item.reason || '').trim();
            });
            var isDentist = role === 'dentist';
            var conditionRecordCount = Number(s.condition_record_count) || 0;
            var conditionCoverage = totalVisits > 0
                ? Math.round((documentedCareRecords / totalVisits) * 100)
                : 0;
            var dailyRecords = Array.isArray(s.daily_consultations) ? s.daily_consultations : [];
            var recentWeek = dailyRecords.slice(-7);
            var earlierDays = dailyRecords.slice(-13, -7);
            var recentVisits = recentWeek.reduce(function (sum, item) { return sum + (Number(item.count) || 0); }, 0);
            var earlierVisits = earlierDays.reduce(function (sum, item) { return sum + (Number(item.count) || 0); }, 0);
            var recentAverage = recentWeek.length ? recentVisits / recentWeek.length : null;
            var earlierAverage = earlierDays.length ? earlierVisits / earlierDays.length : null;
            var activitySummary = 'Recent activity comparison is unavailable.';

            if (recentAverage !== null && earlierAverage !== null) {
                if (earlierAverage === 0 && recentAverage > 0) {
                    activitySummary = 'Visits were recorded in the latest 7 days after no visits in the preceding 6 days.';
                } else if (earlierAverage === 0) {
                    activitySummary = 'No visits were recorded in either the latest 7 days or the preceding 6 days.';
                } else {
                    var activityChange = Math.round(((recentAverage - earlierAverage) / earlierAverage) * 100);
                    var activityDirection = activityChange > 0
                        ? 'increased by ' + activityChange + '%'
                        : activityChange < 0
                            ? 'decreased by ' + Math.abs(activityChange) + '%'
                            : 'was unchanged';
                    activitySummary = 'Average daily visits ' + activityDirection + ' (' +
                        recentAverage.toFixed(1) + ' vs ' + earlierAverage.toFixed(1) +
                        ') for the latest 7 days compared with the preceding 6 days.';
                }
            }

            var conditionSummary = leadingCondition && conditionRecordCount > 0
                ? String(leadingCondition.cond_name || leadingCondition.condition || leadingCondition.reason) +
                    ' is the most frequent ' + (s.condition_label_basis === 'treatment labels' ? 'treatment label' : 'visit complaint') +
                    ' (' + Number(leadingCondition.count).toLocaleString() + ' of ' +
                    conditionRecordCount.toLocaleString() + ' labeled records, ' +
                    Math.round((Number(leadingCondition.count) / conditionRecordCount) * 100) + '%).'
                : 'There are not enough labeled records to identify a leading complaint or treatment label.';
            var pendingWorkCount = pendingTranscriptions + pendingReferrals;
            var recordSummary = totalVisits.toLocaleString() + ' total ' +
                (isDentist ? 'dental/clinic records' : 'clinic visits') + ' are in scope; ' +
                documentedCareRecords.toLocaleString() + ' (' + conditionCoverage +
                '%) include recorded diagnosis/treatment details. ' + conditionSummary + ' ' +
                activitySummary + ' ' + pendingWorkCount.toLocaleString() + ' transcription/referral items are pending.';
            var clinicianInsights = [
                {
                    title: 'Record summary',
                    detail: recordSummary,
                    action: pendingWorkCount > 0
                        ? 'Review the pending transcription and referral queues, then verify the underlying records before acting on any trend.'
                        : 'Use the activity and complaint patterns as a review aid; confirm details in the individual patient records.',
                    evidence: [
                        { label: 'Records in your scope', value: totalVisits.toLocaleString() },
                        { label: 'Records with diagnosis/treatment details', value: documentedCareRecords.toLocaleString() + ' (' + conditionCoverage + '%)' },
                        { label: 'Leading recorded label', value: leadingCondition ? String(leadingCondition.cond_name || leadingCondition.condition || leadingCondition.reason) : 'Not available' },
                        { label: 'Labeled records', value: conditionRecordCount.toLocaleString() },
                        { label: 'Activity comparison', value: activitySummary },
                        { label: 'Pending transcriptions / referrals', value: pendingTranscriptions.toLocaleString() + ' / ' + pendingReferrals.toLocaleString() },
                    ],
                    interpretation: 'This summary combines workflow counts, recorded labels, and a short activity comparison. Label share reflects only records with a usable complaint or treatment label; it is not disease prevalence, clinical urgency, or a treatment recommendation.',
                },
                {
                    title: isDentist ? 'Today’s dental schedule' : 'Today’s consultations',
                    detail: todayConsultations.toLocaleString() + ' consultation' +
                        (todayConsultations === 1 ? '' : 's') + ' scheduled or recorded for today.',
                    action: 'Review the schedule and patient records as part of the usual care workflow.',
                    evidence: [
                        { label: isDentist ? 'Dental consultations today' : 'Consultations today', value: todayConsultations.toLocaleString() },
                        { label: 'Active patients in your scope', value: (Number(s.total_patients) || 0).toLocaleString() },
                    ],
                    interpretation: 'This is a summary of schedule activity only and does not prioritize patients clinically.',
                },
                {
                    title: 'Items needing documentation follow-up',
                    detail: pendingTranscriptions.toLocaleString() + ' transcription' +
                        (pendingTranscriptions === 1 ? '' : 's') + ' pending and ' +
                        pendingReferrals.toLocaleString() + ' referral' + (pendingReferrals === 1 ? '' : 's') + ' pending.',
                    action: 'Review pending documentation and referrals through their respective dashboard sections.',
                    warning: pendingTranscriptions + pendingReferrals > 0,
                    evidence: [
                        { label: 'Pending transcriptions', value: pendingTranscriptions.toLocaleString() },
                        { label: 'Pending referrals', value: pendingReferrals.toLocaleString() },
                        { label: 'Active prescriptions', value: (Number(s.active_prescriptions) || 0).toLocaleString() },
                    ],
                    interpretation: 'Pending counts indicate unfinished workflow items; they are not treatment recommendations.',
                },
            ];

            if (leadingCondition) {
                var conditionName = String(leadingCondition.cond_name || leadingCondition.condition || leadingCondition.reason);
                var conditionCount = Number(leadingCondition.count) || 0;
                var conditionShare = conditionRecordCount > 0
                    ? Math.round((conditionCount / conditionRecordCount) * 100)
                    : null;
                clinicianInsights.push({
                    title: isDentist ? 'Most frequent recorded dental label' : 'Most frequent recorded visit complaint',
                    detail: conditionName + ' appears in ' + conditionCount.toLocaleString() +
                        (conditionRecordCount > 0
                            ? ' of ' + conditionRecordCount.toLocaleString() + ' labeled records (' + conditionShare + '%).'
                            : ' of the returned ranked records; the complete labeled-record count is unavailable.'),
                    action: 'Use this count as a workload summary only; continue to assess each patient individually.',
                    evidence: [
                        { label: isDentist ? 'Leading dental label' : 'Leading visit complaint', value: conditionName },
                        { label: 'Matching records', value: conditionCount.toLocaleString() },
                        { label: 'Share of labeled records', value: conditionShare === null ? 'Unavailable' : conditionShare + '%' },
                        { label: 'Label basis', value: s.condition_label_basis || 'visit complaints' },
                        { label: 'Records with labels', value: conditionRecordCount.toLocaleString() },
                    ],
                    interpretation: 'This ranks recorded visit complaints or treatment labels in the current data. Its share is limited to records with a usable label and is not a disease prevalence estimate or a clinical recommendation.',
                });
            }

            _renderDashboardInsights(prefix, clinicianInsights, 'Clinician dashboard information is unavailable. Refresh the page to try again.');

            /* Daily consultation trend (last 13 days) directly from DB */
            var daily = s.daily_consultations || [];
            var lbl   = daily.map(function (d) { return d.day_label || '—'; });
            var dat   = daily.map(function (d) { return d.count || 0; });
            if (!lbl.length) {
                var fb = last13Days();
                lbl = fb.labels;
                dat = fb.data;
            }
            var chartLabel = role === 'dentist' ? 'Dental Consultations' : 'Consultations';
            lineChart(prefix + '-chart-diagnoses', lbl, dat, { label: chartLabel, color: COLORS.green });

            /* Ranked conditions are clearer as horizontal bars than a doughnut. */
            var conditions = s.top_conditions || [];
            if (conditions.length) {
                var fullLabels = conditions.slice(0, 6).map(function (r) {
                    return r.cond_name || r.condition || r.reason || '—';
                });
                barChart(
                    prefix + '-chart-conditions',
                    fullLabels.map(function (label) {
                        return label.length > 20 ? label.substring(0, 19) + '…' : label;
                    }),
                    conditions.slice(0, 6).map(function (r) { return r.count || 0; }),
                    {
                        label: role === 'dentist' ? 'Procedures' : 'Cases',
                        color: role === 'dentist' ? COLORS.teal : COLORS.green,
                        horizontal: true,
                        fullLabels: fullLabels,
                    }
                );
            } else {
                barChart(prefix + '-chart-conditions', ['No records yet'], [0], {
                    label: role === 'dentist' ? 'Procedures' : 'Cases',
                    color: COLORS.muted,
                    horizontal: true,
                });
            }
        }).catch(function () {
            _unavailableDashboardInsights(prefix, 'Clinician dashboard information is unavailable. Refresh the page to try again.');
            var fb = last13Days();
            lineChart(prefix + '-chart-diagnoses', fb.labels, fb.data, { label: 'Consultations', color: COLORS.green });
            barChart(prefix + '-chart-conditions', ['No data'], [0], {
                label: role === 'dentist' ? 'Procedures' : 'Cases',
                color: COLORS.muted,
                horizontal: true,
            });
        });
    }

    /* ── Student health analytics loader ─────────────────────── */
    function renderStudentInsights(prefix, stats) {
        var nextAppointment = stats.next_appointment || null;
        var activePrescriptions = Number(stats.active_prescriptions) || 0;
        var openReferrals = Number(stats.open_referrals) || 0;
        var healthRecordStatus = stats.health_record_status || 'Not available';

        _renderDashboardInsights(prefix, [
            {
                title: 'Your next clinic appointment',
                detail: nextAppointment
                    ? 'You have a ' + (nextAppointment.status || 'scheduled') + ' appointment for ' +
                        (nextAppointment.appt_date || 'a date not listed') + '.'
                    : 'There is no upcoming appointment listed in your record.',
                action: nextAppointment
                    ? 'Check My Appointments for the latest time and status.'
                    : 'You can request an appointment from Book Appointment if you need one.',
                evidence: [
                    { label: 'Next appointment', value: nextAppointment ? (nextAppointment.appt_date || 'Date not listed') : 'None listed' },
                    { label: 'Status', value: nextAppointment ? (nextAppointment.status || 'Not listed') : '—' },
                    { label: 'Service', value: nextAppointment ? (nextAppointment.service_type || 'Not listed') : '—' },
                ],
                interpretation: 'This summary is based only on your appointment record. It does not assess symptoms or determine whether care is needed.',
            },
            {
                title: 'Your clinic record checklist',
                detail: activePrescriptions.toLocaleString() + ' active prescription' +
                    (activePrescriptions === 1 ? '' : 's') + ', ' + openReferrals.toLocaleString() +
                    ' open referral' + (openReferrals === 1 ? '' : 's') + ', and health record status: ' + healthRecordStatus + '.',
                action: 'Review your Prescriptions, Referrals, and Health Record sections for current details.',
                evidence: [
                    { label: 'Active prescriptions', value: activePrescriptions.toLocaleString() },
                    { label: 'Open referrals', value: openReferrals.toLocaleString() },
                    { label: 'Health record', value: healthRecordStatus },
                ],
                interpretation: 'This is a personal record summary, not medical advice. Follow instructions provided by your care team.',
            },
        ], 'Your clinic information is unavailable. Refresh the dashboard to try again.');
    }

    function loadStudentAnalytics(prefix) {
        prefix = prefix || 'sta';
        uhGet('/student/stats').then(function (res) {
            var s = (res && res.stats) || res || {};
            _animateKpi(prefix + '-kpi-visits', s.total_visits || 0);
            _animateKpi(prefix + '-kpi-appts',  s.total_appointments || 0);
            _animateKpi(prefix + '-kpi-rx',     s.active_prescriptions || 0);
            _animateKpi(prefix + '-kpi-referrals', s.open_referrals || 0);

            /* Forms issued KPI if the tile exists */
            if (s.forms_issued != null) {
                _animateKpi(prefix + '-kpi-forms', s.forms_issued);
            }

            renderStudentInsights(prefix, s);
        }).catch(function () {});

        uhGet('/student/visits').then(function (res) {
            var visits = (res && res.visits) || (Array.isArray(res) ? res : []);

            /* Monthly frequency bar */
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            var counts = new Array(12).fill(0);
            visits.forEach(function (v) {
                if (v.visit_date) {
                    var mo = new Date(v.visit_date).getMonth();
                    counts[mo]++;
                }
            });
            barChart(prefix + '-chart-visits', months, counts, { label: 'Clinic Visits', color: COLORS.blue });
        }).catch(function () {
            barChart(prefix + '-chart-visits', ['—'], [0], { label: 'Clinic Visits', color: COLORS.blue });
        });

        uhGet('/student/appointments').then(function (res) {
            var appts = (res && res.appointments) || (Array.isArray(res) ? res : []);
            var statuses = { pending: 0, confirmed: 0, completed: 0, cancelled: 0, deleted: 0 };
            appts.forEach(function (a) { if (statuses[a.status] !== undefined) { statuses[a.status]++; } });
            doughnutChart(
                prefix + '-chart-appts',
                ['Pending','Confirmed','Completed','Cancelled','Deleted'],
                [statuses.pending, statuses.confirmed, statuses.completed, statuses.cancelled, statuses.deleted],
                { colors: [COLORS.amber, COLORS.blue, COLORS.green, COLORS.red, COLORS.muted] }
            );
        }).catch(function () {
            doughnutChart(prefix + '-chart-appts', ['No data'], [1]);
        });
    }

    /* ── Internal helpers ─────────────────────────────────────── */
    function _animateKpi(id, value) {
        var el = document.getElementById(id);
        if (el) { animateCount(el, value); }
    }

    function _renderDispositions(containerId, dispositions) {
        var el = document.getElementById(containerId);
        if (!el) { return; }
        if (!dispositions || !dispositions.length) {
            el.innerHTML = '<p style="font-size:0.8rem;color:#9ca3af;text-align:center;padding:12px 0;">No disposition data yet.</p>';
            return;
        }
        var data   = dispositions;
        var total  = data.reduce(function (sum, d) { return sum + (d.count || 0); }, 0) || 1;
        var colors = [COLORS.green, COLORS.blue, COLORS.amber, COLORS.red, COLORS.purple, COLORS.teal];
        el.innerHTML = data.slice(0, 6).map(function (d, i) {
            var pct = Math.round((d.count / total) * 100);
            return '<div class="an-progress-item">' +
                '<div class="an-progress-item-label"><span>' + (d.disposition || '—') + '</span><span>' + pct + '%</span></div>' +
                '<div class="an-progress-bar-bg"><div class="an-progress-bar-fill" style="width:' + pct + '%;background:' + colors[i % colors.length] + '"></div></div>' +
                '</div>';
        }).join('');
    }

    /* ── Performance metrics loader (pm-* components) ──────────── */
    /**
     * Load performance metric cards (donut rings), multi-series trend chart,
     * activity feed, and goal progress bars.
     * @param {string} prefix  — 'ad' for admin, 'nh' for nurse head
     * @param {string} campusQs — query string e.g. '?campus_id=2'
     */
    function loadPerfMetrics(prefix, campusQs) {
        campusQs = campusQs || '';

        /* ── Fetch stats and render metric cards ── */
        uhGet('/admin/dashboard' + campusQs).then(function (res) {
            var s = res.stats || {};
            var totalPatients    = s.total_patients    || s.total_students || 0;
            var todayVisits      = s.todays_visits      || 0;
            var visitsThisMonth  = s.visits_this_month || 0;
            var todayAppts       = s.todays_appointments || 0;
            var pendingRefs      = s.pending_referrals  || 0;
            var appointmentStatus = s.appointments_by_status || {};
            var referralStatus    = s.referrals_by_status || {};

            /* Animate large numbers */
            animateCount(document.getElementById(prefix + '-pm-patients'), totalPatients);
            animateCount(document.getElementById(prefix + '-pm-visits'),   todayVisits);
            animateCount(document.getElementById(prefix + '-pm-month-visits'), visitsThisMonth);
            animateCount(document.getElementById(prefix + '-pm-appts'),    todayAppts);
            animateCount(document.getElementById(prefix + '-pm-refs'),     pendingRefs);

            /* Donut rings — each shows fill % relative to a sensible denominator */
            var patientsTarget = Math.max(totalPatients, 1500);
            var visitsTarget   = 50;   /* daily target */
            var apptsTarget    = 60;   /* daily appointment capacity */
            var refsTarget     = 20;   /* threshold before action */

            _pmDonut(prefix + '-pm-donut-patients', totalPatients, patientsTarget, COLORS.blue,   prefix + '-pm-donut-patients-pct');
            _pmDonut(prefix + '-pm-donut-visits',   todayVisits,   visitsTarget,   COLORS.green,  prefix + '-pm-donut-visits-pct');
            _pmDonut(prefix + '-pm-donut-appts',    todayAppts,    apptsTarget,    COLORS.amber,  prefix + '-pm-donut-appts-pct');
            _pmDonut(prefix + '-pm-donut-refs',     pendingRefs,   refsTarget,     COLORS.red,    prefix + '-pm-donut-refs-pct');

            /* Activity feed — seed from today's appointments */
            var appts = res.todays_appointments || [];
            _renderActivityFeed(prefix + '-pm-activity', appts, s);

            /* Goal progress bars */
            var dailyPct = Math.min(100, Math.round((todayVisits  / visitsTarget) * 100));
            var totalAppointments = (appointmentStatus.pending || 0) +
                (appointmentStatus.confirmed || 0) +
                (appointmentStatus.completed || 0) +
                (appointmentStatus.cancelled || 0) +
                (appointmentStatus.deleted || 0);
            var totalReferrals = (referralStatus.pending || 0) +
                (referralStatus.accepted || 0) +
                (referralStatus.completed || 0) +
                (referralStatus.cancelled || 0);
            var apptPct = totalAppointments > 0
                ? Math.round(((appointmentStatus.completed || 0) / totalAppointments) * 100)
                : 0;
            var refPct = totalReferrals > 0
                ? Math.round(((referralStatus.completed || 0) / totalReferrals) * 100)
                : 0;

            _pmGoalBar(prefix, 'daily', dailyPct);
            _pmGoalBar(prefix, 'appt',  apptPct);
            _pmGoalBar(prefix, 'ref',   refPct);

        }).catch(function () {
            /* Fallback zeros on error so no fabricated metrics are displayed */
            _pmDonut(prefix + '-pm-donut-patients', 0, 1500, COLORS.blue,  prefix + '-pm-donut-patients-pct');
            _pmDonut(prefix + '-pm-donut-visits',   0, 50,   COLORS.green, prefix + '-pm-donut-visits-pct');
            _pmDonut(prefix + '-pm-donut-appts',    0, 60,   COLORS.amber, prefix + '-pm-donut-appts-pct');
            _pmDonut(prefix + '-pm-donut-refs',     0, 20,   COLORS.red,   prefix + '-pm-donut-refs-pct');
            _pmGoalBar(prefix, 'daily', 0);
            _pmGoalBar(prefix, 'appt',  0);
            _pmGoalBar(prefix, 'ref',   0);
        });

        /* ── Multi-series trend chart ── */
        uhGet('/admin/reports?type=daily&days=13' + (campusQs ? '&' + campusQs.slice(1) : '')).then(function (res) {
            var daily  = (res.report && res.report.daily_visits) ? res.report.daily_visits : [];
            var lbl    = daily.map(function (d) { return d.day_label || '—'; });
            var actual = daily.map(function (d) { return d.count || 0; });
            if (!lbl.length) {
                var fb = last13Days();
                lbl    = fb.labels;
                actual = fb.data;
            }

            _pmTrendChart(prefix + '-pm-trend-chart', lbl, actual, threeDayMovingAverage(actual));
        }).catch(function () {
            var fb = last13Days();
            _pmTrendChart(prefix + '-pm-trend-chart', fb.labels, fb.data, threeDayMovingAverage(fb.data));
        });
    }

    /* Internal: draw a donut ring for a metric card */
    function _pmDonut(canvasId, value, max, color, labelId) {
        var pct   = max > 0 ? Math.min(100, Math.round((value / max) * 100)) : 0;
        var rem   = 100 - pct;
        var grey  = '#f3f4f6';
        var lbl   = document.getElementById(labelId);
        if (lbl) { lbl.textContent = pct + '%'; }

        _destroy(canvasId);
        var canvas = document.getElementById(canvasId);
        if (!canvas || canvas.tagName !== 'CANVAS') { return; }
        var chart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [pct, rem],
                    backgroundColor: [color, grey],
                    borderWidth: 0,
                    hoverOffset: 0,
                }],
            },
            options: {
                responsive: false,
                cutout: '72%',
                animation: { duration: 900, easing: 'easeInOutQuart' },
                plugins: {
                    legend:  { display: false },
                    tooltip: { enabled: false },
                },
            },
        });
        _charts[canvasId] = chart;
    }

    /* Internal: draw the multi-series trend line */
    function _pmTrendChart(canvasId, labels, actual, avg) {
        _destroy(canvasId);
        var canvas = document.getElementById(canvasId);
        if (!canvas || canvas.tagName !== 'CANVAS') { return; }
        var chart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Actual',
                        data: actual,
                        borderColor: COLORS.blue,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        borderWidth: 2.5,
                    },
                    {
                        label: '3-Day Avg',
                        data: avg,
                        borderColor: COLORS.green,
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.5,
                        pointRadius: 0,
                        borderWidth: 2,
                        borderDash: [4, 3],
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 12 } },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { precision: 0 },
                    },
                },
            },
        });
        _charts[canvasId] = chart;
    }

    /* Internal: animate goal progress bar */
    function _pmGoalBar(prefix, key, pct) {
        var bar  = document.getElementById(prefix + '-pm-goal-' + key + '-bar');
        var span = document.getElementById(prefix + '-pm-goal-' + key + '-pct');
        if (span) { span.textContent = pct + '%'; }
        if (bar)  {
            /* Defer so CSS transition fires */
            setTimeout(function () { bar.style.width = pct + '%'; }, 50);
        }
    }

    /* Internal: render activity feed from today's appointments */
    function _renderActivityFeed(containerId, appts, stats) {
        var el = document.getElementById(containerId);
        if (!el) { return; }

        var items = [];
        if (appts && appts.length) {
            appts.slice(0, 5).forEach(function (a) {
                var name = a.student
                    ? ((a.student.first_name || '') + ' ' + (a.student.last_name || '')).trim()
                    : 'Patient';
                var time = uhFormatTime(a.appt_time);
                var status = a.status || 'scheduled';
                var dot = status === 'completed' ? 'green'
                        : status === 'cancelled'  ? 'red'
                        : status === 'pending'     ? 'amber'
                        : 'blue';
                items.push({ dot: dot, text: name + ' — ' + a.service_type, time: time + ' · ' + status });
            });
        }

        /* Pad with contextual items if few appointments */
        if (stats) {
            if (stats.urgent_referrals > 0) {
                items.push({ dot: 'red',    text: stats.urgent_referrals + ' urgent referral(s) pending', time: 'Action required' });
            }
            if (stats.todays_visits > 0) {
                items.push({ dot: 'green',  text: stats.todays_visits + ' visit(s) recorded today', time: 'Live count' });
            }
            if (stats.pending_appointments > 0) {
                items.push({ dot: 'amber',  text: stats.pending_appointments + ' appointment(s) awaiting confirmation', time: 'Pending' });
            }
        }

        if (!items.length) {
            items.push({ dot: 'blue', text: 'No activity yet today', time: 'Just now' });
        }

        el.innerHTML = items.map(function (item) {
            return '<div class="pm-activity-item">' +
                '<span class="pm-activity-dot ' + item.dot + '"></span>' +
                '<div>' +
                    '<div class="pm-activity-text">' + item.text + '</div>' +
                    '<div class="pm-activity-time">' + item.time + '</div>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    /* ── Public API ───────────────────────────────────────────── */
    global.ClinicAnalytics = {
        COLORS:               COLORS,
        makeChart:            makeChart,
        lineChart:            lineChart,
        barChart:             barChart,
        groupedBarChart:      groupedBarChart,
        doughnutChart:        doughnutChart,
        sparkline:            sparkline,
        animateCount:         animateCount,
        last13Days:           last13Days,
        last12Months:         last12Months,
        loadAdminAnalytics:   loadAdminAnalytics,
        loadNurseAnalytics:   loadNurseAnalytics,
        loadPhysicianAnalytics: loadPhysicianAnalytics,
        loadStudentAnalytics: loadStudentAnalytics,
        renderStudentInsights: renderStudentInsights,
        renderDashboardInsights: _renderDashboardInsights,
        showDashboardInsightsUnavailable: _unavailableDashboardInsights,
        loadPerfMetrics:      loadPerfMetrics,
        applyDefaults:        _applyDefaults,
    };

    /* Apply defaults as soon as Chart.js is ready */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', _applyDefaults);
    } else {
        _applyDefaults();
    }

}(window));
