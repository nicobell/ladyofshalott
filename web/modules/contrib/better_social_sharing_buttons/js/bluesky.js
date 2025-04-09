(function (Drupal, once) {
  Drupal.behaviors.blueskyShareLink = {
    attach(context, settings) {
      once('blueskyShareLink', '.share-bluesky', context).forEach(
        function (element) {
          const pageTitle = encodeURIComponent(document.title);
          const pageUrl = encodeURIComponent(window.location.href);
          element.href = `https://bsky.app/intent/compose?text=${pageTitle}%20${pageUrl}`;
        },
      );
    },
  };
})(Drupal, once);
