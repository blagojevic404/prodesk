CREATE DATABASE  IF NOT EXISTS `prodesk`;
USE `prodesk`;


--
-- Table structure for table `cfg_arrz`
--

DROP TABLE IF EXISTS cfg_arrz;


CREATE TABLE cfg_arrz (
  ID smallint(5) unsigned NOT NULL,
  Section varchar(45) DEFAULT NULL,
  `Name` varchar(45) DEFAULT NULL,
  `Value` varchar(128) DEFAULT NULL,
  Queue tinyint(4) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY section (Section),
  KEY `name` (`Name`)
);


--
-- Table structure for table `cfg_varz`
--

DROP TABLE IF EXISTS cfg_varz;


CREATE TABLE cfg_varz (
  ID smallint(5) unsigned NOT NULL,
  Section varchar(10) DEFAULT NULL,
  `Name` varchar(45) DEFAULT NULL,
  `Value` varchar(45) DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY section (Section)
);


--
-- Table structure for table `channels`
--

DROP TABLE IF EXISTS channels;


CREATE TABLE channels (
  ID tinyint(3) unsigned NOT NULL,
  Caption tinytext,
  Caption_short tinytext,
  TypeX tinyint(3) unsigned DEFAULT NULL,
  Queue tinyint(3) unsigned DEFAULT NULL,
  GroupID smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (ID)
);


--
-- Table structure for table `cn_crw`
--

DROP TABLE IF EXISTS cn_crw;


CREATE TABLE cn_crw (
  ID mediumint(8) unsigned NOT NULL,
  NativeType tinyint(4) unsigned DEFAULT NULL,
  NativeID mediumint(9) unsigned DEFAULT NULL,
  CrewType tinyint(4) unsigned DEFAULT NULL,
  CrewUID smallint(5) unsigned DEFAULT NULL,
  OptData smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY nativetype (NativeType),
  KEY nativeid (NativeID),
  KEY crewtype (CrewType)
);


--
-- Table structure for table `cn_mos`
--

DROP TABLE IF EXISTS cn_mos;


CREATE TABLE cn_mos (
  ID mediumint(8) unsigned NOT NULL,
  NativeType tinyint(3) unsigned DEFAULT NULL,
  NativeID mediumint(9) unsigned DEFAULT NULL,
  IsReady tinyint(3) unsigned DEFAULT NULL,
  Duration time DEFAULT NULL,
  TCin time DEFAULT NULL,
  TCout time DEFAULT NULL,
  Label tinytext,
  Path tinytext,
  PRIMARY KEY (ID),
  KEY `chain` (NativeType,NativeID)
);


--
-- Table structure for table `cn_notes`
--

DROP TABLE IF EXISTS cn_notes;


CREATE TABLE cn_notes (
  ID mediumint(8) unsigned NOT NULL,
  NativeType tinyint(3) unsigned DEFAULT NULL,
  NativeID mediumint(9) unsigned DEFAULT NULL,
  Note tinytext,
  PRIMARY KEY (ID),
  KEY `chain` (NativeType,NativeID)
);


--
-- Table structure for table `epg_bcasts`
--

DROP TABLE IF EXISTS epg_bcasts;


CREATE TABLE epg_bcasts (
  ID int(11) unsigned NOT NULL,
  SchType tinyint(3) unsigned DEFAULT NULL,
  SchLineID mediumint(8) unsigned DEFAULT NULL,
  TermStart datetime DEFAULT NULL,
  `Phase` tinyint(3) unsigned DEFAULT NULL,
  NativeType tinyint(3) unsigned DEFAULT NULL,
  NativeID mediumint(8) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY sch (SchType,SchLineID),
  KEY native (NativeType,NativeID)
);


--
-- Table structure for table `epg_blocks`
--

DROP TABLE IF EXISTS epg_blocks;


CREATE TABLE epg_blocks (
  ID smallint(5) unsigned NOT NULL,
  BlockType tinyint(3) unsigned DEFAULT NULL,
  Caption tinytext,
  DurForc time DEFAULT NULL,
  DurEmit time DEFAULT NULL,
  CtgID tinyint(3) unsigned DEFAULT NULL,
  TermAdd datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID)
);


--
-- Table structure for table `epg_clips`
--

DROP TABLE IF EXISTS epg_clips;


