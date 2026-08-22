/* charts.js — Chart.js configuration for Faculty Analytics */

'use strict';

// Common chart defaults (dark theme)
function setChartDefaults() {
    Chart.defaults.color = '#8b949e';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.font.size = 12;
}

// Gradient helper
function makeGradient(ctx, color1, color2) {
    const grad = ctx.createLinearGradient(0, 0, 0, 300);
    grad.addColorStop(0, color1);
    grad.addColorStop(1, color2);
    return grad;
}

// Bar chart: Student Collaboration Scores
function initCollabBarChart(canvasId, labels, scores) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const grad = makeGradient(ctx, 'rgba(99,102,241,.8)', 'rgba(6,182,212,.4)');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Collaboration Score',
                data: scores,
                backgroundColor: grad,
                borderColor: 'rgba(99,102,241,.9)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(13,17,23,.95)',
                    borderColor: 'rgba(99,102,241,.4)',
                    borderWidth: 1,
                    callbacks: {
                        label: ctx => ` Score: ${ctx.parsed.y} / 100`
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(255,255,255,.05)' },
                    ticks: { callback: v => v + '%' }
                }
            },
            animation: { duration: 1200, easing: 'easeInOutQuart' }
        }
    });
}

// Doughnut chart: Task Completion Rates
function initTaskDoughnut(canvasId, completed, inProgress, pending) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'In Progress', 'Pending'],
            datasets: [{
                data: [completed, inProgress, pending],
                backgroundColor: [
                    'rgba(16,185,129,.8)',
                    'rgba(6,182,212,.8)',
                    'rgba(245,158,11,.8)',
                ],
                borderColor: ['#10b981','#06b6d4','#f59e0b'],
                borderWidth: 2,
                hoverOffset: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, usePointStyle: true, pointStyleWidth: 10 }
                },
                tooltip: {
                    backgroundColor: 'rgba(13,17,23,.95)',
                    borderColor: 'rgba(255,255,255,.1)',
                    borderWidth: 1,
                }
            },
            animation: { animateRotate: true, duration: 1200 }
        }
    });
}

// Line chart: Project Progress Over Months
function initProgressLine(canvasId, labels, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const grad = makeGradient(ctx, 'rgba(99,102,241,.3)', 'rgba(99,102,241,.01)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Avg Progress %',
                data,
                backgroundColor: grad,
                borderColor: '#6366f1',
                borderWidth: 2.5,
                pointBackgroundColor: '#6366f1',
                pointRadius: 5,
                pointHoverRadius: 8,
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: 'rgba(255,255,255,.05)' },
                    ticks: { callback: v => v + '%' }
                }
            },
            animation: { duration: 1200 }
        }
    });
}

document.addEventListener('DOMContentLoaded', setChartDefaults);
