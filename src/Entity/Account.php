<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Account
 *
 * @ORM\Table(name="account", uniqueConstraints={@ORM\UniqueConstraint(name="account_id_uindex", columns={"id"})}, indexes={@ORM\Index(name="account_manager_id_fk", columns={"manager_id"}), @ORM\Index(name="account_user_id_fk", columns={"user_id"})})
 * @ORM\Entity
 */
class Account implements UserInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="salt", type="string", length=255, nullable=true)
     */
    private $salt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="registration_date", type="datetime", nullable=true)
     */
    private $registrationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="password_request_date", type="datetime", nullable=true)
     */
    private $passwordRequestDate;

    /**
     * @var string
     *
     * @ORM\Column(name="password_request", type="string", length=255, nullable=true)
     */
    private $passwordRequest;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_login", type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @var boolean
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=true)
     */
    private $enabled = true;

    /**
     * @var array
     *
     * @ORM\Column(name="roles", type="array", nullable=true)
     */
    private $roles;

    /**
     * @var integer
     *
     * @ORM\Column(name="errored_login_count", type="integer", nullable=false)
     */
    private $erroredLoginCount = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="locked", type="boolean", nullable=true)
     */
    private $locked = false;

    /**
     * @var Manager
     *
     * @ORM\OneToOne(targetEntity="Manager", inversedBy="account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="manager_id", referencedColumnName="id")
     * })
     */
    private $manager;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="User", inversedBy="account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var Subuser
     *
     * @ORM\OneToOne(targetEntity="Subuser", inversedBy="account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="subuser_id", referencedColumnName="id")
     * })
     */
    private $subuser;

    /**
     * @var LoginLog
     *
     * @ORM\OneToOne(targetEntity="LoginLog")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="login_log_id", referencedColumnName="id")
     * })
     */
    private $loginLog;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @return \DateTime
     */
    public function getRegistrationDate()
    {
        return $this->registrationDate;
    }

    /**
     * @param \DateTime $registrationDate
     */
    public function setRegistrationDate($registrationDate)
    {
        $this->registrationDate = $registrationDate;
    }

    /**
     * @return \DateTime
     */
    public function getPasswordRequestDate()
    {
        return $this->passwordRequestDate;
    }

    /**
     * @param \DateTime $passwordRequestDate
     */
    public function setPasswordRequestDate($passwordRequestDate)
    {
        $this->passwordRequestDate = $passwordRequestDate;
    }

    /**
     * @return string
     */
    public function getPasswordRequest()
    {
        return $this->passwordRequest;
    }

    /**
     * @param string $passwordRequest
     */
    public function setPasswordRequest($passwordRequest)
    {
        $this->passwordRequest = $passwordRequest;
    }

    /**
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime $lastLogin
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param Manager $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        if ($this->getSubuser()) {
            return $this->getSubuser()->getUser();
        }
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return Subuser
     */
    public function getSubuser()
    {
        return $this->subuser;
    }

    /**
     * @param Subuser $subuser
     */
    public function setSubuser($subuser)
    {
        $this->subuser = $subuser;
    }

    /**
     * @return LoginLog
     */
    public function getLoginLog()
    {
        return $this->loginLog;
    }

    /**
     * @param LoginLog $loginLog
     */
    public function setLoginLog($loginLog)
    {
        $this->loginLog = $loginLog;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return int
     */
    public function getErroredLoginCount(): int
    {
        return $this->erroredLoginCount;
    }

    /**
     * @param int $erroredLoginCount
     */
    public function setErroredLoginCount(int $erroredLoginCount): void
    {
        $this->erroredLoginCount = $erroredLoginCount;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     */
    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        // TODO: Implement getUsername() method.
        return $this->getEmail();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

}

