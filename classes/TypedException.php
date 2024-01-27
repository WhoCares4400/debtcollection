<?php
/*
 * Copyright (c) 2022. Jakub Turczyński
 *
 * Wszelkie prawa zastrzeżone. Poniższy kod źródłowy (zwany także programem komputerowym lub krótko - programem), zarówno w jego części twórczej jak i całości,  podlega ochronie na mocy prawa autorskiego jako utwór.
 * Użytkownikowi zezwala się na dostęp do kodu źródłowego oraz na jego użytkowanie w sposób w jaki program został do tego przeznaczony. Kopiowanie, powielanie czy edytowanie całości lub części kodu źródłowego programu bez zgody jego autora jest zabronione.
 */

namespace Dc\Classes;
class TypedException extends \Exception
{
    private $eType;
    private $eReference;
    private $eJs;

    public function __construct($message = '', $code = 0, $type = 'other', $reference = null, $js = null, Throwable $previous = null)
    {
        $this->eType = $type;
        $this->eReference = $reference;
        $this->eJs = $js;

        //$this->logException(($message.' - '.$type), $code, $reference);

        parent::__construct($message, $code, $previous);
    }

    final public function getType()
    {
        return $this->eType;
    }

    final public function getReference()
    {
        return $this->eReference;
    }

    final public function getJs()
    {
        return $this->eJs;
    }
}