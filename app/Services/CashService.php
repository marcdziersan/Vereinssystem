<?php
/**
 * Cash use cases and domain validation. No database access, HTML, or $_POST here.
 * Role checks are handled by route middleware, before these methods are called.
 */
declare(strict_types=1);
namespace Vereinssystem\Services;

use Vereinssystem\Cash\CashRepository;
use Vereinssystem\Finance\Money;

final class CashService
{
    public function __construct(private readonly CashRepository $repository) {}
    public function accounts(bool $includeInactive=true): array {return $this->repository->accounts($includeInactive);}
    public function entries(int $year,?int $account=null): array
    {return $this->repository->entries(FinanceService::year($year),$account===null?null:self::positiveId($account));}
    private static function positiveId(int $id): int
    {if($id<1)throw new \DomainException('Bitte gültiges Konto auswählen.');return $id;}
    private static function text(string $value,int $max,bool $required=true):string
    {
        $clean=trim($value);
        if(($required&&$clean==='')||strlen($clean)>$max||str_contains($clean,"\0"))throw new \DomainException('Text fehlt oder ist zu lang (maximal '.$max.' Zeichen).');
        return $clean;
    }
    /** Negative opening balances are permitted, but transactions themselves are always positive. */
    public static function opening(string $value):int
    {
        $text=trim($value);$negative=str_starts_with($text,'-');
        $amount=Money::parse($negative?substr($text,1):$text,true);
        return $negative?-$amount:$amount;
    }
    public function createAccount(string $name,string $type,string $opening,string $date,int $actor):int
    {
        return $this->repository->createAccount(self::text($name,120),self::text($type,40),self::opening($opening),FinanceService::date($date),$actor);
    }
    public function updateAccount(int $id,string $name,string $type,bool $active,int $actor):void
    {
        $this->repository->setAccount(self::positiveId($id),self::text($name,120),self::text($type,40),$active,$actor);
    }
    public function book(int $account,string $type,string $amount,string $date,string $category,string $description,string $receipt,int $actor):int
    {
        if(!in_array($type,['einnahme','ausgabe'],true))throw new \DomainException('Nur Einnahme oder Ausgabe zulässig.');
        return $this->repository->book(self::positiveId($account),$type,Money::parse($amount),FinanceService::date($date),self::text($category,100,false),self::text($description,500),self::text($receipt,100,false),$actor);
    }
    public function transfer(int $from,int $to,string $amount,string $date,string $description,int $actor):array
    {
        if(self::positiveId($from)===self::positiveId($to))throw new \DomainException('Quell- und Zielkonto müssen verschieden sein.');
        return $this->repository->transfer($from,$to,Money::parse($amount),FinanceService::date($date),self::text($description,500),$actor);
    }
    public function voidEntry(int $id,string $reason,int $actor):void
    {$this->repository->voidEntry(self::positiveId($id),self::reason($reason),$actor);}
    private static function reason(string $reason):string
    {
        $reason=self::text($reason,250);
        if(strlen($reason)<5)throw new \DomainException('Stornogrund benötigt mindestens fünf Zeichen.');
        return $reason;
    }
    public function contribution(int $account,int $member,string $amount,string $date,string $channel,string $reference,string $note,int $actor,?int $preferred):array
    {
        self::positiveId($account);self::positiveId($member);
        if(!in_array($channel,['Überweisung','Bar','Lastschrift','PayPal','Sonstiges'],true))throw new \DomainException('Unbekannter Zahlungsweg.');
        if($preferred!==null)self::positiveId($preferred);
        return $this->repository->contribution($account,$member,Money::parse($amount),FinanceService::date($date),$channel,self::text($reference,190,false),self::text($note,500,false),$actor,$preferred);
    }
    public function voidContribution(int $paymentId,string $reason,int $actor):void
    {$this->repository->voidContribution(self::positiveId($paymentId),self::reason($reason),$actor);}
}
