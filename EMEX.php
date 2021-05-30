<?php

class EMEX
{
    private $isDebug = false;
    protected $curl, $locationId, $authKey, $email;

    function __construct($login, $pass, $email = '')
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);

        $this->email = $email;
        $this->auth($login, $pass);
    }

    private function auth($login, $pass)
    {
        curl_setopt($this->curl, CURLOPT_URL, 'https://emex.ru/api/account/login');
        $headers = array(
            "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
            "content-type: application/json;charset=UTF-8",
            "accept: application/json, text/plain, */*"
        );
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode(array('login' => $login, 'password' => $pass, 't' => time())));
        curl_setopt($this->curl, CURLOPT_HEADER, true);
        $resp = curl_exec($this->curl);
        if (!curl_errno($this->curl)) {
            $info = curl_getinfo($this->curl);
            if ($info['http_code'] === 200) {
                if (preg_match('/Set-Cookie:\semex\.auth=(.*?);/i', $resp, $matches)) {
                    $this->authKey = $matches[1];
                }
                $json = json_decode(substr($resp, $info['header_size']), true);
                $this->locationId = $json['locationId'];
            }
        }
    }

    function find($detailNum, $maxPrice, $minPrice = 0)
    {
        $offersOut = '';
        if (!(empty($this->authKey) or empty($this->locationId))) {
            curl_setopt($this->curl, CURLOPT_URL, "https://emex.ru/api/search/search2?detailNum=$detailNum&isHeaderSearch=true&showAll=false&searchString=$detailNum&locationId=$this->locationId");
            curl_setopt($this->curl, CURLOPT_POST, false);
            $headers = array(
                "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
                "content-type: application/json;charset=UTF-8",
                "accept: application/json, text/plain, */*",
                "Cookie: emex.auth=$this->authKey"
            );
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($this->curl, CURLOPT_HEADER, false);
            $resp = curl_exec($this->curl);
            if (!curl_errno($this->curl)) {
                if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE)=== 200) {
                    $json = json_decode($resp, true);
                    $searchResult = $json['searchResult'];
                    if (!$searchResult['noResults']) {
                        $detailName = $searchResult['name'] . ' ' . $searchResult['make'] . ' ' . $searchResult['num'];
                        foreach ($searchResult['originals'][0]['offers'] as $offer) {
                            $quantity = $offer['quantity'];
                            $delivery = $offer['delivery'];
                            $deliveryValue = $delivery['value'];
                            $deliveryUnits = $delivery['units'];
                            $displayPrice = $offer['displayPrice'];
                            $priceValue = intval($displayPrice['value']);
                            $priceSymbolText = $displayPrice['symbolText'];
                            if ($priceValue < $maxPrice and $priceValue > $minPrice) {
                                $offersOut .= "$detailName - $priceValue$priceSymbolText за $deliveryValue $deliveryUnits. В наличии $quantity шт.<br>";
                            }
                        }
                    }
                }
            }
        }

        if (!empty($offersOut)) {
            if ($this->isDebug) print ($offersOut);
            $email = $this->email;
            if (!empty($email)) {
                mail($email, 'EMEX notifications', "<html lang=\"ru\"><body>$offersOut</body></html>", "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n");
            }
        } else {
            if ($this->isDebug) print ("Не смог найти $detailNum согласно заданным условиям.<br>");
        }
    }
}
