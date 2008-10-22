<?php
/*-----8<--------------------------------------------------------------------
 * 
 * BEdita - a semantic content management framework
 * 
 * Copyright 2008 ChannelWeb Srl, Chialab Srl
 * 
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the Affero GNU General Public License as published 
 * by the Free Software Foundation, either version 3 of the License, or 
 * (at your option) any later version.
 * BEdita is distributed WITHOUT ANY WARRANTY; without even the implied 
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the Affero GNU General Public License for more details.
 * You should have received a copy of the Affero GNU General Public License 
 * version 3 along with BEdita (see LICENSE.AGPL).
 * If not, see <http://gnu.org/licenses/agpl-3.0.html>.
 * 
 *------------------------------------------------------------------->8-----
 */

/**
 * Componente per la gestione dell'upload dei file, salvataggio, modifica, delete
 * e interfaccia ai file remoti.
 * I file vanno manipolati utilizzando il componente Transaction....
 * 
 * Dati da passare per salvare/modificare un oggetto co n un file:
 * 
 * 		path		Indica dove il file temporaneo con i dati
 * 					o l'URL dove risiede il file.
 * 		name		Nome del file originale
 * 		type		MIME type, se assente cerca di ricavarlo dal nome file o dall'intestazione (@todo)
 * 		size		Dimensione del file se un URL tenta di leggerla da remoto 
 *  
 * Se le operazioni di salvataggio e cancellazione vanno fate utilizzando questo componente:
 * - Gestisce i file in modo transazionale (modifiche definitive con un $Transaction->commit() )
 * - Esegue il controllo di tipo (MIME) e crea un oggetto di tipo corretto
 * - Per gli URL esegue un controllo (regex) sull'URL
 * - torna in modo corretto e trasparente l'URL al file
 * - Torna le seguenti eccezioni:
 * 		BEditaFileExistException		// File gia' presente nel sistema sistema - nella creazione
 * 		BEditaInfoException				// Informazioni del file non accessibili
 * 		BEditaMIMEException				// MIME type del file non trovato o non corrispondente al tipo di obj
 * 		BEditaURLRxception				// Violazione regole dell'URL
 * 		BEditaSaveStreamObjException	// Errore creazione/ modifica oggetto 
 * 		BEditaDeleteStreamObjException	// Errore cancellazione obj
 * 
 * Se paranoid == false. Non tenta di prelevare le informazioni da remoto e quindi non serve
 * 'allow_php_fopen'. Le informazioni di MIME devono essere passate con i dati per gli URL.
 * 
 * File paths saved on DB are relative to $config['mediaRoot']
 * 
 * @link			http://www.bedita.com
 * @version			$Revision$
 * @modifiedby 		$LastChangedBy$
 * @lastmodified	$LastChangedDate$
 * 
 * $Id$
 */
class BeFileHandlerComponent extends Object {

	var $uses 		= array('BEObject', 'Stream', 'BEFile', 'Image', 'Audio', 'Video') ;
	var $components = array('Transaction');
	var $paranoid 	= true ;
	
	// Errors on save
	var $validateErrors = false ;

	function __construct() {
		foreach ($this->uses as $model) {
			if(!class_exists($model))
				App::import('Model', $model) ;
			$this->{$model} = new $model() ;
		}
		foreach ($this->components as $component) {
			if(isset($this->{$component})) continue;
			$className = $component . 'Component' ;
			if(!class_exists($className))
				App::import('Component', $component);
			$this->{$component} = new $className() ;
		}
	} 

	function startup(&$controller)
	{
		$conf = Configure::getInstance() ;
		$this->controller 	= $controller;
		if(isset($conf->validate_resorce['paranoid'])) $this->paranoid  = (boolean) $conf->validate_resorce['paranoid'] ;
	}

	/**
	 * Save object $data
	 * If $data['id'] modify otherwise create
	 * If file is already present, throw an exception.
	 * File data:
	 * 	path: local path or URL (\.+//:\.+) [remote file]
	 * 			if "allow_url_fopen" is not activated, remote file is not accepted
	 * name		Name of file. Empty if path == URL
	 * type		MIME type. Empty if path == URL
	 * size		File size. Empty if path == URL
	 *
	 * @param array $dati	object data
	 * @param string $model	Create object of specified type, otherwise use MIME type
	 *
	 * @return integer or false (id of the object created or modified)
	 */
	function save(&$dati, $model = null) {
		
		if(isset($dati['id']) && !empty($dati['id'])) { // modify
			if(!isset($dati['path']) || @empty($dati['path'])) {
				return $this->_modify($dati['id'], $dati) ;
			} else if($this->_isURL($dati['path'])) {
				return $this->_modifyFromURL($dati['id'], $dati) ;
			} else {
				return $this->_modifyFromFile($dati['id'], $dati) ;
			}
		} else { // create
			return ($this->_isURL($dati['path'])) ? $this->_createFromURL($dati, $model) : $this->_createFromFile($dati, $model);
		}
	}	

