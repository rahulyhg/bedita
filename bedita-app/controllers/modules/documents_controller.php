<?php
/**
 *
 * @filesource
 * @copyright		
 * @link			
 * @package			
 * @subpackage		
 * @since			
 * @version			
 * @modifiedby		
 * @lastmodified	
 * @license			
 * @author 			giangi@qwerg.com d.domenico@channelweb.it
 */

class DocumentsController extends ModulesController {
	var $name = 'Documents';

	var $helpers 	= array('BeTree', 'BeToolbar');
	var $components = array('BeTree', 'Permission', 'BeCustomProperty', 'BeLangText', 'BeFileHandler');

	var $uses = array('BEObject', 'Document', 'Tree', 'Category') ;
	protected $moduleName = 'documents';
	
    public function index($id = null, $order = "", $dir = true, $page = 1, $dim = 20) {    	
    	$conf  = Configure::getInstance() ;
		$types = array($conf->objectTypes['document']);
		
		if (!empty($this->params["form"]["searchstring"])) {
			$types["search"] = addslashes($this->params["form"]["searchstring"]);
			$this->set("stringSearched", $this->params["form"]["searchstring"]);
		}
		
		$this->paginatedList($id, $types, $order, $dir, $page, $dim);
		
	 }

	 /**
	  * Get document.
	  * If id is null, empty document
	  *
	  * @param integer $id
	  */
	function view($id = null) {
		$conf  = Configure::getInstance() ;
		$this->setup_args(array("id", "integer", &$id)) ;
		$obj = null ;
		$relations = array();
		$parents_id = array();
		
		if($id) {
			$this->Document->contain(array(
										"BEObject" => array("ObjectType", 
															"UserCreated", 
															"UserModified", 
															"Permissions",
															"CustomProperties",
															"LangText",
															"RelatedObject",
															"Category"
															),
										"GeoTag"
										)
									);
			if(!($obj = $this->Document->findById($id))) {
				 throw new BeditaException(sprintf(__("Error loading document: %d", true), $id));
			}
			if(!$this->Document->checkType($obj['object_type_id'])) {
               throw new BeditaException(__("Wrong content type: ", true).$id);
			}
			$relations = $this->objectRelationArray($obj['RelatedObject']);
			
			$parents_id = $this->Tree->getParent($id) ;
			if($parents_id === false) 
				$parents_id = array() ;
			elseif(!is_array($parents_id))
				$parents_id = array($parents_id);
		}

		$tree = $this->BeTree->getSectionsTree() ;
	
		$galleries = $this->BeTree->getDiscendents(null, null, $conf->objectTypes['gallery'], "", true, 1, 10000);
		$status = (!empty($obj['status'])) ? $obj['status'] : null;
		$previews = (isset($id)) ? $this->previewsForObject($parents_id,$id,$status) : array();

		$this->set('object',	$obj);
		$this->set('attach', isset($relations['attach']) ? $relations['attach'] : array());
		$this->set('relObjects', isset($relations) ? $relations : array());
		$this->set('galleries', (count($galleries['items'])==0) ? array() : $galleries['items']);
		$this->set('tree', 		$tree);
		$this->set('parents',	$parents_id);
		$this->set('previews',	$previews);
		$this->setUsersAndGroups();
	 }

	/**
	 * Creates/updates new document
	 */
	function save() {
		$this->checkWriteModulePermission();
		if(empty($this->data)) 
			throw new BeditaException( __("No data", true));
		$new = (empty($this->data['id'])) ? true : false ;
		// Verify object permits
		if(!$new && !$this->Permission->verify($this->data['id'], $this->BeAuth->user['userid'], BEDITA_PERMS_MODIFY)) 
			throw new BeditaException(__("Error modify permissions", true));
		// Format custom properties
		$this->BeCustomProperty->setupForSave($this->data["CustomProperties"]) ;
		
		$this->Transaction->begin() ;
		// Save data
		$this->data["Category"] = $this->Category->saveTagList($this->params["form"]["tags"]);
		if(!$this->Document->save($this->data)) {
			throw new BeditaException(__("Error saving document", true), $this->Document->validationErrors);
		}
		if(!($this->data['status']=='fixed')) {
			if(!isset($this->data['destination'])) 
				$this->data['destination'] = array() ;
			$this->BeTree->updateTree($this->Document->id, $this->data['destination']);
		}
	 	// update permissions
		if(!isset($this->data['Permissions'])) 
			$this->data['Permissions'] = array() ;
		$this->Permission->saveFromPOST($this->Document->id, $this->data['Permissions'], 
	 			!empty($this->data['recursiveApplyPermissions']), 'document');
 		$this->Transaction->commit() ;
 		$this->userInfoMessage(__("Document saved", true)." - ".$this->data["title"]);
		$this->eventInfo("document [". $this->data["title"]."] saved");
	 }

	 /**
	  * Delete a document.
	  */
	function delete() {
		$this->checkWriteModulePermission();
		$objectsListDeleted = $this->deleteObjects("Document");
		$this->userInfoMessage(__("Documents deleted", true) . " -  " . $objectsListDeleted);
		$this->eventInfo("documents $objectsListDeleted deleted");
	}


	protected function forward($action, $esito) {
		$REDIRECT = array(
			"cloneObject"	=> 	array(
							"OK"	=> "/documents/view/".@$this->Document->id,
							"ERROR"	=> "/documents/view/".@$this->Document->id 
							),
			"save"	=> 	array(
							"OK"	=> "/documents/view/".@$this->Document->id,
							"ERROR"	=> "/documents/view/".@$this->Document->id 
							), 
			"delete" =>	array(
							"OK"	=> "/documents",
							"ERROR"	=> "/documents/view/".@$this->params['pass'][0]
							),
			"addItemsToAreaSection"	=> 	array(
							"OK"	=> "/documents",
							"ERROR"	=> "/documents" 
							),
			"changeStatusObjects"	=> 	array(
							"OK"	=> "/documents",
							"ERROR"	=> "/documents" 
							)
		);
		if(isset($REDIRECT[$action][$esito])) return $REDIRECT[$action][$esito] ;
		return false ;
	}
}	

?>