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
    // Remove existing tooltip if present
    const existing = container.querySelector('.skills-viz-tooltip');
    if (existing) existing.remove();

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

// Track current layout
let currentLayout = 'radial';

function initSkillsVisualization(layout = 'radial') {
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

    currentLayout = layout;

    // Clear existing content but preserve data attribute
    const svg = container.querySelector('svg');
    if (svg) svg.remove();
    const existingTooltip = container.querySelector('.skills-viz-tooltip');
    if (existingTooltip) existingTooltip.remove();

    // Remove placeholder if present
    const placeholder = container.querySelector('.flex.items-center');
    if (placeholder) placeholder.remove();

    // Create tooltip
    const tooltip = createTooltip(container);

    // Get container dimensions
    const width = container.clientWidth;
    const height = container.clientHeight;

    // Detect dark mode
    const isDarkMode = document.documentElement.classList.contains('dark') ||
        window.matchMedia('(prefers-color-scheme: dark)').matches;

    const textColour = isDarkMode ? '#e4e4e7' : '#3f3f46';
    const strokeColour = isDarkMode ? '#27272a' : '#ffffff';

    if (layout === 'radial') {
        renderRadialTree(container, data, tooltip, width, height, textColour, strokeColour);
    } else if (layout === 'tree') {
        renderTidyTree(container, data, tooltip, width, height, textColour, strokeColour);
    } else if (layout === 'force') {
        renderForceDirected(container, data, tooltip, width, height, textColour, strokeColour);
    }
}

function renderRadialTree(container, data, tooltip, width, height, textColour, strokeColour) {
    const outerRadius = Math.min(width, height) / 2 - 100;
    const innerRadius = 80;

    // Track rotation angle and pan offset
    let currentRotation = 0;
    let panX = 0;
    let panY = 0;

    // Create SVG
    const svg = d3.select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .attr('viewBox', [-width / 2, -height / 2, width, height])
        .attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif; cursor: grab;');

    // Create a group for all content (for zoom/rotate transforms)
    const g = svg.append('g');

    // Helper to apply current transform
    function applyTransform(scale) {
        g.attr('transform', `translate(${panX}, ${panY}) rotate(${currentRotation}) scale(${scale})`);
    }

    // Zoom behaviour (mouse wheel)
    const zoom = d3.zoom()
        .scaleExtent([0.5, 3])
        .filter(event => event.type === 'wheel' || event.type === 'dblclick')
        .on('zoom', (event) => {
            applyTransform(event.transform.k);
        });

    svg.call(zoom);

    // Drag behaviour: rotate normally, pan with Shift held
    let dragStartAngle = 0;
    let rotationAtDragStart = 0;
    let panStartX = 0;
    let panStartY = 0;
    let isPanning = false;

    const drag = d3.drag()
        .on('start', (event) => {
            isPanning = event.sourceEvent.shiftKey;

            if (isPanning) {
                svg.attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif; cursor: move;');
                panStartX = panX;
                panStartY = panY;
            } else {
                svg.attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif; cursor: grabbing;');
                dragStartAngle = Math.atan2(event.y, event.x) * 180 / Math.PI;
                rotationAtDragStart = currentRotation;
            }
        })
        .on('drag', (event) => {
            const currentScale = d3.zoomTransform(svg.node()).k;

            if (isPanning) {
                panX = panStartX + event.x - event.subject.x;
                panY = panStartY + event.y - event.subject.y;
            } else {
                const currentAngle = Math.atan2(event.y, event.x) * 180 / Math.PI;
                const deltaAngle = dragStartAngle - currentAngle;
                currentRotation = rotationAtDragStart + deltaAngle;
            }

            applyTransform(currentScale);
        })
        .on('end', () => {
            svg.attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif; cursor: grab;');
        });

    svg.call(drag);

    // Create hierarchy
    const root = d3.hierarchy(data);

    // Create tree layout
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

    // Add circles and interactions
    addNodeCircles(node, tooltip);

    // Add labels (skip root node)
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
            if (d.depth === 2) return '14px';
            if (d.depth === 3) return '13px';
            return '12px';
        })
        .style('font-weight', d => d.data.type === 'category' ? '600' : '500')
        .clone(true).lower()
        .attr('stroke', strokeColour)
        .attr('stroke-width', 4);
}

