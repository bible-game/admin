<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <style>
        :root{
            --gh-0:#ebedf0;--gh-1:#9be9a8;--gh-2:#40c463;--gh-3:#30a14e;--gh-4:#216e39;
        }
        .card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;box-shadow:0 4px 18px rgba(0,0,0,.06);}
        .hm-cell{shape-rendering:crispEdges;rx:2px;ry:2px;transition:transform .06s ease-out,stroke .06s ease-out,stroke-width .06s ease-out;}
        .hm-cell:hover{cursor: pointer}
        .hm-wrap{overflow-x:auto;position:relative;min-height:120px;}
        .legend-swatch{width:12px;height:12px;border-radius:2px;border:1px solid rgba(0,0,0,.05);}
        .hm-tooltip{position:fixed;z-index:50;pointer-events:none;background:#000;color:#fff;padding:.5rem .6rem;border-radius:.5rem;font-size:.75rem;line-height:1.1rem;box-shadow:0 6px 24px rgba(0,0,0,.25);opacity:0;transform:translate(-50%,-140%);transition:opacity .08s ease-out,transform .08s ease-out;white-space:nowrap;}
        .hm-tooltip[data-show="1"]{opacity:1;transform:translate(-50%,-160%);}
        .spinner{position:absolute;top:50%;left:50%;width:40px;height:40px;margin:-20px 0 0 -20px;border:4px solid rgba(0,0,0,.1);border-left-color:#09f;border-radius:50%;animation:spin 1s linear infinite;}
        @keyframes spin{to{transform:rotate(360deg);}}
    </style>
</head>
<body class="bg-gray-100 text-gray-800">

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">Dashboard</h1>

        <div class="grid grid-cols-1 gap-8">
            <div>
                <div class="card p-4 md:p-6">
                    <div class="flex items-center justify-between gap-4 flex-wrap mb-4">
                        <div class="text-sm text-gray-600">Random daily passages since <?php if (isset($earliestDate)) { try { echo (new DateTime($earliestDate))->format('jS F Y'); } catch (Exception $e) { echo 'the beginning of time'; } } else { echo 'the beginning of time'; } ?></div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-gray-500">Less</span>
                            <div class="legend-swatch" style="background:var(--gh-0)"></div>
                            <div class="legend-swatch" style="background:var(--gh-1)"></div>
                            <div class="legend-swatch" style="background:var(--gh-2)"></div>
                            <div class="legend-swatch" style="background:var(--gh-3)"></div>
                            <div class="legend-swatch" style="background:var(--gh-4)"></div>
                            <span class="text-gray-500 ml-1">More</span>
                        </div>
                    </div>

                    <div id="heatmap" class="hm-wrap">
                        <div id="spinner" class="spinner"></div>
                    </div>
                    <?php include __DIR__ . '/stats_card.php'; ?>
                </div>
            </div>

            <div>
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline"><?= esc($error) ?></span>
                    </div>
                <?php elseif (empty($leaderboard)): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">No leaderboard data available.</span>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Rank
                                    </th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        User Id
                                    </th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Game Stars
                                    </th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Review Stars
                                    </th>
                                    <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Total Stars
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($leaderboard as $user): ?>
                                    <tr>
                                        <td class="p-3 border-b border-gray-200 bg-white text-sm text-center">
                                            <p class="text-gray-900 whitespace-no-wrap"><?= $rank++ ?></p>
                                        </td>
                                        <td class="p-3 border-b border-gray-200 bg-white text-sm text-center">
                                            <p class="text-gray-900 whitespace-no-wrap"><?= esc($user['id']) ?></p>
                                        </td>
                                        <td class="p-3 border-b border-gray-200 bg-white text-sm text-center">
                                            <p class="text-gray-900 whitespace-no-wrap"><?= esc($user['firstname']) . " " . esc($user['lastname']) ?></p>
                                        </td>
                                        <td class="p-3 border-b border-gray-200 bg-white text-sm text-center">
                                            <p class="text-gray-900 whitespace-no-wrap"><?= esc($user['gameStars']) ?></p>
                                        </td>
                                        <td class="p-3 border-b border-gray-200 bg-white text-sm text-center">
                                            <p class="text-gray-900 whitespace-no-wrap"><?= esc($user['reviewStars']) ?></p>
                                        </td>
                                        <td class="p-3 border-b border-gray-200 bg-white text-sm text-center">
                                            <p class="text-gray-900 whitespace-no-wrap"><?= esc($user['gameStars']) + esc($user['reviewStars']) ?></p>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="hmTooltip" class="hm-tooltip" role="tooltip" aria-live="polite"></div>

    <script>window.passageHeatmapData = <?php echo json_encode($heatmapData ?? []); ?>;</script>
    <script>
    const wrap = d3.select('#heatmap');
    const spinner = document.getElementById('spinner');

    fetch('/passage/heatmap-data')
    .then(response => response.json())
    .then(raw => {
        spinner.style.display = 'none';
        const data = (raw || []).map(d => ({
            bookName: d.bookName ?? d.book ?? 'Unknown',
            chapter: d.chapter ?? d.ch ?? '',
            value: +d.value || 0,
            division: d.division ?? '',
            testament: d.testament ?? ''
        }))

    window.passageHeatmapData = data;

            // ---- Visual constants (bigger cells) ----
    const CELL = 10;      // <— make this 18/20 if you want even bigger
    const GAP  = 3;
    const ROWG = 3;
    const M = { top:8, right:8, bottom:8, left:8 };

    // ---- GitHub-like color buckets based on the spread of actual values ----
    // Zeros are always "empty". Positive values are bucketed by the observed spread.
    // We use the 5th–95th percentile window to avoid outliers dominating the palette.
    // If the spread is very skewed, we bucket in log-space for nicer separation.
    function ghBucketScale(values) {
        const vals = values.filter(v => v > 0).sort((a,b) => a - b);

        // If no positives: everything is empty.
        if (vals.length === 0) return () => 'var(--gh-0)';

        // Robust lower/upper bounds for "typical" values.
        const q = p => d3.quantile(vals, p);
        const lo = Math.max(1, q(0.05) ?? vals[0]);  // avoid 0 lower bound for log
        const hi = q(0.95) ?? vals[vals.length - 1];

        // If all positives are the same value, map that to the darkest bucket.
        if (hi <= lo) {
            return v => (v <= 0 ? 'var(--gh-0)' : 'var(--gh-4)');
        }

        // Decide linear vs log spread (skewed if > 50×).
        const skewed = (hi / lo) > 50;

        // Build 4 thresholds (which create 5 levels counting zero as level 0).
        let thresholds;
        if (skewed) {
            const Llo = Math.log(lo), Lhi = Math.log(hi);
            thresholds = d3.range(1, 5).map(i => Math.exp(Llo + (i / 4) * (Lhi - Llo)));
        } else {
            thresholds = d3.range(1, 5).map(i => lo + (i / 4) * (hi - lo));
        }

        // d3.scaleThreshold maps to 5 positive buckets; zero handled separately.
        const th = d3.scaleThreshold()
            .domain(thresholds.slice(0, 4)) // four cut points -> 5 buckets
            .range([1, 2, 3, 4, 5]);        // bucket indices 1..5

        return v => {
            if (v <= 0) return 'var(--gh-0)';
            const b = th(v);
            return (
                b === 1 ? 'var(--gh-1)' :
                    b === 2 ? 'var(--gh-2)' :
                        b === 3 ? 'var(--gh-3)' :
                            /* b>=4 */ 'var(--gh-4)'
            );
        };
    }
    const colorOf = ghBucketScale(data.map(d => d.value));

    // ---- Build base SVG once ----
    const svg  = wrap.append('svg');
    const g    = svg.append('g');

    // Bind cells once
    const cells = g.selectAll('rect')
        .data(data)
        .enter()
        .append('rect')
        .attr('class','hm-cell')
        .attr('width', CELL)
        .attr('height', CELL)
        .attr('fill', d => colorOf(d.value))
        .attr('aria-label', d => `${d.value} at ${[d.bookName, d.chapter].filter(Boolean).join(' ')}`);

    // Tooltip
    const tooltip = d3.select('#hmTooltip');
    function tipHTML(d){
        const count = d.value;
        const label = count === 0 ? '0' : count.toLocaleString();
        const loc   = [d.bookName, d.chapter].filter(Boolean).join(' ');
        const meta  = [d.division, d.testament].filter(Boolean).join(' • ');
        return `<div><strong>${loc}</strong> - ${label}</div>${meta?`<div class="opacity-80">${meta}</div>`:''}`;
    }
    function showTip(html, [x,y]){
        if (!html) return hideTip();
        const el = tooltip.html(html).attr('data-show','1').node();
        const pad = 12, iw = innerWidth, ih = innerHeight, r = el.getBoundingClientRect();
        let nx = Math.min(Math.max(pad + r.width/2, x), iw - pad - r.width/2);
        let ny = Math.min(Math.max(pad + r.height, y - 10), ih - pad);
        el.style.left = `${nx}px`; el.style.top = `${ny}px`;
    }
    function hideTip(){ tooltip.attr('data-show', null); }

    g.selectAll('rect')
        .on('mousemove', (ev,d) => showTip(tipHTML(d), [ev.clientX, ev.clientY]))
        .on('mouseleave', hideTip)
        .on('touchstart', (ev,d) => {
            const t = ev.touches[0]; showTip(tipHTML(d), [t.clientX, t.clientY]);
        }, { passive:true })
        .on('touchend', hideTip)
        .attr('tabindex', 0)
        .on('focus', function(ev,d){
            const r = this.getBoundingClientRect();
            showTip(tipHTML(d), [r.x + r.width/2, r.y]);
        })
        .on('blur', hideTip);

    // ---- Responsive layout: compute columns from container width; rows overflow as needed ----
    function layout(){
        const boxW = wrap.node().getBoundingClientRect().width || 600;
        // number of columns that fit
        const cols = Math.max(1, Math.floor((boxW - M.left - M.right + GAP) / (CELL + GAP)));
        const rows = Math.ceil(data.length / cols);

        // SVG size
        const w = cols * (CELL + GAP) - GAP;
        const h = rows * (CELL + ROWG) - ROWG;

        svg.attr('viewBox', `0 0 ${w + M.left + M.right} ${h + M.top + M.bottom}`)
            .attr('width', boxW)
            .attr('height', h + M.top + M.bottom);
        g.attr('transform', `translate(${M.left},${M.top})`);

        // Position cells (row-major: left→right, top→bottom)
        g.selectAll('rect')
            .attr('x', (_, i) => (i % cols) * (CELL + GAP))
            .attr('y', (_, i) => Math.floor(i / cols) * (CELL + ROWG));
    }

    layout();
    window.addEventListener('resize', () => {
        layout();
        // nudge tooltip back into bounds if visible
        const el = tooltip.node();
        if (el.getAttribute('data-show') === '1') {
            const r = el.getBoundingClientRect();
            showTip(el.innerHTML, [r.x + r.width/2, r.y]);
        }
    });
    }).catch(error => {
        spinner.style.display = 'none';
        console.error('Error fetching heatmap data:', error);
        wrap.html('<p class="text-red-500">Could not load heatmap data.</p>');
    });
</script>

</body>
</html>
