<?php
/** Domain validation: contributions are entered in Euros but stored as integer cents. */
declare(strict_types=1);
namespace Vereinssystem\Departments;

final class DepartmentService
{
    public function __construct(private readonly DepartmentRepository $departments) {}

    /** @param array<string,string> $input @return array<string,int|string> */
    public function validate(array $input): array
    {
        $name=trim($input['name']??'');$description=trim($input['beschreibung']??'');$url=trim($input['homepage']??'');$errors=[];
        if ($name===''||strlen($name)>160)$errors['name']='Name fehlt oder ist zu lang.';
        if (strlen($description)>5000)$errors['beschreibung']='Beschreibung zu lang.';
        if (strlen($url)>500||($url!==''&&(!filter_var($url,FILTER_VALIDATE_URL)||!preg_match('~^https?://~i',$url))))$errors['homepage']='Nur gültige HTTP(S)-Adressen.';
        $leader=filter_var($input['abteilungsleiter']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
        if ($leader===false)$errors['abteilungsleiter']='Leiter-ID ungültig.';
        $amount=self::parseCents($input['aktivenumlage']??'');
        if ($amount===null||$amount>100000000)$errors['aktivenumlage']='Umlage muss ein gültiger Betrag sein.';
        if ($errors)throw new DepartmentValidationException($errors);
        return ['name'=>$name,'beschreibung'=>$description,'homepage'=>$url,'abteilungsleiter'=>$leader,'aktivenumlage'=>$amount];
    }

    /** Decimal monetary form: "12,50" or "12.50"; never floating-point. */
    public static function parseCents(string $value): ?int
    {
        $value=trim(str_replace(',','.',$value));
        if(!preg_match('/^\d{1,7}(?:\.\d{1,2})?$/D',$value))return null;
        [$euros,$cents]=array_pad(explode('.',$value,2),2,'');
        return (int)$euros*100+(int)str_pad($cents,2,'0');
    }
    /** @param array<string,string> $values */
    public function save(array $values,?int $id): int
    {
        if($id!==null&&($id<1||!$this->departments->find($id)))throw new \DomainException('Abteilung nicht gefunden.');
        return $this->departments->save($this->validate($values),$id);
    }
    public function assign(int $dept,int $member,int $active,string $date): void
    {
        $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);
        if ($dept<1||$member<1||!in_array($active,[0,1],true)||!$parsed||$parsed->format('Y-m-d')!==$date)
            throw new \DomainException('Ungültige Zuordnung oder Datum.');
        $this->departments->assign($dept,$member,$active,$date);
    }
}
