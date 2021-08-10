<?php

declare(strict_types=1);
namespace ExampleTech\Repository;
require(dirname(__DIR__).'../../../vendor/autoload.php');
use Webauthn\PublicKeyCredentialSourceRepository as PublicKeyCredentialSourceRepositoryInterface;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Base64Url\Base64Url;
use \PDO;

class PublicKeyCredentialSourceRepository implements PublicKeyCredentialSourceRepositoryInterface
{
    private function getRegistrationDataByPKCredId($publicKeyCredentialId) {
        // Connect to DB
        include_once('db_connection.php');
        global $pdo;
        // Check whether a participant with this prolific ID is registered and has a credential_id
        $stmt = $pdo->prepare("SELECT `json_parsed` FROM `bio_webauthn_db`.`webauthn_registrations` WHERE raw_id=:raw_id LIMIT 1");
        $stmt->bindParam(':raw_id', $publicKeyCredentialId);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if($result === false) {
            throw new Exception("Error: Can not check whether this participant is registered or not.", -1);
        } else {
            $reg_data = json_decode($result[0]['json_parsed'], true);
            $my_pk_credsource = Array(
                'publicKeyCredentialId' => $reg_data['attestationObject']['authData']['attestedCredentialData']['credentialId'],
                'type' => $reg_data['type'],
                'transports' => $reg_data['transports'],
                'aaguid' => $reg_data['attestationObject']['authData']['attestedCredentialData']['aaguid'],
                'credentialPublicKey' => $reg_data['attestationObject']['authData']['attestedCredentialData']['credentialPublicKey'],
                'userHandle' => Base64Url::encode($_SESSION['username']),
                'counter' => $reg_data['attestationObject']['authData']['signCount'],
                'attestationType' => $reg_data['attestationObject']['type'],
                'trustPath' => $reg_data['attestationObject']['trustPath']
            );
            return $my_pk_credsource;
        } // endif - Could execute statement
    } // end function

    private function getRegistrationDataByUsername($username) {
        // Connect to DB
        include_once('db_connection.php');
        global $pdo;
        // Check whether a participant with this prolific ID is registered and has a credential_id
        $stmt = $pdo->prepare("SELECT `json_parsed` FROM `bio_webauthn_db`.`webauthn_registrations` WHERE username=:username LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if($result === false) {
            throw new Exception("Error: Can not check whether this participant is registered or not.", -1);
        } else {
            $reg_data = json_decode($result[0]['json_parsed'], true);
            $my_pk_credsource = Array(
                'publicKeyCredentialId' => $reg_data['attestationObject']['authData']['attestedCredentialData']['credentialId'],
                'type' => $reg_data['type'],
                'transports' => $reg_data['transports'],
                'aaguid' => $reg_data['attestationObject']['authData']['attestedCredentialData']['aaguid'],
                'credentialPublicKey' => $reg_data['attestationObject']['authData']['attestedCredentialData']['credentialPublicKey'],
                'userHandle' => Base64Url::encode($_SESSION['username']),
                'counter' => $reg_data['attestationObject']['authData']['signCount'],
                'attestationType' => $reg_data['attestationObject']['type'],
                'trustPath' => $reg_data['attestationObject']['trustPath']
            );
            return $my_pk_credsource;
        } // endif - Could execute statement
    } // end function

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        $reg_data = $this->getRegistrationDataByPKCredId(Base64Url::encode($publicKeyCredentialId));
        return PublicKeyCredentialSource::createFromArray($reg_data);
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        $reg_data = $this->getRegistrationDataByUsername($publicKeyCredentialUserEntity->getId());
        $sources = [];
        $source = PublicKeyCredentialSource::createFromArray($reg_data);
        if ($source->getUserHandle() === $publicKeyCredentialUserEntity->getId())
        {
            $sources[] = $source;
        }
        return $sources;
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        // store $publicKeyCredentialSource;
    }
}

?>