<?php
/**
 * Created by PhpStorm.
 * User: guillaume
 * Date: 04/04/2019
 * Time: 14:45
 */

namespace App\Twig;


use App\Entity\Account;
use App\Entity\Action;
use App\Entity\Company;
use App\Entity\Credit;
use App\Entity\Department;
use App\Entity\Establishment;
use App\Entity\EstablishmentPart;
use App\Entity\Internship;
use App\Entity\LoginLog;
use App\Entity\Manager;
use App\Entity\Offer;
use App\Entity\OfferHasDiploma;
use App\Entity\Partner;
use App\Entity\Recruiter;
use App\Entity\Subscription;
use App\Entity\SupervisedProject;
use App\Entity\Training;
use App\Entity\TrainingCampain;
use App\Entity\TrainingRequest;
use App\Entity\User;
use App\Entity\UserRelationship;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function Sodium\add;

class SlappExtensions extends AbstractExtension
{
    protected $container;
    protected $doctrine;
    protected $urlGenerator;

    public function __construct(ContainerInterface $container, EntityManagerInterface $doctrine, UrlGeneratorInterface $urlGenerator)
    {
        $this->container = $container;
        $this->doctrine = $doctrine;
        $this->urlGenerator = $urlGenerator;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('get_user_avatar', array($this, 'getUserAvatar')),
            new TwigFunction('_heget_user_avatarader', array($this, 'getUserAvatarHeader')),
            new TwigFunction('get_partner_avatar', array($this, 'getPartnerAvatar')),
            new TwigFunction('is_mdp_read_doc_allowed', array($this, 'isMdpReadDocAllowed')),
            new TwigFunction('is_print_export_allowed', array($this, 'isPrintExportAllowed')),
            new TwigFunction('get_user_training_request_array', array($this, 'getUserTrainingRequestArray')),
            new TwigFunction('get_user_training_team_addresses_count', array($this, 'getUserTrainingTeamAddressesCount')),
            new TwigFunction('get_user_training_team_addresses_error_count', array($this, 'getUserTrainingTeamAddressesErrorCount')),
            new TwigFunction('get_user_training_campains_array', array($this, 'getUserTrainingCampainsArray')),
            new TwigFunction('get_circle_progress', array($this, 'getCircleProgress')),
            new TwigFunction('get_user_action_group_array', array($this, 'getUserActionGroupArray')),
            new TwigFunction('consists_of_the_same_values', array($this, 'consistsOfTheSameValues')),
            new TwigFunction('training_request_revive_allowed', array($this, 'trainingRequestReviveAllowed')),
            new TwigFunction('has_active_subscription', array($this, 'hasActiveSubscription')),
            new TwigFunction('is_active_subscription', array($this, 'isActiveSubscription')),
        );
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('format_treatment_number', array($this, 'formatTreatmentNumber')),
            new TwigFilter('array_to_textarea', array($this, 'arrayToTextarea')),
            new TwigFilter('format_stock_number', array($this, 'formatStockNumber')),
            new TwigFilter('format_credit_number', array($this, 'formatCreditNumber')),
        );
    }

    public function getUserAvatar(User $user) {
        if ($user->getPicture()) {
            return '<img src="/uploads/pictures/'.$user->getPicture().'" class="avatar-img-contain">';
        } else {
            $initials = mb_strtoupper(substr($user->getContactFirstName(), 0 ,1)).mb_strtoupper(substr($user->getContactLastName(), 0 ,1));
            return '<span class="avatar-title rounded-circle bg-dark">'.$initials.'</span>';
        }
    }

    public function getUserAvatarHeader(User $user) {
        if ($user->getPicture()) {
            return '<img src="/public/uploads/pictures/'.$user->getPicture().'" class="avatar-img-contain">';
        } else {
            $initials = mb_strtoupper(substr($user->getContactFirstName(), 0 ,1)).mb_strtoupper(substr($user->getContactLastName(), 0 ,1));
            return '<span class="avatar-title rounded-circle bg-dark">'.$initials.'</span>';
        }
    }

    public function getPartnerAvatar(Partner $partner) {
        if ($partner->getPicture()) {
            return '<img src="/uploads/pictures/'.$partner->getPicture().'" class="avatar-img">';
        } else {
            return '<span class="avatar-title rounded-circle bg-dark"><i class="mdi mdi-account-box-outline"></i></span>';
        }
    }

    public function formatTreatmentNumber($number) {
        $str = strval($number);
        $strLen = strlen($str);
        $maxLen = 3;
        if ($strLen < $maxLen) {
            for ($i = $strLen; $i < $maxLen; $i++) {
                $str = "0".$str;
            }
        }

        return "T".$str;
    }

    public function formatStockNumber(Credit $credit) {
        $number = $credit->convertToMinutes();

        if (intval($number) == $number) {
            return intval($number)."h";
        }

        //return str_replace(".", "h", $number);
        return number_format($number, 2, "h", ".");
    }

    public function formatCreditNumber($number) {
        $value = $number;
        if ($number > 0) {
            $intStock = intval($number);
            if ($number != $intStock) {
                $decimalMinutePart = $number - $intStock;
                $decimalPart = ($decimalMinutePart / 100) * 60;
                $value = $intStock + $decimalPart;
            }
        }

        if (intval($value) == $value) {
            return intval($value)."h";
        }

        //return str_replace(".", "h", $value);
        return number_format($value, 2, "h", ".");
    }

    public function isMdpReadDocAllowed(Account $account)
    {
        $noSubscription = false;

        if (!$account->getUser()->getCurrentSubscription()) {
            $noSubscription = true;
        } else {
            if (!$account->getUser()->getCurrentSubscription()->isActive()) {
                $noSubscription = true;
            } else {
                if (!$account->getUser()->getCurrentSubscription()->getEndDate()) {
                    $noSubscription = true;
                } else {
                    $now = new \DateTime("now");
                    if ($now >= $account->getUser()->getCurrentSubscription()->getEndDate()) {
                        $noSubscription = true;
                    } else {
                        $allowedSubscriptions = ["ABOPLS", "ABOSTD", "ABOLIB", "PARTEN"];
                        if (!$account->getUser()->getCurrentSubscription()->getType() || !in_array($account->getUser()->getCurrentSubscription()->getType()->getCode(), $allowedSubscriptions)) {
                            $noSubscription = true;
                        }
                    }
                }
            }
        }

        if ($noSubscription) {
            return false;
        }

        return true;
    }

    public function isPrintExportAllowed(Account $account)
    {
        $noSubscription = false;

        if (!$account->getUser()->getCurrentSubscription()) {
            $noSubscription = true;
        } else {
            if (!$account->getUser()->getCurrentSubscription()->isActive()) {
                $noSubscription = true;
            } else {
                if (!$account->getUser()->getCurrentSubscription()->getEndDate()) {
                    $noSubscription = true;
                } else {
                    $now = new \DateTime("now");
                    if ($now >= $account->getUser()->getCurrentSubscription()->getEndDate()) {
                        $noSubscription = true;
                    } else {
                        $allowedSubscriptions = ["ABOPLS", "ABOSTD", "ABOLIB", "PARTEN", "FREE30D"];
                        if (!$account->getUser()->getCurrentSubscription()->getType() || !in_array($account->getUser()->getCurrentSubscription()->getType()->getCode(), $allowedSubscriptions)) {
                            $noSubscription = true;
                        }
                    }
                }
            }
        }

        if ($noSubscription) {
            return false;
        }

        return true;
    }

    public function arrayToTextarea($array) {
        return implode("\n", $array);
    }

    public function getUserTrainingRequestArray(TrainingCampain $trainingCampain) {
        $data = [
            "total" => 0,
            "answered" => 0,
            "rate" => 0,
        ];

        $trainingRequests = $this->doctrine->getRepository(TrainingRequest::class)->findBy(["trainingCampain" => $trainingCampain]);

        $data["total"] = count($trainingRequests);

        $score = 0;
        foreach ($trainingRequests as $trainingRequest) {
            if ($trainingRequest->getAnswerDate()) {
                $score += $trainingRequest->getResult();
                $data["answered"] += 1;
            }
        }

        if ($data["answered"]) {
            $data["rate"] = $score / $data["answered"];
        }

        return $data;
    }

    public function getUserTrainingTeamAddressesCount($text) {
        return count(explode("\n", $text));
    }

    public function getUserTrainingTeamAddressesErrorCount($text) {
        $errors = 0;
        $addresses = explode("\n", $text);

        foreach ($addresses as $address) {
            if (!filter_var(trim($address), FILTER_VALIDATE_EMAIL)) {
                $errors++;
            }
        }

        return $errors;
    }

    public function getUserTrainingCampainsArray(Training $training, User $user) {
        return $this->doctrine->getRepository(TrainingCampain::class)->findBy(["training" => $training, "user" => $user]);
    }

    public function getCircleProgress($percent, $r) {
        return ((100-$percent)/100)*(pi()*($r*2));
    }

    public function getUserActionGroupArray(Action $action) {
        $data = [
            "total" => 0,
            "answered" => 0,
            "rate" => 0,
        ];

        $actions = $this->doctrine->getRepository(Action::class)->findGroupsForAction($action);

        $data["total"] = count($actions);

        $score = 0;
        foreach ($actions as $actionGrp) {
            if ($actionGrp->isTerminated()) {
                $data["answered"] += 1;
            }
        }

        if ($data["answered"]) {
            $data["rate"] = $data["answered"] / $data["total"];
        }

        return $data;
    }

    public function consistsOfTheSameValues(array $a, array $b)
    {
        // check size of both arrays
        if (count($a) !== count($b)) {
            return false;
        }

        foreach ($b as $key => $bValue) {

            // check that expected value exists in the array
            if (!in_array($bValue, $a, true)) {
                return false;
            }

            // check that expected value occurs the same amount of times in both arrays
            if (count(array_keys($a, $bValue, true)) !== count(array_keys($b, $bValue, true))) {
                return false;
            }

        }

        return true;
    }

    public function trainingRequestReviveAllowed(TrainingRequest $trainingRequest) {
        $now = new \DateTime("now");

        if ($trainingRequest->getResendDate() && $now->getTimestamp() - $trainingRequest->getResendDate()->getTimestamp() < 172800) {
            return false;
        }

        return true;
    }

    public function hasActiveSubscription(User $user) {
        $noSubscription = false;

        if (!$user->getCurrentSubscription()) {
            $noSubscription = true;
        } else {
            if (!$user->getCurrentSubscription()->isActive()) {
                $noSubscription = true;
            } else {
                if (!$user->getCurrentSubscription()->getEndDate()) {
                    $noSubscription = true;
                } else {
                    $now = new \DateTime("now");
                    if ($now >= $user->getCurrentSubscription()->getEndDate()) {
                        $noSubscription = true;
                    }
                }
            }
        }

        if ($noSubscription) {
            return false;
        }

        return true;
    }

    public function isActiveSubscription(User $user, Subscription $subscription) {
        $noSubscription = false;

        if (!$user->getCurrentSubscription()) {
            $noSubscription = true;
        } else {
            if ($user->getCurrentSubscription()->getId() != $subscription->getId()) {
                $noSubscription = true;
            } else {
                if (!$user->getCurrentSubscription()->isActive()) {
                    $noSubscription = true;
                } else {
                    if (!$user->getCurrentSubscription()->getEndDate()) {
                        $noSubscription = true;
                    } else {
                        $now = new \DateTime("now");
                        if ($now >= $user->getCurrentSubscription()->getEndDate()) {
                            $noSubscription = true;
                        }
                    }
                }
            }
        }

        if ($noSubscription) {
            return false;
        }

        return true;
    }
}