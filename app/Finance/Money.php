<?php
/** Exact monetary input/output. No float is accepted for database amounts. */
declare(strict_types=1);
namespace Vereinssystem\Finance;
final class Money
{
    public static function parse(string $input,bool $allowZero=false): int
    {
        $text=trim($input);
        if (!preg_match('/^(?:0|[1-9][0-9]{0,6})(?:[,.][0-9]{1,2})?$/D',$text))
            throw new \DomainException('Betrag ohne Tausenderpunkte eingeben, z. B. 15,00.');
        $parts=preg_split('/[,.]/',$text);
        $cents=(int)$parts[0]*100+(isset($parts[1])?(int)str_pad($parts[1],2,'0'):0);
        if((!$allowZero&&$cents<=0) || $cents>100000000)throw new \DomainException('Betrag außerhalb des zulässigen Bereichs (bis 1.000.000,00 Euro).');
        return $cents;
    }
    public static function format(int $cents): string
    {
        $sign=$cents<0?'-':'';$abs=abs($cents);
        return $sign.number_format(intdiv($abs,100),0,',','.').','.str_pad((string)($abs%100),2,'0',STR_PAD_LEFT).' €';
    }
}