CREATE TABLE epg_clips (
  ID smallint(5) unsigned NOT NULL,
  Caption tinytext,
  DurForc time(3) DEFAULT NULL,
  CtgID tinyint(3) unsigned DEFAULT NULL,
  Placing tinyint(3) unsigned DEFAULT NULL,
  TermAdd datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY `chain` (CtgID,Placing)
);


--
-- Table structure for table `epg_cn_blocks`
--

DROP TABLE IF EXISTS epg_cn_blocks;


CREATE TABLE epg_cn_blocks (
  ID mediumint(8) unsigned NOT NULL,
  BlockID smallint(5) unsigned DEFAULT NULL,
  NativeType tinyint(3) unsigned DEFAULT NULL,
  NativeID smallint(5) unsigned DEFAULT NULL,
  Queue tinyint(3) unsigned DEFAULT NULL,
  IsActive tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY blockid (BlockID),
  KEY queue (Queue),
  KEY nativetype (NativeType),
  KEY nativeid (NativeID),
  KEY isactive (IsActive)
);


--
-- Table structure for table `epg_cn_ties`
--

DROP TABLE IF EXISTS epg_cn_ties;


CREATE TABLE epg_cn_ties (
  ID mediumint(9) unsigned NOT NULL,
  PremiereID mediumint(9) unsigned DEFAULT NULL,
  RerunID mediumint(9) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY premiereid (PremiereID),
  KEY rerunid (RerunID)
);


--
-- Table structure for table `epg_coverz`
--

DROP TABLE IF EXISTS epg_coverz;


CREATE TABLE epg_coverz (
  ID mediumint(8) unsigned NOT NULL,
  OwnerType tinyint(3) unsigned DEFAULT NULL,
  OwnerID mediumint(8) unsigned DEFAULT NULL,
  TypeX tinyint(3) unsigned DEFAULT NULL,
  Texter text,
  TCin time DEFAULT NULL,
  TCout time DEFAULT NULL,
  IsReady tinyint(3) unsigned DEFAULT NULL,
  ProoferUID smallint(5) unsigned DEFAULT NULL,
  PageLabel varchar(30) DEFAULT NULL,
  TermAdd datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY `chain` (OwnerType,OwnerID),
  FULLTEXT KEY idx_cvrz_Texter (Texter)
);


--
-- Table structure for table `epg_elements`
--

DROP TABLE IF EXISTS epg_elements;


CREATE TABLE epg_elements (
  ID mediumint(9) unsigned NOT NULL,
  EpgID smallint(5) unsigned DEFAULT NULL,
  NativeType tinyint(3) unsigned DEFAULT NULL,
  NativeID mediumint(9) unsigned DEFAULT NULL,
  Queue tinyint(3) unsigned DEFAULT NULL,
  TimeAir datetime DEFAULT NULL,
  DurForc time DEFAULT NULL,
  IsActive tinyint(3) unsigned DEFAULT NULL,
  OnHold tinyint(3) unsigned DEFAULT NULL,
  WebLIVE tinyint(3) unsigned DEFAULT NULL,
  WebVOD tinyint(3) unsigned DEFAULT NULL,
  TermEmit datetime DEFAULT NULL,
  AttrA tinyint(4) unsigned DEFAULT NULL,
  AttrB smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY epgid (EpgID),
  KEY queue (Queue),
  KEY nativetype (NativeType),
  KEY attra (AttrA),
  KEY termemit (TermEmit),
  KEY nativeid (NativeID),
  KEY isactive (IsActive)
);


--
-- Table structure for table `epg_films`
--

DROP TABLE IF EXISTS epg_films;


CREATE TABLE epg_films (
  ID mediumint(9) unsigned NOT NULL,
  FilmID mediumint(8) unsigned DEFAULT NULL,
  FilmParentID mediumint(8) unsigned DEFAULT NULL,
  ScnrID mediumint(8) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY scnrid (ScnrID),
  KEY filmid (FilmID),
  KEY filmparentid (FilmParentID)
);


--
-- Table structure for table `epg_market`
--

DROP TABLE IF EXISTS epg_market;


