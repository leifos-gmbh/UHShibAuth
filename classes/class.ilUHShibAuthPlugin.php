<?php

include_once './Services/AuthShibboleth/classes/class.ilShibbolethAuthenticationPlugin.php';

/**
 * Shibboleth authentication plugin for matriculation number modification
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 **/
class ilUHShibAuthPlugin extends ilShibbolethAuthenticationPlugin
{
    const PLNAME = 'UHShibAuth';

    /**
     * @var string
     */
    const SHIB_MATRICULATION_FIELD = 'shib_matriculation';

    /**
     * @var string
     */
    const SHIB_MATRICULATION_UPDATE = 'shib_update_matriculation';

    /**
     * @var null | \ilLogger
     */
    private $logger = null;

    /**
     * @var null | \ilSetting
     */
    private $settings = null;

    /**
     * @inheritDoc
     */
    protected function init()
    {
        global $DIC;

        $this->logger = $DIC->logger()->auth();
        $this->settings = $DIC->settings();
    }

    /**
     * Get plugin name
     * @return string
     */
    public function getPluginName()
    {
        return static::PLNAME;
    }

    /**
     * @param ilObjUser $user
     * @return ilObjUser
     */
    public function beforeCreateUser(ilObjUser $user)
    {
        $this->logger->debug('Before user creation');

        $user = $this->updateMatriculation($user, true);


        return $user;
    }

    /**
     * @param ilObjUser $user
     * @return ilObjUser
     */
    public function beforeUpdateUser(ilObjUser $user)
    {
        $this->logger->debug('Before user update');

        $user = $this->updateMatriculation($user, false);

        return $user;
    }

    /**
     * @param ilObjUser $user
     * @param bool      $is_creation_mode
     * @return ilObjUser
     */
    private function updateMatriculation(\ilObjUser $user, bool $is_creation_mode)
    {
        $shib_mn_field = $this->settings->get(self::SHIB_MATRICULATION_FIELD, '');
        if (!strlen($shib_mn_field)) {
            $this->logger->debug('No matriculation number mapping configured');
            return $user;
        }

        $shib_mn_update = $this->settings->get(self::SHIB_MATRICULATION_UPDATE, 0 );
        if (!$is_creation_mode && !$shib_mn_update) {
            $this->logger->debug('No update configured for matriculation in global settings');
            return $user;
        }

        if (array_key_exists($shib_mn_field, $_SERVER)) {
            $shib_mn_value = trim($_SERVER[$shib_mn_field]);
            if (!strlen($shib_mn_value)) {
                $this->logger->debug('No matriculation number send by shib server.');
                return $user;
            }

            $shib_mn_parts = explode(':', $shib_mn_value);
            if($shib_mn_parts === false) {
                $this->logger->debug('Cannot parse matriculation number: ' . $shib_mn_value);
                return $user;
            }

            $matriculation = end($shib_mn_parts);
            $this->logger->debug('Update matriculation number: ' . $matriculation);
            $user->setMatriculation($matriculation);
        }
        else {
            $this->logger->debug('No matriculation number found. ');
        }
        return $user;
    }
}

?>