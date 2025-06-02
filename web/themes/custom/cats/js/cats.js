/**
 * @file
 * Cats behaviors.
 */
(function (Drupal) {
  "use strict";

  Drupal.behaviors.scrollFadeIn = {
    attach(context, settings) {
      window.addEventListener("scroll", function () {
        const header = document.querySelector(".header");
        if (window.scrollY > 0) {
          header.classList.add("scrolled");
        } else {
          header.classList.remove("scrolled");
        }

        checktofadein();
      });
    },
  };
  
})(Drupal);

function checktofadein() {
  document.querySelectorAll(".tofadein").forEach((el) => {
    let box = el.getBoundingClientRect();
    if (box.top < (window.innerHeight * 80) / 100) el.classList.add("fadein");
    else if (box.top > window.innerHeight) el.classList.remove("fadein");
  });
}
