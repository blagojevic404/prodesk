





function film_genres_flip(){

    var genres1 = document.getElementById("genres1");
    var genres2 = document.getElementById("genres2");

    var sid = document.getElementById("SectionID");


    if (!sid.onchange) {

        // Set *onchange* event for SectionID cbo
        sid.onchange = function() {
            film_genres_flip();
        };
    }

    if (sid.selectedIndex==1) {

        genres1.style.display = 'none';
        genres2.style.display = '';

    } else {

        genres2.style.display = 'none';
        genres1.style.display = '';
    }
}


