
/**
 * Select and copy CarGen content
 *
 * @param {object} z *this* element, i.e. button
 */
function cg_selcopy(z) {

    var tarea_arr = z.parentNode.parentNode.parentNode.getElementsByTagName('textarea');
    var ctrl = tarea_arr[0];

    ctrl.select();
    document.execCommand('copy');
}


/**
 * Front for text alphabet convert
 *
 * @param {string} convtyp Conversion type: (up2low, lat2cyr, cyr2cyrdos)
 * @param {string} iface Interface type: (modal, cg, atom)
 * @param {object} z *this* element, i.e. button
 *
 * @return void
 */
function alphconv(convtyp, iface, z) {

    var ctrl;

    if (!iface) {
        iface = 'modal';
    }

    if (iface=='modal') {

        ctrl = document.querySelector('textarea#alphconv')

    } else { // cg, atom

        var tarea_arr = z.parentNode.parentNode.parentNode.getElementsByTagName('textarea');
        ctrl = tarea_arr[0];
    }

    if (convtyp=='up2low') {

        ctrl.value = up2low(ctrl.value);

    } else if (convtyp=='lat2cyr') {

        ctrl.value = yu_conv(ctrl.value);

    } else if (convtyp=='cyr2cyrdos') {

        ctrl.value = yu_conv(ctrl.value, 'cyr', 'cyrdos');
    }

    if (iface=='atom') {
        return;
    }

    ctrl.select();
    document.execCommand('copy');
}


function up2low(str){

	var lead = str.substr(0,1);
	var rest = str.substr(1);

	str = lead.toUpperCase() + rest.toLowerCase();

	return str;
}

function yu_conv(str, typ1, typ2){

    var arr1, arr2, str1, str2;

    var str_lat = 'lj nj dž LJ NJ DŽ Dž a b v g d đ e ž z i j k l lj m n nj o p r s t ć u f h c č dž š ' +
        'A B V G D Đ E Ž Z I J K L LJ M N NJ O P R S T Ć U F H C Č DŽ Š';

    var str_cyr = 'љ њ џ Љ Њ Џ Џ а б в г д ђ е ж з и ј к л љ м н њ о п р с т ћ у ф х ц ч џ ш ' +
        'А Б В Г Д Ђ Е Ж З И Ј К Л Љ М Н Њ О П Р С Т Ћ У Ф Х Ц Ч Џ Ш';

    var str_cyrdos = "q w x Q W X X a b v g d | e ` z i j k l lj m n nj o p r s t } u f h c ~ x { " +
        "A B V G D \\ E @ Z I J K L Q M N W O P R S T ] U F H C ^ X [";

    switch (typ1){
        case 'cyr': str1 = str_cyr; break;
        case 'cyrdos': str1 = str_cyrdos; break;
        default: str1 = str_lat;
    }

    switch (typ2){
        case 'lat': str2 = str_lat; break;
        case 'cyrdos': str2 = str_cyrdos; break;
        default: str2 = str_cyr;
    }

    arr1 = str1.split(' ');
    arr2 = str2.split(' ');

	str = str_replace(arr1, arr2, str);
	return str;
}





function str_replace(search, replace, subject) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Gabriel Paderni
    // +   improved by: Philip Peterson
    // +   improved by: Simon Willison (http://simonwillison.net)
    // +    revised by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   bugfixed by: Anton Ongson
    // +      input by: Onno Marsman
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    tweaked by: Onno Marsman
    // *     example 1: str_replace(' ', '.', 'Kevin van Zonneveld');
    // *     returns 1: 'Kevin.van.Zonneveld'
    // *     example 2: str_replace(['{name}', 'l'], ['hello', 'm'], '{name}, lars');
    // *     returns 2: 'hemmo, mars'

    var i, j;
    var f = search, r = replace, s = subject;
    var ra = r instanceof Array, sa = s instanceof Array;
    f = [].concat(f); r = [].concat(r); i = (s = [].concat(s)).length;

    while (j = 0, i--) {
        if (s[i]) {
            while (s[i] = s[i].split(f[j]).join(ra ? r[j] || "" : r[0]), ++j in f){};
        }
    };
 
    return sa ? s : s[0];
}

