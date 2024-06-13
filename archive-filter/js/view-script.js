(() => {
	/**
	 * Initialize Block.
	 * @param {Node} block Gutenberg block element.
	 */
	const initializeBlock = block => {
		if (block === null) return;

		/**
		 * Add JS code here.
		 */
		console.log('B00 React block - view script');
	};

	// Initialize each block on page load (front end).
	document.querySelectorAll('.b00').forEach(element => initializeBlock(element));
})();
