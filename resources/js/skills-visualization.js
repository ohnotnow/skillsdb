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

    // Get container dimensions
    const width = container.clientWidth;
    const height = container.clientHeight;
    const radius = Math.min(width, height) / 2 - 80;

    // Create SVG
    const svg = d3.select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .attr('viewBox', [-width / 2, -height / 2, width, height])
        .attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif;');

    // Create hierarchy
    const root = d3.hierarchy(data);

    // Create tree layout
    const tree = d3.tree()
        .size([2 * Math.PI, radius])
        .separation((a, b) => (a.parent === b.parent ? 1 : 2) / a.depth);

    tree(root);

    // Create links
    svg.append('g')
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
    const node = svg.append('g')
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
        });

    // Add labels
    node.append('text')
        .attr('dy', '0.31em')
        .attr('x', d => d.x < Math.PI === !d.children ? 6 : -6)
        .attr('text-anchor', d => d.x < Math.PI === !d.children ? 'start' : 'end')
        .attr('transform', d => d.x >= Math.PI ? 'rotate(180)' : null)
        .attr('fill', 'currentColor')
        .attr('class', 'text-zinc-700 dark:text-zinc-300')
        .text(d => d.data.name)
        .style('font-size', d => {
            if (d.data.type === 'root') return '14px';
            if (d.data.type === 'category') return '12px';
            return '11px';
        })
        .style('font-weight', d => d.data.type === 'root' || d.data.type === 'category' ? '600' : '400')
        .clone(true).lower()
        .attr('stroke', 'white')
        .attr('stroke-width', 3);
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSkillsVisualization);
} else {
    initSkillsVisualization();
}

// Re-initialize on Livewire navigation (if using wire:navigate)
document.addEventListener('livewire:navigated', initSkillsVisualization);
