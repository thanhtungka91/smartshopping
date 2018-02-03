<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
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

/**
 * Static content controller
 *
 * This controller will render views from Template/Pages/
 *
 * @link https://book.cakephp.org/3.0/en/controllers/pages-controller.html
 */
class CrawlersController extends AppController
{

    /**
     * Displays a view
     *
     * @param array ...$path Path segments.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Network\Exception\ForbiddenException When a directory traversal attempt.
     * @throws \Cake\Network\Exception\NotFoundException When the view file could not
     *   be found or \Cake\View\Exception\MissingTemplateException in debug mode.
     */


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
            $crawler = $this->client->submit($form, array('field-keywords' => $jan));
            $crawler->filter('.a-link-normal.s-access-detail-page.s-color-twister-title-link.a-text-normal')->each(function ($node) {
                $crawler = $this->client->click($node->link());
                $checkMerchanInfo = $crawler->filter('#merchant-info')->text();
                if(strpos($checkMerchanInfo,"発送します。")){
                    // extract data and save to db 
                    $urlLink = $this->client->getHistory()->current()->getUri(); 
                    $firstNaemPosition = strpos($urlLink, 'jp');  
                    $lastNamePosition = strpos($urlLink,'/dp');   
                    $itemNameEncode = substr($urlLink , $firstNaemPosition+3, $lastNamePosition-$firstNaemPosition-3);
                    $itemNameDecode = urldecode($itemNameEncode);
                
                    $asin = substr($urlLink , $lastNamePosition+4,10);

                    //insert to db 
                    $products_table = TableRegistry::get('products');
                    $products = $products_table->newEntity();
                    $products->product_jan = $this->jancode; 
                    $products->product_asin = $asin; 
                    $products->product_amz_url = urldecode($urlLink);
                    $products->product_name = $itemNameDecode;// name 
                    if(!$products_table->save($products)){
                        print_r("cannot save the link to databse"); 
                    }
                }
            });
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
            die("The file is not exist or no permission "); 
        }
        return $jansArray;  
    }
}
