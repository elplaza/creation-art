$(document).ready(function() {
    // se clicco sulla freccia in basso mi scrolla fino a su
    $('.scroll-top').click(function() {
        $('html,body').animate({
            scrollTop: $("body").offset().top
        }, 'slow');
    });
});