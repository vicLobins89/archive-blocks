/* eslint-disable no-restricted-syntax */
/**
 * JS Functionality for the Clarity Feed class
 */
(() => {
	// This is set to false if we're using the 'Load more' button.
	let clearContainer = true;
	let offset = false;
	let found = 0;

	/**
	 * Stringifies FormData key/value pairs into URL params.
	 * @param {FormData} formData object of submitted form data
	 * @returns {string} json serialised string
	 */
	const serializeFormData = formData => {
		let dataString = '';
		for (const [name, value] of formData) {
			dataString += `${name}=${value}&`;
		}

		// Remove last ampersand.
		if (dataString.charAt(dataString.length - 1) === '&') {
			dataString = dataString.substring(0, dataString.length - 1);
		}

		return dataString;
	};

	/**
	 * Updates URL in browser window
	 * @param {string} dataString stringifed form params
	 */
	const updateURL = dataString => {
		const { hash } = window.location;
		const urlData = [];
		const queryUrl = `${location.protocol}//${location.host}${location.pathname}`;

		// Prepare url params.
		const values = dataString.split('&');
		values.forEach(part => {
			if (part.indexOf('posts-') === -1 && part.indexOf('query-') === -1) {
				const item = part.split('=');
				if (item[1]) {
					urlData.push(part);
				}
			}
		});

		// Set URL.
		const parameters = urlData.join('&');
		const qmark = parameters.length ? '?' : '';
		const url = queryUrl + qmark + parameters + hash;
		const isIE11 = !!window.MSInputMethodContext && !!document.documentMode;

		if (!isIE11) {
			window.history.pushState({}, '', url);
		}
	};

	/**
	 * Trigger XmlHttpRequest to send data to wp_ajax and return post items
	 * @param {string} dataString stringifed form params
	 * @param {Node}   form html tag
	 */
	const doXHR = (dataString, form) => {
		form.classList.add('cty-archive__form--loading');
		form.classList.remove('cty-archive__form--done');

		const data = {
			action: 'clarity-archive',
			nonce: cty.nonce,
			feedFilter: true,
			feedAppend: offset && clearContainer === false ? offset : !clearContainer,
			feedName: form.getAttribute('name'),
			params: dataString,
			unique_id: form.dataset.unique_id,
		};
		const url = `${cty.ajaxUrl}?${new URLSearchParams(data).toString()}`;

		// Conatiners.
		const postsContainer = form.querySelectorAll('.cty-archive-posts');
		const paginatonContainer = form.querySelector('.cty-archive-pagination');
		const summaryContainer = form.querySelector('.cty-archive-summary');
		const featuredContainer = form.querySelector('.cty-archive-featured');

		const xhttp = new XMLHttpRequest();
		xhttp.open('GET', url, true);
		/**
		 * Perform XHR
		 */
		xhttp.onreadystatechange = () => {
			if (xhttp.readyState === 4) {
				const response = JSON.parse(xhttp.response);

				// Remove featured.
				if (featuredContainer) {
					featuredContainer.remove();
				}

				// Set response vars and replace content.
				if (response.post_items && postsContainer !== null) {
					if (clearContainer) {
						postsContainer.forEach((element, index) => {
							element.innerHTML = response.post_items[index];
						});
					} else {
						for (let i = 0; i < response.post_items.length; i++) {
							postsContainer.item(postsContainer.length - 1).innerHTML += response.post_items[i];
						}
					}
				}
				if (typeof response.paginaton !== 'undefined' && paginatonContainer) {
					paginatonContainer.innerHTML = response.paginaton;
				}
				if (response.summary && summaryContainer) {
					summaryContainer.innerHTML = response.summary;
				}
				if (response.offset) {
					offset = response.offset;
				}
				if (response.found) {
					found = response.found;
				}

				// Show/hide load more button.
				const loadMore = form.querySelector('.cty-archive-pagination__button');
				if (found === 0 || offset === found || offset === false) {
					form.classList.add('cty-archive__form--none');
					if (loadMore) {
						loadMore.classList.add('is-hidden');
					}
				} else {
					form.classList.remove('cty-archive__form--none');
					if (loadMore) {
						loadMore.classList.remove('is-hidden');
					}
				}

				form.classList.remove('cty-archive__form--loading');
				form.classList.add('cty-archive__form--done');

				// Trigger custom event.
				const event = new Event('clarityArchiveDone');
				form.dispatchEvent(event);
			}
		};
		xhttp.send();
	};

	/**
	 * Submits given form
	 * @param {Node} form html tag
	 */
	const submitForm = form => {
		// Reset offset if not load more button.
		if (clearContainer === true) {
			offset = false;
		}
		const formData = new FormData(form);
		const dataString = serializeFormData(formData);
		updateURL(dataString);
		doXHR(dataString, form);
	};

	/**
	 * Toggles on/off array of elements
	 * @param { Array } elements to loop and toggle.
	 */
	const toggle = elements => {
		for (let i = 0; i < elements.length; i++) {
			elements[i].classList.toggle('is-hidden');
		}
	};

	/**
	 * Start Clarity Archive
	 * @param {Node} form html tag
	 */
	const initClarityFeed = form => {
		// Filter triggers.
		const filters = form.querySelectorAll('select[data-filter], input[data-filter]');
		filters.forEach(filter => {
			filter.addEventListener('change', () => {
				clearContainer = true;
				submitForm(form);
			});
		});

		// Search input triggers.
		const searchInput = form.querySelectorAll('input[type="search"]');
		searchInput.forEach(input => {
			const debounce = parseInt(input.getAttribute('data-debounce'), 10) || 200;
			let timeout = null;
			input.addEventListener('input', () => {
				clearContainer = true;
				clearTimeout(timeout);
				timeout = setTimeout(() => {
					submitForm(form);
				}, debounce);
			});
		});

		// Load more button.
		const loadMoreWrapper = form.querySelector('.cty-archive-pagination__button');
		const loadMore = form.querySelector('.cty-archive-pagination__submit');
		if (loadMore && loadMoreWrapper) {
			loadMore.addEventListener('click', e => {
				e.preventDefault();
				clearContainer = false;
				submitForm(form);
			});

			// Remove button if there are no posts found.
			if (form.classList.contains('cty-archive__form--none')) {
				loadMoreWrapper.classList.add('is-hidden');
			}
		}

		// Limit radio/checkboxes + add See More button.
		const inputLimit = 10;
		const filterWrappers = form.querySelectorAll('.cty-archive-filter');
		filterWrappers.forEach(wrapper => {
			const inputWrappers = wrapper.querySelectorAll('.cty-archive-filter__inner');
			const inputArr = Array.from(inputWrappers);
			const hidden = inputArr.slice(inputLimit);
			const toggleBtn = wrapper.querySelector('.cty-archive-filter__see-more');
			if (toggleBtn) {
				toggleBtn.addEventListener('click', () => {
					toggle(hidden);
					toggleBtn.remove();
				});
			}
		});
	};

	const forms = document.querySelectorAll('.cty-archive__form');
	forms.forEach(form => {
		initClarityFeed(form);
	});
})();