	/**
	 * Delete object
	 * @param integer $id	object id
	 */
	function del($id) {
		if(!($path = $this->Stream->read("path", $id))) return true ;
		$path = (isset($path['Stream']['path']))?$path['Stream']['path']:$path ;
		// If file path is local, delete
		if(!$this->_isURL($path)) {
			if(!$this->Transaction->rm(Configure::read("mediaRoot").$path)) return false ;
		}
		$model = $this->BEObject->getType($id) ;
		if(!class_exists($model)) {
			loadModel($model) ;
		}
		$mod = new $model() ;
	 	if(!$mod->del($id)) {
			throw new BEditaDeleteStreamObjException(__("Error deleting stream object",true)) ;	
	 	}
	 	return true ;
	}

	/**
	 * Return URL of file object
	 * @param integer $id	object id
	 */
	function url($id) {
		if(!($ret = $this->Stream->read("path", $id))) return false ;
		$path = $ret['Stream']['path'] ;
		return ($this->_isURL($path)) ? $path : (Configure::read("mediaUrl").$path);
	}

	/**
	 * Return object path, URL if remote file
	 * @param integer $id	object id
	 */
	function path($id) {
		if(!($ret = $this->Stream->read("path", $id))) return false ;
		$path = $ret['Stream']['path'] ;
		return ($this->_isURL($path)) ? $path : (Configure::read("mediaUrl").$path);
	}

	/**
	 * Return object id (object that contains file $path)
	 * @param string $path	File name or URL
	 * @todo VERIFY
	 */
	function isPresent($path, $id = null) {
		if(!$this->_isURL($path)) {
			$path = $this->_getPathTargetFile($path);
		}
		$clausoles = array() ;
		$clausoles[] = array("path" => trim($path)) ;
		if(isset($id)) $clausoles[] = array("id " => "not {$id}") ;
		$ret = $this->Stream->find($clausoles, 'id') ;
		if(!count($ret)) return false ;
				
		return $ret['Stream']['id'] ;
	}

	////////////////////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////////////////////
	
	private function _createFromURL(&$dati, $model = null) {
		if(!isset($dati['path'])) return false ;
		
		// URL accettabile
		if(!$this->_regularURL($dati['path'])) throw new BEditaURLException(__("URL not valid",true)) ;

		if($this->paranoid) {
			// Permesso di usare file remoti
			if(!ini_get('allow_url_fopen')) throw  new BEditaAllowURLException(__("You can't use remote file",true)) ;
			
			// Preleva MIME type e dimensioni
			if(!$this->_getInfoURL($dati['path'], $dati)) throw new BEditaInfoException() ;
		}
			
		// Il file/URL non deve essere presente
		if($this->_isPresent($dati['path'])) throw new BEditaFileExistException(__("File already exists in the filesystem",true)) ;
		
		return $this->_create($dati, $model) ;
	}

	private function _createFromFile(&$dati, $model = null) {
		if(!isset($dati['path'])) return false ;
		// Create destination path
		$sourcePath = $dati['path'] ;
		$targetPath	= $this->_getPathTargetFile($dati['name']); 
		// File should not exist
		if($this->_isPresent($targetPath)) {
			throw new BEditaFileExistException(__("File already exists in the filesystem",true)) ;
		}
		// Create file
		if(!$this->_putFile($sourcePath, $targetPath)) return false ;
		$dati['path'] = $targetPath ;
		// Create object
		return $this->_create($dati, $model) ;
	}

