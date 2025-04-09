/**
 * @file
 * JS code for setting print view of page.
 */

(function () {
  /**
   * Set the print view of the page.
   */
  Drupal.behaviors.printLink = {
    attach(context) {
      const printLink = context.querySelector('.bssb-printlink');
      printLink.addEventListener('click', function (e) {
        const links = document.getElementsByTagName('link');
        Array.from(links).forEach(function (link) {
          const rel = printLink.getAttribute('rel');
          link.setAttribute('href', rel);
        });

        e.preventDefault();
      });
    },
  };
})(Drupal);