CREATE TABLE epg_market (
  ID smallint(5) unsigned NOT NULL,
  Caption tinytext,
  DurForc time(3) DEFAULT NULL,
  AgencyID smallint(5) unsigned DEFAULT NULL,
  DateStart date DEFAULT NULL,
  DateExpire date DEFAULT NULL,
  TermAdd datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  VideoID smallint(5) unsigned DEFAULT NULL,
  IsBumper tinyint(3) unsigned DEFAULT NULL,
  IsGratis tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY agencyid (AgencyID),
  KEY videoid (VideoID),
  KEY channelid (ChannelID)
);


--
-- Table structure for table `epg_market_agencies`
--

DROP TABLE IF EXISTS epg_market_agencies;


CREATE TABLE epg_market_agencies (
  ID smallint(6) unsigned NOT NULL,
  Caption tinytext,
  TermAdd datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID)
);


--
-- Table structure for table `epg_market_plan`
--

DROP TABLE IF EXISTS epg_market_plan;


CREATE TABLE epg_market_plan (
  ID mediumint(8) unsigned NOT NULL,
  ItemID smallint(5) unsigned DEFAULT NULL,
  DateEPG date DEFAULT NULL,
  BlockTermEPG time DEFAULT NULL,
  BlockPos tinyint(3) unsigned DEFAULT NULL,
  BlockProgID smallint(6) unsigned DEFAULT NULL,
  BLC_Wrapclips tinyint(3) unsigned DEFAULT NULL,
  BLC_Label varchar(45) DEFAULT NULL,
  Position tinyint(3) unsigned DEFAULT NULL,
  Queue tinyint(3) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY itemid (ItemID),
  KEY dateepg (DateEPG),
  KEY queue (Queue),
  KEY position (Position),
  KEY blocktermepg (BlockTermEPG),
  KEY blockpos (BlockPos),
  KEY blockprogid (BlockProgID),
  KEY channelid (ChannelID)
);


--
-- Table structure for table `epg_market_siblings`
--

DROP TABLE IF EXISTS epg_market_siblings;


CREATE TABLE epg_market_siblings (
  ID mediumint(8) unsigned NOT NULL,
  MktplanCode varchar(17) CHARACTER SET utf8 DEFAULT NULL,
  MktepgID mediumint(8) unsigned DEFAULT NULL,
  MktepgType tinyint(2) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY mktplancode (MktplanCode),
  KEY mktepgid (MktepgID)
);


--
-- Table structure for table `epg_notes`
--

DROP TABLE IF EXISTS epg_notes;


CREATE TABLE epg_notes (
  ID mediumint(8) unsigned NOT NULL,
  NoteType tinyint(3) unsigned DEFAULT NULL,
  Note tinytext,
  PRIMARY KEY (ID)
);


--
-- Table structure for table `epg_promo`
--

DROP TABLE IF EXISTS epg_promo;


CREATE TABLE epg_promo (
  ID smallint(5) unsigned NOT NULL,
  Caption tinytext,
  DurForc time(3) DEFAULT NULL,
  CtgID tinyint(3) unsigned DEFAULT NULL,
  DateStart date DEFAULT NULL,
  DateExpire date DEFAULT NULL,
  TermAdd datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY ctgid (CtgID),
  KEY date_expire (DateExpire),
  KEY channelid (ChannelID)
);


--
-- Table structure for table `epg_scnr`
--

DROP TABLE IF EXISTS epg_scnr;


CREATE TABLE epg_scnr (
  ID mediumint(8) unsigned NOT NULL,
  ProgID smallint(5) unsigned DEFAULT NULL,
  Caption tinytext,
  MatType tinyint(3) unsigned DEFAULT NULL,
  IsReady tinyint(3) unsigned DEFAULT NULL,
  IsFilm tinyint(3) unsigned DEFAULT NULL,
  DurEmit time DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY progid (ProgID),
  KEY mattype (MatType)
);


--
-- Table structure for table `epg_scnr_fragments`
--

DROP TABLE IF EXISTS epg_scnr_fragments;


CREATE TABLE epg_scnr_fragments (
  ID mediumint(8) unsigned NOT NULL,
  ScnrID mediumint(8) unsigned DEFAULT NULL,
  NativeType tinyint(3) unsigned DEFAULT NULL,
  NativeID mediumint(9) unsigned DEFAULT NULL,
  Queue tinyint(3) unsigned DEFAULT NULL,
  TimeAir datetime DEFAULT NULL,
  DurForc time DEFAULT NULL,
  IsActive tinyint(3) unsigned DEFAULT NULL,
  TermEmit time DEFAULT NULL,
  AttrA tinyint(4) unsigned DEFAULT NULL,
  AttrB smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY scnrid (ScnrID),
  KEY queue (Queue),
  KEY nativetype (NativeType),
  KEY attra (AttrA),
  KEY termemit (TermEmit),
  KEY nativeid (NativeID),
  KEY isactive (IsActive)
);


