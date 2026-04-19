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

        if(preview)
          preview.addEventListener("click", (event) => {
            preview.classList.add("hidden");
          });

        if(certs.length)
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

  Drupal.behaviors.showCookieNotHome = {
    attach: function (context) {
      window.addEventListener("load", () => {
        let cookies = document.cookie
          .split("; ")
          .find((row) => row.startsWith("ladyofshalott-categories="));

        if (!cookies || cookies?.split("=")[1].length <= 2)
          setTimeout(() => {
            document
              .getElementById("sliding-popup")
              .classList.add("frombottom");
          }, 1500);
      });

      const cookies = document.querySelector("#block-cats-footer ul li:last-child span");
      cookies.addEventListener("click", () => {
        event.preventDefault();
        document.getElementById("sliding-popup").classList.add("frombottom");
      });
    },
  };

  Drupal.behaviors.gallerySwiper = {
    attach: function (context) {
      const swiper = document.querySelectorAll('.swiper');
      if(swiper.length)
        swiper.forEach((swiperEl) => {
          new Swiper(swiperEl, {
            direction: 'horizontal',
            pagination: {
              el: swiperEl.querySelector(".swiper-pagination"),
              clickable: true,
              //dynamicBullets: true
            },
            navigation: {
              nextEl: swiperEl.querySelector(".swiper-button-next"),
              prevEl: swiperEl.querySelector(".swiper-button-prev"),
            },
            slidesPerView: 1,
            slidesPerGroup: 1,
            spaceBetween: 0,
            loop: true,
            lazy: true,
          });
        });
    },
  };

  Drupal.behaviors.accordionCats = {
    attach: function (context) {
      const accordion = document.querySelector("#accordion-cats-button");
      if(accordion)
        accordion.addEventListener("click", (event) => {
          const scacchiera = event.target.closest(".accordion-cats")//.querySelector(".scacchiera");
          if (scacchiera) {
            const expand = scacchiera.classList.contains("expanded");
            if (expand) {
              scacchiera.classList.remove("expanded");
              setTimeout(() => {
                const anchor = document.getElementById("i-gatti");
                console.log("anchor", anchor)
                if(anchor)
                  anchor.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                  })
              }, 200);

            } else {
              scacchiera.classList.add("expanded")
              setTimeout(() => {
                const anchor = document.getElementById("accordion-anchor");
                console.log("anchor", anchor)
                if(anchor)
                  anchor.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                  })
              }, 200);
            }
          }
        })
    },
  };

})(Drupal);

function checktofadein() {
  const fading = document.querySelectorAll(".tofadein");
  if(fading.length)
    fading.forEach((el) => {
      let box = el.getBoundingClientRect();
      if (box.top < (window.innerHeight * 80) / 100) el.classList.add("fadein");
      else if (box.top > window.innerHeight) el.classList.remove("fadein");
    });
}
