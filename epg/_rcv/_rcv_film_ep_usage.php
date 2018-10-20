<?php


pms('epg/film', 'mdf', $x, true);


$post = (isset($_POST['Submit_FILM_EP_USAGE_AUTO'])) ? $_POST['AUTO'] : $_POST['ID'];


foreach ($post as $k => $v) {

    $mdf['ID']	= wash('int', $k);
    $mdf['FilmID']	= wash('int', $v);

    $cur = rdr_row('epg_films', 'ID, FilmID', $mdf['ID']);

    if ($cur && $mdf!=$cur) {

        receiver_upd('epg_films', $mdf, $cur, LOGSKIP);

        // Termemit

        $elmid = scnr_id_to_elmid($mdf['ID'], 13);

        $epgid = rdr_cell('epg_elements', 'EpgID', $elmid);

        sch_termemit($epgid, 'epg');
    }
}


qry_log(null, ['tbl_name' => 'film', 'x_id' => $x['ID'], 'act_id' => 63, 'act' => 'epg']);


hop($pathz['www_root'].$_SERVER['SCRIPT_NAME'].'?typ='.$x['TYP'].'&id='.$x['ID']);
