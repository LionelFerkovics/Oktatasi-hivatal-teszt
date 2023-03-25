<?php

require_once __DIR__ . '/vendor/autoload.php';

require_once 'src/input.php';
use App\Pontszamolo;

$exampleDataArray = [$exampleData, $exampleData2, $exampleData3, $exampleData4];

for ($i = 0; $i < count($exampleDataArray); $i++) {
    $exampleData = $exampleDataArray[$i];
    try {
        $pontszamolo = new Pontszamolo($exampleData);
        $pontszam = $pontszamolo->calculateTotalPoints();
        if ($pontszam != 0) {
            echo "A(z) " . ($i + 1) . ". példa pontszáma: " . $pontszam . "\n<br>";
        }
    } catch (Exception $e) {
        echo "Hiba a(z) " . ($i + 1) . ". példa pontszámításában: " . $e->getMessage() . "\n";
    }
}