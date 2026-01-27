import * as d3 from 'd3';

// Colour mapping from Flux/Tailwind colour names to hex values
const colourMap = {
    zinc: '#71717a',
    red: '#ef4444',
    orange: '#f97316',
    amber: '#f59e0b',
    yellow: '#eab308',
    lime: '#84cc16',
    green: '#22c55e',
    emerald: '#10b981',
    teal: '#14b8a6',
    cyan: '#06b6d4',
    sky: '#0ea5e9',
    blue: '#3b82f6',
    indigo: '#6366f1',
    violet: '#8b5cf6',
    purple: '#a855f7',
    fuchsia: '#d946ef',
    pink: '#ec4899',
    rose: '#f43f5e',
};

function getColour(colourName) {
    return colourMap[colourName] || colourMap.zinc;
}

function createTooltip(container) {
    const tooltip = document.createElement('div');
    tooltip.className = 'skills-viz-tooltip';
    tooltip.style.cssText = `
        position: absolute;
        pointer-events: none;
        background: var(--tooltip-bg, #fff);
        border: 1px solid var(--tooltip-border, #e4e4e7);
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        line-height: 1.4;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        opacity: 0;
        transition: opacity 0.15s ease;
        z-index: 1000;
        max-width: 280px;
    `;

    // Apply dark mode styles
    const isDarkMode = document.documentElement.classList.contains('dark');
    if (isDarkMode) {
        tooltip.style.setProperty('--tooltip-bg', '#27272a');
        tooltip.style.setProperty('--tooltip-border', '#3f3f46');
        tooltip.style.color = '#e4e4e7';
    } else {
        tooltip.style.setProperty('--tooltip-bg', '#ffffff');
        tooltip.style.setProperty('--tooltip-border', '#e4e4e7');
        tooltip.style.color = '#27272a';
    }

    container.appendChild(tooltip);
    return tooltip;
}

function showTooltip(tooltip, event, d) {
    let html = `<strong>${d.data.name}</strong>`;

    if (d.data.type === 'skill') {
        if (d.data.description) {
            const truncated = d.data.description.length > 100
                ? d.data.description.substring(0, 100) + '...'
                : d.data.description;
            html += `<div style="margin-top: 4px; opacity: 0.8;">${truncated}</div>`;
        }
        const userLabel = d.data.userCount === 1 ? 'user' : 'users';
        html += `<div style="margin-top: 6px; font-weight: 500;">${d.data.userCount} ${userLabel}</div>`;
    } else if (d.data.type === 'category') {
        const skillCount = d.children?.length || 0;
        const skillLabel = skillCount === 1 ? 'skill' : 'skills';
        const userLabel = d.data.userCount === 1 ? 'user' : 'users';
        html += `<div style="margin-top: 4px;">${skillCount} ${skillLabel}</div>`;
        html += `<div style="margin-top: 2px; font-weight: 500;">${d.data.userCount} ${userLabel} total</div>`;
    }

    tooltip.innerHTML = html;
    tooltip.style.opacity = '1';

    // Position tooltip near cursor
    const containerRect = tooltip.parentElement.getBoundingClientRect();
    const x = event.clientX - containerRect.left + 15;
    const y = event.clientY - containerRect.top - 10;

    tooltip.style.left = `${x}px`;
    tooltip.style.top = `${y}px`;
}

function hideTooltip(tooltip) {
    tooltip.style.opacity = '0';
}

