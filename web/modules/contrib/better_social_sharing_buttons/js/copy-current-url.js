(function (Drupal) {
  /**
   * Copies the provided text to the clipboard using
   * the Clipboard API (for HTTPS sites).
   *
   * @param {string} valueToBeCopiedToClipboard - Text (URL) to be copied.
   */
  function secureCopyToClipboard(valueToBeCopiedToClipboard) {
    // Here we use the clipboardAPI to copy to clipboard.
    navigator.clipboard.writeText(valueToBeCopiedToClipboard).catch((err) => {
      console.error('Error copying current URL to clipboard: ', err);
    });
  }

  /**
   * Fallback function for copying the provided text to the clipboard
   * for non-HTTPS sites. Uses an input element to select and copy the text.
   *
   * @param {string} valueToBeCopiedToClipboard - The text (URL) to be copied.
   */
  function unsecureCopyToClipboard(valueToBeCopiedToClipboard) {
    const inputElem = document.createElement('input');
    inputElem.value = valueToBeCopiedToClipboard;
    // Append the element to the body.
    document.body.append(inputElem);
    // Select the element.
    inputElem.select();
    try {
      /* This section copies the current selection to clipboard using 'execCommand',
      which is in the process of being deprecated, however its 'copy' command is still
      fully supported by major browsers. To learn more, please follow the link below:
      https://developer.mozilla.org/en-US/docs/Web/API/Document/execCommand. */
      document.execCommand('copy');
    } catch (err) {
      // If unable to copy to clipboard, raise an error.
      console.error('Unable to copy to clipboard', err);
    }
    // Remove the appended input element.
    document.body.removeChild(inputElem);
  }

  /**
   * Displays a popup message when the button is clicked,
   *  showing a confirmation of the copied URL.
   * The popup message is hidden after a certain duration.
   *
   * @param {HTMLElement} clickedButton - The button element that was clicked.
   * @param {string} popupMessage - The message to be displayed in the popup.
   */
  function showPopUpMessage(clickedButton, popupMessage) {
    // Find the parent container.
    const parentContainer = clickedButton.closest('.social-sharing-buttons');
    if (parentContainer) {
      const elemPopUpShow = parentContainer.querySelector(
        '.social-sharing-buttons-popup',
      );
      if (elemPopUpShow) {
        /* Restore the original popup text to trigger
        the aria-live for assistive technologies. */
        elemPopUpShow.innerHTML = popupMessage;

        elemPopUpShow.classList.add('visible');
        /* Remove 'visible' from class after a certain time
        and empty popup content so that the popup content is read again
        by assistive technologies on next click. */
        setTimeout(() => {
          elemPopUpShow.classList.remove('visible');
          elemPopUpShow.innerHTML = '';
        }, 4000);
      }
    }
  }

  /* Main function, listens for a click event,
  calls all the other functions upon element click */
  Drupal.behaviors.copyButtonElements = {
    attach(context) {
      /* Select buttons within the current context using 'once' to ensure
      listeners are added only once. */
      once(
        'copyButtonBehavior',
        context.querySelectorAll('.btn-copy.social-sharing-buttons-button'),
      ).forEach((btnCopy) => {
        const popup = btnCopy.querySelector('.social-sharing-buttons-popup');

        // Store popup content and clear popup text on page load.
        let originalText = '';
        originalText = popup.innerHTML;
        popup.innerHTML = '';

        btnCopy.addEventListener('click', function (event) {
          event.preventDefault();
          // Checks if page is using HTTPS.
          if (window.isSecureContext) {
            // Calls the secureCopyToClipboard function.
            secureCopyToClipboard(window.location.href);
          } else {
            // If site is not using HTTPS, use the fallback function.
            unsecureCopyToClipboard(window.location.href);
          }
          // Calls the function that pops up the message.
          showPopUpMessage(event.currentTarget, originalText);
        });
      });
    },
  };
})(Drupal);
