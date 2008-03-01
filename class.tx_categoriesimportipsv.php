<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Mads Brunn (mads@brunn.dk)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_t3lib.'class.t3lib_extobjbase.php');
require_once(t3lib_extMgm::extPath('categoriesimportipsv').'xmlparser.php');
require_once(PATH_t3lib."class.t3lib_tcemain.php");
$LANG->includeLLFile('EXT:categoriesimportipsv/locallang.xml');

		//$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE tx_categories');
		//$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE tx_categories_mm');
		//$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE tx_categories_related_category_mm');


class tx_categoriesimportipsv extends t3lib_extobjbase {
	

	function modMenu()	{
		global $LANG;
		
		$modMenu = array(
			'tx_categoriesimportipsv_filepath' => 'EXT:categoriesimportipsv/ipsvhierarchy.xml',
			'tx_categoriesimportipsv_origidprefix' => 'ipsv_',			
			'tx_categoriesimportipsv_action' => array(
				'import' => 'Import',
				'update' => 'Update',
			),			
		);
		
		$this->pObj->modMenu_setDefaultList = 'tx_categoriesimportipsv_filepath,tx_categoriesimportipsv_origidprefix';
		
		return $modMenu;
	}

	
	/**
	 * Creation of the main content. 
	 *
	 * @return	string		The content
	 * @see t3lib_extobjbase::extObjContent()
	 */
	function main()	{
		global $SOBE,$LANG;
		$out = array();
		
		$GLOBALS['TYPO3_DB']->debugOutput = TRUE;
		
		if(t3lib_div::_GP('import')){
			$this->doImport();
		}
		
		
		$TDparams = ' class="bgColor4" valign="top"';
		$out = t3lib_BEfunc::cshItem('_MOD_txcategoriesimportipsv_modfunc', 'whatistheipsv', $this->pObj->doc->backPath,'|'.$LANG->getLL('whatistheipsv', 1)).'
		<table border="0" cellpadding="3" cellspacing="1" width="100%">
			<tr>
		 		<td'.$TDparams.'>Path to csv-file: '.t3lib_BEfunc::cshItem('_MOD_txcategoriesimportipsv_modfunc', 'pathtocsvfile', $this->pObj->doc->backPath).'</td>
		 		<td'.$TDparams.'><input type="text" name="SET[tx_categoriesimportipsv_filepath]" size="60" value="'.$this->pObj->MOD_SETTINGS['tx_categoriesimportipsv_filepath'].'" /></td>
		 	</tr>
			<tr>
				<td'.$TDparams.'>Prefix original id: '.t3lib_BEfunc::cshItem('_MOD_txcategoriesimportipsv_modfunc', 'prefixoriginalid', $this->pObj->doc->backPath).'</td>
				<td'.$TDparams.'><input type="text" name="SET[tx_categoriesimportipsv_origidprefix]" size="20" value="'.$this->pObj->MOD_SETTINGS['tx_categoriesimportipsv_origidprefix'].'" /></td>
			</tr>
			<tr>
				<td'.$TDparams.'>Action: '.t3lib_BEfunc::cshItem('_MOD_txcategoriesimportipsv_modfunc', 'action', $this->pObj->doc->backPath).'</td>
				<td'.$TDparams.'>'.t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[tx_categoriesimportipsv_action]',$this->pObj->MOD_SETTINGS['tx_categoriesimportipsv_action'],$this->pObj->MOD_MENU['tx_categoriesimportipsv_action']).'</td>
			</tr>
			<tr>
				<td'.$TDparams.'>&nbsp;</td>
				<td'.$TDparams.'><input type="submit" name="import" value="Import" onclick="alert('.$LANG->JScharCode('Notice! This script will run for a very long time and in some situations it may even halt or cause the browser to freeze. If you experience any problems try running it from a shell (see manual)').');return confirm('.$LANG->JScharCode('Are you sure? You are about to create many categories').');" /></td>
			</tr>
		</table>
		';
		//(@ini_get("safe_mode") == 'On' || @init_get("safe_mode") === 1) ? TRUE : FALSE;
		return $this->pObj->doc->section($LANG->getLL('subtitle'),$out,0,1);
	}
	
	
	function doImport(){
		
		global $LANG;

		//this disables output from the main import module. All output must be echoed out directly
		$this->pObj->disableOutput();					
		echo $this->pObj->getStartPageHTML();
		flush();

		
		$inputfile = $this->pObj->MOD_SETTINGS['tx_categoriesimportipsv_filepath'];
		$origidprefix = trim($this->pObj->MOD_SETTINGS['tx_categoriesimportipsv_origidprefix']);
		$action = $this->pObj->MOD_SETTINGS['tx_categoriesimportipsv_action'];
		
		if($absfile = t3lib_div::getFileAbsFileName($inputfile)){
			
			if(@is_file($absfile)){

				//fetches the contents of the xml-file and converts to a multidimensional array
				$xml = t3lib_div::getUrl($absfile);
				$data = XML_unserialize($xml);

				if(is_array($data) && isset($data['HierarchicalControlledList'])) {
					
					//notice, this will not work when in safemode
					set_time_limit(0);
					echo $this->pObj->getProgressBarHTML();	
					flush();
					tx_categoriesimportipsv::import($data['HierarchicalControlledList'],$this->pObj->id,$this->pObj->id,$origidprefix,$action);

				} else {
					
					//wrong format
					echo '
					<div class="warningbox">
						ERROR!<br />
						File does not contain a valid format.											
					</div>
					';
				}
			}
		}
		echo '<br /><br /><a href="'.t3lib_div::linkThisScript(array('SET[submodule]'=>'tx_categoriesimportipsv')).'">'.$LANG->getLL('backlink').'</a>';
		echo $this->pObj->getEndPageHTML();			
	}
	
	function import($data,$targetpid=0,$localpid=0,$origidprefix='',$action='import'){

		$counter = 0;			//for the progressbar
		$parentid = $localpid;	//the parent category for those categories which will be imported / updated in this recursion
		$num_elements = 0;		//for the progressbar (number of level 1 nodes)
		
		if(is_array($data['Item'])){
			
			$num_elements = count($data['Item']) / 2;	//only used in the outermost recursion
			
			foreach($data['Item'] as $k => $v){
		
				if(!is_numeric($k)) continue;
				
				$datamap = array();
				
				//let's lookup if this category already exists
				$origid = $origidprefix.trim($data['Item'][$k.' attr']['Id']);
				
				//echo $origid.'<br />';
				
				/*if($parentid == 0){
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'tx_categories.*',
							'tx_categories LEFT JOIN tx_categories_mm mm ON tx_categories.uid=mm.uid_local AND mm.localtable="tx_categories"',
							'tx_categories.orig_id="'.$origid.'" AND mm.uid_foreign IS NULL'
						);
					
				} else {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'tx_categories.*',
							'tx_categories INNER JOIN tx_categories_mm mm ON tx_categories.uid=mm.uid_local AND mm.uid_foreign='.$parentid.' AND mm.localtable="tx_categories"',
							'tx_categories.orig_id="'.$origid.'" AND deleted=0'
						);
				}*/
				
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'tx_categories.*',
						'tx_categories',
						'tx_categories.orig_id="'.$origid.'" AND tx_categories.deleted=0'
					);				
				
				//if the category already exists
				if($cat = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)){

					$datamap['tx_categories'][$cat['uid']] = array(
						'pid' => tx_categories_div::getPid(),
						'title' => trim($v['Name']),
						'description' => trim($v['ScopeNotes']),
						'orig_id' => $origid
					);
					
					//if($parentid > 0){
						
					$tmpres = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'tx_categories_mm.uid_foreign AS uid',
						'tx_categories_mm',
						'tx_categories_mm.uid_local='.$cat['uid']
					);
					
					$cids = array();
					$cids[$parentid] = $parentid;

					while($tmprow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($tmpres)){
						$cids[$tmprow['uid']]=$tmprow['uid'];							
					} 
					
					$datamap['tx_categories'][$cat['uid']]['*'] = implode(",",$cids);
					//}
				
				} else {
					
					$newid = uniqid('NEW');
					$datamap['tx_categories'][$newid] = array(
						'pid' 	=> tx_categories_div::getPid(),
						'title' => trim($v['Name']),
						'description' => trim($v['ScopeNotes']),
						'orig_id' => $origid,
						'*' => $parentid
					);
				}

				$tce = t3lib_div::makeInstance('t3lib_TCEmain');
				$tce->stripslashes_values = 0;
				$tce->storeLogMessages = FALSE;
				$tce->enableLogging = FALSE;
				$tce->checkStoredRecords = FALSE;
				$tce->reverseOrder = 1;
				$tce->start($datamap,array());
				$tce->process_datamap();	
				
				$insertid = $tce->substNEWwithIDs[$newid];				
				
				
				if(isset($v['Item'])){

					$firstkey = key($v['Item']);
					$keyparts = t3lib_div::trimExplode(" ",$firstkey,1);
					$realkey = $keyparts[0];
					if(!is_numeric($realkey)){
						$tmp = array(
							'0' => $v['Item'],
							'0 attr' => $v['Item attr']
						);
						unset($v['Item']);
						unset($v['Item attr']);
						$v['Item'] = $tmp;
					}
					
					$tmp = array( 'Item' => $v['Item'] );
					
					$oldparentid = $parentid;
					$parentid = $insertid;
					tx_categoriesimportipsv::import($tmp,$targetpid,$parentid,$origidprefix,$action);
					$parentid = $oldparentid;
				}


				//Progressbar moves one step for each node tree on level 1 (16 nodes)				
				if($parentid == $targetpid){
					$counter++;
					
					$percentDone = intval (($counter * 100) / $num_elements);
					
					echo '
					<script type="text/javascript">
						document.getElementById(\'progress-bar\').style.width = \''.$percentDone.'%\';
						document.getElementById(\'progress-bar\').style.display = \'block\';';

					if($percentDone < 100){
						echo '
						document.getElementById(\'transparent-bar\').style.width = \''.(100-$percentDone).'%\';';
					} else {
						
						echo '
						document.getElementById(\'transparent-bar\').style.display = \'none\';';

					}
					echo '
						document.getElementById(\'progress-message\').innerHTML = \''.$percentDone.'% completed\';
					</script>
					';
					
					flush();

				}
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/categories_importipsv/class.tx_categoriesimportipsv.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/categories_importipsv/class.tx_categoriesimportipsv.php']);
}



?>