[common]

app_version 3.6
app_vdate 2018/08/19

company_id 1


# epg settings which we also need in dsk section because we call epg functions
zerotime 06:00:00
lbl_parental_filmbased 1
lbl_rec4rerun_prgbased 1
speakerz_cnt_max 5

# Characters per 100 seconds. We need characters per one second but we use 100 in order to save values to two decimal points.
# prefix 1,2 is for channel type
read_speed_1 1730
read_speed_2 1540

# How old records are taken into account for conflict, and how often ajax updates TermAccess, (both in seconds).
mdflog_expire 60
mdflog_ajax_interval 20

cvr_pagelbl_maxlen 30

strynew_2in1 1

stry_sec_level 0

# How many seconds shoud script execution take, in order to trigger srpriz log
log_exec_time_limit 3

# PRG & UZR info conversion cyr2lat. Activate only when cyr installation is used in yu-lat/eng language.
cyr2lat 0
user_scramble 0

dur_use_milli 1

[/common]



[epg]

film_weblive_def 0
film_webvod_def 0

tmpl_dummy_starttime 1970-01-01 00:00:00

mattype_use_cbo 0
epg_prg_use_cbo 0
epguptd_warney_cnt 20
epguptd_ajax_cnt 10
phzuptd_ajax_cnt 10

termnow_delay 2

atom_last_phrase_width 20

bcast_cnt_separate 0

epgxml_path ../../_local/cron_doc/epg/

epg_cron_chnlz 1,2,4

#epg_auto_chnlz 2

# prefix 1,2 is for channel type
epg_auto_daycnt_1 12
epg_auto_daycnt_2 9

scnr_show_cln_dur_invalid 0

epg_mattyp_def 1

epg_vo_sign <span class="glyphicon glyphicon-film"></span>

epg_radio_live_sign <span class="glyphicon glyphicon-headphones"></span>

mktplan_positions 1,2,0,-2,-1

mktplan_sibling_tframes 1

mktitemlist_show_agency_cln 0

epg_plan_days_limit 42

# whether to use NOTES in mktplan (they clutter!)
mktplan_use_notes 1


mkt_timeframe_cutter_mm 30
mkt_timeframe_limit_ss 360

mkt_slct_float_t_before 30
mkt_slct_float_t_after 30

mkt_min_distance_in_scnr 25

# FOOLZ insist to use pseudo video id (added by user, not by program). vbdo: Delete when possible, and don't forget TXT "video_id".
use_mktitem_video_id 1

unique_mktitem_video_id 0

[/epg]



[dsk]

# prefix 1,2 is for channel type
strytyp_default_1 3
strytyp_default_2 1

# whether to use "mos" procedure in atoms of *record* type
use_mos_for_rec_atom 0

# whether to use NOTES in stories (they clutter!)
dsk_use_notes 0

[/dsk]



[hrm]

ctrl_fathername 0
ctrl_gender 0
ctrl_contract 0

[/hrm]



[admin]

# Whether to limit ftp only to www root directory, or allow browsing entire server dir structure.
ftp_wwwdir_limit 1

db_arhiver 400
jnt_epgxml 7
jnt_stry_versions 15
jnt_stry_trash 30
jnt_log_sql 15
jnt_log_mdf 2
jnt_log_inout 60

[/admin]

