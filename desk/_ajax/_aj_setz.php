<?php
/**
 * Settings change (ReadSpeed and Color). The script is called via ajax.
 */


require '../../../__ssn/ssn_boot.php';

$id = (isset($_POST['id'])) ? wash('int', $_POST['id']) : 0;

$typ = (isset($_POST['typ'])) ? wash('arr_assoc', $_POST['typ'], ['dft', 'del', 'clr']) : null;

if (!$typ) exit;
if (!$id && $typ!='clr') exit;



if ($typ=='dft') {


    qry('UPDATE stry_readspeed SET IsDefault=0 WHERE UID='.UZID);
    qry('UPDATE stry_readspeed SET IsDefault=1 WHERE ID='.$id);

    echo '1';


} elseif ($typ=='del') {


    $is_default = rdr_cell('stry_readspeed', 'IsDefault', $id);

    qry('DELETE FROM stry_readspeed WHERE ID='.$id);

    if ($is_default) {

        $new_default = qry_numer_var('SELECT ID FROM stry_readspeed WHERE UID='.UZID.' ORDER BY ID DESC');

        if ($new_default) {
            qry('UPDATE stry_readspeed SET IsDefault=1 WHERE ID='.$new_default);
        }
    }

    echo $id;


} elseif ($typ=='clr') {


    $n = wash('int', implode('', $_POST['c']));

    setz_put('reader_color', $n);

}




if (in_array($typ, ['dft', 'del'])) { // Speaker RS settings

    speakerUID_termemit(UZID);
}