	private function _create(&$dati, $model = null) {
		$model = false ;
		$modelType = $this->_getTypeFromMIME($dati['mime_type'], $model);
		switch($modelType) {
			case 'BEFile':
				$model = 'BEFile' ;
				break ;
			case 'Image':
				$model = 'Image' ;
				if ( $imageSize =@ getimagesize(Configure::read("mediaRoot") . $dati['path']) )
				{
					if (!empty($imageSize[0]))
						$dati["width"] = $imageSize[0];
					if (!empty($imageSize[1]))
						$dati["height"] = $imageSize[1];
				}
				break ;
			case 'Audio':
				$model = 'Audio' ; 
				break ;
			case 'Video':
				$model = 'Video' ; 
				break ;
			default:
				throw new BEditaMIMEException(__("MIME type not found",true).": ".$dati['mime_type'].
					" - matches: ".$modelType) ;
		}
		$this->{$model}->id = false ;
		
		if(!($ret = $this->{$model}->save($dati))) {
			throw new BEditaSaveStreamObjException(__("Error saving stream object",true), $this->{$model}->validationErrors) ;
		}
		return ($this->{$model}->{$this->{$model}->primaryKey}) ;
	}

	private function _modifyFromURL($id, &$dati) {
		// URL accettabile
		if(!$this->_regularURL($dati['path'])) 
			throw new BEditaURLException(__("URL not valid",true)) ;
			
		if($this->paranoid) {
			// Permesso di usare file remoti
			if(!ini_get('allow_url_fopen')) throw  new BEditaAllowURLException(__("You can't use remote file",true)) ;
			
			// Preleva MIME type e dimensioni
			if(!$this->_getInfoURL($dati['path'], $dati)) throw new BEditaInfoException() ;
		}
	
		// se il file e' presente in un altro oggetto torna un eccezione
		if($this->_isPresent($dati['path'], $id)) throw new BEditaFileExistException(__("File is already associated to another object",true)) ;
		
		// Se e' presente un path ad file su file system, cancella
		if(($ret = $this->Stream->read('path', $id) && !$this->_isURL($ret['path']))) {
			$this->_removeFile($ret['path']) ;		
		}
		
		return $this->_modify($id, $dati) ;
	}

	private function _modifyFromFile($id, &$dati) {
		$sourcePath = $dati['path'] ;
		$targetPath	= $this->_getPathTargetFile($dati['name']); 
		
		// se il file e' presente in un altro oggetto torna un eccezione
		if($this->_isPresent($targetPath, $id)) 
			throw new BEditaFileExistException(__("File is already associated to another object",true)) ;
		
		$ret = $this->Stream->read('path', $id);
			
		// Se e' presente un path ad file su file system, cancella
		if(($ret && !$this->_isURL($ret['Stream']['path']))) {
			$this->_removeFile($ret['Stream']['path']) ;		
		}
		
		// Crea il file
		if(!$this->_putFile($sourcePath, $targetPath)) return false ;
		$dati['path'] = $targetPath ;

		return $this->_modify($id, $dati) ;
	}
	
	private function _modify($id, &$dati) {
		$conf = Configure::getInstance() ;

		$ret = $this->Stream->read('mime_type', $id) ;
			
		if(empty($ret['Stream']['mime_type'])) 
			throw new BEditaMIMEException(__("MIME type of previous file isn't defined in database. Impossible replace it.", true)) ;
		
		// Preleva il tipo di oggetto da salvare e salva
		$rec = $this->BEObject->recursive ;
		$this->BEObject->recursive = -1 ;
		if(!($ret = $this->BEObject->read('object_type_id', $id)))  
			throw new BEditaMIMEException(__("MIME type not found", true)) ;
		$this->BEObject->recursive = $rec ;
		$model = $conf->objectTypes[$ret['BEObject']['object_type_id']]["model"] ;
		
		if (!$this->_getTypeFromMIME($dati["type"], $model))
			throw new BEditaMIMEException(__("MIME type (" . $dati["type"] . ") is not compatible with " . $model . " object", true)) ;
		
		$this->{$model}->id =  $id ;
		if(!($ret = $this->{$model}->save($dati))) {
			throw new BEditaSaveStreamObjException(__("Error saving stream object",true)) ;
		}
		
		return ($this->{$model}->{$this->{$model}->primaryKey}) ;
	}	
	
	/**
	 * Torna TRUE se il path e' un URL
	 *
	 * @param unknown_type $path
	 */
	private function _isURL($path) {
		$conf 		= Configure::getInstance() ;
		
		if(preg_match($conf->validate_resorce['URL'], $path)) return true ;
		else return false ;
	}

	/**
	 * Torna true se l'URL supera le regole definite in configurazione
	 */
	private function _regularURL($URL) {
		$conf 		= Configure::getInstance() ;
		
		foreach ($conf->validate_resorce['allow'] as $reg) {
			if(preg_match($reg, $URL)) return true ;
		}

		return false ;	
	}
			
