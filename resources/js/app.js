import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import './monitoring-charts';

window.Alpine = Alpine;
window.Chart = Chart;

const createLoadingSpinner = () => {
	const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
	svg.setAttribute('viewBox', '0 0 24 24');
	svg.setAttribute('fill', 'none');
	svg.setAttribute('aria-hidden', 'true');
	svg.classList.add('h-4', 'w-4', 'shrink-0', 'animate-spin');

	const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
	circle.setAttribute('cx', '12');
	circle.setAttribute('cy', '12');
	circle.setAttribute('r', '10');
	circle.setAttribute('stroke', 'currentColor');
	circle.setAttribute('stroke-width', '4');
	circle.classList.add('opacity-25');

	const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
	path.setAttribute('fill', 'currentColor');
	path.setAttribute('d', 'M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z');
	path.classList.add('opacity-75');

	svg.append(circle, path);
	return svg;
};

document.addEventListener('submit', (event) => {
	const form = event.target;
	if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-loading')) return;
	if (form.matches('#createBackupForm, #restoreBackupForm, #gpoaForm') || form.dataset.loadingStarted === 'true') return;

	const button = event.submitter instanceof HTMLButtonElement
		? event.submitter
		: form.querySelector('button[type="submit"], input[type="submit"]');
	if (!button || button.disabled) return;

	form.dataset.loadingStarted = 'true';
	button.disabled = true;
	const label = document.createElement('span');
	label.textContent = button.dataset.loadingText || 'Loading…';
	button.replaceChildren(createLoadingSpinner(), label);
}, true);

document.addEventListener('click', (event) => {
	const link = event.target.closest('a[data-download-loading]');
	if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
	if (link.getAttribute('aria-disabled') === 'true') {
		event.preventDefault();
		return;
	}

	link.setAttribute('aria-disabled', 'true');
	link.classList.add('pointer-events-none');
	link.append(createLoadingSpinner());
	window.setTimeout(() => {
		link.querySelector('svg.animate-spin')?.remove();
		link.removeAttribute('aria-disabled');
		link.classList.remove('pointer-events-none');
	}, 8000);
}, true);

Alpine.start();
