<?php
/** Contribution use cases and input validation. Permission checks occur in route middleware. */
declare(strict_types=1);
namespace Vereinssystem\Services;
use DateTimeImmutable;
use Vereinssystem\Finance\{FinanceRepository,Money};
final class FinanceService
{
    public function __construct(private readonly FinanceRepository $repository) {}
    public static function period(string $value): string
    {
        if(!preg_match('/^20[0-9]{2}-(?:0[1-9]|1[0-2])$/D',$value))
            throw new \DomainException('Beitragsmonat im Format JJJJ-MM eingeben.');
        return $value;
    }
    public static function date(string $value): string
    {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        if(!$date || $date->format('Y-m-d')!==$value)throw new \DomainException('Ungültiges Datum.');
        return $value;
    }
    public static function member(int $id): int
    {if($id<1)throw new \DomainException('Ein gültiges Mitglied auswählen.');return $id;}
    public static function year(int $year): int
    {if($year<2000||$year>2099)throw new \DomainException('Ungültiges Beitragsjahr.');return $year;}
    public function statement(int $memberId,int $year): array
    {return $this->repository->statement(self::member($memberId),self::year($year));}
    public function rates(): array {return $this->repository->rates();}
    public function setRate(string $kind,int $department,string $effective,string $amount,int $actor): void
    {
        if(!in_array($kind,['kind','erwachsen','abteilung'],true)||($kind==='abteilung'&&$department<1)||($kind!=='abteilung'&&$department!==0))
            throw new \DomainException('Ungültiger Beitragstyp oder Abteilung.');
        $this->repository->setRate($kind,$department,self::date($effective),Money::parse($amount,true),$actor);
    }
    public function charge(int $member,string $period,string $kind,int $department,string $description,string $amount,string $due,int $actor): int
    {
        if(!in_array($kind,['sonstiges','grundbeitrag','abteilung'],true)||($kind==='abteilung'&&$department<1)||($kind!=='abteilung'&&$department!==0))
            throw new \DomainException('Ungültige Forderungsart.');
        $description=trim($description);
        if(strlen($description)<3||strlen($description)>190)throw new \DomainException('Beschreibung: 3 bis 190 Zeichen.');
        return $this->repository->createClaim(self::member($member),self::period($period),$kind,$department,$description,Money::parse($amount),self::date($due),$actor);
    }
    public function generateMonth(string $period,int $actor): array
    {return $this->repository->generateMonth(self::period($period),$actor);}
    public function payment(int $member,string $amount,string $date,string $channel,string $reference,string $note,int $actor,?int $preferred=null): array
    {
        if(!in_array($channel,['Überweisung','Bar','Lastschrift','PayPal','Sonstiges'],true))throw new \DomainException('Zahlungsweg ungültig.');
        if(strlen($reference)>190||strlen($note)>500)throw new \DomainException('Referenz oder Hinweis zu lang.');
        if($preferred!==null&&$preferred<1)throw new \DomainException('Ungültige Forderungszuordnung.');
        return $this->repository->receivePayment(self::member($member),Money::parse($amount),self::date($date),$channel,trim($reference),trim($note),$actor,$preferred);
    }
    public function reconcile(int $member): int {return $this->repository->reconcile(self::member($member));}
    public function voidPayment(int $id,string $reason,int $actor): void
    {$this->reason($id,$reason);$this->repository->voidPayment($id,trim($reason),$actor);}
    public function voidClaim(int $id,string $reason,int $actor): void
    {$this->reason($id,$reason);$this->repository->voidClaim($id,trim($reason),$actor);}
    private function reason(int $id,string $reason): void
    {if($id<1||strlen(trim($reason))<5||strlen(trim($reason))>250)throw new \DomainException('Storno-ID oder Grund (5–250 Zeichen) ungültig.');}
}
