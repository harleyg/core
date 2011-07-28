<?php
$this->Report->squashFields($squashed);
$this->Report->headerAliases($aliases);
$this->Csv->addRow($this->Report->createHeaders($models));

$rows = $this->Report->getResults($results);
foreach ($rows as $row) {
	$this->Csv->addRow($row);
}

echo $this->Csv->render(false);

?>