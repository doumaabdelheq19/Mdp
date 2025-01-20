<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 08/07/2018
 * Time: 17:25
 */

namespace App\Pdf;

use TCPDF;

class MyPdf extends TCPDF
{
    private $user;

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    public function Header() {
        $this->SetY(8);

        $userLogo = "";

        if ($this->getUser()){
            if ($this->getUser()->getPicture()) {
                $userLogo = '<img src="/uploads/pictures/'.$this->getUser()->getPicture().'" style="height: 1cm"/>';
            }
        }

        $html = '<table border="0" cellpadding="0">
                <tr>
                    <td style="width: 50%; text-align: left;">
                        <img src="/bundles/app/assets/img/logo-cropped.png" style="height: 1cm"/>
                    </td>
                    <td style="width: 50%; text-align: right;">
                        '.$userLogo.'
                    </td>
                </tr>
            </table>';

        $this->writeHTML($html, true, false, true, false, '');
    }

    public function Footer() {
        $this->SetY(-15);

        $html = '<table border="0" cellpadding="0"><tr><td style="width: 50%;"><p style="font-size: 8px; color: #224c66; text-align: left;">Document édité par Pilot !</p></td><td style="width: 50%;"><p style="font-size: 8px; color: #224c66; text-align: right;">Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages().'</p></td></tr></table>';

        $this->writeHTML($html, true, false, true, false, '');
    }
}