--
-- Table structure for table `epg_templates`
--

DROP TABLE IF EXISTS epg_templates;


CREATE TABLE epg_templates (
  ID mediumint(9) unsigned NOT NULL,
  Caption text,
  PRIMARY KEY (ID)
);


--
-- Table structure for table `epg_tips`
--

DROP TABLE IF EXISTS epg_tips;


CREATE TABLE epg_tips (
  ID int(11) unsigned NOT NULL,
  SchType tinyint(3) unsigned DEFAULT NULL,
  SchLineID mediumint(8) unsigned DEFAULT NULL,
  TipType tinyint(3) unsigned DEFAULT NULL,
  Tip tinytext,
  PRIMARY KEY (ID),
  KEY schtype (SchType),
  KEY schlineid (SchLineID),
  KEY tiptype (TipType)
);


--
-- Table structure for table `epgz`
--

DROP TABLE IF EXISTS epgz;


CREATE TABLE epgz (
  ID smallint(5) unsigned NOT NULL,
  IsTMPL tinyint(3) unsigned DEFAULT NULL,
  DateAir date DEFAULT NULL,
  IsReady tinyint(3) unsigned DEFAULT NULL,
  TermAdd datetime DEFAULT NULL,
  TermMod datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY channelid (ChannelID),
  KEY dateair (DateAir),
  KEY termmod (TermMod),
  KEY `chain` (IsTMPL,ChannelID,DateAir),
  KEY istmpl (IsTMPL)
);


--
-- Table structure for table `film`
--

DROP TABLE IF EXISTS film;


CREATE TABLE film (
  ID mediumint(8) unsigned NOT NULL,
  TypeID tinyint(3) unsigned DEFAULT NULL,
  SectionID tinyint(3) unsigned DEFAULT NULL,
  LicenceStart date DEFAULT NULL,
  LicenceExpire date DEFAULT NULL,
  DurApprox time DEFAULT NULL,
  DurReal time DEFAULT NULL,
  DurDesc varchar(8) DEFAULT NULL,
  IsDelivered tinyint(3) unsigned DEFAULT NULL,
  ProdType tinyint(3) unsigned DEFAULT NULL,
  EpisodeCount smallint(5) unsigned DEFAULT NULL,
  TermEPG datetime DEFAULT NULL,
  TermAdd datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY typeid (TypeID),
  KEY sectionid (SectionID),
  KEY lic_expire (LicenceExpire)
);


--
-- Table structure for table `film_agencies`
--

DROP TABLE IF EXISTS film_agencies;


CREATE TABLE film_agencies (
  ID smallint(6) unsigned NOT NULL,
  Caption tinytext,
  TermAdd datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY caption (Caption(3))
);


--
-- Table structure for table `film_cn_bcasts`
--

DROP TABLE IF EXISTS film_cn_bcasts;


CREATE TABLE film_cn_bcasts (
  ID int(11) unsigned NOT NULL,
  FilmID mediumint(8) unsigned DEFAULT NULL,
  BCmax tinyint(3) unsigned DEFAULT NULL,
  BCcur tinyint(3) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY filmid (FilmID),
  KEY channelid (ChannelID)
);


--
-- Table structure for table `film_cn_channel`
--

DROP TABLE IF EXISTS film_cn_channel;


CREATE TABLE film_cn_channel (
  ID int(11) unsigned NOT NULL,
  FilmID mediumint(8) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY filmid (FilmID)
);


--
-- Table structure for table `film_cn_contracts`
--

DROP TABLE IF EXISTS film_cn_contracts;


CREATE TABLE film_cn_contracts (
  ID int(11) unsigned NOT NULL,
  FilmID mediumint(8) unsigned DEFAULT NULL,
  ContractID smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY filmid (FilmID),
  KEY contractid (ContractID)
);


--
-- Table structure for table `film_cn_genre`
--

DROP TABLE IF EXISTS film_cn_genre;