	/**
	 * Torna il nome del model a cui MIME corrisponde
	 *
	 * @param string $mime	MIME  tyep da cercare 
	 * @param string $model	Se presente verifica se puo' tornare il tipo di oggetto dato
	 */
	private function _getTypeFromMIME($mime, $model = null) {
		$conf 		= Configure::getInstance() ;
		if(@empty($mime))	
			return false ;
		if(isset($model) && isset($conf->validate_resorce['mime'][$model] )) {
			$regs = $conf->validate_resorce['mime'][$model] ;
			foreach ($regs as $reg) {
				if(preg_match($reg, $mime)) 
					return $model ;
			}
		} else {
			$models = $conf->validate_resorce['mime'] ;
			foreach ($models as $model => $regs) {
				foreach ($regs as $reg) {
					if(preg_match($reg, $mime)) 
						return $model ;
				}
			}
		}
		return false ;
	}

	function getInfoURL($path, &$dati) {
		return $this->_getInfoURL($path, $dati) ;
	}
	
	/**
	 * Preleva il MIME type e le dimensioni da un URL remoto e il nome del file
	 */
	private function _getInfoURL($path, &$dati) {		
		if(!(isset($dati['name']) && !empty($dati['name']))) {
			$dati['name']  = basename($path) ;
		}
		
		/**
		 * Preleva il MIME type
		 */
		if(!(isset($dati['mime_type']) && !empty($dati['mime_type']))) {			
			// Cerca tramite l'estensione del path
			$dati['mime_type']= $this->_mimeByFInfo($path) ;
			
			if(!(isset($dati['mime_type']) && !empty($dati['mime_type']))) {
				if(!@empty($dati['name'])) {
					$extension = pathinfo($dati['name'], PATHINFO_EXTENSION);
				} else {
					$extension = pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION);
				}					
				if(@empty($extension)) return false ;
				$dati['mime_type']= $this->_mimeByExtension($extension) ;						

			}
			
			if(!(isset($dati['mime_type']) && !empty($dati['mime_type']))) {
				// Cerca tramite implementazione ricerca in magic
				$magic 			= new MimeByMagic() ;
				$dati['mime_type']	= $magic->getMime($path) ;
			}
		}
		
		if(!(isset($dati['size']) && !empty($dati['size']))) {
			// Preleva le dimensioni del file
			if(($info = @stat($path))) {
				$dati['size'] = $info[7] ;
			}
		}
		