function renderTidyTree(container, data, tooltip, width, height, textColour, strokeColour) {
    // Calculate tree dimensions - horizontal layout
    const marginTop = 20;
    const marginRight = 150;
    const marginBottom = 20;
    const marginLeft = 80;

    // Track pan offset
    let panX = 0;
    let panY = 0;

    // Create hierarchy and calculate depth
    const root = d3.hierarchy(data);
    const treeHeight = (root.leaves().length + 1) * 25; // Dynamic height based on nodes
    const treeWidth = width - marginLeft - marginRight;

    // Create tree layout - horizontal orientation
    const tree = d3.tree()
        .size([treeHeight, treeWidth])
        .separation((a, b) => (a.parent === b.parent ? 1 : 1.5));

    tree(root);

    // Create SVG with dynamic viewBox
    const svg = d3.select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .attr('viewBox', [-marginLeft, -marginTop, width, Math.max(height, treeHeight + marginTop + marginBottom)])
        .attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif; cursor: grab;');

    // Create a group for all content
    const g = svg.append('g');

    // Helper to apply current transform
    function applyTransform(scale) {
        g.attr('transform', `translate(${panX}, ${panY}) scale(${scale})`);
    }

    // Zoom behaviour
    const zoom = d3.zoom()
        .scaleExtent([0.3, 3])
        .filter(event => event.type === 'wheel' || event.type === 'dblclick')
        .on('zoom', (event) => {
            applyTransform(event.transform.k);
        });

    svg.call(zoom);

    // Drag to pan (no rotation for tidy tree)
    let panStartX = 0;
    let panStartY = 0;

    const drag = d3.drag()
        .on('start', (event) => {
            svg.attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif; cursor: grabbing;');
            panStartX = panX;
            panStartY = panY;
        })
        .on('drag', (event) => {
            panX = panStartX + event.x - event.subject.x;
            panY = panStartY + event.y - event.subject.y;
            const currentScale = d3.zoomTransform(svg.node()).k;
            applyTransform(currentScale);
        })
        .on('end', () => {
            svg.attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif; cursor: grab;');
        });

    svg.call(drag);

    // Create links - horizontal curved links
    g.append('g')
        .attr('fill', 'none')
        .attr('stroke', '#94a3b8')
        .attr('stroke-opacity', 0.4)
        .attr('stroke-width', 1.5)
        .selectAll('path')
        .data(root.links())
        .join('path')
        .attr('d', d3.linkHorizontal()
            .x(d => d.y)
            .y(d => d.x));

    // Create nodes
    const node = g.append('g')
        .selectAll('g')
        .data(root.descendants())
        .join('g')
        .attr('transform', d => `translate(${d.y},${d.x})`);

    // Add circles and interactions
    addNodeCircles(node, tooltip);

    // Add labels
    node.filter(d => d.data.type !== 'root')
        .append('text')
        .attr('dy', '0.31em')
        .attr('x', d => d.children ? -10 : 10)
        .attr('text-anchor', d => d.children ? 'end' : 'start')
        .attr('fill', textColour)
        .text(d => d.data.name)
        .style('font-size', d => {
            if (d.data.type === 'category') return '14px';
            if (d.depth === 2) return '13px';
            return '12px';
        })
        .style('font-weight', d => d.data.type === 'category' ? '600' : '500')
        .clone(true).lower()
        .attr('stroke', strokeColour)
        .attr('stroke-width', 4);

    // Add root label
    node.filter(d => d.data.type === 'root')
        .append('text')
        .attr('dy', '0.31em')
        .attr('x', -10)
        .attr('text-anchor', 'end')
        .attr('fill', textColour)
        .text(d => d.data.name)
        .style('font-size', '16px')
        .style('font-weight', '700')
        .clone(true).lower()
        .attr('stroke', strokeColour)
        .attr('stroke-width', 4);
}