CREATE TABLE film_cn_genre (
  ID int(11) unsigned NOT NULL,
  FilmID mediumint(8) unsigned DEFAULT NULL,
  GenreID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY filmid (FilmID)
);


--
-- Table structure for table `film_contracts`
--

DROP TABLE IF EXISTS film_contracts;


CREATE TABLE film_contracts (
  ID smallint(6) unsigned NOT NULL,
  CodeLabel varchar(10) DEFAULT NULL,
  AgencyID smallint(5) unsigned DEFAULT NULL,
  DateContract date DEFAULT NULL,
  LicenceType varchar(7) DEFAULT NULL,
  PriceSum mediumint(8) unsigned DEFAULT NULL,
  PriceCurrencyID tinyint(3) unsigned DEFAULT NULL,
  LicenceStart date DEFAULT NULL,
  LicenceExpire date DEFAULT NULL,
  TermAdd datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY agencyid (AgencyID),
  FULLTEXT KEY idx_film_contract (CodeLabel)
);


--
-- Table structure for table `film_description`
--

DROP TABLE IF EXISTS film_description;


CREATE TABLE film_description (
  ID mediumint(8) unsigned NOT NULL,
  LanguageID tinyint(3) unsigned DEFAULT NULL,
  Title varchar(255) DEFAULT NULL,
  OriginalTitle varchar(200) DEFAULT NULL,
  Country varchar(50) DEFAULT NULL,
  `Year` varchar(4) DEFAULT NULL,
  DscTitle tinytext,
  DscShort tinytext,
  DscLong text,
  Director varchar(50) DEFAULT NULL,
  Writer varchar(50) DEFAULT NULL,
  Actors varchar(255) DEFAULT NULL,
  Parental tinyint(3) unsigned DEFAULT NULL,
  Seasons_arr varchar(40) DEFAULT NULL,
  PRIMARY KEY (ID),
  FULLTEXT KEY idx_film_dsc (Title,OriginalTitle,Director,Actors)
);


--
-- Table structure for table `film_episodes`
--

DROP TABLE IF EXISTS film_episodes;


CREATE TABLE film_episodes (
  ID mediumint(8) unsigned NOT NULL,
  ParentID mediumint(8) unsigned DEFAULT NULL,
  Title varchar(255) DEFAULT NULL,
  Ordinal smallint(5) unsigned DEFAULT NULL,
  DurApprox time DEFAULT NULL,
  DurReal time DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY parentid (ParentID),
  KEY ordinal (Ordinal)
);


--
-- Table structure for table `hrm_groups`
--

DROP TABLE IF EXISTS hrm_groups;


CREATE TABLE hrm_groups (
  ID smallint(5) unsigned NOT NULL,
  Title tinytext COLLATE utf8_unicode_ci,
  ParentID smallint(5) unsigned DEFAULT NULL,
  ChiefID smallint(5) unsigned DEFAULT NULL,
  Queue tinyint(4) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY parentid (ParentID),
  KEY queue (Queue),
  KEY chiefid (ChiefID)
);


--
-- Table structure for table `hrm_users`
--

DROP TABLE IF EXISTS hrm_users;


CREATE TABLE hrm_users (
  ID smallint(6) unsigned NOT NULL,
  Name1st varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  Name2nd varchar(25) COLLATE utf8_unicode_ci DEFAULT NULL,
  ADuser varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  ADpass varchar(15) CHARACTER SET utf8 DEFAULT NULL,
  GroupID smallint(5) unsigned DEFAULT NULL,
  IsActive tinyint(1) unsigned DEFAULT NULL,
  IsHidden tinyint(1) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  UNIQUE KEY aduser (ADuser),
  KEY groupid (GroupID),
  KEY isactive (IsActive),
  KEY ishidden (IsHidden),
  KEY name1st (Name1st),
  KEY name2nd (Name2nd),
  FULLTEXT KEY idx_users_fullname (Name1st,Name2nd)
);


--
-- Table structure for table `hrm_users_data`
--

DROP TABLE IF EXISTS hrm_users_data;


CREATE TABLE hrm_users_data (
  ID smallint(6) unsigned NOT NULL,
  Title tinytext,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  LanguageID tinyint(3) unsigned DEFAULT NULL,
  ContractType tinyint(3) unsigned DEFAULT NULL,
  FatherName varchar(15) DEFAULT NULL,
  Gender tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID)
);


--
-- Table structure for table `log_in_out`
--

