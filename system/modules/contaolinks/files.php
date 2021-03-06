<?php

#copyright


/**
 * Initialize system
 */
define('TL_MODE', 'FE');
require('../../initialize.php');


/**
 * Class contaolinks
 */
class ContaoFiles extends ContaoLinksLib
{
	public function __construct()
	{
		parent::__construct();
		$this->import('Input');
		if (in_array('Thumbnify', $this->Config->getActiveModules()))
		{
			$this->import('Thumbnify');
		}
		else
		{
			$this->Thumbnify = false;
		}
	}
	
	
	public function run()
	{
		header('Content-Type: application/json');
		
		$strPreview = urldecode($this->Input->get('preview'));
		if ($strPreview) {
			$arrData = array();
			if (is_file(TL_ROOT . '/' . $strPreview))
			{
				$objFile = new File($strPreview);
				if ($this->Thumbnify && $this->Thumbnify->isThumbSupported($strPreview) || $objFile->isGdImage)
				{
					$arrData['src'] = $strPreview;
					$arrData['thumb']['src'] = $this->Thumbnify && $this->Thumbnify->isThumbSupported($strPreview)
						? $this->Thumbnify->getThumb($strPreview, 240, 180)
						: $this->getImage($strPreview, 240, 180);
					
					if ($arrData['thumb']['src'])
					{
						$objFile = new File($arrData['thumb']['src']);
						$arrData['thumb']['width'] = $objFile->width;
						$arrData['thumb']['height'] = $objFile->height;
					}
					
					if ($objFile->isGdImage)
					{
						$arrData['width']  = $objFile->width;
						$arrData['height'] = $objFile->height;
					}
				}
			}
			echo json_encode(count($arrData) ? $arrData : false);
			exit;
		}
		
		$strPath = urldecode($this->Input->get('path'));
		if (!$strPath)
		{
			if ($this->User->isAdmin)
			{
				$strPath = $GLOBALS['TL_CONFIG']['uploadPath'];
			}
			else
			{
				// TODO
			}
		}
		
		$strSelectedPath = urldecode($this->Input->get('selectedPath'));

		echo json_encode($this->getFileList($strPath, $strSelectedPath));
	}
	
	
	/**
	 * Get a list of files.
	 * 
	 * @param string $strPath
	 * @param string $strSelectedPath
	 * @return
	 */
	public function getFileList($strPath, $strSelectedPath)
	{
		$session = $this->Session->getData();
		
		if ($this->User->isAdmin)
		{
			// pass access
		}
		else
		{
			// TODO
		}
		
		if (!is_dir(TL_ROOT . '/' . $strPath))
		{
			return array();
		}

		$arrScan = scandir(TL_ROOT . '/' . $strPath);
		usort($arrScan, 'strcasecmp');
		$arrFiles = array();
		foreach ($arrScan as $strFile)
		{
			if ($strFile == '.' || $strFile == '..' || $strFile == '.htaccess')
				continue;
			
			$strRelative = $strPath . '/' . $strFile;
			$strAbsolute = TL_ROOT . '/' . $strRelative;
			$blnIsDir = is_dir($strAbsolute);
			
			if ($blnIsDir)
			{
				$blnPreview = false;
				$strOpenIcon = $this->getIconSrc('folderO');
				$strCloseIcon = $this->getIconSrc('folderC');
			}
			else
			{
				$objFile = new File($strRelative);
				$blnPreview = $this->Thumbnify && $this->Thumbnify->isThumbSupported($strRelative) || $objFile->isGdImage;
				$strOpenIcon = $strCloseIcon = $this->extendIconSrc($objFile->icon);
			}
			
			$arrFile = array
			(
				'property' => array
				(
					'path' => $strRelative,
					'name' => $strFile,
					'loadable' => $blnIsDir,
					'openIconUrl' => $strOpenIcon,
					'closeIconUrl' => $strCloseIcon,
					'preview' => $blnPreview
				),
				'type' => $blnIsDir ? 'folder' : 'file'
			);
			if ($strRelative == $strSelectedPath)
			{
				$arrFile['state']['selected'] = true;
			}
			else if ($blnIsDir && substr($strSelectedPath, 0, strlen($strRelative)+1) == ($strRelative . '/'))
			{
				$arrFile['children'] = $this->getFileList($strRelative, $strSelectedPath);
				$arrFile['state']['open'] = true;
			}
			$arrFiles[] = $arrFile;
		}
		return $arrFiles;
	}
}

$objContaoFiles = new ContaoFiles();
$objContaoFiles->run();

?>
