<?php
/**
 * Displays a form for entering the title of a page, which then redirects
 * to the form for creating/editing the page.
 *
 * @author Yaron Koren
 * @author Jeffrey Stuckman
 * @file
 * @ingroup SF
 */

/**
 * @ingroup SFSpecialPages
 */
class NSImportSKOS extends SpecialPage {


  /**
   * Constructor
   */
  function __construct() {
    parent::__construct( 'ImportSKOS' );
  }

  function execute( $query ) {
    global $wgOut, $wgRequest, $nsfgIP;
    $out = $this->getOutput();
    $request = $this->getRequest();
    if ($wgRequest->wasPosted()) {
      foreach ($_FILES as $fileName => $fileData) {
        if ($fileData['error'] == 0) {
         $msgImport = self::execPythonScript ($fileData['tmp_name'],
            ($request->getVal("pagesThatExist")=='overwrite' ? True : False) ,
            $request->getVal("import_summary"),
            $request->getVal("elementToImport-schemes"),
            $request->getVal("elementToImport-collections"), 
            $request->getVal("elementToImport-concepts")
          );
          $text = 'import done';
          $text .= "<script>$( '.msg-import-skos').remove();</script>";
          $text.= '<div class="import-skos" style="background-color: rgba(128, 128, 128, 0.15);">'.$msgImport.'</div>';
        }
      }
    }
    else if ($query) {
    
    }
    else {
      $formText = $formText = self::printFileSelector( 'RDF' );
      
      $formText .= "\t" . '<hr style="margin: 10px 0 10px 0" />' . "\n";
      $formText .= self::printElementToImportSelector();
      $formText .= self::printExistingPagesHandling();
      $formText .= self::printImportSummaryInput( 'CSV' );
      $formText .= self::printSubmitButton();
      $text = "\t" . Xml::tags( 'form',
        array(
          'enctype' => 'multipart/form-data',
          'action' => '',
          'method' => 'post'
        ), $formText ) . "\n";
    }   
  $out->addHTML( $text );
}


  function execPythonScript ($filePath, $pagesThatExist, $import_summary, $elementToImportSchema,$elementToImportCollection,$elementToImportConcept ) {
    #$this->displayForm();
    global  $nsfgIP;
    #', '/tmp/phpA1t5wz', ',', '1', ',', 'Added from ImportMediawikiSKOSTemplatePage', ',', 'scout<script>window.scrollTo(0,99999);</script>hemes', ',', '', ',', '
    $command = "python  $nsfgIP/includes/python_bots/skos_importer_pythonLib.py ";
    $command .= " '$filePath' '$pagesThatExist' '$import_summary' '$elementToImportSchema' '$elementToImportCollection' '$elementToImportConcept' 2>&1";
    echo "<style type='text/css'>
     div.msg-import-skos{
       background:#000;
       color: #7FFF00;
       font-family:'Lucida Console',sans-serif !important;
       font-size: 12px;
     }
     </style>";
    echo '<div class="msg-import-skos">';
    $importMsg = '';
    $pid = popen( $command,"r");
    while( !feof( $pid ) ) {
      $msg = fread($pid, 256);
      $msg = str_replace ("\n", '<br/>', $msg);
      $msg = str_replace ("\r", '', $msg);
      $importMsg.= $msg;
      echo $msg;
      flush();
      ob_flush();
      echo "<script>window.scrollTo(0,99999);</script>";
      usleep(100000);
    }
    echo '</div>';
    pclose($pid);
    return $importMsg;
  }
  
  

	static function printFileSelector( $fileType ) {
		$text = "\n\t" . Xml::element( 'p', null, wfMessage( 'dt_import_selectfile', $fileType )->text() ) . "\n";
		$text .= <<<END
	<p><input type="file" name="file_name" size="25" /></p>
END;
		$text .= "\t" . '<hr style="margin: 10px 0 10px 0" />' . "\n";
		return $text;
	}
  
  static function printElementToImportSelector() {
		$text = "\t" . Xml::element( 'p', null, 'Select element to import' ) . "\n";
		$elementToImportText = "\n\t" .
			Xml::element( 'input',
				array(
          'type'=>'checkbox',
					'name' => 'elementToImport-schemes',
					'value' => 'schemes',
					'checked' => 'checked'
				) ) . "\n" .
			"\t" .'schemes' . "<br />" . "\n" .
			"\t" . Xml::element(  'input',
				array(
          'type'=>'checkbox',
					'name' => 'elementToImport-collections',
					'value' => 'collections',
				) ) . "\n" .
			"\t" . 'collections' . "<br />" . "\n" .
			"\t" . Xml::element(  'input',
				array(
          'type'=>'checkbox',
					'name' => 'elementToImport-concepts',
					'value' => 'concepts',
				) ) . "\n" .
			"\t" .'concepts' . "<br />" . "\n\t";
		$text .= "\t" . Xml::tags( 'p', null, $elementToImportText ) . "\n";
		$text .= "\t" .  '<hr style="margin: 10px 0 10px 0" />' . "\n";
		return $text;
	}
  
	static function printExistingPagesHandling() {
		$text = "\t" . Xml::element( 'p', null, wfMessage( 'dt_import_forexisting' )->text() ) . "\n";
		$existingPagesText = "\n\t" .
			Xml::element( 'input',
				array(
					'type' => 'radio',
					'name' => 'pagesThatExist',
					'value' => 'overwrite',
					'checked' => 'checked'
				) ) . "\n" .
			"\t" . wfMessage( 'dt_import_overwriteexisting' )->text() . "<br />" . "\n" .
			"\t" . Xml::element( 'input',
				array(
					'type' => 'radio',
					'name' => 'pagesThatExist',
					'value' => 'skip',
				) ) . "\n" .
			"\t" . wfMessage( 'dt_import_skipexisting' )->text() . "<br />" . "\n";
			/*"\t" . Xml::element( 'input',
				array(
					'type' => 'radio',
					'name' => 'pagesThatExist',
					'value' => 'append',
				) ) . "\n" .
			"\t" . wfMessage( 'dt_import_appendtoexisting' )->text() . "<br />" . "\n\t";*/
		$text .= "\t" . Xml::tags( 'p', null, $existingPagesText ) . "\n";
		$text .= "\t" .  '<hr style="margin: 10px 0 10px 0" />' . "\n";
		return $text;
	}

	static function printImportSummaryInput( $fileType ) {
		$importSummaryText = "\t" . Xml::element( 'input',
			array(
				'type' => 'text',
				'id' => 'wpSummary', // ID is necessary for CSS formatting
				'class' => 'mw-summary',
				'name' => 'import_summary',
				'value' => 'Added from ImportMediawikiSKOSTemplatePage'
			)
		) . "\n";
		return "\t" . Xml::tags( 'p', null,
			wfMessage( 'dt_import_summarydesc' )->text() . "\n" .
			$importSummaryText ) . "\n";
	}

	static function printSubmitButton() {
		$formSubmitText = Xml::element( 'input',
			array(
				'type' => 'submit',
				'name' => 'import_file',
				'value' => wfMessage( 'import-interwiki-submit' )->text()
			)
		);
		return "\t" . Xml::tags( 'p', null, $formSubmitText ) . "\n";
	}
  
}
