

function js_onload() {


    /* VB: adjust targeting to section when called via url (because fixed nav header is 50px tall) */

    var n = location.href.lastIndexOf('#');

    if (n>0) {

        var jumper = location.href.substring(n);

        var p = $( jumper );
        var offset = p.offset();

        $('html,body').animate({
            scrollTop: offset.top - 50
        }, 100);
    }


    /* activate sidebar */
    $('#sidebar').affix({
        offset: {
            top: 235
        }
    });

    /* activate scrollspy menu */
    var $body = $(document.body);
    var navHeight = $('.navbar').outerHeight(true) + 30;

    $body.scrollspy({
        target: '#leftCol',
        offset: navHeight
    });

    /* smooth scrolling sections */
    $('a[href*=#]:not([href=#])').click(function () {
        if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
            var target = $(this.hash);
            target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
            if (target.length) {
                $('html,body').animate({
                    scrollTop: target.offset().top - 50
                }, 100);
                return false;
            }
        }
    });

}






