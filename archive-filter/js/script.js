import $ from 'jquery';

(() => {
	/**
	 * Initialize Block.
	 * @param {Node} block Gutenberg block element.
	 */
	const initializeBlock = block => {
		const element = block instanceof $ ? block[0] : block;
		if (element === null) return;

		/**
		 * Add JS code here.
		 */
		console.log('B00 React block - script');
	};

	// Initialize each block on page load (front end).
	document.querySelectorAll('.b00').forEach(element => initializeBlock(element));
})();