DROP TABLE IF EXISTS log_in_out;


CREATE TABLE log_in_out (
  ID int(10) unsigned NOT NULL,
  UserID smallint(6) unsigned DEFAULT NULL,
  ActionID tinyint(3) unsigned DEFAULT NULL,
  `Time` datetime DEFAULT NULL,
  IP tinytext,
  PRIMARY KEY (ID),
  KEY `chain` (UserID,ActionID)
);


--
-- Table structure for table `log_mdf`
--

DROP TABLE IF EXISTS log_mdf;


CREATE TABLE log_mdf (
  ID mediumint(9) unsigned NOT NULL,
  ItemType tinyint(3) unsigned DEFAULT NULL,
  ItemID mediumint(9) unsigned DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  TermAccess datetime DEFAULT NULL,
  PRIMARY KEY (ID)
);


--
-- Table structure for table `log_qry`
--

DROP TABLE IF EXISTS log_qry;


CREATE TABLE log_qry (
  ID int(10) unsigned NOT NULL,
  `XID` int(10) unsigned DEFAULT NULL,
  `Action` varchar(5) DEFAULT NULL,
  TableName varchar(25) DEFAULT NULL,
  Section varchar(5) DEFAULT NULL,
  Script varchar(45) DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  TermAdd datetime DEFAULT NULL,
  TableID tinyint(3) unsigned DEFAULT NULL,
  ActionID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY `chain` (`XID`,TableID)
);


--
-- Table structure for table `log_sql`
--

DROP TABLE IF EXISTS log_sql;


CREATE TABLE log_sql (
  ID int(10) unsigned NOT NULL,
  qrySQL text,
  PRIMARY KEY (ID)
);


--
-- Table structure for table `prgm`
--

DROP TABLE IF EXISTS prgm;


CREATE TABLE prgm (
  ID smallint(6) unsigned NOT NULL,
  Caption varchar(200) COLLATE utf8_unicode_ci DEFAULT NULL,
  TeamID tinyint(4) unsigned DEFAULT NULL,
  IsActive tinyint(3) unsigned DEFAULT NULL,
  ProdType tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY teamid (TeamID),
  KEY isactive (IsActive),
  FULLTEXT KEY idx_prgm_Caption (Caption)
);


--
-- Table structure for table `prgm_settings`
--

DROP TABLE IF EXISTS prgm_settings;


CREATE TABLE prgm_settings (
  ID smallint(6) unsigned NOT NULL,
  WebLIVE tinyint(3) unsigned DEFAULT NULL,
  WebVOD tinyint(3) unsigned DEFAULT NULL,
  WebHide tinyint(3) unsigned DEFAULT NULL,
  EPG_Rerun tinyint(3) unsigned DEFAULT NULL,
  EPG_TemplateID mediumint(9) unsigned DEFAULT NULL,
  EPG_Skip_Dflt_Tmpl_Auto_Import tinyint(3) unsigned DEFAULT NULL,
  DurDesc varchar(8) DEFAULT NULL,
  TermDesc tinytext,
  DscTitle tinytext,
  Note tinytext,
  SecurityStrict tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID)
);


--
-- Table structure for table `prgm_teams`
--

DROP TABLE IF EXISTS prgm_teams;


CREATE TABLE prgm_teams (
  ID tinyint(4) unsigned NOT NULL,
  Caption tinytext,
  GroupID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  Queue tinyint(3) unsigned DEFAULT NULL,
  PMS_loose tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY channelid (ChannelID),
  KEY groupid (GroupID),
  KEY queue (Queue)
);


--
-- Table structure for table `settingz`
--

DROP TABLE IF EXISTS settingz;


CREATE TABLE settingz (
  ID mediumint(9) unsigned NOT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  SettingName varchar(30) DEFAULT NULL,
  SettingValue smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY `chain` (SettingName,UID)
);


--
-- Table structure for table `settingz_lst`
--

DROP TABLE IF EXISTS settingz_lst;


CREATE TABLE settingz_lst (
  ID mediumint(9) unsigned NOT NULL,
  TableID tinyint(3) unsigned DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  SettingName varchar(15) DEFAULT NULL,
  SettingValue tinyint(4) DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY uid (UID),
  KEY settingname (SettingName),
  KEY tableid (TableID),
  KEY channelid (ChannelID)
);


