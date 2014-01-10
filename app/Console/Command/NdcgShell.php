<?php

App::uses('AppShell', 'Console/Command');

class NdcgShell extends AppShell {
	
	public $uses = array('Respondent');

	public function startup() {
		set_time_limit(0);
		parent::startup();
	}
	
	public function main() {
		App::import('Vendor', 'PHPExcel/Classes/PHPExcel');
		
		$objPHPExcel = new PHPExcel();
		$objPHPExcel->setActiveSheetIndex(0);
		$rowCount = 2;
		
		
		$objPHPExcel->getActiveSheet()->SetCellValue('A1', "#Neighbors");
		$objPHPExcel->getActiveSheet()->SetCellValue('B1', "Pearson AVG");
		$objPHPExcel->getActiveSheet()->SetCellValue('C1', "Pearson WA");
		$objPHPExcel->getActiveSheet()->SetCellValue('D1', "Cosine AVG");
		$objPHPExcel->getActiveSheet()->SetCellValue('E1', "Cosine WA");
		$objPHPExcel->getActiveSheet()->SetCellValue('F1', "AdjustedCosine AVG");
		$objPHPExcel->getActiveSheet()->SetCellValue('G1', "AdjustedCosine WA");
		$objPHPExcel->getActiveSheet()->SetCellValue('H1', "Xtreme AVG");
		$objPHPExcel->getActiveSheet()->SetCellValue('I1', "Xtreme WA");
		$objPHPExcel->getActiveSheet()->SetCellValue('J1', "Xtreme AVG +-1");
		$objPHPExcel->getActiveSheet()->SetCellValue('K1', "Xtreme WA +-1");
		
		
		
		for($i=1 ; $i<40; $i = $i+1){
			$objPHPExcel->getActiveSheet()->SetCellValue('A'.$rowCount, $i);
			$objPHPExcel->getActiveSheet()->SetCellValue('B'.$rowCount, $this->Respondent->calculateNDCG("Pearson", $i));
			$objPHPExcel->getActiveSheet()->SetCellValue('C'.$rowCount, $this->Respondent->calculateNDCG("Pearson", $i, 'weightedSum'));
			$objPHPExcel->getActiveSheet()->SetCellValue('D'.$rowCount, $this->Respondent->calculateNDCG("Cosine", $i));
			$objPHPExcel->getActiveSheet()->SetCellValue('E'.$rowCount, $this->Respondent->calculateNDCG("Cosine", $i, 'weightedSum'));
			$objPHPExcel->getActiveSheet()->SetCellValue('F'.$rowCount, $this->Respondent->calculateNDCG("AdjustedCosine", $i));
			$objPHPExcel->getActiveSheet()->SetCellValue('G'.$rowCount, $this->Respondent->calculateNDCG("AdjustedCosine", $i, 'weightedSum'));
			$objPHPExcel->getActiveSheet()->SetCellValue('H'.$rowCount, $this->Respondent->calculateNDCG("Xtreme", $i));
			$objPHPExcel->getActiveSheet()->SetCellValue('I'.$rowCount, $this->Respondent->calculateNDCG("Xtreme", $i, 'weightedSum'));
			$objPHPExcel->getActiveSheet()->SetCellValue('J'.$rowCount, $this->Respondent->calculateNDCG("Xtreme", $i, 'avg', array('offset'=>1)));
			$objPHPExcel->getActiveSheet()->SetCellValue('K'.$rowCount, $this->Respondent->calculateNDCG("Xtreme", $i, 'weightedSum', array('offset'=>1)));
		
			$this->out("Neigbors $i completed.");
			$rowCount++;
				
			//	echo($this->Respondent->calculateNDCG("Pearson", $i));
			//	echo "<br />";
		}
		
		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
		$objWriter->save('some_excel_file.xlsx');
		/*
		 echo "AdjustedCosine <br />";
		for($i=5 ; $i<100; $i = $i+5){
		echo($this->Respondent->calculateNDCG("AdjustedCosine", $i));
		echo "<br />";
		}
		*/
		$this->out('Completed.');
	}
}

?>