function renderForceDirected(container, data, tooltip, width, height, textColour, strokeColour) {
    // Create hierarchy
    const root = d3.hierarchy(data);

    // Calculate max userCount for scaling circle sizes
    let maxUserCount = 1;
    root.each(d => {
        if (d.data.type === 'skill' && d.data.userCount > maxUserCount) {
            maxUserCount = d.data.userCount;
        }
    });

    // Create sqrt scale for skill node radius (area proportional to user count)
    const radiusScale = d3.scaleSqrt()
        .domain([0, maxUserCount])
        .range([5, 22]);

    // Track expansion state - categories start collapsed
    const expandedNodes = new Set();

    // Track which nodes have been positioned (to initialize new nodes near parent)
    const positionedNodes = new Set();

    // Get visible nodes based on expansion state
    function getVisibleNodes() {
        const visible = [];
        root.each(d => {
            // Root and categories always visible
            if (d.depth <= 1) {
                visible.push(d);
                return;
            }
            // Skills visible if their parent (category) is expanded
            // Check all ancestors - if any ancestor is collapsed, this node is hidden
            let ancestor = d.parent;
            let isVisible = true;
            while (ancestor && ancestor.depth >= 1) {
                if (!expandedNodes.has(ancestor)) {
                    isVisible = false;
                    break;
                }
                ancestor = ancestor.parent;
            }
            if (isVisible) {
                // Initialize new nodes near their parent's position
                if (!positionedNodes.has(d) && d.parent) {
                    d.x = d.parent.x + (Math.random() - 0.5) * 20;
                    d.y = d.parent.y + (Math.random() - 0.5) * 20;
                    positionedNodes.add(d);
                }
                visible.push(d);
            }
        });
        return visible;
    }

    // Get visible links based on visible nodes
    function getVisibleLinks(visibleNodes) {
        const nodeSet = new Set(visibleNodes);
        return root.links().filter(link =>
            nodeSet.has(link.source) && nodeSet.has(link.target)
        );
    }

    // Create SVG
    const svg = d3.select(container)
        .append('svg')
        .attr('width', width)
        .attr('height', height)
        .attr('viewBox', [0, 0, width, height])
        .attr('style', 'max-width: 100%; height: auto; font: 12px sans-serif;');

    // Create a group for all content
    const g = svg.append('g');

    // Zoom behaviour
    const zoom = d3.zoom()
        .scaleExtent([0.3, 3])
        .on('zoom', (event) => {
            g.attr('transform', event.transform);
        });

    svg.call(zoom);

    // Groups for links and nodes
    const linkGroup = g.append('g')
        .attr('fill', 'none')
        .attr('stroke', '#94a3b8')
        .attr('stroke-opacity', 0.4)
        .attr('stroke-width', 1.5);

    const nodeGroup = g.append('g');

    // Create force simulation (will be updated with visible nodes)
    const simulation = d3.forceSimulation()
        .force('link', d3.forceLink()
            .id(d => d.index)
            .distance(d => {
                if (d.source.depth === 0) return 120;
                if (d.source.depth === 1) return 80;
                return 60;
            })
            .strength(0.8))
        .force('charge', d3.forceManyBody()
            .strength(d => {
                if (d.data.type === 'root') return -400;
                if (d.data.type === 'category') return -200;
                return -80;
            }))
        .force('center', d3.forceCenter(width / 2, height / 2).strength(0.05))
        .force('collision', d3.forceCollide()
            .radius(d => {
                if (d.data.type === 'root') return 30;
                if (d.data.type === 'category') return 25;
                // Match visual radius plus padding for labels
                return radiusScale(d.data.userCount || 0) + 12;
            }));

    // Drag behaviour
    const drag = d3.drag()
        .on('start', (event, d) => {
            if (!event.active) simulation.alphaTarget(0.3).restart();
            d.fx = d.x;
            d.fy = d.y;
        })
        .on('drag', (event, d) => {
            d.fx = event.x;
            d.fy = event.y;
        })
        .on('end', (event, d) => {
            if (!event.active) simulation.alphaTarget(0);
            d.fx = null;
            d.fy = null;
        });

    // Update the visualization
    function update() {
        const visibleNodes = getVisibleNodes();
        const visibleLinks = getVisibleLinks(visibleNodes);

        // Update links
        const link = linkGroup.selectAll('line')
            .data(visibleLinks, d => `${d.source.data.name}-${d.target.data.name}`);

        link.exit().remove();

        const linkEnter = link.enter().append('line');

        const linkMerge = linkEnter.merge(link);

        // Update nodes
        const node = nodeGroup.selectAll('g.node')
            .data(visibleNodes, d => d.data.name + d.depth);

        node.exit().remove();

        const nodeEnter = node.enter()
            .append('g')
            .attr('class', 'node')
            .style('cursor', 'grab');

        // Add circles to new nodes
        nodeEnter.append('circle')
            .attr('stroke', '#fff')
            .attr('stroke-width', 2);

        // Add expand indicator for any node with children (categories and parent skills)
        nodeEnter.filter(d => d.children?.length && d.data.type !== 'root')
            .append('text')
            .attr('class', 'expand-indicator')
            .attr('dy', '0.35em')
            .attr('text-anchor', 'middle')
            .attr('fill', '#fff')
            .style('font-size', '10px')
            .style('font-weight', 'bold')
            .style('pointer-events', 'none');

        // Add labels
        nodeEnter.filter(d => d.data.type !== 'root')
            .append('text')
            .attr('class', 'label')
            .attr('dy', '0.31em')
            .style('pointer-events', 'none');

        nodeEnter.filter(d => d.data.type !== 'root')
            .append('text')
            .attr('class', 'label-bg')
            .attr('dy', '0.31em')
            .style('pointer-events', 'none');

        // Root label
        nodeEnter.filter(d => d.data.type === 'root')
            .append('text')
            .attr('class', 'label')
            .attr('dy', '0.31em')
            .attr('x', 16)
            .attr('fill', textColour)
            .style('font-size', '14px')
            .style('font-weight', '700')
            .style('pointer-events', 'none');

        nodeEnter.filter(d => d.data.type === 'root')
            .append('text')
            .attr('class', 'label-bg')
            .attr('dy', '0.31em')
            .attr('x', 16)
            .attr('stroke', strokeColour)
            .attr('stroke-width', 3)
            .style('font-size', '14px')
            .style('font-weight', '700')
            .style('pointer-events', 'none');

        const nodeMerge = nodeEnter.merge(node);

        // Helper to get node radius - skills sized by user count
        function getNodeRadius(d) {
            if (d.data.type === 'root') return 12;
            if (d.data.type === 'category') return 14;
            // Skills sized by user count (minimum 5px for skills with 0 users)
            return radiusScale(d.data.userCount || 0);
        }

        // Update circle attributes
        nodeMerge.select('circle')
            .attr('fill', d => {
                if (d.data.type === 'root') return '#6366f1';
                return getColour(d.data.colour);
            })
            .attr('r', getNodeRadius)
            .on('click', (event, d) => {
                event.stopPropagation();
                // Expand/collapse any node with children
                if (d.children?.length && d.data.type !== 'root') {
                    if (expandedNodes.has(d)) {
                        expandedNodes.delete(d);
                    } else {
                        expandedNodes.add(d);
                    }
                    update();
                } else if (d.data.type === 'skill' && d.data.id) {
                    window.location.href = `/admin/dashboard?tab=team&skillFilter=${d.data.id}`;
                }
            })
            .on('mouseenter', (event, d) => {
                if (d.data.type !== 'root') {
                    showTooltip(tooltip, event, d);
                    d3.select(event.target)
                        .transition()
                        .duration(150)
                        .attr('r', getNodeRadius(d) + 3);
                }
            })
            .on('mousemove', (event, d) => {
                if (d.data.type !== 'root') {
                    showTooltip(tooltip, event, d);
                }
            })
            .on('mouseleave', (event, d) => {
                hideTooltip(tooltip);
                d3.select(event.target)
                    .transition()
                    .duration(150)
                    .attr('r', getNodeRadius(d));
            })
            .style('cursor', d => {
                if (d.children?.length && d.data.type !== 'root') return 'pointer';
                if (d.data.type === 'skill') return 'pointer';
                return 'grab';
            });

        // Update expand indicator
        nodeMerge.select('.expand-indicator')
            .text(d => expandedNodes.has(d) ? '−' : '+');

        // Helper to get label x offset (based on node size)
        function getLabelOffset(d) {
            if (d.data.type === 'category') return 18;
            // Offset based on dynamic radius
            return getNodeRadius(d) + 4;
        }

        // Update labels
        nodeMerge.filter(d => d.data.type !== 'root').select('.label')
            .attr('x', getLabelOffset)
            .attr('fill', textColour)
            .text(d => d.data.name)
            .style('font-size', d => d.data.type === 'category' ? '13px' : '11px')
            .style('font-weight', d => d.data.type === 'category' ? '600' : '500');

        nodeMerge.filter(d => d.data.type !== 'root').select('.label-bg')
            .attr('x', getLabelOffset)
            .attr('stroke', strokeColour)
            .attr('stroke-width', 3)
            .text(d => d.data.name)
            .style('font-size', d => d.data.type === 'category' ? '13px' : '11px')
            .style('font-weight', d => d.data.type === 'category' ? '600' : '500')
            .lower();

        nodeMerge.filter(d => d.data.type === 'root').select('.label')
            .text(d => d.data.name);

        nodeMerge.filter(d => d.data.type === 'root').select('.label-bg')
            .text(d => d.data.name)
            .lower();

        nodeMerge.call(drag);

        // Update simulation - use lower alpha on updates to avoid jarring reorganization
        simulation.nodes(visibleNodes);
        simulation.force('link').links(visibleLinks);
        simulation.alpha(0.3).alphaDecay(0.02).restart();

        simulation.on('tick', () => {
            linkMerge
                .attr('x1', d => d.source.x)
                .attr('y1', d => d.source.y)
                .attr('x2', d => d.target.x)
                .attr('y2', d => d.target.y);

            nodeMerge.attr('transform', d => `translate(${d.x},${d.y})`);
        });
    }

    // Initialize root at center
    root.x = width / 2;
    root.y = height / 2;
    positionedNodes.add(root);

    // Initial render
    update();
}

function addNodeCircles(node, tooltip) {
    node.append('circle')
        .attr('fill', d => {
            if (d.data.type === 'root') return '#6366f1';
            if (d.data.type === 'category') return getColour(d.data.colour);
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
            d3.select(event.target)
                .transition()
                .duration(150)
                .attr('r', () => {
                    if (d.data.type === 'root') return 8;
                    if (d.data.type === 'category') return 6;
                    return 4;
                });
        });
}

// Initialize when DOM is ready
function getInitialLayout() {
    const container = document.getElementById('skills-visualization');
    return container?.getAttribute('data-initial-layout') || 'radial';
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initSkillsVisualization(getInitialLayout()));
} else {
    initSkillsVisualization(getInitialLayout());
}

// Re-initialize on Livewire navigation (if using wire:navigate)
document.addEventListener('livewire:navigated', () => initSkillsVisualization(getInitialLayout()));

// Listen for layout changes from Alpine
document.addEventListener('layout-changed', (event) => {
    initSkillsVisualization(event.detail.layout);
});