--
-- Table structure for table `stry_atoms`
--

DROP TABLE IF EXISTS stry_atoms;


CREATE TABLE stry_atoms (
  ID mediumint(9) unsigned NOT NULL,
  StoryID mediumint(9) unsigned DEFAULT NULL,
  Queue tinyint(3) unsigned DEFAULT NULL,
  TypeX tinyint(3) unsigned DEFAULT NULL,
  Duration time DEFAULT NULL,
  TechType tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY storyid (StoryID),
  KEY queue (Queue),
  KEY typex (TypeX)
);


--
-- Table structure for table `stry_atoms_speaker`
--

DROP TABLE IF EXISTS stry_atoms_speaker;


CREATE TABLE stry_atoms_speaker (
  ID mediumint(8) unsigned NOT NULL,
  SpeakerX tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID)
);


--
-- Table structure for table `stry_atoms_text`
--

DROP TABLE IF EXISTS stry_atoms_text;


CREATE TABLE stry_atoms_text (
  ID mediumint(9) unsigned NOT NULL,
  Texter text,
  PRIMARY KEY (ID),
  FULLTEXT KEY idx_stry_Texter (Texter)
);


--
-- Table structure for table `stry_copies`
--

DROP TABLE IF EXISTS stry_copies;


CREATE TABLE stry_copies (
  ID mediumint(9) unsigned NOT NULL,
  OriginalID mediumint(9) unsigned DEFAULT NULL,
  CopyID mediumint(9) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY originalid (OriginalID),
  KEY copyid (CopyID)
);


--
-- Table structure for table `stry_followz`
--

DROP TABLE IF EXISTS stry_followz;


CREATE TABLE stry_followz (
  ID mediumint(8) unsigned NOT NULL,
  ItemID mediumint(8) unsigned DEFAULT NULL,
  ItemType tinyint(3) unsigned DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  MarkTerm datetime DEFAULT NULL,
  MarkType tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY itemtype (ItemType),
  KEY itemid (ItemID),
  KEY uid (UID)
);


--
-- Table structure for table `stry_readspeed`
--

DROP TABLE IF EXISTS stry_readspeed;


CREATE TABLE stry_readspeed (
  ID int(10) unsigned NOT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  `Name` varchar(20) DEFAULT NULL,
  Velocity smallint(5) unsigned DEFAULT NULL,
  IsDefault tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY uid (UID),
  KEY isdefault (IsDefault)
);


--
-- Table structure for table `stry_trash`
--

DROP TABLE IF EXISTS stry_trash;


CREATE TABLE stry_trash (
  ID mediumint(8) unsigned NOT NULL,
  ItemID mediumint(8) unsigned DEFAULT NULL,
  ItemType tinyint(3) unsigned DEFAULT NULL,
  DelUID smallint(5) unsigned DEFAULT NULL,
  DelTerm datetime DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY `chain` (ItemID,ItemType)
);


--
-- Table structure for table `stry_versions`
--

DROP TABLE IF EXISTS stry_versions;


CREATE TABLE stry_versions (
  ID mediumint(9) unsigned NOT NULL,
  StoryID mediumint(9) unsigned DEFAULT NULL,
  Texter text,
  UID smallint(5) unsigned DEFAULT NULL,
  TermMod datetime DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY storyid (StoryID),
  KEY termmod (TermMod)
);


--
-- Table structure for table `stryz`
--

DROP TABLE IF EXISTS stryz;


CREATE TABLE stryz (
  ID mediumint(9) unsigned NOT NULL,
  Caption varchar(255) DEFAULT NULL,
  DurForc time DEFAULT NULL,
  DurEmit time DEFAULT NULL,
  `Phase` tinyint(3) unsigned DEFAULT NULL,
  IsDeleted tinyint(3) unsigned DEFAULT NULL,
  TermAdd datetime DEFAULT NULL,
  UID smallint(5) unsigned DEFAULT NULL,
  ChannelID tinyint(3) unsigned DEFAULT NULL,
  ScnrID mediumint(8) unsigned DEFAULT NULL,
  ProgID smallint(6) unsigned DEFAULT NULL,
  PRIMARY KEY (ID),
  KEY uid (UID),
  KEY channelid (ChannelID),
  KEY scnrid (ScnrID),
  KEY progid (ProgID),
  FULLTEXT KEY idx_stryz_Caption (Caption)
);