		return $dati['mime_type'] ;
	}
	
	/**
	 * Crea target con source (file temporaneo) con l'oggetto transazionale
	 *
	 * @param string $sourcePath
	 * @param string $targetPath
	 */
	private function _putFile($sourcePath, $targetPath) {
		if(@empty($targetPath)) return false ;
		
		// Determina quali directory creare per registrare il file
		$tmp = Configure::read("mediaRoot") . $targetPath ;
		$stack = array() ;
		$dir = dirname($tmp) ;
		
		while($dir != Configure::read("mediaRoot")) {
			if(is_dir($dir)) break ;
			
			array_push($stack, $dir) ;
			
			$dir = dirname($dir) ;
		} 
		unset($dir) ;
		
		// Crea le directory non ancora presenti
		while(($current = array_pop($stack))) {
			if(!$this->Transaction->mkdir($current)) return false ;
		}
		
		return $this->Transaction->makeFromFile($tmp, $sourcePath) ;
	}	

	/**
	 * Cancella un file da file system con l'oggetto transazionale
	 *
	 * @param string $path
	 */
	private function _removeFile($path) {
		$path = Configure::read("mediaRoot") . $path ;
		
		// Cancella
		if(!$this->Transaction->rm($path))
			return false ;
		
		// Se la directory contenitore e' vuota, la cancella
		$dir = dirname($path) ;
		while($dir != Configure::read("mediaRoot")) {
			// Verifica che sia vuota
			$vuota = true ;
			if($handle = opendir($dir)) {
			    while (false !== ($file = readdir($handle))) {
        			if ($file != "." && $file != "..") {
        				$vuota = false ;
		            	break ;
		        	}
    			}
    			closedir($handle);				
			}
			
			// Se vuota cancella altrimenti interrompe
			if($vuota) {
				if(!$this->Transaction->rmdir($dir))
					return false ;
			}else {
				break ;
			}
			
			$dir = dirname($dir) ;
		} 

		return true ;
	}

	/**
	 * Torna TRUE e' gia' presente ad eccezione dell'oggetto indicato in id
	 *
	 * @param string $path
	 * @param intger $id
	 */
	private function _isPresent($path, $id = null) {
		
		$clausoles = array() ;
		$clausoles[] = array("path" => trim($path)) ;
		if(isset($id)) $clausoles[] = array("id" => "not {$id}") ;
		
		$ret = $this->Stream->find($clausoles, 'id') ;
		
		return ((is_array($ret))?((boolean)count($ret)):false) ;
	}
	
	private function _mimeByExtension($ext) {
		$conf 		= Configure::getInstance() ;
		$lines = file($conf->validate_resorce['mime.types']) ;
		foreach($lines as $line) {
			if(preg_match('/^([^#]\S+)\s+.*'.strtolower($ext).'.*$/',$line,$m)) {
				return $m[1];
			}
		}
		return false ;
	}

	private function _mimeByFInfo($file) {
		if(!function_exists("finfo_open")) return false ;
		$conf 	= Configure::getInstance() ;
		$finfo 	= finfo_open(FILEINFO_MIME, $conf->validate_resorce['magic']); // return mime type alla mimetype extension
		if (!$finfo) return false ;
		$mime = finfo_file($finfo, $file);
		finfo_close($finfo);
		return $mime ;
	}

  	/**
  	 * Torna il path dove inserire il file uploadato
  	 *
  	 * @param string $name 	Nome del file
  	 */
	function _getPathTargetFile($name)  {
   		$conf 		= Configure::getInstance() ;
		
   		// Determina le directory dove salvare il file
		$md5 = md5($name) ;
		preg_match("/(\w{2,2})(\w{2,2})(\w{2,2})(\w{2,2})/", $md5, $dirs) ;
		array_shift($dirs) ;
		$path =  DS . implode(DS, $dirs) . DS . $name ;
		
		return $path ;
	}
   
} ;


////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Implementa la funzione di cercare il MIME type tramite il magic file dove
 * non e' presente l'estensio FINFO di PHP.
 * 
 * @todo All
 *
 */
class MimeByMagic {
	function __construction() {
		
	}
	
	function getMime($path) {
		
		return "application/octet-stream" ;
	}
} ;

////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * 		BEditaIOException		// Generic I/O Error
 */
class BEditaIOException extends BeditaException
{
} ;

/**
 * 		BEditaAllowURLException		// Non � permesso l'uso di file remoti
 */
class BEditaAllowURLException extends BeditaException
{
} ;

/**
 * 		BEditaFileExistException		// File gia' presente in sistema - nella creazione
 */
class BEditaFileExistException extends BeditaException
{
}

/**
 * 		BEditaMIMEException				// MIME type del file non trovato o non corrispondente al tipo di obj
 */
class BEditaMIMEException extends BeditaException
{
}

/**
 * 		BEditaURLException				// Violazione regole dell'URL
 */
class BEditaURLException extends BeditaException
{
} ;

/**
 * 		BEditaInfoException				// Informazioni non accessibili
 */
class BEditaInfoException extends BeditaException
{
} ;


class BEditaSaveStreamObjException extends BeditaException
{
} ;

/**
 * 		BEditaDeleteStreamObjException	// Errore cancellazione obj
 */
class BEditaDeleteStreamObjException extends BeditaException
{
}

class BEditaMediaProviderException extends BeditaException
{
}

/**
 * 		BEditaUploadPHPException	// handle php upload errors
 */
class BEditaUploadPHPException extends BeditaException
{
	private $phpError = array(
							UPLOAD_ERR_INI_SIZE		=> "The uploaded file exceeds the upload_max_filesize directive in php.ini",
							UPLOAD_ERR_FORM_SIZE	=> "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
							UPLOAD_ERR_PARTIAL		=> "The uploaded file was only partially uploaded",
							UPLOAD_ERR_NO_FILE		=> "No file was uploaded",
							UPLOAD_ERR_NO_TMP_DIR	=> "Missing a temporary folder",
							UPLOAD_ERR_CANT_WRITE	=> "Failed to write file to disk",
							UPLOAD_ERR_EXTENSION	=> "File upload stopped by extension"
							); 
	
	public function __construct($numberError, $details = NULL, $res  = AppController::ERROR, $code = 0) {
		parent::__construct($this->phpError[$numberError], $details, $res, $code);
	}
}
?>