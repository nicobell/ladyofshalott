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

      window.addEventListener("load", function () {
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

  Drupal.behaviors.showPreview = {
    attach(context, settings) {
      if (window.innerWidth >= 576) {
        const preview = document.querySelector(".cert-preview");
        const certs = document.querySelectorAll(".webinars-view .views-row .cert-image img");

        preview.addEventListener("click", (event) => {
          preview.classList.add("hidden");
        });

        certs.forEach((el) => {
          el.addEventListener("click", (event) => {
            const url = event.target.getAttribute("src");
            preview.querySelector("img").setAttribute("src", url);
            preview.classList.remove("hidden");
          });
        });
      }
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
