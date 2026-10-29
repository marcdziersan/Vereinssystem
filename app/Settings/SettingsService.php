<?php
/** Explicit settings allowlist: configuration only; no actual delivery or secrets. */
declare(strict_types=1);
namespace Vereinssystem\Settings;

use DomainException;

final class SettingsService
{
    public const DEFAULTS=[
        'club_name'=>'Vereinssystem',
        'club_street'=>'',
        'club_postcode'=>'',
        'club_city'=>'',
        'club_email'=>'',
        'club_website'=>'',
        'mail_from_name'=>'',
        'mail_from_email'=>'',
        'mail_reply_to'=>'',
        'mail_transport'=>'disabled',
        'smtp_host'=>'',
        'smtp_port'=>'587',
        'smtp_encryption'=>'starttls',
        'smtp_username'=>'',
        'notify_membership'=>'0',
        'notify_events'=>'0',
        'notify_birthdays'=>'0',
    ];
    public function __construct(private readonly SettingsRepository $repository) {}

    /** @return array{revision:int,values:array<string,string>} */
    public function snapshot():array
    {
        $raw=$this->repository->load();
        return ['revision'=>$raw['revision'],'values'=>array_replace(self::DEFAULTS,$raw['values'])];
    }

    /** @param array<string,mixed> $input */
    public function save(array $input,int $revision,int $actorId):int
    {
        if($revision<0||$actorId<1)throw new DomainException('Ungültige Version oder Berechtigung.');
        $values=self::validate($input);
        return $this->repository->save($values,$revision,$actorId);
    }

    /** @return array<string,string> */
    public static function validate(array $input):array
    {
        $maxima=[
            'club_name'=>150,'club_street'=>180,'club_postcode'=>16,'club_city'=>120,
            'club_email'=>254,'club_website'=>255,'mail_from_name'=>150,
            'mail_from_email'=>254,'mail_reply_to'=>254,'mail_transport'=>20,
            'smtp_host'=>200,'smtp_port'=>5,'smtp_encryption'=>20,'smtp_username'=>180,
            'notify_membership'=>1,'notify_events'=>1,'notify_birthdays'=>1,
        ];
        $values=[];
        foreach(self::DEFAULTS as $key=>$default){
            $value=$input[$key]??$default;
            if(!is_string($value))throw new DomainException('Feld '.$key.' hat einen ungültigen Typ.');
            $value=trim($value);
            if(strlen($value)>$maxima[$key]||preg_match('/[\x00-\x1F\x7F]/',$value))
                throw new DomainException('Ungültiger Feldinhalt: '.$key.'.');
            $values[$key]=$value;
        }
        if(strlen($values['club_name'])<2)throw new DomainException('Vereinsname muss mindestens zwei Zeichen haben.');
        foreach(['club_email','mail_from_email','mail_reply_to'] as $key){
            if($values[$key]!==''&&!filter_var($values[$key],FILTER_VALIDATE_EMAIL))
                throw new DomainException('Bitte eine gültige E-Mail-Adresse eingeben: '.$key.'.');
        }
        if($values['club_website']!==''){
            $url=filter_var($values['club_website'],FILTER_VALIDATE_URL);
            $scheme=is_string($url)?strtolower((string)parse_url($url,PHP_URL_SCHEME)):'';
            if(!$url||!in_array($scheme,['http','https'],true))throw new DomainException('Website benötigt eine vollständige http(s)-URL.');
        }
        if(!in_array($values['mail_transport'],['disabled','php_mail','smtp'],true))
            throw new DomainException('Unbekannter E-Mail-Versandweg.');
        if(!in_array($values['smtp_encryption'],['none','starttls','tls'],true))
            throw new DomainException('Unbekannte SMTP-Verschlüsselung.');
        $port=filter_var($values['smtp_port'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
        if($port===false)throw new DomainException('SMTP-Port muss zwischen 1 und 65535 liegen.');
        $values['smtp_port']=(string)$port;
        if($values['smtp_host']!==''&&!preg_match('/^[a-zA-Z0-9.-]{1,200}$/D',$values['smtp_host']))
            throw new DomainException('Ungültiger SMTP-Hostname.');
        if($values['mail_transport']!=='disabled'&&$values['mail_from_email']==='')
            throw new DomainException('Für einen konfigurierten Versandweg ist eine Absenderadresse erforderlich.');
        if($values['mail_transport']==='smtp'&&$values['smtp_host']==='')
            throw new DomainException('Für SMTP muss ein Host angegeben werden.');
        foreach(['notify_membership','notify_events','notify_birthdays'] as $flag){
            if(!in_array($values[$flag],['0','1'],true))throw new DomainException('Ungültige Benachrichtigungsauswahl.');
        }
        return $values;
    }

    public function history(int $page=1):array
    {
        $page=max(1,min(1000,$page));
        return $this->repository->history(30,($page-1)*30);
    }
}
