<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Passage Heatmap</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Tailwind like admin_leaderboard.php -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- D3 -->
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <style>
        .antialiased-shape { shape-rendering: geometricPrecision; }
        .soft-shadow { filter: drop-shadow(0 2px 10px rgba(0,0,0,.08)); }
        .caption { font-size: .75rem; color: #6b7280; }
    </style>
</head>
<body class="bg-gray-100 text-gray-800">
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold">Passage Heatmap</h1>
            <p class="caption mt-1">Explore activity by chapter. Switch between a structured layout and an image-like hex mosaic.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <label class="text-sm font-medium">View</label>
            <select id="viewMode" class="px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm">
                <option value="hex">Hex Mosaic (pretty)</option>
                <option value="structured">Structured (by testament/division/book)</option>
            </select>
            <label class="text-sm font-medium">Cell size</label>
            <input id="cellSize" type="range" min="8" max="26" step="1" value="8" class="w-40">
            <button id="downloadBtn" class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-500 transition">
                Download PNG
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-4 sm:p-6 soft-shadow">
        <div class="flex items-center gap-3 mb-4">
            <span class="text-sm font-medium text-gray-700">Legend</span>
            <div class="h-3 flex-1 max-w-xs rounded-full overflow-hidden border border-gray-200">
                <div id="legendGradient" class="w-full h-full"></div>
            </div>
            <div class="flex items-center gap-1 text-xs text-gray-500">
                <span id="legendMin">0</span>
                <span>—</span>
                <span id="legendMax">0</span>
            </div>
        </div>

        <div id="heatmap-container" class="overflow-auto"></div>
        <p class="caption mt-3">Tip: use the “Hex Mosaic” for a modern, image-like look with white spacing and smooth color blending. Switch to “Structured” to see labels by testament, division, and book.</p>
    </div>
</div>

<!-- Tooltip (added) -->
<div id="tooltip"
     class="pointer-events-none fixed z-50 bg-white/95 backdrop-blur border border-gray-200 rounded-xl shadow-lg px-3 py-2 text-xs text-gray-800"
     style="opacity:0; transform:translate(-9999px,-9999px);">
</div>

<script>
    // --- Data in from PHP ---
    var heatmapData = <?php echo json_encode($heatmapData ?? []); ?>;
    heatmapData = (heatmapData || []).map(d => ({
        testament: d.testament ?? 'Unknown',
        division: d.division ?? 'Unknown',
        bookName: d.bookName ?? d.book ?? 'Unknown',
        chapter: d.chapter ?? d.ch ?? '',
        value: +d.value || 0
    }));

    // --- Color scale + legend ---
    const maxVal = d3.max(heatmapData, d => d.value) || 1;
    const colorScale = d3.scaleSequential(d3.interpolateTurbo).domain([0, maxVal]);
    document.getElementById('legendMin').textContent = '0';
    document.getElementById('legendMax').textContent = String(maxVal);

    (function buildLegend(){
        const n = 40;
        const stops = d3.range(n).map(i => {
            const t = i/(n-1);
            return colorScale(t * maxVal);
        });
        const gradient = `linear-gradient(90deg, ${stops.map((c,i)=>`${c} ${(i/(n-1))*100}%`).join(',')})`;
        document.getElementById('legendGradient').style.background = gradient;
    })();

    // --- Tooltip helpers ---
    const tooltip = d3.select('#tooltip');

    function tooltipHtml(d){
        return `
                <div class="font-semibold">${d.bookName} ${d.chapter}</div>
                <div class="text-gray-500">${d.division} • ${d.testament}</div>
                <div class="mt-1"><span class="text-gray-500">Value:</span> ${d.value}</div>
            `;
    }

    function onEnter(event, d){
        tooltip.style('opacity', 1).html(tooltipHtml(d));
    }
    function onMove(event){
        const pad = -50;
        const x = event.pageX;
        const y = event.pageY;
        tooltip.style('transform', `translate(${x}px, ${y}px)`);
    }
    function onLeave(){
        tooltip.style('opacity', 0).style('transform', 'translate(-9999px,-9999px)');
    }

    // --- UI refs ---
    const viewModeEl = document.getElementById('viewMode');
    const cellSizeEl = document.getElementById('cellSize');
    const containerEl = document.getElementById('heatmap-container');
    const downloadBtn = document.getElementById('downloadBtn');

    function clearContainer(){ containerEl.innerHTML = ''; }

    // Hex geometry
    function hexPoints(cx, cy, r){
        const pts = [];
        for (let i = 0; i < 6; i++){
            const a = Math.PI/3 * i + Math.PI/6; // flat-top
            pts.push([cx + r * Math.cos(a), cy + r * Math.sin(a)]);
        }
        return pts.map(p => p.join(',')).join(' ');
    }

    // --- Renderers ---
    function renderHexMosaic(cell = 18){
        const r = cell, w = r*2, h = Math.sqrt(3)*r, xStep = 0.75*w, yStep = h;
        const cols = Math.max(8, Math.floor((window.innerWidth - 64) / xStep));
        const rows = Math.ceil(heatmapData.length / cols);
        const padding = 24;
        const svgWidth  = Math.ceil(padding*2 + (cols-1)*xStep + w);
        const svgHeight = Math.ceil(padding*2 + rows*yStep + h*0.5);

        const svg = d3.select(containerEl)
            .append('svg')
            .attr('width', svgWidth)
            .attr('height', svgHeight)
            .attr('class', 'antialiased-shape');

        const g = svg.append('g').attr('transform', `translate(${padding}, ${padding})`);

        // Use a proper data join so event handlers get (event, d)
        const nodes = g.selectAll('polygon')
            .data(heatmapData)
            .enter()
            .append('polygon')
            .attr('fill', d => colorScale(d.value))
            .attr('stroke', '#ffffff')
            .attr('stroke-width', 2)
            .attr('opacity', 0.95);

        nodes.attr('points', (_, i) => {
            const col = i % cols;
            const row = Math.floor(i / cols);
            const cx = col * xStep + r;
            const cy = row * yStep + (col % 2 === 0 ? h/2 : 0);
            return hexPoints(cx, cy, r * 0.92);
        })
            .on('mouseenter', onEnter)
            .on('mousemove', onMove)
            .on('mouseleave', onLeave);
    }

    function renderStructured(cell = 15){
        const nestedData = d3.groups(heatmapData, d => d.testament, d => d.division, d => d.bookName);

        const chapterWidth = cell, chapterHeight = cell;
        const bookPadding = 6, divisionPadding = 18, testamentPadding = 26, labelOffset = 120;
        let currentY = 28;
        const elements = [];

        nestedData.forEach(([testamentName, testamentGroup]) => {
            elements.push({type:'testamentLabel', name:testamentName, x:8, y:currentY-(testamentPadding/2)});
            currentY += testamentPadding;

            testamentGroup.forEach(([divisionName, divisionGroup]) => {
                elements.push({type:'divisionLabel', name:divisionName, x:16, y:currentY-(divisionPadding/2)});
                currentY += divisionPadding;

                let currentBookX = labelOffset;
                let maxBookHeightInDivision = 0;

                divisionGroup.forEach(([bookName, bookGroup]) => {
                    elements.push({type:'bookLabel', name:bookName, x:currentBookX, y:currentY-(bookPadding/2)});
                    currentY += bookPadding;

                    const chaptersInBook = bookGroup.length;
                    let perRow = Math.ceil(Math.sqrt(chaptersInBook));
                    if (perRow < 1) perRow = 1;

                    const bookHeight = Math.ceil(chaptersInBook / perRow) * chapterHeight;
                    maxBookHeightInDivision = Math.max(maxBookHeightInDivision, bookHeight);

                    bookGroup.forEach((d, i) => {
                        const col = i % perRow;
                        const row = Math.floor(i / perRow);
                        const x = currentBookX + col * chapterWidth;
                        const y = currentY + row * chapterHeight;
                        // keep full datum for tooltip
                        elements.push({
                            type: 'chapterCell',
                            x, y,
                            width: chapterWidth-1, height: chapterHeight-1,
                            datum: d
                        });
                    });

                    currentBookX += perRow * chapterWidth + bookPadding * 2;
                });

                currentY += maxBookHeightInDivision + divisionPadding;
            });
        });

        const svgWidth = (d3.max(elements, d => (d.x || 0) + (d.width || 0)) || 800) + 80;
        const svgHeight = (d3.max(elements, d => (d.y || 0) + (d.height || 0)) || currentY) + 60;

        const svg = d3.select(containerEl)
            .append('svg')
            .attr('width', svgWidth)
            .attr('height', svgHeight)
            .attr('class', 'antialiased-shape');

        // Draw chapter cells with tooltip events
        svg.selectAll('rect.chapter')
            .data(elements.filter(e => e.type === 'chapterCell'))
            .enter()
            .append('rect')
            .attr('class', 'chapter')
            .attr('x', d => d.x)
            .attr('y', d => d.y)
            .attr('width', d => d.width)
            .attr('height', d => d.height)
            .attr('rx', 2).attr('ry', 2)
            .attr('fill', d => colorScale(d.datum.value))
            .on('mouseenter', (event, d) => onEnter(event, d.datum))
            .on('mousemove', onMove)
            .on('mouseleave', onLeave);

        // Labels
        elements.filter(e => e.type === 'bookLabel').forEach(el => {
            svg.append('text')
                .attr('x', el.x).attr('y', el.y).attr('dy', '.35em')
                .attr('font-size', 10).attr('fill', '#4b5563')
                .text(el.name);
        });
        elements.filter(e => e.type === 'divisionLabel').forEach(el => {
            svg.append('text')
                .attr('x', el.x).attr('y', el.y).attr('dy', '.35em')
                .attr('font-size', 12).attr('font-weight', 600).attr('fill', '#374151')
                .text(el.name);
        });
        elements.filter(e => e.type === 'testamentLabel').forEach(el => {
            svg.append('text')
                .attr('x', el.x).attr('y', el.y).attr('dy', '.35em')
                .attr('font-size', 14).attr('font-weight', 700).attr('fill', '#111827')
                .text(el.name);
        });
    }

    // --- PNG download from SVG ---
    async function downloadSVGasPNG(){
        const svg = containerEl.querySelector('svg');
        if (!svg) return;
        const serializer = new XMLSerializer();
        const svgStr = serializer.serializeToString(svg);
        const blob = new Blob([svgStr], {type: 'image/svg+xml;charset=utf-8'});
        const url = URL.createObjectURL(blob);

        const img = new Image();
        const scale = 2;
        const width = svg.viewBox.baseVal?.width || svg.width.baseVal.value;
        const height = svg.viewBox.baseVal?.height || svg.height.baseVal.value;

        await new Promise(resolve => { img.onload = resolve; img.src = url; });

        const canvas = document.createElement('canvas');
        canvas.width = Math.ceil(width * scale);
        canvas.height = Math.ceil(height * scale);
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0,0,canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(b => {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(b);
            a.download = 'passage-heatmap.png';
            a.click();
            URL.revokeObjectURL(url);
        }, 'image/png', 0.95);
    }

    function render(){
        containerEl.innerHTML = '';
        const mode = viewModeEl.value;
        const size = +cellSizeEl.value;
        if (mode === 'hex') {
            renderHexMosaic(size);
        } else {
            renderStructured(Math.max(10, size - 3));
        }
    }

    viewModeEl.addEventListener('change', render);
    cellSizeEl.addEventListener('input', render);
    downloadBtn.addEventListener('click', downloadSVGasPNG);

    render();
</script>
</body>
</html>
