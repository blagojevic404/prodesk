
var yylead = datelead.substr(0,4);
var mmlead = datelead.substr(4,2);
var ddlead = datelead.substr(6,2);
var yynow = datenow.substr(0,4);
var mmnow = datenow.substr(4,2);
var ddnow = datenow.substr(6,2);
var yycur = datecur.substr(0,4);
var mmcur = datecur.substr(4,2);
var ddcur = datecur.substr(6,2);


mdays = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

function redo_calendar(typ, date_submited) {

	var html_y = '';
	var html_m = '';
	var html_d = '';
    var i, css;

	var fn_submit = (typ=='submit_to_self') ? 'get_submit' : 'tunel';


	for (i=yylead; i<=yynow; i++) {
		if (i==yycur) {
			html_y += '<span class="cur">' + (i) + "</span>\n";
		} else {
			html_y += "<a href=\"#\" onClick=\"reforma(" + (i) + ",-1,'" + typ + "')\">" + (i) + "</a>\n";
		}
	}

	for (i=0; i<12; i++) {
		if ((yycur==yylead && (i+1)<mmlead) || (yycur==yynow && (i+1)>mmnow)) {
			html_m += (i+1) + ' ';
		} else if (i==(mmcur-1)) {
            html_m += '<span class="cur">' + (i+1) + "</span>\n";
		} else {
			html_m += "<a href=\"#\" onClick=\"reforma(-1," + (i+1) + ",'" + typ + "')\">" + (i+1) + "</a>\n";
		}
	}

	for (i=0; i<mdays[mmcur-1]; i++) {
		if ((yycur==yylead && (mmcur-1)<mmlead && (i+1)<ddlead)||(yycur==yynow && mmcur==mmnow && (i+1)>ddnow)) {
			html_d += (i+1) + ' ';
		} else {
			css = (date_submited && i==(ddcur-1)) ? 'cur' : '';
			html_d += '<a class="'+css+'" href="javascript:'+fn_submit+"('dtr', "+yycur+fd(mmcur)+fd(i+1)+');">' +
				(i+1) + "</a>\n";
		}
	}


	var bumper = "&nbsp;&raquo;&raquo;&raquo;&nbsp;";
	var s = html_y + bumper + html_m + bumper + html_d;
	write_layer("clndar", s);
}



function reforma(ny, nm, typ) {
	
	if (ny!=-1) {
		yycur = ny;
		if (yycur==yynow) {
			mmcur = mmnow;
		} else {
			mmcur = 12;
		}
	}
	if (nm!=-1) mmcur = nm;
	redo_calendar(typ, null);
}


	
