<?php
/** Application rules and strict validation for an address/location. */
declare(strict_types=1);
namespace Vereinssystem\Places;

final class PlaceService
{
    public function __construct(private readonly PlaceRepository $places) {}

    /** @param array<string,string> $input @return array<string,int|string> */
    public function validate(array $input): array
    {
        $limits=['name'=>160,'strasse'=>250,'hausnummer'=>5,'plz'=>5,'ort'=>250,'telefon'=>250];
        $values=[];$errors=[];
        foreach ($limits as $field=>$limit) {
            $value=trim($input[$field]??'');
            if (strlen($value)>$limit) $errors[$field]='Angabe zu lang.';
            $values[$field]=$value;
        }
        foreach (['name'=>'Bezeichnung','strasse'=>'Straße','plz'=>'Postleitzahl','ort'=>'Ort'] as $field=>$label) {
            if ($values[$field]==='') $errors[$field]=$label.' fehlt.';
        }
        if ($values['plz']!=='' && !preg_match('/^\d{5}$/D',$values['plz'])) $errors['plz']='Fünfstellige deutsche Postleitzahl erforderlich.';
        $type=filter_var($input['typ']??'',FILTER_VALIDATE_INT);
        if ($type===false||!in_array($type,[0,1,2],true)) $errors['typ']='Ortstyp ungültig.';
        $values['typ']=$type===false?0:$type;
        if ($errors) throw new PlaceValidationException($errors);
        return $values;
    }

    /** @param array<string,string> $values */
    public function save(array $values, ?int $id, int $actor): int
    {
        if ($actor<1||($id!==null&&($id<1||$this->places->find($id)===null))) throw new \InvalidArgumentException('Ungültige Adresse.');
        return $this->places->save($this->validate($values),$id,$actor);
    }
}
