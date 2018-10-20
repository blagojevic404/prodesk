<?php

// SECTION-specific functions

// This file can be loaded only after the SCTN is determined, i.e. after ssn_sets.php.




if (SCTN) {
    require 'lib_'.SCTN.'.php';
}


if (SCTN=='dsk') {
    require 'lib_epg.php';
    require 'lib_hrm.php'; // TMZ needs org_tree()..
}


if (SCTN=='dsk' || SCTN=='epg') {
    require 'lib_dsk-epg.php';
    require 'lib_dsk-epg_cover.php';
    require 'lib_dsk-epg_termemit.php';
    require 'lib_dsk-epg_prgm.php';
    require 'lib_dsk-epg_versions.php';
}


