<?php

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Network\Exception\ForbiddenException;
use Cake\Network\Exception\NotFoundException;
use Cake\View\Exception\MissingTemplateException;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions; 
use Symfony\Component\BrowserKit\Cookie;

class CrawlersController extends AppController
{

    public function index(){
       
        $this->client = new Client();
        $cookieJar = new \GuzzleHttp\Cookie\CookieJar(true);
        $cookieJar->setCookie(new \GuzzleHttp\Cookie\SetCookie([
            'Domain'  => "www.amazon.co.jp",
            'Name'    => "smartshopping",
            'Value'   => "smartshopping",
            'Discard' => true
        ]));
        
        $guzzleClient = new GuzzleClient(array(
            'timeout' => 60,
            'defaults' => ['verify' => false],
            'cookies' => $cookieJar
        ));
        $this->client->setClient($guzzleClient);
        $this->client->setHeader('User-Agent', "Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36");
 
        $crawler = $this->client->request('GET', 'https://www.amazon.co.jp');
        $form = $crawler->selectButton('検索')->form();

        $jansArray = $this->readJanCode();
        $newArray = array();

        foreach($jansArray as $jan){
            $this->jancode = $jan;
            $this->products_table = TableRegistry::get('products');

            $query = $this->products_table->find('all')
                    ->where(['Products.product_jan =' => $jan])
                    ->limit(10);
            $row = $query->first();

            // check if jan has been crawled, skip it 

            if(!$row){
                $crawler = $this->client->submit($form, array('field-keywords' => $jan));
                $crawler->filter('.a-link-normal.s-access-detail-page.s-color-twister-title-link.a-text-normal')->each(function ($node) {
                    $crawler = $this->client->click($node->link());
                    $checkMerchanInfo = $crawler->filter('#merchant-info')->text();
                    if(strpos($checkMerchanInfo,"Amazon.co.jp が販売、発送します。")){
                        // extract data from crawler 
                        $urlLink = $this->client->getHistory()->current()->getUri(); 
                        $firstNaemPosition = strpos($urlLink, 'jp');  
                        $lastNamePosition = strpos($urlLink,'/dp');   
                        $itemNameEncode = substr($urlLink , $firstNaemPosition+3, $lastNamePosition-$firstNaemPosition-3);
                        $itemNameDecode = urldecode($itemNameEncode);
                    
                        $asin = substr($urlLink , $lastNamePosition+4,10);

                        //insert to db 
                        $products = $this->products_table->newEntity();
                        $products->product_jan = $this->jancode; 
                        $products->product_asin = $asin; 
                        $products->product_amz_url = urldecode($urlLink);
                        $products->product_name = $itemNameDecode;// name 
                        if(!$this->products_table->save($products)){
                            $this->logging("cannot save the link to database");
                        }
                        $this->logging("$this->jancode  was inserted to tabase");
                    }
                });
            }else{
                $this->logging("$jan has been crawled");
            }
        }

        die("done!!!");

        try {
            $this->render();
        } catch (MissingTemplateException $exception) {
            if (Configure::read('debug')) {
                throw $exception;
            }
            throw new NotFoundException();
        }
    }

    public function readJanCode(){
        $jansArray = array(); 
        $jansfile = fopen("jans.txt", "r");
        if ($jansfile) {
            while (($line = fgets($jansfile)) !== false) {
                $jansArray[]=$line;  
            }
            fclose($jansfile);
        } else {
            $this->logging("cannot read the file, please check the permision or file");
            die("The file is not exist or no permission "); 
        }
        return $jansArray;  
    }

    public function logging($msg){
        $fileName = "crawl.txt";
        $fp = fopen($fileName, "a");
        if ( !$fp ) {
            die("please check file permission");
        }
        $str_log = stream_get_contents($fp);
        $str = "[" . date("Y/m/d h:i:s", time()) . "] " . $msg;
        // write string
        $fwrite = fwrite($fp, "\n".$str);
        if ($fwrite === false) {
             die("cannot write a logs");
         }
        fclose($fp);
    }

}
