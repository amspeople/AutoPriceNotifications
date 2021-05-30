<?php


class Autopiter
{
    public $isDebug = false;
    protected $curl, $accessToken, $email;

    function __construct($login, $pass, $email = '')
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);

        $this->email = $email;
        $this->auth($login, $pass);
    }

    private function get_session_key()
    {
        $sessionKey = '';
        curl_setopt($this->curl, CURLOPT_URL, "https://autopiter.ru/");
        curl_setopt($this->curl, CURLOPT_HEADER, true);
        $headers = array(
            "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
            "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9"
        );
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($this->curl);
        if (!curl_errno($this->curl)) {
            if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) === 200) {
                if (preg_match('/Set-Cookie:\smySessionCookie=(.*?);/i', $resp, $matches)) {
                    $sessionKey = $matches[1];
                }
            }
        }
        return $sessionKey;
    }

    private function auth($login, $pass)
    {
        $session = $this->get_session_key();
        if (!empty($session)) {
            curl_setopt($this->curl, CURLOPT_URL, 'https://autopiter.ru/api/api/auth/token');
            $headers = array(
                "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
                "content-type: application/json;charset=UTF-8",
                "accept: application/json, text/plain, */*",
                "session: $session",
            );
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode(array('clientId' => $login, 'password' => $pass)));
            curl_setopt($this->curl, CURLOPT_HEADER, false);
            $resp = curl_exec($this->curl);
            if (!curl_errno($this->curl)) {
                if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) === 200) {
                    $json = json_decode($resp, true);
                    if ($json['code'] == 200) {
                        $this->accessToken = $json['data']['accessToken'];
                    }
                }
            }
        }
    }

    private function get_detail_name($detailId): string
    {
        $detailName = '';
        $token = $this->accessToken;
        if (!empty($token)) {
            curl_setopt($this->curl, CURLOPT_URL, "https://autopiter.ru/api/api/tecdoc/info?id=$detailId");
            curl_setopt($this->curl, CURLOPT_POST, false);
            $headers = array(
                "user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36",
                "accept: application/json, text/plain, */*",
                "authorization: $token",
            );
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
            $resp = curl_exec($this->curl);
            if (!curl_errno($this->curl)) {
                if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) === 200) {
                    $json = json_decode($resp, true);
                    $data = $json['data'];
                    $number = $data['number'];
                    $name = $data['name'];
                    $brand = $data['brand'];
                    $detailName = "$name $brand $number";
                }
            }
        }
        return $detailName;
    }

    function find($detailId, $maxPrice, $minPrice = 0)
    {
        $offersOut = '';
        $detailName = $this->get_detail_name($detailId);
        if (!empty($detailName)){
            curl_setopt($this->curl, CURLOPT_URL, "https://autopiter.ru/api/api/appraise?id=$detailId&searchType=1");
            $resp = curl_exec($this->curl);
            if (!curl_errno($this->curl)) {
                if (curl_getinfo($this->curl, CURLINFO_HTTP_CODE) === 200) {
                    $json = json_decode($resp, true);
                    foreach ($json['data'] as $offer) {
                        if ($offer['isSub'] == 1) { // detail is original
                            $price = intval($offer['price']);
                            $quantity = $offer['quantity'];
                            $deliveryDays = $offer['deliveryDays'];
                            if ($price < $maxPrice and $price > $minPrice) {
                                $offersOut .= "$detailName - $price за $deliveryDays дн. В наличии $quantity шт.<br>";
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
                mail($email, 'Autopiter notifications', "<html lang=\"ru\"><body>$offersOut</body></html>", "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n");
            }
        } else {
            if ($this->isDebug) print ("Не смог найти $detailName согласно заданным условиям.<br>");
        }
    }
}