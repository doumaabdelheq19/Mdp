<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 23/08/2018
 * Time: 19:11
 */

namespace App\Libraries;

use setasign\Fpdi\Fpdi;

class Pdf_concat extends FPDI {
    var $files = array();

    function setFiles($files) {
        $this->files = $files;
    }

    function concat() {
        foreach($this->files AS $file) {
            $pagecount = $this->setSourceFile($file);
            for ($i = 1; $i <= $pagecount; $i++) {
                $tplidx = $this->ImportPage($i);
                $s = $this->getTemplatesize($tplidx);
                $this->AddPage();
                $this->useTemplate($tplidx);
            }
        }
    }
}