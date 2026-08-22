/* recommendations.js — AI Team Recommendation Animations */

'use strict';

// Note: animateCards() and animateScoreBars() are defined in main.js
// This file handles star ratings and any recommendation-specific logic

// Animate star ratings on load
function renderStars(rating, max = 5) {
    let html = '';
    for (let i = 1; i <= max; i++) {
        html += `<span style="color:${i <= rating ? '#fbbf24' : '#484f58'}">★</span>`;
    }
    return html;
}

// Override animateCards with a nicer cubic-bezier version
function animateCards() {
    document.querySelectorAll('.rec-card').forEach((card, i) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all .5s cubic-bezier(.4,0,.2,1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, i * 120 + 100);
    });
}

// Override animateScoreBars with a nicer version
function animateScoreBars() {
    document.querySelectorAll('.rec-score-fill').forEach((bar, i) => {
        const target = bar.dataset.score || 0;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.transition = 'width 1s cubic-bezier(.4,0,.2,1)';
            bar.style.width = target + '%';
        }, i * 150 + 300);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Star render for static star containers
    document.querySelectorAll('.star-display').forEach(el => {
        const rating = parseInt(el.dataset.rating || 0);
        el.innerHTML = `<span class="stars">${renderStars(rating)}</span>`;
    });
});
