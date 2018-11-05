<?php
/**
 * EDIFACT Messages Encoder
 * (c) 2018 Stefano Sabatini
 */

namespace EDI;

class Encoder
{

    private $output = '';
    private $UNAActive = false; // disable by default to preserve backward compatibility
    private $originalArray = [];
    private $wrap = true; //when false adds a newline after each segment

    /**
     * @var string : component separator character (default :)
     */
    private $sepComp;

    /**
     * @var string : data separator character (default +)
     */
    private $sepData;

    /**
     * @var string : dec separator character (no use but here) (default .)
     */
    private $sepDec;

    /**
     * @var string : release character (default ?)
     */
    private $symbRel;

    /**
     * @var string : repetition character (no use but here) (default space)
     */
    private $symbRep;

    /**
     * @var string : end character (default ')
     */
    private $symbEnd;

    public function __construct($array = null, $wrap = true)
    {
        $this->setUNA(":+.? '", false);
        if ($array === null) {
            return;
        }
        $this->encode($array, $wrap);
    }

    public function encode($arr, $wrap = true, $filterKeys = false)
    {
        $this->originalArray = $arr;
        $this->wrap = $wrap;

        $edistring = '';
        $count = count($arr);
        $k = 0;
        foreach ($arr as $row) {
            $k++;
            if ($filterKeys) {
                unset($row['segmentIdx']);
            }
            $row = array_values($row);
            $edistring .= $this->encodeSegment($row);
            if (!$wrap && $k < $count) {
                $edistring .= "\n";
            }
        }
        $this->output = $edistring;
        return $edistring;
    }

    public function encodeSegment($row)
    {
        $str = '';
        $t = count($row);
        for ($i = 0; $i < $t; $i++) {
            $elm = '';
            if (!is_array($row[$i])) {
                $elm = $this->escapeValue($row[$i]);
            } else {
                if (count($row[$i]) == 1 && is_array(reset($row[$i]))) {
                    $row[$i] = array_pop($row[$i]);
                }
                foreach ($row[$i] as &$temp) {
                    $temp = $this->escapeValue($temp);
                }
                $elm = implode($this->sepComp, $row[$i]);
            }
            $str .= $elm;
            if ($i == $t - 1) {
                break;
            }
            $str .= $this->sepData;
        }
        $str .= $this->symbEnd;
        return $str;
    }

    private function escapeValue($str)
    {
        $search = [
            $this->symbRel,
            $this->sepComp,
            $this->sepData,
            $this->symbEnd
        ];
        $replace = [
            $this->symbRel . $this->symbRel,
            $this->symbRel . $this->sepComp,
            $this->symbRel . $this->sepData,
            $this->symbRel . $this->symbEnd
        ];
        return str_replace($search, $replace, $str);
    }

    public function get()
    {
        if ($this->UNAActive) {
            $una = "UNA" . $this->sepComp .
                    $this->sepData .
                    $this->sepDec .
                    $this->symbRel .
                    $this->symbRep .
                    $this->symbEnd ;
            if ($this->wrap === false) {
                $una .= "\n";
            }
            return $una . $this->output;
        } else {
            return $this->output;
        }
    }

    public function setUNA($chars, $user_call = true)
    {
        if (is_string($chars) && strlen($chars) == 6) {
            $this->sepComp = $chars[0];
            $this->sepData = $chars[1];
            $this->sepDec = $chars[2];
            $this->symbRel = $chars[3];
            $this->symbRep = $chars[4];
            $this->symbEnd = $chars[5];
            if ($user_call) {
                $this->enableUNA();
            }
            if ($this->output != '') {
                $this->output = $this->encode($this->originalArray);
            }
            return true;
        } else {
            return false;
        }
    }

    public function enableUNA()
    {
        $this->UNAActive = true;
    }

    public function disableUNA()
    {
        $this->UNAActive = false;
    }
}
