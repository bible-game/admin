<?php
/**
 * stats_card.php — Tailwind, async-friendly
 *
 * Looks for data in this order:
 *   1) window.passageHeatmapData (reactive: later assignments auto-update)
 *   2) "passage:data" CustomEvent (detail = rows)
 *   3) PHP $heatmapData (fallback if parent provides server-side data)
 *
 * Each row should have: { value:number, bookName?:string, chapter?:string }
 */
?>
<div id="insights-card-root"></div>

<script>
    (function attachInsightsCard(){
        const root = document.getElementById('insights-card-root');
        if (!root) return;

        // ----- UI helpers -----
        function card(content){
            return `
      <div class="bg-white p-4 md:p-6 mt-6">
        <h2 class="text-lg font-semibold mb-3">Insights</h2>
        ${content}
      </div>
    `;
        }
        function renderLoading(){
            root.innerHTML = card(`
      <div class="flex items-center gap-2 text-sm text-gray-500">
        <svg class="animate-spin h-4 w-4 text-gray-400" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4A4 4 0 008 12H4z"/>
        </svg>
        Loading insights…
      </div>
    `);
        }
        function renderEmpty(){
            root.innerHTML = card(`<div class="text-sm text-gray-500">No data available.</div>`);
        }

        // ----- Stats helpers (no d3) -----
        function quantile(sorted, p){
            if (!sorted.length) return 0;
            const H = (sorted.length - 1) * p;
            const i = Math.floor(H), f = H - i;
            return i + 1 < sorted.length ? sorted[i]*(1-f) + sorted[i+1]*f : sorted[i];
        }
        function gini(arr){
            const n = arr.length;
            if (!n) return 0;
            const s = arr.slice().sort((a,b)=>a-b);
            const total = s.reduce((a,b)=>a+b,0);
            if (total === 0) return 0;
            let cum = 0, lorenzArea = 0;
            for (let i=0;i<n;i++){ cum += s[i]; lorenzArea += cum; }
            return 1 - 2*(lorenzArea/(n*total));
        }
        const fmt = x => (Math.round((x + Number.EPSILON) * 100) / 100).toString();
        const pct = x => ((x*100)|0) + '%';
        const labelOf = d => {
            if (!d) return '(unlabeled)';
            const loc = [d.bookName, d.chapter].filter(Boolean).join(' ');
            return loc || '(unlabeled)';
        };

        function computeInsights(rows){
            const vals = rows.map(d => +((d && d.value) || 0));
            const n = vals.length || 0;
            const sum = vals.reduce((a,b)=>a+b,0);
            const mean = n ? sum/n : 0;
            const sorted = vals.slice().sort((a,b)=>a-b);
            const median = n ? (n%2 ? sorted[(n-1)/2] : (sorted[n/2-1]+sorted[n/2])/2) : 0;
            const zeros = vals.filter(v=>v===0).length;

            const freq = new Map();
            for (const v of vals) freq.set(v,(freq.get(v)||0)+1);
            let mode = null, modeCount = -1;
            freq.forEach((c,v)=>{ if(c>modeCount){ mode=v; modeCount=c; }});

            const variance = n ? vals.reduce((a,v)=>a+Math.pow(v-mean,2),0)/n : 0;
            const stdev = Math.sqrt(variance);

            const p05 = quantile(sorted, 0.05);
            const p25 = quantile(sorted, 0.25);
            const p50 = median;
            const p75 = quantile(sorted, 0.75);
            const p95 = quantile(sorted, 0.95);
            const iqr = (p75||0) - (p25||0);

            const minVal = sorted[0] ?? 0;
            const maxVal = sorted[n-1] ?? 0;
            const minItems = rows.filter(d => (+d.value||0) === minVal).slice(0,3);
            const maxItems = rows.filter(d => (+d.value||0) === maxVal).slice(0,3);

            const giniVal = gini(vals);
            const top5 = rows.slice().sort((a,b)=>(+b.value||0)-(+a.value||0)).slice(0,5);
            const top5Share = sum ? top5.reduce((a,d)=>a + (+d.value||0), 0)/sum : 0;

            return { n,sum,mean,median,mode,stdev,zeros,p05,p25,p50,p75,p95,iqr,
                minVal,maxVal,minItems,maxItems,gini:giniVal,top5Share };
        }

        function render(rows){
            if (!rows) { renderLoading(); return; }
            if (!rows.length) { renderEmpty(); return; }

            const S = computeInsights(rows);
            root.innerHTML = card(`
      <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
        <!-- Overview -->
        <div>
          <div class="text-sm text-gray-500 mb-2">Overview</div>
          <ul class="space-y-1 text-sm">
            <li><span class="text-gray-500">Total:</span>
                <span class="font-medium [font-variant-numeric:tabular-nums]">${S.sum.toLocaleString()}</span></li>
            <li class="flex flex-wrap items-center gap-x-2">
              <span class="text-gray-500">Mean:</span><span class="[font-variant-numeric:tabular-nums]">${fmt(S.mean)}</span>
              <span class="text-gray-300">·</span>
              <span class="text-gray-500">Median:</span><span class="[font-variant-numeric:tabular-nums]">${fmt(S.median)}</span>
              <span class="text-gray-300">·</span>
              <span class="text-gray-500">Mode:</span><span class="[font-variant-numeric:tabular-nums]">${S.mode}</span>
            </li>
            <li class="flex flex-wrap items-center gap-x-2">
              <span class="text-gray-500">Std dev:</span><span class="[font-variant-numeric:tabular-nums]">${fmt(S.stdev)}</span>
              <span class="text-gray-300">·</span>
              <span class="text-gray-500">Zeros:</span><span class="[font-variant-numeric:tabular-nums]">${S.zeros}</span>
              <span class="text-gray-400">(${pct(S.zeros/(S.n||1))})</span>
            </li>
            <li class="flex flex-wrap items-center gap-x-2">
              <span class="text-gray-500">Inequality (Gini):</span><span class="[font-variant-numeric:tabular-nums]">${fmt(S.gini)}</span>
              <span class="text-gray-300">·</span>
              <span class="text-gray-500">Top 5 share:</span><span class="[font-variant-numeric:tabular-nums]">${pct(S.top5Share)}</span>
            </li>
          </ul>
        </div>

        <!-- Spread -->
        <div>
          <div class="text-sm text-gray-500 mb-2">Spread</div>
          <ul class="space-y-1 text-sm">
            <li class="flex flex-wrap items-center gap-x-2">
              <span class="text-gray-500">Min:</span><span class="[font-variant-numeric:tabular-nums]">${S.minVal}</span>
              <span class="text-gray-300">·</span>
              <span class="text-gray-500">P5:</span><span class="[font-variant-numeric:tabular-nums]">${fmt(S.p05)}</span>
              <span class="text-gray-300">·</span>
              <span class="text-gray-500">P25:</span><span class="[font-variant-numeric:tabular-nums]">${fmt(S.p25)}</span>
            </li>
            <li class="flex flex-wrap items-center gap-x-2">
              <span class="text-gray-500">Median (P50):</span><span class="[font-variant-numeric:tabular-nums]">${fmt(S.p50)}</span>
              <span class="text-gray-300">·</span>
              <span class="text-gray-500">P75:</span><span class="[font-variant-numeric:tabular-nums]">${fmt(S.p75)}</span>
              <span class="text-gray-300">·</span>
              <span class="text-gray-500">P95:</span><span class="[font-variant-numeric:tabular-nums]">${fmt(S.p95)}</span>
            </li>
            <li class="flex flex-wrap items-center gap-x-2">
              <span class="text-gray-500">Max:</span><span class="[font-variant-numeric:tabular-nums]">${S.maxVal}</span>
              <span class="text-gray-300">·</span>
              <span class="text-gray-500">IQR:</span><span class="[font-variant-numeric:tabular-nums]">${fmt(S.iqr)}</span>
            </li>
          </ul>
        </div>

        <!-- Popularity -->
        <div>
          <div class="text-sm text-gray-500 mb-2 underlined">Popularity</div>
          <ul class="space-y-1 text-sm">
            <li><span class="text-gray-500">Top:</span>
              <span class="text-xs">${S.maxItems.length ? S.maxItems.map(d=>`<span class="[font-variant-numeric:tabular-nums]">${labelOf(d)}</span> (${+d.value||0})`).join(', ') : '<span class="text-gray-400">—</span>'}</span>
            </li>
            <li><span class="text-gray-500">Bottom:</span>
              <span class="text-xs">${S.minItems.length ? S.minItems.map(d=>`<span class="[font-variant-numeric:tabular-nums]">${labelOf(d)}</span>(${+d.value||0})`).join(', ') : '<span class="text-gray-400">—</span>'}</span>
            </li>
          </ul>
        </div>
      </div>
    `);
        }

        // ----- Reactive data wiring -----
        let hasRenderedOnce = false;

        // 1) CustomEvent hook (one-liner you can dispatch when fetch resolves)
        window.addEventListener('passage:data', (e) => {
            render(e.detail || []);
            hasRenderedOnce = true;
        });

        // 2) Reactive global: catch later assignments to window.passageHeatmapData
        (function makeReactive(){
            const name = 'passageHeatmapData';
            let value = (typeof window[name] !== 'undefined') ? window[name] : undefined;

            // If a value is already present (e.g., SSR), render it immediately
            if (Array.isArray(value) && value.length && !hasRenderedOnce) {
                render(value); hasRenderedOnce = true;
            } else {
                renderLoading();
            }

            try {
                Object.defineProperty(window, name, {
                    configurable: true,
                    get(){ return value; },
                    set(v){
                        value = v;
                        render(Array.isArray(v) ? v : []);
                        hasRenderedOnce = true;
                    }
                });
            } catch (e) {
                // Non-configurable? Fall back to polling briefly.
                const poll = setInterval(() => {
                    const v = window[name];
                    if (Array.isArray(v)) {
                        clearInterval(poll);
                        render(v);
                        hasRenderedOnce = true;
                    }
                }, 150);
                setTimeout(() => clearInterval(poll), 8000);
            }
        })();

        // 3) Manual escape hatch (call this yourself if you mutate in place)
        window.updateInsights = function(rows){ render(Array.isArray(rows) ? rows : []); };

        // 4) PHP fallback if provided and JS globals absent
        <?php if (isset($heatmapData)): ?>
        if (!hasRenderedOnce && Array.isArray(window.passageHeatmapData) === false) {
            render(<?php echo json_encode($heatmapData); ?>);
            hasRenderedOnce = true;
        }
        <?php endif; ?>
    })();
</script>
