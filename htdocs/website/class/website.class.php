<?php
/* Copyright (C) 2007-2018  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/website/class/website.class.php
 * \ingroup website
 * \brief   File for the CRUD class of website (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';


/**
 * Class Website
 */
class Website extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'website';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'website';

	/**
	 * @var int  	Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;


	protected $childtablesoncascade = array();


	/**
	 * @var string String with name of icon for website. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'globe';

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var string Main language of web site
	 */
	public $lang;

	/**
	 * @var string List of languages of web site ('fr', 'es_MX', ...)
	 */
	public $otherlang;

	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var integer date_creation
	 */
	public $date_creation;

	/**
	 * @var integer	date_modification
	 */
	public $date_modification;
	/**
	 * @var integer date_modification
	 */
	public $tms;

	/**
	 * @var integer Default home page
	 */
	public $fk_default_home;

	/**
	 * @var int User Create Id
	 */
	public $fk_user_creat;

	/**
	 * @var int User Modification Id
	 */
	public $fk_user_modif;

	/**
	 * @var string Virtual host
	 */
	public $virtualhost;

	/**
	 * @var int Use a manifest file
	 */
	public $use_manifest;

	/**
	 * @var int	Position
	 */
	public $position;

	/**
	 * @var array List of containers
	 */
	public $lines;

	/**
	 * @var string name of template
	 */
	public $name_template;

	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      	User that creates
	 * @param  int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int 				Return integer <0 if KO, 0 if already exists, ID of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf, $langs;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;
		$now = dol_now();

		// Clean parameters
		if (isset($this->entity)) {
			$this->entity = (int) $this->entity;
		}
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->description)) {
			$this->description = trim($this->description);
		}
		if (isset($this->status)) {
			$this->status = (int) $this->status;
		}
		if (empty($this->date_creation)) {
			$this->date_creation = $now;
		}
		if (empty($this->date_modification)) {
			$this->date_modification = $now;
		}
		// Remove spaces and be sure we have main language only
		$this->lang = preg_replace('/[_-].*$/', '', trim($this->lang)); // en_US or en-US -> en
		$tmparray = explode(',', $this->otherlang);
		if (is_array($tmparray)) {
			foreach ($tmparray as $key => $val) {
				// It possible we have empty val here if postparam WEBSITE_OTHERLANG is empty or set like this : 'en,,sv' or 'en,sv,'
				if (empty(trim($val))) {
					unset($tmparray[$key]);
					continue;
				}
				$tmparray[$key] = preg_replace('/[_-].*$/', '', trim($val)); // en_US or en-US -> en
			}
			$this->otherlang = join(',', $tmparray);
		}

		// Check parameters
		if (empty($this->entity)) {
			$this->entity = $conf->entity;
		}
		if (empty($this->lang)) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MainLanguage"));
			return -1;
		}

		// Insert request
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'(';
		$sql .= 'entity,';
		$sql .= 'ref,';
		$sql .= 'description,';
		$sql .= 'lang,';
		$sql .= 'otherlang,';
		$sql .= 'status,';
		$sql .= 'fk_default_home,';
		$sql .= 'virtualhost,';
		$sql .= 'fk_user_creat,';
		$sql .= 'date_creation,';
		$sql .= 'position,';
		$sql .= 'tms';
		$sql .= ') VALUES (';
		$sql .= ' '.((empty($this->entity) && $this->entity != '0') ? 'NULL' : $this->entity).',';
		$sql .= ' '.(!isset($this->ref) ? 'NULL' : "'".$this->db->escape($this->ref)."'").',';
		$sql .= ' '.(!isset($this->description) ? 'NULL' : "'".$this->db->escape($this->description)."'").',';
		$sql .= ' '.(!isset($this->lang) ? 'NULL' : "'".$this->db->escape($this->lang)."'").',';
		$sql .= ' '.(!isset($this->otherlang) ? 'NULL' : "'".$this->db->escape($this->otherlang)."'").',';
		$sql .= ' '.(!isset($this->status) ? '1' : $this->status).',';
		$sql .= ' '.(!isset($this->fk_default_home) ? 'NULL' : $this->fk_default_home).',';
		$sql .= ' '.(!isset($this->virtualhost) ? 'NULL' : "'".$this->db->escape($this->virtualhost)."'").",";
		$sql .= ' '.(!isset($this->fk_user_creat) ? $user->id : $this->fk_user_creat).',';
		$sql .= ' '.(!isset($this->date_creation) || dol_strlen($this->date_creation) == 0 ? 'NULL' : "'".$this->db->idate($this->date_creation)."'").",";
		$sql .= ' '.((int) $this->position).",";
		$sql .= ' '.(!isset($this->date_modification) || dol_strlen($this->date_modification) == 0 ? 'NULL' : "'".$this->db->idate($this->date_modification)."'");
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			// Create a subdirectory for each language (except main language)
			$tmplangarray = explode(',', $this->otherlang);
			if (is_array($tmplangarray)) {
				dol_mkdir($conf->website->dir_output.'/'.$this->ref);
				foreach ($tmplangarray as $val) {
					if (trim($val) == $this->lang) {
						continue;
					}
					dol_mkdir($conf->website->dir_output.'/'.$this->ref.'/'.trim($val), DOL_DATA_ROOT);
				}
			}

			// Create subdirectory for images and js
			dol_mkdir($conf->medias->multidir_output[$conf->entity].'/image/'.$this->ref, DOL_DATA_ROOT);
			dol_mkdir($conf->medias->multidir_output[$conf->entity].'/js/'.$this->ref, DOL_DATA_ROOT);

			// Uncomment this and change WEBSITE to your own tag if you
			// want this action to call a trigger.
			// if (!$notrigger) {

			//     // Call triggers
			//     $result = $this->call_trigger('WEBSITE_CREATE',$user);
			//     if ($result < 0) $error++;
			//     // End call triggers
			// }
		}

		if (!$error) {
			$stringtodolibarrfile = "# Some properties for Dolibarr web site CMS\n";
			$stringtodolibarrfile .= "param=value\n";
			//print $conf->website->dir_output.'/'.$this->ref.'/.dolibarr';exit;
			file_put_contents($conf->website->dir_output.'/'.$this->ref.'/.dolibarr', $stringtodolibarrfile);
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				return 0;
			} else {
				return -1 * $error;
			}
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param 	int    $id  	Id object
	 * @param 	string $ref 	Ref
	 * @return 	int 			Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.ref,";
		$sql .= " t.position,";
		$sql .= " t.description,";
		$sql .= " t.lang,";
		$sql .= " t.otherlang,";
		$sql .= " t.status,";
		$sql .= " t.fk_default_home,";
		$sql .= " t.use_manifest,";
		$sql .= " t.virtualhost,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.date_creation,";
		$sql .= " t.tms as date_modification,";
		$sql .= " t.name_template";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.entity IN (".getEntity('website').")";
		if (!empty($ref)) {
			$sql .= " AND t.ref = '".$this->db->escape($ref)."'";
		} else {
			$sql .= " AND t.rowid = ".(int) $id;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->entity = $obj->entity;
				$this->ref = $obj->ref;
				$this->position = $obj->position;
				$this->description = $obj->description;
				$this->lang = $obj->lang;
				$this->otherlang = $obj->otherlang;
				$this->status = $obj->status;
				$this->fk_default_home = $obj->fk_default_home;
				$this->virtualhost = $obj->virtualhost;
				$this->use_manifest = $obj->use_manifest;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->name_template = $obj->name_template;
			}
			$this->db->free($resql);

			if ($numrows > 0) {
				// Lines
				$this->fetchLines();
			}

			if ($numrows > 0) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		// Load lines with object MyObjectLine

		return count($this->lines) ? 1 : 0;
	}


	/**
	 * Load all object in memory ($this->records) from the database
	 *
	 * @param 	string 		$sortorder 		Sort Order
	 * @param 	string 		$sortfield 		Sort field
	 * @param 	int    		$limit     		offset limit
	 * @param 	int    		$offset    		offset limit
	 * @param 	array  		$filter    		filter array
	 * @param 	string 		$filtermode 	filter mode (AND or OR)
	 * @return 	array|int                 	int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.ref,";
		$sql .= " t.description,";
		$sql .= " t.lang,";
		$sql .= " t.otherlang,";
		$sql .= " t.status,";
		$sql .= " t.fk_default_home,";
		$sql .= " t.virtualhost,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.date_creation,";
		$sql .= " t.tms as date_modification";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.entity IN (".getEntity('website').")";
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				$sqlwhere[] = $key." LIKE '%".$this->db->escape($value)."%'";
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND '.implode(' '.$this->db->escape($filtermode).' ', $sqlwhere);
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$record = new self($this->db);

				$record->id = $obj->rowid;

				$record->entity = $obj->entity;
				$record->ref = $obj->ref;
				$record->description = $obj->description;
				$record->lang = $obj->lang;
				$record->otherlang = $obj->otherlang;
				$record->status = $obj->status;
				$record->fk_default_home = $obj->fk_default_home;
				$record->virtualhost = $obj->virtualhost;
				$record->fk_user_creat = $obj->fk_user_creat;
				$record->fk_user_modif = $obj->fk_user_modif;
				$record->date_creation = $this->db->jdate($obj->date_creation);
				$record->date_modification = $this->db->jdate($obj->date_modification);

				$records[$record->id] = $record;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      	User that modifies
	 * @param  int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int 				Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		// Clean parameters

		if (isset($this->entity)) {
			$this->entity = (int) $this->entity;
		}
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->description)) {
			$this->description = trim($this->description);
		}
		if (isset($this->status)) {
			$this->status = (int) $this->status;
		}

		// Remove spaces and be sure we have main language only
		$this->lang = preg_replace('/[_-].*$/', '', trim($this->lang)); // en_US or en-US -> en
		$tmparray = explode(',', $this->otherlang);
		if (is_array($tmparray)) {
			foreach ($tmparray as $key => $val) {
				// It possible we have empty val here if postparam WEBSITE_OTHERLANG is empty or set like this : 'en,,sv' or 'en,sv,'
				if (empty(trim($val))) {
					unset($tmparray[$key]);
					continue;
				}
				$tmparray[$key] = preg_replace('/[_-].*$/', '', trim($val)); // en_US or en-US -> en
			}
			$this->otherlang = join(',', $tmparray);
		}
		if (empty($this->lang)) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MainLanguage"));
			return -1;
		}

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET';
		$sql .= ' entity = '.(isset($this->entity) ? $this->entity : "null").',';
		$sql .= ' ref = '.(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").',';
		$sql .= ' description = '.(isset($this->description) ? "'".$this->db->escape($this->description)."'" : "null").',';
		$sql .= ' lang = '.(isset($this->lang) ? "'".$this->db->escape($this->lang)."'" : "null").',';
		$sql .= ' otherlang = '.(isset($this->otherlang) ? "'".$this->db->escape($this->otherlang)."'" : "null").',';
		$sql .= ' status = '.(isset($this->status) ? $this->status : "null").',';
		$sql .= ' fk_default_home = '.(($this->fk_default_home > 0) ? $this->fk_default_home : "null").',';
		$sql .= ' use_manifest = '.((int) $this->use_manifest).',';
		$sql .= ' virtualhost = '.(($this->virtualhost != '') ? "'".$this->db->escape($this->virtualhost)."'" : "null").',';
		$sql .= ' fk_user_modif = '.(!isset($this->fk_user_modif) ? $user->id : $this->fk_user_modif).',';
		$sql .= ' date_creation = '.(!isset($this->date_creation) || dol_strlen($this->date_creation) != 0 ? "'".$this->db->idate($this->date_creation)."'" : 'null').',';
		$sql .= ' tms = '.(dol_strlen($this->date_modification) != 0 ? "'".$this->db->idate($this->date_modification)."'" : "'".$this->db->idate(dol_now())."'");
		$sql .= ' WHERE rowid='.((int) $this->id);

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
		}

		if (!$error && !$notrigger) {
			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action calls a trigger.

			// Create subdirectory per language
			$tmplangarray = explode(',', $this->otherlang);
			if (is_array($tmplangarray)) {
				dol_mkdir($conf->website->dir_output.'/'.$this->ref);
				foreach ($tmplangarray as $val) {
					if (trim($val) == $this->lang) {
						continue;
					}
					dol_mkdir($conf->website->dir_output.'/'.$this->ref.'/'.trim($val));
				}
			}

			//// Call triggers
			//$result=$this->call_trigger('WEBSITE_MODIFY',$user);
			//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
			//// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User 	$user      	User that deletes
	 * @param int 	$notrigger 	0=launch triggers, 1=disable triggers
	 * @return int 				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'website_page';
			$sql .= ' WHERE fk_website = '.((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
			}
		}

		// Delete common code. This include execution of trigger.
		$result = $this->deleteCommon($user, $notrigger);
		if ($result <= 0) {
			$error++;
		}

		if (!$error && !empty($this->ref)) {
			$pathofwebsite = DOL_DATA_ROOT.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.$this->ref;

			dol_delete_dir_recursive($pathofwebsite);
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Load a website its id and create a new one in database.
	 * This copy website directories, regenerate all the pages + alias pages and recreate the medias link.
	 *
	 * @param	User	$user		User making the clone
	 * @param 	int 	$fromid 	Id of object to clone
	 * @param	string	$newref		New ref
	 * @param	string	$newlang	New language
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone($user, $fromid, $newref, $newlang = '')
	{
		global $conf, $langs;
		global $dolibarr_main_data_root;

		$now = dol_now();
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$newref = dol_sanitizeFileName($newref);

		if (empty($newref)) {
			$this->error = 'ErrorBadParameter';
			return -1;
		}

		$object = new self($this->db);

		// Check no site with ref exists
		if ($object->fetch(0, $newref) > 0) {
			$this->error = 'ErrorNewRefIsAlreadyUsed';
			return -1;
		}

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);

		$oldidforhome = $object->fk_default_home;
		$oldref = $object->ref;

		$pathofwebsiteold = $dolibarr_main_data_root.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.dol_sanitizeFileName($oldref);
		$pathofwebsitenew = $dolibarr_main_data_root.($conf->entity > 1 ? '/'.$conf->entity : '').'/website/'.dol_sanitizeFileName($newref);
		dol_delete_dir_recursive($pathofwebsitenew);

		$fileindex = $pathofwebsitenew.'/index.php';

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		$object->ref = $newref;
		$object->fk_default_home = 0;
		$object->virtualhost = '';
		$object->date_creation = $now;
		$object->fk_user_creat = $user->id;
		$object->position = ((int) $object->position) + 1;
		$object->status = self::STATUS_DRAFT;
		if (empty($object->lang)) {
			$object->lang = substr($langs->defaultlang, 0, 2); // Should not happen. Protection for corrupted site with no languages
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			dolCopyDir($pathofwebsiteold, $pathofwebsitenew, $conf->global->MAIN_UMASK, 0, null, 2);

			// Check symlink to medias and restore it if ko
			$pathtomedias = DOL_DATA_ROOT.'/medias'; // Target
			$pathtomediasinwebsite = $pathofwebsitenew.'/medias'; // Source / Link name
			if (!is_link(dol_osencode($pathtomediasinwebsite))) {
				dol_syslog("Create symlink for ".$pathtomedias." into name ".$pathtomediasinwebsite);
				dol_mkdir(dirname($pathtomediasinwebsite)); // To be sure dir for website exists
				$result = symlink($pathtomedias, $pathtomediasinwebsite);
			}

			// Copy images and js dir
			$pathofmediasjsold = DOL_DATA_ROOT.'/medias/js/'.$oldref;
			$pathofmediasjsnew = DOL_DATA_ROOT.'/medias/js/'.$newref;
			dolCopyDir($pathofmediasjsold, $pathofmediasjsnew, $conf->global->MAIN_UMASK, 0);

			$pathofmediasimageold = DOL_DATA_ROOT.'/medias/image/'.$oldref;
			$pathofmediasimagenew = DOL_DATA_ROOT.'/medias/image/'.$newref;
			dolCopyDir($pathofmediasimageold, $pathofmediasimagenew, $conf->global->MAIN_UMASK, 0);

			$newidforhome = 0;

			// Duplicate pages
			$objectpages = new WebsitePage($this->db);
			$listofpages = $objectpages->fetchAll($fromid);
			foreach ($listofpages as $pageid => $objectpageold) {
				// Delete old file
				$filetplold = $pathofwebsitenew.'/page'.$pageid.'.tpl.php';
				dol_delete_file($filetplold);

				// Create new file
				$objectpagenew = $objectpageold->createFromClone($user, $pageid, $objectpageold->pageurl, '', 0, $object->id, 1);

				//print $pageid.' = '.$objectpageold->pageurl.' -> '.$objectpagenew->id.' = '.$objectpagenew->pageurl.'<br>';
				if (is_object($objectpagenew) && $objectpagenew->pageurl) {
					$filealias = $pathofwebsitenew.'/'.$objectpagenew->pageurl.'.php';
					$filetplnew = $pathofwebsitenew.'/page'.$objectpagenew->id.'.tpl.php';

					// Save page alias
					$result = dolSavePageAlias($filealias, $object, $objectpagenew);
					if (!$result) {
						setEventMessages('Failed to write file '.$filealias, null, 'errors');
					}

					$result = dolSavePageContent($filetplnew, $object, $objectpagenew);
					if (!$result) {
						setEventMessages('Failed to write file '.$filetplnew, null, 'errors');
					}

					if ($pageid == $oldidforhome) {
						$newidforhome = $objectpagenew->id;
					}
				} else {
					setEventMessages($objectpageold->error, $objectpageold->errors, 'errors');
					$error++;
				}
			}
		}

		if (!$error) {
			// Restore id of home page
			$object->fk_default_home = $newidforhome;
			$res = $object->update($user);
			if (!($res > 0)) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}

			if (!$error) {
				$filetpl = $pathofwebsitenew.'/page'.$newidforhome.'.tpl.php';
				$filewrapper = $pathofwebsitenew.'/wrapper.php';

				// Re-generates the index.php page to be the home page, and re-generates the wrapper.php
				//--------------------------------------------------------------------------------------
				$result = dolSaveIndexPage($pathofwebsitenew, $fileindex, $filetpl, $filewrapper, $object);
			}
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();

			return $object;
		} else {
			$this->db->rollback();

			return -1;
		}
	}

	/**
	 *  Return a link to the user card (with optionally the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to
	 *  @param	integer	$notooltip			1=Disable tooltip
	 *  @param	int		$maxlen				Max length of visible user name
	 *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $maxlen = 24, $morecss = '')
	{
		global $langs, $conf, $db;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;


		$result = '';
		$companylink = '';

		$label = '<u>'.$langs->trans("WebSite").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref.'<br>';
		$label .= '<b>'.$langs->trans('MainLanguage').':</b> '.$this->lang;

		$linkstart = '<a href="'.DOL_URL_ROOT.'/website/card.php?id='.$this->id.'"';
		$linkstart .= ($notooltip ? '' : ' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip'.($morecss ? ' '.$morecss : '').'"');
		$linkstart .= '>';
		$linkend = '</a>';

		$linkstart = $linkend = '';

		if ($withpicto) {
			$result .= ($linkstart.img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? '' : 'class="classfortooltip"')).$linkend);
			if ($withpicto != 2) {
				$result .= ' ';
			}
		}
		$result .= $linkstart.$this->ref.$linkend;
		return $result;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Offline');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Online');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Offline');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Online');
		}

		$statusType = 'status5';
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status4';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}


	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		global $user;

		$this->id = 0;
		$this->specimen = 1;
		$this->entity = 1;
		$this->ref = 'myspecimenwebsite';
		$this->description = 'A specimen website';
		$this->lang = 'en';
		$this->otherlang = 'fr,es';
		$this->status = 1;
		$this->fk_default_home = null;
		$this->virtualhost = 'http://myvirtualhost';
		$this->fk_user_creat = $user->id;
		$this->fk_user_modif = $user->id;
		$this->date_creation = dol_now();
		$this->tms = dol_now();
	}


	/**
	 * Generate a zip with all data of web site.
	 *
	 * @return  string						Path to file with zip or '' if error
	 */
	public function exportWebSite()
	{
		global $conf, $mysoc;

		$website = $this;

		if (empty($website->id) || empty($website->ref)) {
			setEventMessages("Website id or ref is not defined", null, 'errors');
			return '';
		}

		dol_syslog("Create temp dir ".$conf->website->dir_temp);
		dol_mkdir($conf->website->dir_temp);
		if (!is_writable($conf->website->dir_temp)) {
			setEventMessages("Temporary dir ".$conf->website->dir_temp." is not writable", null, 'errors');
			return '';
		}

		$destdir = $conf->website->dir_temp.'/'.$website->ref;
		dol_syslog("Clear temp dir ".$destdir);
		$count = 0;
		$countreallydeleted = 0;
		$counttodelete = dol_delete_dir_recursive($destdir, $count, 1, 0, $countreallydeleted);
		if ($counttodelete != $countreallydeleted) {
			setEventMessages("Failed to clean temp directory ".$destdir, null, 'errors');
			return '';
		}

		$arrayreplacementinfilename = array();
		$arrayreplacementincss = array();
		$arrayreplacementincss['file=image/'.$website->ref.'/'] = "file=image/__WEBSITE_KEY__/";
		$arrayreplacementincss['file=js/'.$website->ref.'/'] = "file=js/__WEBSITE_KEY__/";
		$arrayreplacementincss['medias/image/'.$website->ref.'/'] = "medias/image/__WEBSITE_KEY__/";
		$arrayreplacementincss['medias/js/'.$website->ref.'/'] = "medias/js/__WEBSITE_KEY__/";
		if ($mysoc->logo_small) {
			$arrayreplacementincss['file=logos%2Fthumbs%2F'.$mysoc->logo_small] = "file=logos%2Fthumbs%2F__LOGO_SMALL_KEY__";
		}
		if ($mysoc->logo_mini) {
			$arrayreplacementincss['file=logos%2Fthumbs%2F'.$mysoc->logo_mini] = "file=logos%2Fthumbs%2F__LOGO_MINI_KEY__";
		}
		if ($mysoc->logo) {
			$arrayreplacementincss['file=logos%2Fthumbs%2F'.$mysoc->logo] = "file=logos%2Fthumbs%2F__LOGO_KEY__";
		}

		// Create output directories
		dol_syslog("Create containers dir");
		dol_mkdir($conf->website->dir_temp.'/'.$website->ref.'/containers');
		dol_mkdir($conf->website->dir_temp.'/'.$website->ref.'/medias/image/websitekey');
		dol_mkdir($conf->website->dir_temp.'/'.$website->ref.'/medias/js/websitekey');

		// Copy files into 'containers'
		$srcdir = $conf->website->dir_output.'/'.$website->ref;
		$destdir = $conf->website->dir_temp.'/'.$website->ref.'/containers';

		dol_syslog("Copy pages from ".$srcdir." into ".$destdir);
		dolCopyDir($srcdir, $destdir, 0, 1, $arrayreplacementinfilename, 2, array('old', 'back'));

		// Copy file README.md and LICENSE from directory containers into directory root
		if (dol_is_file($conf->website->dir_temp.'/'.$website->ref.'/containers/README.md')) {
			dol_copy($conf->website->dir_temp.'/'.$website->ref.'/containers/README.md', $conf->website->dir_temp.'/'.$website->ref.'/README.md');
		}
		if (dol_is_file($conf->website->dir_temp.'/'.$website->ref.'/containers/LICENSE')) {
			dol_copy($conf->website->dir_temp.'/'.$website->ref.'/containers/LICENSE', $conf->website->dir_temp.'/'.$website->ref.'/LICENSE');
		}

		// Copy files into medias/image
		$srcdir = DOL_DATA_ROOT.'/medias/image/'.$website->ref;
		$destdir = $conf->website->dir_temp.'/'.$website->ref.'/medias/image/websitekey';

		dol_syslog("Copy content from ".$srcdir." into ".$destdir);
		dolCopyDir($srcdir, $destdir, 0, 1, $arrayreplacementinfilename);

		// Copy files into medias/js
		$srcdir = DOL_DATA_ROOT.'/medias/js/'.$website->ref;
		$destdir = $conf->website->dir_temp.'/'.$website->ref.'/medias/js/websitekey';

		dol_syslog("Copy content from ".$srcdir." into ".$destdir);
		dolCopyDir($srcdir, $destdir, 0, 1, $arrayreplacementinfilename);

		// Make some replacement into some files
		$cssindestdir = $conf->website->dir_temp.'/'.$website->ref.'/containers/styles.css.php';
		if (dol_is_file($cssindestdir)) {
			dolReplaceInFile($cssindestdir, $arrayreplacementincss);
		}

		$htmldeaderindestdir = $conf->website->dir_temp.'/'.$website->ref.'/containers/htmlheader.html';
		if (dol_is_file($htmldeaderindestdir)) {
			dolReplaceInFile($htmldeaderindestdir, $arrayreplacementincss);
		}

		// Build sql file
		$filesql = $conf->website->dir_temp.'/'.$website->ref.'/website_pages.sql';
		$fp = fopen($filesql, "w");
		if (empty($fp)) {
			setEventMessages("Failed to create file ".$filesql, null, 'errors');
			return '';
		}

		$objectpages = new WebsitePage($this->db);
		$listofpages = $objectpages->fetchAll($website->id);

		// Assign ->newid and ->newfk_page
		$i = 1;
		foreach ($listofpages as $pageid => $objectpageold) {
			$objectpageold->newid = $i;
			$i++;
		}
		$i = 1;
		foreach ($listofpages as $pageid => $objectpageold) {
			// Search newid
			$newfk_page = 0;
			foreach ($listofpages as $pageid2 => $objectpageold2) {
				if ($pageid2 == $objectpageold->fk_page) {
					$newfk_page = $objectpageold2->newid;
					break;
				}
			}
			$objectpageold->newfk_page = $newfk_page;
			$i++;
		}
		foreach ($listofpages as $pageid => $objectpageold) {
			$allaliases = $objectpageold->pageurl;
			$allaliases .= ($objectpageold->aliasalt ? ','.$objectpageold->aliasalt : '');

			$line = '-- File generated by Dolibarr '.DOL_VERSION.' -- '.dol_print_date(dol_now('gmt'), 'standard', 'gmt').' UTC --;';
			$line .= "\n";

			$line .= '-- Page ID '.$objectpageold->id.' -> '.$objectpageold->newid.'__+MAX_llx_website_page__ - Aliases '.$allaliases.' --;'; // newid start at 1, 2...
			$line .= "\n";
			fputs($fp, $line);

			// Warning: We must keep llx_ here. It is a generic SQL.
			$line = 'INSERT INTO llx_website_page(rowid, fk_page, fk_website, pageurl, aliasalt, title, description, lang, image, keywords, status, date_creation, tms, import_key, grabbed_from, type_container, htmlheader, content, author_alias, allowed_in_frames)';

			$line .= " VALUES(";
			$line .= $objectpageold->newid."__+MAX_llx_website_page__, ";
			$line .= ($objectpageold->newfk_page ? $this->db->escape($objectpageold->newfk_page)."__+MAX_llx_website_page__" : "null").", ";
			$line .= "__WEBSITE_ID__, ";
			$line .= "'".$this->db->escape($objectpageold->pageurl)."', ";
			$line .= "'".$this->db->escape($objectpageold->aliasalt)."', ";
			$line .= "'".$this->db->escape($objectpageold->title)."', ";
			$line .= "'".$this->db->escape($objectpageold->description)."', ";
			$line .= "'".$this->db->escape($objectpageold->lang)."', ";
			$line .= "'".$this->db->escape($objectpageold->image)."', ";
			$line .= "'".$this->db->escape($objectpageold->keywords)."', ";
			$line .= "'".$this->db->escape($objectpageold->status)."', ";
			$line .= "'".$this->db->idate($objectpageold->date_creation)."', ";
			$line .= "'".$this->db->idate($objectpageold->date_modification)."', ";
			$line .= ($objectpageold->import_key ? "'".$this->db->escape($objectpageold->import_key)."'" : "null").", ";
			$line .= "'".$this->db->escape($objectpageold->grabbed_from)."', ";
			$line .= "'".$this->db->escape($objectpageold->type_container)."', ";

			// Make substitution with a generic path into htmlheader content
			$stringtoexport = $objectpageold->htmlheader;
			$stringtoexport = str_replace(array("\r\n", "\r", "\n"), "__N__", $stringtoexport);
			$stringtoexport = str_replace('file=image/'.$website->ref.'/', "file=image/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('file=js/'.$website->ref.'/', "file=js/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('medias/image/'.$website->ref.'/', "medias/image/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('medias/js/'.$website->ref.'/', "medias/js/__WEBSITE_KEY__/", $stringtoexport);

			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo_small, "file=logos%2Fthumbs%2F__LOGO_SMALL_KEY__", $stringtoexport);
			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo_mini, "file=logos%2Fthumbs%2F__LOGO_MINI_KEY__", $stringtoexport);
			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo, "file=logos%2Fthumbs%2F__LOGO_KEY__", $stringtoexport);
			$line .= "'".$this->db->escape(str_replace(array("\r\n", "\r", "\n"), "__N__", $stringtoexport))."', "; // Replace \r \n to have record on 1 line

			// Make substitution with a generic path into page content
			$stringtoexport = $objectpageold->content;
			$stringtoexport = str_replace(array("\r\n", "\r", "\n"), "__N__", $stringtoexport);
			$stringtoexport = str_replace('file=image/'.$website->ref.'/', "file=image/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('file=js/'.$website->ref.'/', "file=js/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('medias/image/'.$website->ref.'/', "medias/image/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('medias/js/'.$website->ref.'/', "medias/js/__WEBSITE_KEY__/", $stringtoexport);
			$stringtoexport = str_replace('"image/'.$website->ref.'/', '"image/__WEBSITE_KEY__/', $stringtoexport);	// When we have a link src="image/websiteref/file.png" into html content
			$stringtoexport = str_replace('"/image/'.$website->ref.'/', '"/image/__WEBSITE_KEY__/', $stringtoexport);	// When we have a link src="/image/websiteref/file.png" into html content
			$stringtoexport = str_replace('"js/'.$website->ref.'/', '"js/__WEBSITE_KEY__/', $stringtoexport);
			$stringtoexport = str_replace('"/js/'.$website->ref.'/', '"/js/__WEBSITE_KEY__/', $stringtoexport);

			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo_small, "file=logos%2Fthumbs%2F__LOGO_SMALL_KEY__", $stringtoexport);
			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo_mini, "file=logos%2Fthumbs%2F__LOGO_MINI_KEY__", $stringtoexport);
			$stringtoexport = str_replace('file=logos%2Fthumbs%2F'.$mysoc->logo, "file=logos%2Fthumbs%2F__LOGO_KEY__", $stringtoexport);


			$line .= "'".$this->db->escape($stringtoexport)."', "; // Replace \r \n to have record on 1 line
			$line .= "'".$this->db->escape($objectpageold->author_alias)."', ";
			$line .= (int) $objectpageold->allowed_in_frames;
			$line .= ");";
			$line .= "\n";

			fputs($fp, $line);

			// Add line to update home page id during import
			//var_dump($this->fk_default_home.' - '.$objectpageold->id.' - '.$objectpageold->newid);exit;
			if ($this->fk_default_home > 0 && ($objectpageold->id == $this->fk_default_home) && ($objectpageold->newid > 0)) {	// This is the record with home page
				// Warning: We must keep llx_ here. It is a generic SQL.
				$line = "UPDATE llx_website SET fk_default_home = ".($objectpageold->newid > 0 ? $this->db->escape($objectpageold->newid)."__+MAX_llx_website_page__" : "null")." WHERE rowid = __WEBSITE_ID__;";
				$line .= "\n";
				fputs($fp, $line);
			}
		}

		$line = "\n-- For Dolibarr v14+ --;\n";
		$line .= "UPDATE llx_website SET lang = '".$this->db->escape($this->lang)."' WHERE rowid = __WEBSITE_ID__;\n";
		$line .= "UPDATE llx_website SET otherlang = '".$this->db->escape($this->otherlang)."' WHERE rowid = __WEBSITE_ID__;\n";
		$line .= "\n";
		fputs($fp, $line);

		fclose($fp);
		dolChmod($filesql);

		// Build zip file
		$filedir  = $conf->website->dir_temp.'/'.$website->ref.'/.';
		$fileglob = $conf->website->dir_temp.'/'.$website->ref.'/website_'.$website->ref.'-*.zip';
		$filename = $conf->website->dir_temp.'/'.$website->ref.'/website_'.$website->ref.'-'.dol_print_date(dol_now(), 'dayhourlog').'-V'.((float) DOL_VERSION).'.zip';

		dol_delete_file($fileglob, 0);

		$result = dol_compress_dir($filedir, $filename, 'zip');

		if ($result > 0) {
			return $filename;
		} else {
			global $errormsg;
			$this->error = $errormsg;
			return '';
		}
	}


	/**
	 * Open a zip with all data of web site and load it into database.
	 *
	 * @param 	string		$pathtofile		Full path of zip file
	 * @return  int							Return integer <0 if KO, Id of new website if OK
	 */
	public function importWebSite($pathtofile)
	{
		global $conf, $mysoc;

		$error = 0;

		$pathtofile = dol_sanitizePathName($pathtofile);
		$object = $this;
		if (empty($object->ref)) {
			$this->error = 'Function importWebSite called on object not loaded (object->ref is empty)';
			return -2;
		}

		dol_delete_dir_recursive($conf->website->dir_temp."/".$object->ref);
		dol_mkdir($conf->website->dir_temp.'/'.$object->ref);

		$filename = basename($pathtofile);
		if (!preg_match('/^website_(.*)-(.*)$/', $filename, $reg)) {
			$this->errors[] = 'Bad format for filename '.$filename.'. Must be website_XXX-VERSION.';
			return -3;
		}

		$result = dol_uncompress($pathtofile, $conf->website->dir_temp.'/'.$object->ref);

		if (!empty($result['error'])) {
			$this->errors[] = 'Failed to unzip file '.$pathtofile.'.';
			return -4;
		}

		$arrayreplacement = array();
		$arrayreplacement['__WEBSITE_ID__'] = $object->id;
		$arrayreplacement['__WEBSITE_KEY__'] = $object->ref;
		$arrayreplacement['__N__'] = $this->db->escape("\n"); // Restore \n
		$arrayreplacement['__LOGO_SMALL_KEY__'] = $this->db->escape($mysoc->logo_small);
		$arrayreplacement['__LOGO_MINI_KEY__'] = $this->db->escape($mysoc->logo_mini);
		$arrayreplacement['__LOGO_KEY__'] = $this->db->escape($mysoc->logo);

		// Copy containers
		dolCopyDir($conf->website->dir_temp.'/'.$object->ref.'/containers', $conf->website->dir_output.'/'.$object->ref, 0, 1); // Overwrite if exists

		// Make replacement into css and htmlheader file
		$cssindestdir = $conf->website->dir_output.'/'.$object->ref.'/styles.css.php';
		$result = dolReplaceInFile($cssindestdir, $arrayreplacement);

		$htmldeaderindestdir = $conf->website->dir_output.'/'.$object->ref.'/htmlheader.html';
		$result = dolReplaceInFile($htmldeaderindestdir, $arrayreplacement);

		// Now generate the master.inc.php page
		$filemaster = $conf->website->dir_output.'/'.$object->ref.'/master.inc.php';
		$result = dolSaveMasterFile($filemaster);
		if (!$result) {
			$this->errors[] = 'Failed to write file '.$filemaster;
			$error++;
		}

		dolCopyDir($conf->website->dir_temp.'/'.$object->ref.'/medias/image/websitekey', $conf->website->dir_output.'/'.$object->ref.'/medias/image/'.$object->ref, 0, 1); // Medias can be shared, do not overwrite if exists
		dolCopyDir($conf->website->dir_temp.'/'.$object->ref.'/medias/js/websitekey', $conf->website->dir_output.'/'.$object->ref.'/medias/js/'.$object->ref, 0, 1); // Medias can be shared, do not overwrite if exists

		$sqlfile = $conf->website->dir_temp."/".$object->ref.'/website_pages.sql';

		$result = dolReplaceInFile($sqlfile, $arrayreplacement);

		$this->db->begin();

		// Search the $maxrowid because we need it later
		$sqlgetrowid = 'SELECT MAX(rowid) as max from '.MAIN_DB_PREFIX.'website_page';
		$resql = $this->db->query($sqlgetrowid);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$maxrowid = $obj->max;
		}

		// Load sql record
		$runsql = run_sql($sqlfile, 1, '', 0, '', 'none', 0, 1, 0, 0, 1); // The maxrowid of table is searched into this function two
		if ($runsql <= 0) {
			$this->errors[] = 'Failed to load sql file '.$sqlfile.' (ret='.((int) $runsql).')';
			$error++;
		}

		$objectpagestatic = new WebsitePage($this->db);

		// Make replacement of IDs
		$fp = fopen($sqlfile, "r");
		if ($fp) {
			while (!feof($fp)) {
				$reg = array();

				// Warning fgets with second parameter that is null or 0 hang.
				$buf = fgets($fp, 65000);
				if (preg_match('/^-- Page ID (\d+)\s[^\s]+\s(\d+).*Aliases\s(.*)\s--;/i', $buf, $reg)) {
					$oldid = $reg[1];
					$newid = ($reg[2] + $maxrowid);
					$aliasesarray = explode(',', $reg[3]);

					dol_syslog("Found ID ".$oldid." to replace with ID ".$newid." and shortcut aliases to create: ".$reg[3]);

					dol_move($conf->website->dir_output.'/'.$object->ref.'/page'.$oldid.'.tpl.php', $conf->website->dir_output.'/'.$object->ref.'/page'.$newid.'.tpl.php', 0, 1, 0, 0);

					$objectpagestatic->fetch($newid);

					// The move is not enough, so we regenerate page
					$filetpl = $conf->website->dir_output.'/'.$object->ref.'/page'.$newid.'.tpl.php';
					$result = dolSavePageContent($filetpl, $object, $objectpagestatic);
					if (!$result) {
						$this->errors[] = 'Failed to write file '.basename($filetpl);
						$error++;
					}

					// Regenerate alternative aliases pages
					if (is_array($aliasesarray)) {
						foreach ($aliasesarray as $aliasshortcuttocreate) {
							if (trim($aliasshortcuttocreate)) {
								$filealias = $conf->website->dir_output.'/'.$object->ref.'/'.trim($aliasshortcuttocreate).'.php';
								$result = dolSavePageAlias($filealias, $object, $objectpagestatic);
								if (!$result) {
									$this->errors[] = 'Failed to write file '.basename($filealias);
									$error++;
								}
							}
						}
					}
				}
			}
		}

		// Read record of website that has been updated by the run_sql function previously called so we can get the
		// value of fk_default_home that is ID of home page
		$sql = "SELECT fk_default_home FROM ".MAIN_DB_PREFIX."website WHERE rowid = ".((int) $object->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$object->fk_default_home = $obj->fk_default_home;
			} else {
				//$this->errors[] = 'Failed to get the Home page';
				//$error++;
			}
		}

		// Regenerate index page to point to the new index page
		$pathofwebsite = $conf->website->dir_output.'/'.$object->ref;
		dolSaveIndexPage($pathofwebsite, $pathofwebsite.'/index.php', $pathofwebsite.'/page'.$object->fk_default_home.'.tpl.php', $pathofwebsite.'/wrapper.php', $object);
		$this->initFilesStatus($pathofwebsite);
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $object->id;
		}
	}

	/**
	 * Rebuild all files of all the pages/containers of a website. Rebuild also the index and wrapper.php file.
	 * Note: Files are already regenerated during importWebSite so this function is useless when importing a website.
	 *
	 * @return 	int						Return integer <0 if KO, >=0 if OK
	 */
	public function rebuildWebSiteFiles()
	{
		global $conf;

		$error = 0;

		$object = $this;
		if (empty($object->ref)) {
			$this->error = 'Function rebuildWebSiteFiles called on object not loaded (object->ref is empty)';
			return -1;
		}

		$objectpagestatic = new WebsitePage($this->db);

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."website_page WHERE fk_website = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$num = $this->db->num_rows($resql);

		// Loop on each container/page
		$i = 0;
		while ($i < $num) {
			$obj = $this->db->fetch_object($resql);

			$newid = $obj->rowid;

			$objectpagestatic->fetch($newid);

			$aliasesarray = explode(',', $objectpagestatic->aliasalt);

			$filetpl = $conf->website->dir_output.'/'.$object->ref.'/page'.$newid.'.tpl.php';
			$result = dolSavePageContent($filetpl, $object, $objectpagestatic);
			if (!$result) {
				$this->errors[] = 'Failed to write file '.basename($filetpl);
				$error++;
			}

			// Add main alias to list of alternative aliases
			if (!empty($objectpagestatic->pageurl) && !in_array($objectpagestatic->pageurl, $aliasesarray)) {
				$aliasesarray[] = $objectpagestatic->pageurl;
			}

			// Regenerate also all aliases pages (pages with a natural name) by calling dolSavePageAlias()
			if (is_array($aliasesarray)) {
				foreach ($aliasesarray as $aliasshortcuttocreate) {
					if (trim($aliasshortcuttocreate)) {
						$filealias = $conf->website->dir_output.'/'.$object->ref.'/'.trim($aliasshortcuttocreate).'.php';
						$result = dolSavePageAlias($filealias, $object, $objectpagestatic);	// This includes also a copy into sublanguage directories.
						if (!$result) {
							$this->errors[] = 'Failed to write file '.basename($filealias);
							$error++;
						}
					}
				}
			}

			$i++;
		}

		if (!$error) {
			// Save index.php and wrapper.php
			$pathofwebsite = $conf->website->dir_output.'/'.$object->ref;
			$fileindex = $pathofwebsite.'/index.php';
			$filetpl = '';
			if ($object->fk_default_home > 0) {
				$filetpl = $pathofwebsite.'/page'.$object->fk_default_home.'.tpl.php';
			}
			$filewrapper = $pathofwebsite.'/wrapper.php';
			dolSaveIndexPage($pathofwebsite, $fileindex, $filetpl, $filewrapper, $object);	// This includes also a version of index.php into sublanguage directories
		}

		if ($error) {
			return -1;
		} else {
			return $num;
		}
	}

	/**
	 * Return if web site is a multilanguage web site. Return false if there is only 0 or 1 language.
	 *
	 * @return boolean			True if web site is a multilanguage web site
	 */
	public function isMultiLang()
	{
		return (empty($this->otherlang) ? false : true);
	}

	/**
	 * Component to select language inside a container (Full CSS Only)
	 *
	 * @param	array|string	$languagecodes			'auto' to show all languages available for page, or language codes array like array('en','fr','de','es')
	 * @param	Translate		$weblangs				Language Object
	 * @param	string			$morecss				More CSS class on component
	 * @param	string			$htmlname				Suffix for HTML name
	 * @return 	string									HTML select component
	 */
	public function componentSelectLang($languagecodes, $weblangs, $morecss = '', $htmlname = '')
	{
		global $websitepagefile, $website;

		if (!is_object($weblangs)) {
			return 'ERROR componentSelectLang called with parameter $weblangs not defined';
		}

		$arrayofspecialmainlanguages = array(
			'en'=>'en_US',
			'sq'=>'sq_AL',
			'ar'=>'ar_SA',
			'eu'=>'eu_ES',
			'bn'=>'bn_DB',
			'bs'=>'bs_BA',
			'ca'=>'ca_ES',
			'zh'=>'zh_CN',
			'cs'=>'cs_CZ',
			'da'=>'da_DK',
			'et'=>'et_EE',
			'ka'=>'ka_GE',
			'el'=>'el_GR',
			'he'=>'he_IL',
			'kn'=>'kn_IN',
			'km'=>'km_KH',
			'ko'=>'ko_KR',
			'lo'=>'lo_LA',
			'nb'=>'nb_NO',
			'fa'=>'fa_IR',
			'sr'=>'sr_RS',
			'sl'=>'sl_SI',
			'uk'=>'uk_UA',
			'vi'=>'vi_VN'
		);

		// Load tmppage if we have $websitepagefile defined
		$tmppage = new WebsitePage($this->db);

		$pageid = 0;
		if (!empty($websitepagefile)) {
			$websitepagefileshort = basename($websitepagefile);
			if ($websitepagefileshort == 'index.php') {
				$pageid = $website->fk_default_home;
			} else {
				$pageid = str_replace(array('.tpl.php', 'page'), array('', ''), $websitepagefileshort);
			}
			if ($pageid > 0) {
				$tmppage->fetch($pageid);
			}
		}

		// Fill $languagecodes array with existing translation, nothing if none
		if (!is_array($languagecodes) && $pageid > 0) {
			$languagecodes = array();

			$sql = "SELECT wp.rowid, wp.lang, wp.pageurl, wp.fk_page";
			$sql .= " FROM ".MAIN_DB_PREFIX."website_page as wp";
			$sql .= " WHERE wp.fk_website = ".((int) $website->id);
			$sql .= " AND (wp.fk_page = ".((int) $pageid)." OR wp.rowid  = ".((int) $pageid);
			if ($tmppage->fk_page > 0) {
				$sql .= " OR wp.fk_page = ".((int) $tmppage->fk_page)." OR wp.rowid = ".((int) $tmppage->fk_page);
			}
			$sql .= ")";

			$resql = $this->db->query($sql);
			if ($resql) {
				while ($obj = $this->db->fetch_object($resql)) {
					$newlang = $obj->lang;
					if ($obj->rowid == $pageid) {
						$newlang = $obj->lang;
					}
					if (!in_array($newlang, $languagecodes)) {
						$languagecodes[] = $newlang;
					}
				}
			}
		}
		// Now $languagecodes is always an array. Example array('en', 'fr', 'es');

		$languagecodeselected = substr($weblangs->defaultlang, 0, 2); // Because we must init with a value, but real value is the lang of main parent container
		if (!empty($websitepagefile)) {
			$pageid = str_replace(array('.tpl.php', 'page'), array('', ''), basename($websitepagefile));
			if ($pageid > 0) {
				$pagelang = substr($tmppage->lang, 0, 2);
				$languagecodeselected = substr($pagelang, 0, 2);
				if (!in_array($pagelang, $languagecodes)) {
					$languagecodes[] = $pagelang; // We add language code of page into combo list
				}
			}
		}

		$weblangs->load('languages');
		//var_dump($weblangs->defaultlang);

		$url = $_SERVER["REQUEST_URI"];
		$url = preg_replace('/(\?|&)l=([a-zA-Z_]*)/', '', $url); // We remove param l from url
		//$url = preg_replace('/(\?|&)lang=([a-zA-Z_]*)/', '', $url);	// We remove param lang from url
		$url .= (preg_match('/\?/', $url) ? '&' : '?').'l=';
		if (!preg_match('/^\//', $url)) {
			$url = '/'.$url;
		}

		$HEIGHTOPTION = 40;
		$MAXHEIGHT = 4 * $HEIGHTOPTION;
		$nboflanguage = count($languagecodes);

		$out = '<!-- componentSelectLang'.$htmlname.' -->'."\n";

		$out .= '<style>';
		$out .= '.componentSelectLang'.$htmlname.':hover { height: '.min($MAXHEIGHT, ($HEIGHTOPTION * $nboflanguage)).'px; overflow-x: hidden; overflow-y: '.((($HEIGHTOPTION * $nboflanguage) > $MAXHEIGHT) ? ' scroll' : 'hidden').'; }'."\n";
		$out .= '.componentSelectLang'.$htmlname.' li { line-height: '.$HEIGHTOPTION.'px; }'."\n";
		$out .= '.componentSelectLang'.$htmlname.' {
			display: inline-block;
			padding: 0;
			height: '.$HEIGHTOPTION.'px;
			overflow: hidden;
			transition: all .3s ease;
			margin: 0 0 0 0;
			vertical-align: top;
		}
		.componentSelectLang'.$htmlname.':hover, .componentSelectLang'.$htmlname.':hover a { background-color: #fff; color: #000 !important; }
		ul.componentSelectLang'.$htmlname.' { width: 150px; }
		ul.componentSelectLang'.$htmlname.':hover .fa { visibility: hidden; }
		.componentSelectLang'.$htmlname.' a { text-decoration: none; width: 100%; }
		.componentSelectLang'.$htmlname.' li { display: block; padding: 0px 15px; margin-left: 0; margin-right: 0; }
		.componentSelectLang'.$htmlname.' li:hover { background-color: #EEE; }
		';
		$out .= '</style>';
		$out .= '<ul class="componentSelectLang'.$htmlname.($morecss ? ' '.$morecss : '').'">';

		if ($languagecodeselected) {
			// Convert $languagecodeselected into a long language code
			if (strlen($languagecodeselected) == 2) {
				$languagecodeselected = (empty($arrayofspecialmainlanguages[$languagecodeselected]) ? $languagecodeselected.'_'.strtoupper($languagecodeselected) : $arrayofspecialmainlanguages[$languagecodeselected]);
			}

			$countrycode = strtolower(substr($languagecodeselected, -2));
			$label = $weblangs->trans("Language_".$languagecodeselected);
			if ($countrycode == 'us') {
				$label = preg_replace('/\s*\(.*\)/', '', $label);
			}
			$out .= '<li><a href="'.$url.substr($languagecodeselected, 0, 2).'"><img height="12px" src="/medias/image/common/flags/'.$countrycode.'.png" style="margin-right: 5px;"/><span class="websitecomponentlilang">'.$label.'</span>';
			$out .= '<span class="fa fa-caret-down" style="padding-left: 5px;" />';
			$out .= '</a></li>';
		}
		$i = 0;
		if (is_array($languagecodes)) {
			foreach ($languagecodes as $languagecode) {
				// Convert $languagecode into a long language code
				if (strlen($languagecode) == 2) {
					$languagecode = (empty($arrayofspecialmainlanguages[$languagecode]) ? $languagecode.'_'.strtoupper($languagecode) : $arrayofspecialmainlanguages[$languagecode]);
				}

				if ($languagecode == $languagecodeselected) {
					continue; // Already output
				}

				$countrycode = strtolower(substr($languagecode, -2));
				$label = $weblangs->trans("Language_".$languagecode);
				if ($countrycode == 'us') {
					$label = preg_replace('/\s*\(.*\)/', '', $label);
				}
				$out .= '<li><a href="'.$url.substr($languagecode, 0, 2).'"><img height="12px" src="/medias/image/common/flags/'.$countrycode.'.png" style="margin-right: 5px;"/><span class="websitecomponentlilang">'.$label.'</span>';
				if (empty($i) && empty($languagecodeselected)) {
					$out .= '<span class="fa fa-caret-down" style="padding-left: 5px;" />';
				}
				$out .= '</a></li>';
				$i++;
			}
		}
		$out .= '</ul>';

		return $out;
	}

	/**
	 * Overite template by copying all files
	 *
	 * @param	string	$pathtotmpzip		Path to the tmp zip file
	 * @return 	int							Return integer <0 if KO, >0 if OK
	 */
	public function overwriteTemplate(string $pathtotmpzip)
	{
		global $conf;

		//$error = 0;

		$website = $this;
		if (empty($website->id) || empty($website->ref)) {
			setEventMessages("Website id or ref is not defined", null, 'errors');
			return -1;
		}
		if (empty($website->name_template)) {
			setEventMessages("To export the website template into the GIT sources directory, the name of the directory/template must be know. For this website, the variable 'name_template' is unknown, so export in GIT sources is not possible.", null, 'errors');
			return -1;
		}
		if (!is_writable($conf->website->dir_temp)) {
			setEventMessages("Temporary dir ".$conf->website->dir_temp." is not writable", null, 'errors');
			return -1;
		}

		// Replace modified files into the doctemplates directory.
		if (getDolGlobalString('WEBSITE_ALLOW_OVERWRITE_GIT_SOURCE') == '1') {
			$destdirrel = 'install/doctemplates/websites/'.$website->name_template;
			$destdir = DOL_DOCUMENT_ROOT.'/'.$destdirrel;
		} else {
			$destdirrel = basename(dirname(getDolGlobalString('WEBSITE_ALLOW_OVERWRITE_GIT_SOURCE'))).'/'.basename(getDolGlobalString('WEBSITE_ALLOW_OVERWRITE_GIT_SOURCE'));
			$destdir = getDolGlobalString('WEBSITE_ALLOW_OVERWRITE_GIT_SOURCE');
		}

		// Export on target sources
		$resultarray = dol_uncompress($pathtotmpzip, $destdir);
		if (!empty($resultarray)) {
			setEventMessages("Error, failed to unzip the export into target dir", null, 'errors');
		} else {
			setEventMessages("Website content written into ".$destdirrel, null, 'mesgs');
		}

		/*
		$sourcedir = $conf->website->dir_output."/".$website->ref;

		// Get array with hash of files (for the last sync)
		$fichierEtat = $sourcedir . '/filelist-'.dol_sanitizeFileName($destdir).'.txt';
		$etatPrecedent = $this->checkPreviousState($fichierEtat);

		// Get list of all source files of the website
		$arraySourcedir = dol_dir_list($sourcedir);

		// Get list of modified files
		$modifications = [];
		foreach ($arraySourcedir as $file) {
			if (substr($file['name'], -4) === '.old') {
				continue;
			}
			$hashActuel = hash_file('md5', $file['fullname']);

			// Check whether the file is new or has been modified
			if (!isset($etatPrecedent[$file['name']]) || $etatPrecedent[$file['name']] !== $hashActuel) {
				$modifications[] = $file;
			}

			$etatPrecedent[$file['name']] = $hashActuel;	// we store he new hash to record it later on disk.
		}

		$arraydestdir = dol_dir_list($destdir, "all", 1);
		$differences = [];
		$names = array_column($arraydestdir, 'name');
		$namesSource = array_column($arraySourcedir, 'name');

		if (count($modifications) > 1) {
			foreach ($modifications as $fichierModifie) {
				$nomFichierModifie = $fichierModifie['name'];
				if ($nomFichierModifie == basename($fichierEtat)) {
					continue;
				}
				$success = 0;

				//check if it is a new file
				if ((!preg_match('/^page\d+\.tpl\.php$/', $nomFichierModifie)) && (!in_array($nomFichierModifie, $names))) {
					if (file_exists($fichierModifie['fullname']) && dol_is_dir($destdir.'/containers')) {
						$cp = dol_copy($fichierModifie['fullname'], $destdir.'/containers/'.$nomFichierModifie, '0664');
						if ($cp > 0) {
							if (file_exists($destdir.'/containers/'.$nomFichierModifie)) {
								$tabnumpage = array();
								foreach ($arraydestdir as $fileDest) {
									if ($this->extractNumberFromFilename($fileDest['name']) !== -1) {
										$tabnumpage[] = $this->extractNumberFromFilename($fileDest['name']);
									}
								}
								$getContentSource = file_get_contents($destdir.'/containers/'.$nomFichierModifie);
								$nextpage = max($tabnumpage) + 1;
								$chaineModifiee = preg_replace('/page\d+\.tpl\.php/', 'page' . $nextpage . '.tpl.php', $getContentSource);
								$write = file_put_contents($destdir.'/containers/'.$nomFichierModifie, $chaineModifiee);
								if ($write !== false) {
									if (!touch($destdir.'/containers/'."page" . $nextpage . ".tpl.php")) {
										setEventMessages("Please check permission to create  <b>page" . $nextpage . ".tpl.php</b> in template <b>".$website->name_template."</b>", null, 'errors');
									}
									$fileFounded = '';
									foreach ($arraySourcedir as $file) {
										if ($file['name'] == $nomFichierModifie) {
											$fileContent = file_get_contents($file['fullname']);
											$matches = array();
											if (preg_match("/page\d+\.tpl\.php/", $fileContent, $matches)) {
												$fileFounded = $matches[0];
												break;
											}
										}
									}
									foreach ($arraySourcedir as $file) {
										if ($file['name'] == $fileFounded) {
											if (!is_writable($file['fullname'])) {
												dolChmod($file['fullname'], '0664');
											}
											$diff = $this->showDifferences(file_get_contents($destdir.'/containers/'."page" . $nextpage . ".tpl.php"), file_get_contents($file['fullname']), array($nextpage,$this->extractNumberFromFilename($file['name'])));
											if ($diff != -1) {
												$replace = $this->replaceLineUsingNum($destdir.'/containers/'."page" . $nextpage . ".tpl.php", $diff);
												if ($replace !== false) {
													setEventMessages("Copy file <b>page".$nextpage.".tpl.php</b> in template <b>".$this->name_template."</b> with success", null, 'warnings');
												}
											}
										}
									}
								}
							}

							$this->saveState($etatPrecedent, $fichierEtat);
							setEventMessages("file <b>".$nomFichierModifie."</b> was created in template <b>".$website->name_template."</b>", null, 'warnings');

							header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website->ref);
							exit();
						}
					} else {
						setEventMessages("Error, target dir containers not found", null, 'errors');
						$error = 1;
						break;
					}
				}

				// Find the corresponding file in the destination folder
				if (!$error && in_array($nomFichierModifie, $namesSource)) {
					foreach ($arraydestdir as $destFile) {
						if ($destFile['name'] == $nomFichierModifie) {
							$sourceContent = file_get_contents($fichierModifie['fullname']);
							$destContent = file_get_contents($destFile['fullname']);

							if ($sourceContent !== $destContent) {
								$differences[$nomFichierModifie] = $this->showDifferences($destContent, $sourceContent);
								if (count($differences[$nomFichierModifie]) > 0) {
									$result = $this->replaceLineUsingNum($destFile['fullname'], $differences[$nomFichierModifie]);
									if ($result >= 0) {
										setEventMessages("file <b>".$nomFichierModifie."</b> was modified in template <b>".$website->name_template."</b>", null, 'warnings');
									} else {
										if ($result == -2) {
											setEventMessages("No permissions to write into file <b>".$destdirrel.'/'.$nomFichierModifie."</b> from the current website <b>".$website->name_template."</b>", null, 'errors');

											header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website->ref);
											exit();
										}
										setEventMessages("file ".$nomFichierModifie." was not modified", null, 'errors');
									}
								}
							}
						}

						if (preg_match('/page(\d+)\.tpl\.php/', $nomFichierModifie)) {
							$differences[$nomFichierModifie] = $this->compareFichierModifie($sourcedir, $destdir, $fichierModifie);
							if (count($differences[$nomFichierModifie]) > 0) {
								$result = $this->replaceLineUsingNum($differences[$nomFichierModifie]['file_destination']['fullname'], $differences[$nomFichierModifie]);
								if ($result !== false) {
									if ($result == -2) {
										setEventMessages("No permissions to write into file <b>".$destdirrel.'/'.$differences[$nomFichierModifie]['file_destination']['name']."</b> from the current website <b>".$website->name_template."</b>", null, 'errors');
										header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website->ref);
										exit();
									}
									$success++;
								}
							}
						}
					}
				}
			}
			if ($success > 0) {
				// Save the state file filelist.txt
				$this->saveState($etatPrecedent, $fichierEtat);
				setEventMessages("file <b>".$differences[$nomFichierModifie]['file_destination']['name']."</b> was modified in template <b>".$website->name_template."</b>", null, 'warnings');

				header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website->ref);
				exit();
			}
		} else {
			setEventMessages("No file has been modified", null, 'errors');
		}

		// save state file
		if (!$error) {
			$this->saveState($etatPrecedent, $fichierEtat);
		}
		*/

		header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website->ref);
		exit();
	}

	/**
	 * extract num of page
	 * @param  string  $filename   name of file
	 * @return int 1 if OK, -1 if KO
	 */
	protected function extractNumberFromFilename($filename)
	{
		$matches = [];
		if (preg_match('/page(\d+)\.tpl\.php/', $filename, $matches)) {
			return (int) $matches[1];
		}
		return -1;
	}

	/**
	 * update name_template in table after import template
	 * @param  string    $name_template   name of template
	 * @return int     1 if OK, -1 if KO
	 */
	public function setTemplateName($name_template)
	{
		$sql = "UPDATE ".$this->db->prefix()."website SET";
		$sql .= " name_template = '".$this->db->escape($name_template)."'";
		$sql .= " WHERE rowid = ".(int) $this->id;
		$result = $this->db->query($sql);

		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * check previous state for file
	 * @param  string   $pathname  path of file
	 * @return  array|mixed
	 */
	public function checkPreviousState($pathname)
	{
		if (!file_exists($pathname)) {
			if (touch($pathname)) {
				dolChmod($pathname, '0664');
			}
			return [];
		}
		return unserialize(file_get_contents($pathname));
	}


	/**
	 * Save state for File
	 * @param mixed $etat   state
	 * @param mixed $pathname  path of file
	 * @return int|false
	 */
	public function saveState($etat, $pathname)
	{
		return file_put_contents($pathname, serialize($etat));
	}

	/**
	 * create file for save state of all files in folder
	 * @param string  $sourcedir   path of folder
	 * @return void
	 */
	public function initFilesStatus($sourcedir)
	{
		$fichierEtat = $sourcedir . '/filelist-lastwrite-doctemplates.txt';

		$etatPrecedent = $this->checkPreviousState($fichierEtat);

		// for first save state when create file
		if (empty($etatPrecedent)) {
			$arraySourcedir = dol_dir_list($sourcedir, "files");
			$etatFichiers = [];

			foreach ($arraySourcedir as $file) {
				// Ignore .old files and the status file itself
				if (substr($file['name'], -4) === '.old' || $file['name'] === basename($fichierEtat)) {
					continue;
				}

				$hashActuel = hash_file('md5', $file['fullname']);
				$etatFichiers[$file['name']] = $hashActuel;
			}
			$this->saveState($etatFichiers, $fichierEtat);
		}
	}

	/**
	 * Compare two files has not same name but same content
	 * @param  string   $dossierSource        filepath of folder source
	 * @param  string   $dossierDestination   filepath of folder dest
	 * @param  mixed   $fichierModifie       files modified
	 * @return array    empty if KO, array if OK
	 */
	public function compareFichierModifie($dossierSource, $dossierDestination, $fichierModifie)
	{

		$fichiersSource = [];
		$fichiersDestination = [];

		$fichierWithNoPage = [];
		$fichierWithNoPageInDest = [];

		//  filter files source
		foreach (dol_dir_list($dossierSource, "files") as $file) {
			if (preg_match('/^page\d+/', $file['name']) && !str_contains($file['name'], '.old')) {
				$fichiersSource[] = $file;
			} else {
				$fichierWithNoPage[] = $file;
			}
		}

		//  filter files destination
		foreach (dol_dir_list($dossierDestination, "all", 1) as $file) {
			if (preg_match('/^page\d+/', $file['name']) && !str_contains($file['name'], '.old')) {
				$fichiersDestination[] = $file;
			} else {
				$fichierWithNoPageInDest[] = $file;
			}
		}

		// find index source and search it in folder destination
		$numOfPageSource = 0;
		foreach ($fichiersSource as $index => $file) {
			if ($file['name'] == basename($fichierModifie['fullname'])) {
				$numOfPageSource = $this->extractNumberFromFilename($file['name']);
				break;
			}
		}

		//search numPage where was declared
		$fileFounded = array();
		foreach ($fichierWithNoPage as $filesource) {
			$fileContent = file_get_contents($filesource['fullname']);
			if (strpos($fileContent, "require './page".$numOfPageSource.".tpl.php'") !== false) {
				$fileFounded = $filesource;
				break;
			}
		}
		// find file with same name and extract num page in destination folder
		$numPageFounded = '';
		foreach ($fichierWithNoPageInDest as $filedest) {
			if ($filedest['name'] === $fileFounded['name']) {
				$fileContent = file_get_contents($filedest['fullname']);
				if (preg_match("/page\d+\.tpl\.php/", $fileContent, $matches)) {
					$numPageFounded = $matches[0];
					break;
				}
			}
		}
		//search file with the number of page founded
		$fileNeeded = array();
		foreach ($fichiersDestination as $index => $file) {
			if ($file['name'] == $numPageFounded) {
				$fileNeeded = $file;
				break;
			}
		}

		if (isset($fileNeeded)) {
			$sourceContent = file_get_contents($fichierModifie['fullname']);
			if (file_exists($fileNeeded['fullname'])) {
				$destContent = file_get_contents($fileNeeded['fullname']);

				$numOfPageDest = $this->extractNumberFromFilename($fileNeeded['name']);
				$differences = $this->showDifferences($destContent, $sourceContent, array($numOfPageDest,$numOfPageSource));
				$differences['file_destination'] = $fileNeeded;
			}
			return $differences;
		}
		return array();
	}

	/**
	 * remove espace in string
	 * @param   string   $str    string
	 * @return string
	 */
	private function normalizeString($str)
	{
		$str = str_replace("\r\n", "\n", $str);
		$str = str_replace("\r", "\n", $str);
		return $str;
	}

	/**
	 * show difference between to string
	 * @param string  $str1   first string
	 * @param string  $str2   seconde string
	 * @param array  $exceptNumPge    num of page files we don't want to change
	 * @return array|int      -1 if KO, array if OK
	 */
	protected function showDifferences($str1, $str2, $exceptNumPge = array())
	{
		$diff = array();
		$str1 = $this->normalizeString($str1);
		$str2 = $this->normalizeString($str2);

		$lines1 = explode("\n", $str1);
		$lines2 = explode("\n", $str2);

		$linesShouldnChange = array();
		$linesShouldnNotChange = array();
		$linefound = array();
		$countNumPage = count($exceptNumPge);

		for ($i = 0;$i< $countNumPage; $i++) {
			$linefound[$i]['meta'] = '/content="' . preg_quote($exceptNumPge[$i], '/') . '" \/>/';
			$linefound[$i]['output'] = '/dolWebsiteOutput\(\$tmp, "html", ' . preg_quote($exceptNumPge[$i], '/') . '\);/';
		}

		$maxLines = max(count($lines1), count($lines2));
		for ($lineNum = 0; $lineNum < $maxLines; $lineNum++) {
			$lineContent1 = $lines1[$lineNum] ?? '';
			$lineContent2 = $lines2[$lineNum] ?? '';
			if (preg_match($linefound[0]['output'], $lineContent1)) {
				$linesShouldnChange[] = $lineContent1;
			}
			if (preg_match($linefound[0]['meta'], $lineContent1)) {
				$linesShouldnChange[] = $lineContent1;
			}
			if (preg_match($linefound[1]['output'], $lineContent2)) {
				$linesShouldnNotChange[] = $lineContent2;
			}
			if (preg_match($linefound[1]['meta'], $lineContent2)) {
				$linesShouldnNotChange[] = $lineContent2;
			}
			if ($lineContent1 !== $lineContent2) {
				if (isset($lines1[$lineNum]) && !isset($lines2[$lineNum])) {
					// Ligne deleted de la source
					$diff["Supprimée à la ligne " . ($lineNum + 1)] = $lineContent1;
				} elseif (!isset($lines1[$lineNum]) && isset($lines2[$lineNum])) {
					// Nouvelle ligne added dans la destination
					$diff["Ajoutée à la ligne " . ($lineNum + 1)] = $lineContent2;
				} else {
					// Différence found it
					$diff["Modifiée à la ligne " . ($lineNum + 1)] = $lineContent2;
				}
			}
		}


		if (empty($linesShouldnChange)) {
			$linesShouldnChange[0] = '<meta name="dolibarr:pageid" content="'.$exceptNumPge[0].'" />';
			$linesShouldnChange[1] = '$tmp = ob_get_contents(); ob_end_clean(); dolWebsiteOutput($tmp, "html", '.$exceptNumPge[0].');';
		}

		$pairesRemplacement = array();
		if (!empty($linesShouldnNotChange)) {
			$i =0;
			foreach ($linesShouldnNotChange as $numLigne => $ligneRemplacement) {
				if (isset($linesShouldnChange[$numLigne])) {
					$pairesRemplacement[$ligneRemplacement] = $linesShouldnChange[$numLigne];
				} else {
					$pairesRemplacement[$ligneRemplacement] = $linesShouldnChange[$i];
				}
				$i++;
			}
			$diff['lignes_dont_change'] = $pairesRemplacement;
		}
		// search path of image and replace it with the correcte path
		$pattern = '/medias\/image\/'.$this->ref.'\/([^\'"\s]+)/';

		foreach ($diff as $key => $value) {
			// Assurez-vous que la valeur est une chaîne
			if (is_string($value)) {
				if (preg_match($pattern, $value)) {
					$newValue = preg_replace($pattern, 'medias/image/'.$this->name_template.'/$1', $value);
					$diff[$key] = $newValue;
				}
			}
		}
		return $diff;
	}

	/**
	 * Replace line by line in file using num of line
	 *
	 * @param 	string 		$desfFile   	path of file dest
	 * @param 	array 		$differences 	array of differences between files
	 * @return 	int  						Return 0 if we can replace, <0 if not (-2=not writable)
	 */
	protected function replaceLineUsingNum($desfFile, $differences)
	{
		if (file_exists($desfFile)) {
			dolChmod($desfFile, '0664');
		}
		if (!is_writable($desfFile)) {
			return -2;
		}

		unset($differences['file_destination']);
		$contentDest = file($desfFile, FILE_IGNORE_NEW_LINES);
		foreach ($differences as $key => $ligneSource) {
			$matches = array();
			if (preg_match('/(Ajoutée|Modifiée) à la ligne (\d+)/', $key, $matches)) {
				$typeModification = $matches[1];
				$numLigne = (int) $matches[2] - 1;

				if ($typeModification === 'Ajoutée') {
					array_splice($contentDest, $numLigne, 0, $ligneSource);
				} elseif ($typeModification === 'Modifiée') {
					$contentDest[$numLigne] = $ligneSource;
				}
			} elseif (preg_match('/Supprimée à la ligne (\d+)/', $key, $matches)) {
				$numLigne = (int) $matches[1] - 1;
				unset($contentDest[$numLigne]);
			}
		}
		// Reindex the table keys
		$contentDest = array_values($contentDest);
		$stringreplacement = implode("\n", $contentDest);
		file_put_contents($desfFile, $stringreplacement);
		foreach ($differences['lignes_dont_change'] as $linechanged => $line) {
			if (in_array($linechanged, $contentDest)) {
				dolReplaceInFile($desfFile, array($linechanged => $line));
			}
		}

		return 0;
	}
}
