<?php
/**
 * OFX Reader in PHP to simplify reading extract in OFX files
 * PHP 8.1
 * Author Anderson Arruda < andmarruda@gmail.com >
 */

 namespace ofxphp;

use DateTime;
use \DOMDocument;

 class ofx{
    /**
     * Properties of OFX file
     * @var array
     */
    private array $properties = [];

    /**
     * DOMDocument of OFX
     * @var DOMDocument
     */
    private DOMDocument $dom;

    /**
     * XML from OFX File
     * @var string
     */
    private string $xml='';

    /**
     * @description     Construct class and prepares the image edition
     * @author          Anderson Arruda < contato@sysborg.com.br >
     * @version         1.0.0
     * @param           private string $filepath
     * @return          void
     */
    public function __construct(private string $filepath)
    {
        if(!file_exists($filepath))
            throw new \Exception('Path of OFX file doesn\'t exists.');

        $this->dom = new DOMDocument();
        $this->workOfx();
    }

    /**
     * @description     Work the ofx file to get properties and XML
     * @author          Anderson Arruda < contato@sysborg.com.br >
     * @version         1.0.0
     * @param           string $filepath
     * @return          void
     */
    private function workOfx() : void
    {
        $ofxLine = -1;
        $file = fopen($this->filepath, 'r');
        while(!feof($file)){
            $line = trim(fgets($file));
            if(preg_match('/^<OFX>$/', $line))
                $ofxLine = 1;

            if($ofxLine == -1 && preg_match('/(?!\<)(.*\:)/', $line)){
                $attr = explode(':', $line);
                $this->properties[$attr[0]] = $attr[1] ?? null;
                continue;
            }

            $this->xml .= $line;
        }
        fclose($file);
        $this->dom->loadXML($this->xml);
    }

    /**
     * Convert data string to Date
     * @author      Anderson Arruda < contato@sysborg.com.br >
     * @version     1.0.0
     * @param       string $date
     * @param       string $format='Ymd'
     * @return      instanceof DateTime
     */
    private function strToDate(string $date, string $format='Ymd') : \DateTime
    {
        return DateTime::createFromFormat($format, $date);
    }

    /**
     * Returns all properties
     * @author  Anderson Arruda < contato@sysborg.com.br >
     * @version 1.0.0
     * @param   
     * @return  array
     */
    public function getProperties() : array
    {
        return $this->properties;
    }

    /**
     * Get informations from signonmsgsrsv1
     * @author  Anderson Arruda < contato@sysborg.com.br >
     * @version 1.0.0
     * @param
     * @return  array
     */
    public function signonmsgsrsv1() : array
    {
        $sonrs = $this->dom->documentElement->getElementsByTagName('SIGNONMSGSRSV1')->item(0)->getElementsByTagName('SONRS')->item(0);

        return [
            'status_code' => $sonrs->getElementsByTagName('STATUS')->item(0)->getElementsByTagName('CODE')->item(0)->nodeValue,
            'status_severity' => $sonrs->getElementsByTagName('STATUS')->item(0)->getElementsByTagName('SEVERITY')->item(0)->nodeValue,
            'dtserver' => $this->strToDate($sonrs->getElementsByTagName('DTSERVER')->item(0)->nodeValue),
            'language' => $sonrs->getElementsByTagName('LANGUAGE')->item(0)->nodeValue,
            'fi_org' => $sonrs->getElementsByTagName('FI')->item(0)->getElementsByTagName('ORG')->item(0)->nodeValue,
            'fi_fid' => $sonrs->getElementsByTagName('FI')->item(0)->getElementsByTagName('FID')->item(0)->nodeValue,
        ];
    }

    /**
     * Get information from stmttrnrs
     * @author  Anderson Arruda < contato@sysborg.com.br >
     * @version 1.0.0
     * @param
     * @return  array
     */
    public function stmttrnrs() : array
    {
        $stmttrnrs = $this->dom->documentElement->getElementsByTagName('BANKMSGSRSV1')->item(0)->getElementsByTagName('STMTTRNRS')->item(0);

        return [
            'trnuid' => $stmttrnrs->getElementsByTagName('TRNUID')->item(0)->nodeValue,
            'status_code' => $stmttrnrs->getElementsByTagName('STATUS')->item(0)->getElementsByTagName('CODE')->item(0)->nodeValue,
            'status_severity' => $stmttrnrs->getElementsByTagName('STATUS')->item(0)->getElementsByTagName('SEVERITY')->item(0)->nodeValue,
        ];
    }

    /**
     * Return information about account
     * @author  Anderson Arruda < contato@sysborg.com.br >
     * @version 1.0.0
     * @param
     * @return  array
     */
    public function account() : array
    {
        $stmtrs = $this->dom->documentElement->getElementsByTagName('BANKMSGSRSV1')->item(0)->getElementsByTagName('STMTTRNRS')->item(0)->getElementsByTagName('STMTRS')->item(0);

        return [
            'currency' => $stmtrs->getElementsByTagName('CURDEF')->item(0)->nodeValue,
            'bankid' => $stmtrs->getElementsByTagName('BANKACCTFROM')->item(0)->getElementsByTagName('BANKID')->item(0)->nodeValue,
            'branchid' => $stmtrs->getElementsByTagName('BANKACCTFROM')->item(0)->getElementsByTagName('BRANCHID')->item(0)->nodeValue,
            'acctid' => $stmtrs->getElementsByTagName('BANKACCTFROM')->item(0)->getElementsByTagName('ACCTID')->item(0)->nodeValue,
            'accttype' => $stmtrs->getElementsByTagName('BANKACCTFROM')->item(0)->getElementsByTagName('ACCTTYPE')->item(0)->nodeValue
        ];
    }

    /**
     * Return data of financial movement
     * @author  Anderson Arruda < contato@sysborg.com.br >
     * @version 1.0.0
     * @param
     * @return  array
     */
    public function movements() : array
    {
        $banktranlist = $this->dom->documentElement->getElementsByTagName('BANKMSGSRSV1')->item(0)
                            ->getElementsByTagName('STMTTRNRS')->item(0)
                            ->getElementsByTagName('STMTRS')->item(0)
                            ->getElementsByTagName('BANKTRANLIST')->item(0);
        $movements = [];
        foreach($banktranlist->getElementsByTagName('STMTTRN') as $stmttrn){
            $movements[] = [
                'trntype' => $stmttrn->getElementsByTagName('TRNTYPE')->item(0)->nodeValue,
                'dtposted' => $this->strToDate($stmttrn->getElementsByTagName('DTPOSTED')->item(0)->nodeValue),
                'trnamt' => (float) $stmttrn->getElementsByTagName('TRNAMT')->item(0)->nodeValue,
                'fitid' => $stmttrn->getElementsByTagName('FITID')->item(0)->nodeValue,
                'checknum' => $stmttrn->getElementsByTagName('CHECKNUM')->item(0)->nodeValue,
                'refnum' => $stmttrn->getElementsByTagName('REFNUM')->item(0)->nodeValue,
                'memo' => $stmttrn->getElementsByTagName('MEMO')->item(0)->nodeValue,
            ];
        }

        return [
            'dtstart' => $banktranlist->getElementsByTagName('DTSTART')->item(0)->nodeValue,
            'dtend' => $banktranlist->getElementsByTagName('DTEND')->item(0)->nodeValue,
            'movements' => $movements
        ];
    }

    /**
     * Return data of balance
     * @author  Anderson Arruda < contato@sysborg.com.br >
     * @version 1.0.0
     * @param
     * @return  array
     */
    public function balance() : array
    {
        $ledgerbal = $this->dom->documentElement->getElementsByTagName('BANKMSGSRSV1')->item(0)
                            ->getElementsByTagName('STMTTRNRS')->item(0)
                            ->getElementsByTagName('STMTRS')->item(0)
                            ->getElementsByTagName('LEDGERBAL')->item(0);

        return [
            'balamt' => (float) $ledgerbal->getElementsByTagName('BALAMT')->item(0)->nodeValue,
            'dtasof' => $this->strToDate($ledgerbal->getElementsByTagName('DTASOF')->item(0)->nodeValue)
        ];
    }
 }
?>
