// path: slick-settings.js
jQuery(document).ready(function($){
    $(".slick").slick({
        infinite: true,
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 5000,
        speed: 500,
        fade: true,
        dots: true,
        cssEase: "linear"
    });
});