function initSkillsVisualization() {
    const container = document.getElementById('skills-visualization');
    if (!container) return;

    const dataAttr = container.getAttribute('data-hierarchy');
    if (!dataAttr) return;

    let data;
    try {
        data = JSON.parse(dataAttr);
    } catch (e) {
        console.error('Failed to parse hierarchy data:', e);
        return;
    }

    // Clear placeholder content
    container.innerHTML = '';

    // Create tooltip
    const tooltip = createTooltip(container);

    // Get container dimensions
    const width = container.clientWidth;
    const height = container.clientHeight;
    const outerRadius = Math.min(width, height) / 2 - 100;
    const innerRadius = 80; // Push categories away from centre

    // Track rotation angle
    let currentRotation = 0;

    // Create SVG
    const svg = d3.select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .attr('viewBox', [-width / 2, -height / 2, width, height])
        .attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif; cursor: grab;');

    // Create a group for all content (for zoom/rotate transforms)
    const g = svg.append('g');

    // Zoom behaviour (mouse wheel)
    const zoom = d3.zoom()
        .scaleExtent([0.5, 3])
        .filter(event => event.type === 'wheel' || event.type === 'dblclick')
        .on('zoom', (event) => {
            g.attr('transform', `rotate(${currentRotation}) scale(${event.transform.k})`);
        });

    svg.call(zoom);

    // Drag-to-rotate behaviour
    let dragStartAngle = 0;
    let rotationAtDragStart = 0;

    const drag = d3.drag()
        .on('start', (event) => {
            svg.attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif; cursor: grabbing;');
            // Calculate starting angle from centre
            dragStartAngle = Math.atan2(event.y, event.x) * 180 / Math.PI;
            rotationAtDragStart = currentRotation;
        })
        .on('drag', (event) => {
            const currentAngle = Math.atan2(event.y, event.x) * 180 / Math.PI;
            const deltaAngle = dragStartAngle - currentAngle;
            currentRotation = rotationAtDragStart + deltaAngle;

            const currentScale = d3.zoomTransform(svg.node()).k;
            g.attr('transform', `rotate(${currentRotation}) scale(${currentScale})`);
        })
        .on('end', () => {
            svg.attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif; cursor: grab;');
        });

    svg.call(drag);

    // Create hierarchy
    const root = d3.hierarchy(data);

    // Create tree layout - use the range from inner to outer radius
    const tree = d3.tree()
        .size([2 * Math.PI, outerRadius - innerRadius])
        .separation((a, b) => (a.parent === b.parent ? 1 : 2) / a.depth);

    tree(root);

    // Offset all y values by innerRadius (except root which stays at centre)
    root.each(d => {
        if (d.depth > 0) {
            d.y = d.y + innerRadius;
        }
    });

    // Create links
    g.append('g')
        .attr('fill', 'none')
        .attr('stroke', '#94a3b8')
        .attr('stroke-opacity', 0.4)
        .attr('stroke-width', 1.5)
        .selectAll('path')
        .data(root.links())
        .join('path')
        .attr('d', d3.linkRadial()
            .angle(d => d.x)
            .radius(d => d.y));

    // Create nodes
    const node = g.append('g')
        .selectAll('g')
        .data(root.descendants())
        .join('g')
        .attr('transform', d => `rotate(${d.x * 180 / Math.PI - 90}) translate(${d.y},0)`);

    // Add circles to nodes
    node.append('circle')
        .attr('fill', d => {
            if (d.data.type === 'root') return '#6366f1';
            if (d.data.type === 'category') return getColour(d.data.colour);
            // For skills, use the colour property or inherit from category
            return getColour(d.data.colour);
        })
        .attr('r', d => {
            if (d.data.type === 'root') return 8;
            if (d.data.type === 'category') return 6;
            return 4;
        })
        .attr('stroke', '#fff')
        .attr('stroke-width', 1.5)
        .style('cursor', d => d.data.type === 'skill' ? 'pointer' : 'default')
        .on('click', (event, d) => {
            if (d.data.type === 'skill' && d.data.id) {
                window.location.href = `/admin/dashboard?tab=team&skillFilter=${d.data.id}`;
            }
        })
        .on('mouseenter', (event, d) => {
            if (d.data.type !== 'root') {
                showTooltip(tooltip, event, d);
                // Highlight the node
                d3.select(event.target)
                    .transition()
                    .duration(150)
                    .attr('r', d.data.type === 'category' ? 8 : 6);
            }
        })
        .on('mousemove', (event, d) => {
            if (d.data.type !== 'root') {
                showTooltip(tooltip, event, d);
            }
        })
        .on('mouseleave', (event, d) => {
            hideTooltip(tooltip);
            // Restore node size
            d3.select(event.target)
                .transition()
                .duration(150)
                .attr('r', () => {
                    if (d.data.type === 'root') return 8;
                    if (d.data.type === 'category') return 6;
                    return 4;
                });
        });

    // Detect dark mode
    const isDarkMode = document.documentElement.classList.contains('dark') ||
        window.matchMedia('(prefers-color-scheme: dark)').matches;

    const textColour = isDarkMode ? '#e4e4e7' : '#3f3f46'; // zinc-200 / zinc-700
    const strokeColour = isDarkMode ? '#27272a' : '#ffffff'; // zinc-800 / white

    // Add labels (skip root node - it just clutters the centre)
    node.filter(d => d.data.type !== 'root')
        .append('text')
        .attr('dy', '0.31em')
        .attr('x', d => d.x < Math.PI === !d.children ? 10 : -10)
        .attr('text-anchor', d => d.x < Math.PI === !d.children ? 'start' : 'end')
        .attr('transform', d => d.x >= Math.PI ? 'rotate(180)' : null)
        .attr('fill', textColour)
        .text(d => d.data.name)
        .style('font-size', d => {
            if (d.data.type === 'category') return '15px';
            // Skill sizes based on depth
            if (d.depth === 2) return '14px'; // Direct skills under category
            if (d.depth === 3) return '13px'; // Child skills
            return '12px'; // Grandchildren and deeper
        })
        .style('font-weight', d => d.data.type === 'category' ? '600' : '500')
        .clone(true).lower()
        .attr('stroke', strokeColour)
        .attr('stroke-width', 4);
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSkillsVisualization);
} else {
    initSkillsVisualization();
}

// Re-initialize on Livewire navigation (if using wire:navigate)
document.addEventListener('livewire:navigated', initSkillsVisualization);
