<?php

namespace App;

class Pontszamolo
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    private function calculateAlapPontok(): int
    {
        $alapPontok = 0;

        $kovetelmenyek = [
            'ELTE' => [
                'IK' => [
                    'Programtervező informatikus' => [
                        'kovetelmeny' => ['matematika emelt'],
                        'kovetelmeny_valaszthato' => ['biológia', 'fizika', 'informatika', 'kémia'],
                    ],
                ],
            ],
            'PPKE' => [
                'BTK' => [
                    'Anglisztika' => [
                        'kovetelmeny' => ['angol emelt'],
                        'kovetelmeny_valaszthato' => ['francia', 'német', 'olasz', 'orosz', 'spanyol', 'történelem'],
                    ],
                ],
            ],
        ];

        $valasztottSzak = $this->data['valasztott-szak'];
        $szakKovetelmenyek = $kovetelmenyek[$valasztottSzak['egyetem']][$valasztottSzak['kar']][$valasztottSzak['szak']];

        $kovetelmenyPontok = [];
        $valaszthatoPontok = [];

        foreach ($this->data['erettsegi-eredmenyek'] as $eredmeny) {
            $eredmenySzazalek = intval(str_replace('%', '', $eredmeny['eredmeny']));
            $nevTipus = $eredmeny['nev'] . ($eredmeny['tipus'] == 'emelt' ? ' emelt' : '');

            if (in_array($nevTipus, $szakKovetelmenyek['kovetelmeny'])) {
                if (!isset($kovetelmenyPontok[$nevTipus])) {
                    $kovetelmenyPontok[$nevTipus] = 0;
                }
                $kovetelmenyPontok[$nevTipus] = max($kovetelmenyPontok[$nevTipus], $eredmenySzazalek);
            } elseif (in_array($eredmeny['nev'], $szakKovetelmenyek['kovetelmeny_valaszthato'])) {
                if (!isset($valaszthatoPontok[$eredmeny['nev']])) {
                    $valaszthatoPontok[$eredmeny['nev']] = 0;
                }
                $valaszthatoPontok[$eredmeny['nev']] = max($valaszthatoPontok[$eredmeny['nev']], $eredmenySzazalek);
            }
        }
        foreach ($szakKovetelmenyek['kovetelmeny'] as $kovetelmeny) {
            if (isset($kovetelmenyPontok[$kovetelmeny])) {
                $alapPontok += $kovetelmenyPontok[$kovetelmeny];
            } else {
                throw new \Exception("Hiányzik a kötelező érettségi eredmény a(z) {$kovetelmeny} tárgyból.<br>");
            }
        }

        $legjobbValaszthatoPont = 0;

        foreach ($valaszthatoPontok as $pont) {
            $legjobbValaszthatoPont = max($legjobbValaszthatoPont, $pont);
        }

        $alapPontok += $legjobbValaszthatoPont;
        $alapPontok *= 2;
        foreach (['magyar nyelv és irodalom', 'történelem', 'matematika'] as $kovetelmeny) {
            $eredmenyTalalt = false;
            $eredmenySzazalek = null;

            foreach ($this->data['erettsegi-eredmenyek'] as $eredmeny) {
                if ($eredmeny['nev'] == $kovetelmeny) {
                    $eredmenySzazalek = intval(str_replace('%', '', $eredmeny['eredmeny']));
                    if ($eredmenySzazalek >= 20) {
                        $eredmenyTalalt = true;
                        break;
                    }
                }
            }

            if (!$eredmenyTalalt) {
                if ($eredmenySzazalek === null) {
                    throw new \Exception("Nem lehetséges a pontszámítás, mert hiányzik a kötelező érettségi eredmény a(z) {$kovetelmeny} tárgyból.<br>");
                } else {
                    throw new \Exception("Nem lehetséges a pontszámítás, mert a(z) {$kovetelmeny} tárgyból elért eredménye 20% alatt van.<br>");
                }
            }
        }

        return $alapPontok;
    }

    private function calculateTobbletPontok(): int
    {
        $tobbletPontok = 0;

        $nyelvvizsgaPontok = [];

        foreach ($this->data['tobbletpontok'] as $tobbletPont) {
            if ($tobbletPont['kategoria'] == 'Nyelvvizsga') {
                if (!isset($nyelvvizsgaPontok[$tobbletPont['nyelv']])) {
                    $nyelvvizsgaPontok[$tobbletPont['nyelv']] = 0;
                }
                if ($tobbletPont['tipus'] == 'B2') {
                    $nyelvvizsgaPontok[$tobbletPont['nyelv']] = max($nyelvvizsgaPontok[$tobbletPont['nyelv']], 28);
                } elseif ($tobbletPont['tipus'] == 'C1') {
                    $nyelvvizsgaPontok[$tobbletPont['nyelv']] = max($nyelvvizsgaPontok[$tobbletPont['nyelv']], 40);
                }
            }
        }

        foreach ($this->data['erettsegi-eredmenyek'] as $eredmeny) {
            if ($eredmeny['tipus'] == 'emelt') {
                $tobbletPontok += 50;
            }
        }

        foreach ($nyelvvizsgaPontok as $pont) {
            $tobbletPontok += $pont;
        }

        if ($tobbletPontok > 100) {
            $tobbletPontok = 100;
        }

        return $tobbletPontok;
    }

    public function calculateTotalPoints(): int
    {
        try {
            $alapPontok = $this->calculateAlapPontok();
            $tobbletPontok = $this->calculateTobbletPontok();
        } catch (\Exception $e) {
            echo $e->getMessage();
            return 0;
        }
        $totalPoints = $alapPontok + $tobbletPontok;
        return $totalPoints;
    }

}